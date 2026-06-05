<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AttendanceLog;
use App\Models\DeviceCommand;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;

use App\Models\User;
use App\Models\Employee;
use App\Models\SslDevice;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class IclockController extends Controller
{
    private function processUserLine($line)
    {
        $data = substr($line, 5);
        
        $parts = explode("\t", str_replace(' ', "\t", $data));
        $attributes = [];
        
        foreach ($parts as $part) {
            if (str_contains($part, '=')) {
                [$key, $value] = explode('=', $part, 2);
                $attributes[strtolower($key)] = trim($value);
            }
        }

        $pin = $attributes['pin'] ?? null;
        $name = trim($attributes['name'] ?? '');

        if ($pin) {
            // Sync to dedicated Employees table
            // Only update the name if the device actually sent one
            $employeeData = ['pin' => $pin];
            if (!empty($name)) {
                $employeeData['name'] = $name;
            } else {
                // If device sent no name and we don't have this employee yet, give a default
                $existingEmployee = Employee::where('pin', $pin)->first();
                if (!$existingEmployee) {
                    $employeeData['name'] = "User $pin";
                }
            }

            Employee::updateOrCreate(
                ['pin' => $pin],
                $employeeData
            );

            // Also sync to Users table (for admin login if needed)
            $userData = [
                'pin' => $pin,
                'email' => "user{$pin}@punch.local",
            ];
            
            if (!empty($name)) {
                $userData['name'] = $name;
            }

            $existingUser = User::where('pin', $pin)->first();
            if (!$existingUser) {
                $userData['name'] = $userData['name'] ?? "User $pin";
                $userData['password'] = Hash::make(Str::random(16));
                User::create($userData);
            } elseif (!empty($name)) {
                $existingUser->update(['name' => $name]);
            }

            // Only backfill attendance if we have a real name to provide
            if (!empty($name)) {
                AttendanceLog::where('employee_pin', $pin)
                    ->whereNull('employee_name')
                    ->update(['employee_name' => $name]);
            }

            Log::info("iClock user synced", ['pin' => $pin, 'name' => $name ?: '(empty)']);
        }
    }

    public function fetchAllUsers(Request $request)
    {
        $deviceSn = $request->query('sn');
        if (!$deviceSn) {
            return response("Error: Missing 'sn' parameter", 400);
        }

        // ADMS Command to fetch all user info from the device
        $command = DeviceCommand::create([
            'device_sn' => $deviceSn,
            'command' => 'DATA QUERY USERINFO',
            'status' => 'pending'
        ]);

        return response("Fetch Users command queued for device '{$deviceSn}'. The device will upload its user list on the next check-in.");
    }

    public function optimizeApp()
    {
        try {
            Artisan::call('optimize');
            Artisan::call('route:clear');
            Artisan::call('config:clear');
            Artisan::call('view:clear');
            Artisan::call('cache:clear');

            return response("Application optimized and caches cleared successfully.");
        } catch (\Exception $e) {
            return response("Error optimizing application: " . $e->getMessage(), 500);
        }
    }

    public function cdata(Request $request)
    {
        $deviceSn = $request->query('SN');
        
        // Fallback for malformed URLs like /iclock/cdata.aspxSN=...
        if (!$deviceSn) {
            $fullUrl = $request->fullUrl();
            if (preg_match('/SN=([A-Z0-9]+)/i', $fullUrl, $matches)) {
                $deviceSn = $matches[1];
            }
        }

        $this->ensureDeviceExists($deviceSn);
        $table = $request->query('table');
        $ip = $request->ip();

        Log::info("iClock cdata request received", [
            'ip' => $ip,
            'sn' => $deviceSn,
            'table' => $table,
            'method' => $request->method(),
        ]);

        if ($request->isMethod('get')) {
            if ($request->has('options')) {
                $response = "GET OPTION FROM: {$deviceSn}\r\n" .
                           "Stamp=0\r\n" .
                           "OpStamp=0\r\n" .
                           "ErrorDelay=60\r\n" .
                           "Delay=30\r\n" .
                           "TransTimes=00:00;14:00\r\n" .
                           "TransInterval=1\r\n" .
                           "TransFlag=1111111111\r\n" .
                           "Realtime=1\r\n" .
                           "ServerVer=3.4.1\r\n";
                return response($response, 200)
                    ->header('Content-Type', 'text/plain')
                    ->header('Connection', 'close');
            }

            return response("OK", 200)->header('Content-Type', 'text/plain');
        }

        if ($request->isMethod('post')) {
            $content = $request->getContent();
            
            if (strlen($content) < 1000) {
                Log::debug("iClock POST raw body", ['body' => $content]);
            } else {
                Log::debug("iClock POST large body received", ['size' => strlen($content)]);
            }

            if (empty($content)) {
                Log::warning("iClock received empty POST body", ['sn' => $deviceSn]);
                return response("OK", 200)->header('Content-Type', 'text/plain');
            }

            // Link this data upload to a recent command if applicable (e.g., DATA QUERY or INFO)
            $matchingCommand = DeviceCommand::where('device_sn', $deviceSn)
                ->whereIn('status', ['sent', 'completed'])
                ->where(function($q) {
                    $q->where('command', 'like', 'DATA QUERY%')
                      ->orWhere('command', 'like', 'INFO%')
                      ->orWhere('command', 'like', 'SET USERINFO%');
                })
                ->orderBy('updated_at', 'desc')
                ->first();

            if ($matchingCommand) {
                $existing = $matchingCommand->response_payload ?? '';
                $matchingCommand->update([
                    'response_payload' => $existing . ($existing ? "\n---\n" : "") . $content
                ]);
            }

            // If the device is sending something other than attendance, we should be careful.
            // Common tables: ATTLOG, OPERLOG, USERINFO, BIODATA, BIOPHOTO
            if ($table && !in_array(strtoupper($table), ['ATTLOG', 'OPERLOG'])) {
                Log::info("iClock skipping non-attendance table processing", ['table' => $table, 'sn' => $deviceSn]);
                return response("OK", 200)->header('Content-Type', 'text/plain');
            }

            $lines = explode("\n", $content);
            $processedCount = 0;
            $skippedCount = 0;

            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) continue;

                // Handle User/Employee sync lines
                if (str_starts_with($line, 'USER') || str_starts_with($line, 'PIN ')) {
                    $this->processUserLine($line);
                    continue;
                }

                // Skip Operator logs or biometric data lines that don't match attendance format
                if (str_starts_with($line, 'OP') || str_starts_with($line, 'FACE') || str_starts_with($line, 'FP')) {
                    continue;
                }

                $parts = explode("\t", $line);
                if (count($parts) < 2) {
                    Log::warning("iClock skipped invalid line format", ['line' => $line, 'sn' => $deviceSn]);
                    $skippedCount++;
                    continue;
                }

                $employeePin = trim($parts[0]);
                $timestamp = trim($parts[1]);
                
                // Final safety check: attendance timestamps must contain a colon or space
                if (!str_contains($timestamp, ':') || !str_contains($timestamp, '-')) {
                    Log::debug("iClock skipping line with invalid timestamp format", ['pin' => $employeePin, 'val' => $timestamp]);
                    $skippedCount++;
                    continue;
                }
                $statusCode = isset($parts[2]) ? trim($parts[2]) : '0';
                $verifyCode = isset($parts[3]) ? trim($parts[3]) : '0';

                $statusMap = [
                    '0' => 'Check-In',
                    '1' => 'Check-Out',
                    '2' => 'Break-Out',
                    '3' => 'Break-In',
                    '4' => 'OT-In',
                    '5' => 'OT-Out'
                ];

                $verifyMap = [
                    '1' => 'Fingerprint',
                    '3' => 'Password',
                    '4' => 'Card',
                    '15' => 'Face',
                    '20' => 'Palm',
                    '25' => 'FingerVein'
                ];

                $status = $statusMap[$statusCode] ?? "Status $statusCode";
                $verifyMode = $verifyMap[$verifyCode] ?? "Other";

                if (empty($employeePin) || empty($timestamp)) {
                    Log::warning("iClock skipped missing required fields", ['line' => $line, 'sn' => $deviceSn]);
                    $skippedCount++;
                    continue;
                }

                try {
                    $tenMinutesAgo = date('Y-m-d H:i:s', strtotime($timestamp . ' -10 minutes'));
                    $recentPunch = AttendanceLog::where('employee_pin', $employeePin)
                        ->where('timestamp', '>=', $tenMinutesAgo)
                        ->where('timestamp', '<=', $timestamp)
                        ->exists();

                    if ($recentPunch) {
                        Log::debug("iClock skipped duplicate punch (spam prevention)", [
                            'pin' => $employeePin,
                            'timestamp' => $timestamp
                        ]);
                        $skippedCount++;
                        continue;
                    }

                    $user = User::where('pin', $employeePin)->first();

                    AttendanceLog::updateOrCreate([
                        'employee_pin' => $employeePin,
                        'timestamp' => $timestamp,
                    ], [
                        'employee_name' => $user ? $user->name : null,
                        'status' => $status,
                        'verify_mode' => $verifyMode,
                        'device_sn' => $deviceSn,
                    ]);
                    $processedCount++;
                } catch (\Exception $e) {
                    Log::error("iClock database failure", [
                        'pin' => $employeePin,
                        'time' => $timestamp,
                        'error' => $e->getMessage(),
                        'sn' => $deviceSn
                    ]);
                    $skippedCount++;
                }
            }

            Log::info("iClock process summary", [
                'sn' => $deviceSn,
                'processed' => $processedCount,
                'skipped' => $skippedCount
            ]);

            return response("OK", 200)->header('Content-Type', 'text/plain');
        }

        return response("OK", 200)->header('Content-Type', 'text/plain');
    }

    public function triggerCommand(Request $request)
    {
        $deviceSn = $request->query('sn');
        $commandStr = $request->query('command', 'REBOOT');

        if (!$deviceSn) {
            return response("Error: Missing 'sn' parameter", 400);
        }

        $command = DeviceCommand::create([
            'device_sn' => $deviceSn,
            'command' => $commandStr,
            'status' => 'pending'
        ]);

        return response("Command '{$commandStr}' queued for device '{$deviceSn}'. Command ID: {$command->id}");
    }

    public function getrequest(Request $request)
    {
        $deviceSn = $request->query('SN');
        
        // Fallback for malformed URLs
        if (!$deviceSn) {
            $fullUrl = $request->fullUrl();
            if (preg_match('/SN=([A-Z0-9]+)/i', $fullUrl, $matches)) {
                $deviceSn = $matches[1];
            }
        }

        $this->ensureDeviceExists($deviceSn);
        Log::info("iClock getrequest received", [
            'ip' => $request->ip(),
            'sn' => $deviceSn
        ]);

        $pendingCommand = DeviceCommand::where('device_sn', $deviceSn)
            ->where('status', 'pending')
            ->orderBy('id', 'asc')
            ->first();

        if ($pendingCommand) {
            $pendingCommand->update(['status' => 'sent']);
            
            $commandString = "C:{$pendingCommand->id}:{$pendingCommand->command}";
            Log::info("iClock sending command to device", ['sn' => $deviceSn, 'command' => $commandString]);
            
            return response($commandString, 200)->header('Content-Type', 'text/plain');
        }

        return response("OK", 200)->header('Content-Type', 'text/plain');
    }

    public function devicecmd(Request $request)
    {
        $deviceSn = $request->query('SN');

        // Fallback for malformed URLs
        if (!$deviceSn) {
            $fullUrl = $request->fullUrl();
            if (preg_match('/SN=([A-Z0-9]+)/i', $fullUrl, $matches)) {
                $deviceSn = $matches[1];
            }
        }

        $this->ensureDeviceExists($deviceSn);
        $content = $request->getContent();

        Log::info("iClock devicecmd received", [
            'sn' => $deviceSn,
            'payload' => $content
        ]);

        parse_str($content, $response);
        $commandId = $response['ID'] ?? null;
        $returnCode = $response['Return'] ?? null;

        if ($commandId) {
            $command = DeviceCommand::find($commandId);
            if ($command) {
                $status = ($returnCode == '0') ? 'completed' : 'error';
                $existing = $command->response_payload ?? '';
                $command->update([
                    'status' => $status,
                    'response_payload' => $existing . ($existing ? "\n---\n" : "") . $content
                ]);
                Log::info("iClock command updated", ['id' => $commandId, 'status' => $status]);
            }
        }

        return response("OK", 200)->header('Content-Type', 'text/plain');
    }

    private function ensureDeviceExists($sn)
    {
        if (!$sn) return;

        $device = SslDevice::where('serial_number', $sn)->first();

        if (!$device) {
            SslDevice::create([
                'serial_number' => $sn,
                'display_name' => "Device $sn",
                'status' => true,
            ]);
            Log::info("iClock new device auto-registered", ['sn' => $sn]);
        } else {
            // Always update to keep 'updated_at' fresh for the heartbeat
            $device->update(['status' => true]);
        }
    }
}
