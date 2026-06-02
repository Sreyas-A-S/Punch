<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AttendanceLog;
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

        Log::info("iClock cdata request received from Device SN: {$deviceSn}, Table: {$table}, Method: " . $request->method());

        if ($request->isMethod('get')) {
            // Devices query capabilities/options or register themselves via GET request
            return response("OK", 200)
                ->header('Content-Type', 'text/plain');
        }

        // Handle POST request (uploading logs)
        if ($request->isMethod('post')) {
            $content = $request->getContent();
            
            if (empty($content)) {
                return response("OK", 200)->header('Content-Type', 'text/plain');
            }

            // Parse tab-delimited records line-by-line
            $lines = explode("\n", $content);
            $processedCount = 0;

            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) {
                    continue;
                }

                // Check for standard ADMS header lines (e.g. OP LOG, ATTLOG, etc. sometimes start with header metadata)
                if (str_starts_with($line, 'PIN') || str_starts_with($line, 'USER') || str_starts_with($line, 'OP')) {
                    continue;
                }

                // Parse tab-separated values
                $parts = explode("\t", $line);
                if (count($parts) < 2) {
                    continue;
                }

                // Standard field indices:
                // 0: Employee PIN (e.g. 1, 1002)
                // 1: DateTime String (e.g. 2026-06-02 14:02:11)
                // 2: Verification status / code (e.g. 0, 15)
                // 3: Verify Type
                $employeePin = trim($parts[0]);
                $timestamp = trim($parts[1]);
                $status = isset($parts[2]) ? trim($parts[2]) : null;

                // Validate employee_pin and timestamp basic structure
                if (empty($employeePin) || empty($timestamp)) {
                    continue;
                }

                try {
                    // Save to the database, ignoring or updating if already exists to prevent duplication
                    AttendanceLog::updateOrCreate([
                        'employee_pin' => $employeePin,
                        'timestamp' => $timestamp,
                    ], [
                        'status' => $status,
                        'device_sn' => $deviceSn,
                    ]);
                    $processedCount++;
                } catch (\Exception $e) {
                    Log::error("Failed to store attendance log for PIN: {$employeePin}, Time: {$timestamp}. Error: " . $e->getMessage());
                }
            }

            Log::info("Successfully processed {$processedCount} logs from Device: {$deviceSn}");

            // The device expects an "OK" response to clear its memory/buffer
            return response("OK", 200)
                ->header('Content-Type', 'text/plain');
        }

        return response("OK", 200)->header('Content-Type', 'text/plain');
    }

    /**
     * Handle the /iclock/getrequest route (polling command requests).
     */
    public function getrequest(Request $request)
    {
        $deviceSn = $request->query('SN');
        Log::info("iClock getrequest received from Device SN: {$deviceSn}");

        // Return OK to signal no pending commands
        return response("OK", 200)
            ->header('Content-Type', 'text/plain');
    }
}
