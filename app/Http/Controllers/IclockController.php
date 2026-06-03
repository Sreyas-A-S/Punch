<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AttendanceLog;
use App\Models\DeviceCommand;
use Illuminate\Support\Facades\Log;

class IclockController extends Controller
{
    /**
     * Handle the /iclock/cdata route (GET and POST).
     */
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
            // Handle initial handshake/options request
            if ($request->has('options')) {
                $response = "GET OPTION FROM: {$deviceSn}\n" .
                           "Stamp=0\n" .
                           "OpStamp=0\n" .
                           "ErrorDelay=60\n" .
                           "Delay=30\n" .
                           "TransTimes=00:00;14:00\n" .
                           "TransInterval=1\n" .
                           "TransFlag=1111111111\n" .
                           "Realtime=1\n" .
                           "ServerVer=3.4.1\n";
                return response($response, 200)->header('Content-Type', 'text/plain');
            }

            return response("OK", 200)->header('Content-Type', 'text/plain');
        }

        if ($request->isMethod('post')) {
            $content = $request->getContent();
            
            // Log the raw body for debugging if it's small, otherwise just the size
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

                if (str_starts_with($line, 'PIN') || str_starts_with($line, 'USER') || str_starts_with($line, 'OP')) {
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
                $status = isset($parts[2]) ? trim($parts[2]) : null;

                if (empty($employeePin) || empty($timestamp)) {
                    Log::warning("iClock skipped missing required fields", ['line' => $line, 'sn' => $deviceSn]);
                    $skippedCount++;
                    continue;
                }

                try {
                    AttendanceLog::updateOrCreate([
                        'employee_pin' => $employeePin,
                        'timestamp' => $timestamp,
                    ], [
                        'status' => $status,
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

    /**
     * Handle the /iclock/getrequest route (polling command requests).
     */
    public function getrequest(Request $request)
    {
        $deviceSn = $request->query('SN');
        Log::info("iClock getrequest received", [
            'ip' => $request->ip(),
            'sn' => $deviceSn
        ]);

        // Check for oldest pending command for this device
        $pendingCommand = DeviceCommand::where('device_sn', $deviceSn)
            ->where('status', 'pending')
            ->orderBy('id', 'asc')
            ->first();

        if ($pendingCommand) {
            $pendingCommand->update(['status' => 'sent']);
            
            // Format: C:ID:COMMAND
            $commandString = "C:{$pendingCommand->id}:{$pendingCommand->command}";
            Log::info("iClock sending command to device", ['sn' => $deviceSn, 'command' => $commandString]);
            
            return response($commandString, 200)->header('Content-Type', 'text/plain');
        }

        return response("OK", 200)->header('Content-Type', 'text/plain');
    }

    /**
     * Handle the /iclock/devicecmd route (command execution acknowledgment).
     */
    public function devicecmd(Request $request)
    {
        $deviceSn = $request->query('SN');
        $content = $request->getContent();

        Log::info("iClock devicecmd received", [
            'sn' => $deviceSn,
            'payload' => $content
        ]);

        // ADMS response format is usually "ID=123&Return=0"
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
