<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AttendanceLog;
use App\Models\DeviceCommand;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;

use App\Models\User;
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
        $name = $attributes['name'] ?? null;

        if ($pin) {
            User::updateOrCreate(
                ['pin' => $pin],
                [
                    'name' => $name ?? "User $pin",
                    'email' => "user{$pin}@punch.local",
                    'password' => Hash::make(Str::random(16)),
                ]
            );
            Log::info("iClock user synced", ['pin' => $pin, 'name' => $name]);
        }
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

            $lines = explode("\n", $content);
            $processedCount = 0;
            $skippedCount = 0;

            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) continue;

                if (str_starts_with($line, 'USER') || str_starts_with($line, 'PIN ')) {
                    $this->processUserLine($line);
                    continue;
                }

                if (str_starts_with($line, 'OP')) {
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
                $command->update([
                    'status' => $status,
                    'response_payload' => $content
                ]);
                Log::info("iClock command updated", ['id' => $commandId, 'status' => $status]);
            }
        }

        return response("OK", 200)->header('Content-Type', 'text/plain');
    }
}
