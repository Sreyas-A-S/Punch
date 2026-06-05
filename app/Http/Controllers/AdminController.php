<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\SslDevice;
use App\Models\AttendanceLog;

class AdminController extends Controller
{
    public function login()
    {
        if (Auth::check()) {
            return redirect()->route('admin.dashboard');
        }
        return view('admin.login');
    }

    public function authenticate(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            return redirect()->intended(route('admin.dashboard'));
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('admin.login');
    }

    public function index()
    {
        $devices = SslDevice::all();
        $totalDevices = $devices->count();
        $workingDevices = $devices->filter(function ($device) {
            return $device->status;
        })->count();

        return view('admin.dashboard', compact('devices', 'totalDevices', 'workingDevices'));
    }

    public function getDeviceStatus()
    {
        $devices = SslDevice::all();
        $total = $devices->count();
        $working = $devices->filter(fn($d) => $d->status)->count();
        $offline = $total - $working;

        return response()->json([
            'stats' => [
                'total' => $total,
                'working' => $working,
                'offline' => $offline,
            ],
            'devices' => $devices->map(function ($device) {
                return [
                    'serial_number' => $device->serial_number,
                    'status' => $device->status,
                ];
            })
        ]);
    }

    public function attendance(Request $request)
    {
        if ($request->ajax() && $request->has('draw')) {
            $query = AttendanceLog::query();

            // Search
            if ($search = $request->input('search.value')) {
                $query->where(function($q) use ($search) {
                    $q->where('employee_name', 'like', "%$search%")
                      ->orWhere('employee_pin', 'like', "%$search%")
                      ->orWhere('device_sn', 'like', "%$search%")
                      ->orWhere('status', 'like', "%$search%");
                });
            }

            // Individual Filters from original form
            if ($request->filled('name')) {
                $query->where('employee_name', 'like', '%' . $request->name . '%');
            }
            if ($request->filled('date')) {
                $query->whereDate('timestamp', $request->date);
            }
            if ($request->filled('device_sn')) {
                $query->where('device_sn', $request->device_sn);
            }

            $totalData = AttendanceLog::count();
            $totalFiltered = $query->count();

            // Sorting
            $columns = [
                0 => 'id', 
                1 => 'employee_pin', 
                2 => 'employee_name', 
                3 => 'timestamp', 
                4 => 'status', 
                5 => 'device_sn', 
                6 => 'verify_mode'
            ];
            
            $orderColumnIndex = $request->input('order.0.column', 3); // Default to Date & Time
            $orderColumn = $columns[$orderColumnIndex] ?? 'timestamp';
            $orderDir = $request->input('order.0.dir', 'desc');

            $logs = $query->orderBy($orderColumn, $orderDir)
                          ->offset($request->input('start', 0))
                          ->limit($request->input('length', 10))
                          ->get();

            $data = $logs->map(function($log, $index) use ($request) {
                return [
                    $request->input('start', 0) + $index + 1,
                    $log->employee_pin,
                    $log->employee_name ?? 'N/A',
                    $log->timestamp,
                    '<span class="status-badge" style="background-color: rgba(59, 130, 246, 0.1); color: #3B82F6;">' . $log->status . '</span>',
                    '<span style="font-family: monospace;">' . $log->device_sn . '</span>',
                    $log->verify_mode
                ];
            });

            return response()->json([
                "draw"            => intval($request->input('draw')),
                "recordsTotal"    => intval($totalData),
                "recordsFiltered" => intval($totalFiltered),
                "data"            => $data
            ]);
        }

        $devices = SslDevice::all();
        return view('admin.attendance', compact('devices'));
    }

    public function storeDevice(Request $request)
    {
        $request->validate([
            'display_name' => 'required|string|max:255',
            'serial_number' => 'required|string|max:255|unique:device_ssl_devices',
        ]);

        SslDevice::create([
            'display_name' => $request->display_name,
            'serial_number' => $request->serial_number,
            'status' => false, // Default to offline, updated by system later
        ]);

        return redirect()->route('admin.dashboard')->with('success', 'Device added successfully.');
    }

    public function editDevice($id)
    {
        $device = SslDevice::findOrFail($id);
        return view('admin.edit-device', compact('device'));
    }

    public function updateDevice(Request $request, $id)
    {
        $device = SslDevice::findOrFail($id);

        $request->validate([
            'display_name' => 'required|string|max:255',
            'serial_number' => 'required|string|max:255|unique:device_ssl_devices,serial_number,' . $device->id,
        ]);

        $device->update([
            'display_name' => $request->display_name,
            'serial_number' => $request->serial_number,
        ]);

        return redirect()->route('admin.dashboard')->with('success', 'Device updated successfully.');
    }

    public function destroyDevice($id)
    {
        $device = SslDevice::findOrFail($id);
        $device->delete();

        return redirect()->route('admin.dashboard')->with('success', 'Device deleted successfully.');
    }

    public function controls()
    {
        $devices = SslDevice::all();
        $recentCommands = \App\Models\DeviceCommand::orderBy('created_at', 'desc')->take(20)->get();
        return view('admin.controls', compact('devices', 'recentCommands'));
    }

    public function sendCommand(Request $request)
    {
        $request->validate([
            'device_sn' => 'required|string',
            'command' => 'required|string',
        ]);

        $command = \App\Models\DeviceCommand::create([
            'device_sn' => $request->device_sn,
            'command' => $request->command,
            'status' => 'pending',
        ]);

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Command queued.', 'command' => $command]);
        }

        return back()->with('success', 'Command queued successfully.');
    }

    public function getRecentCommands()
    {
        $commands = \App\Models\DeviceCommand::orderBy('created_at', 'desc')->take(20)->get();
        return response()->json($commands->map(function($cmd) {
            return [
                'device_sn' => $cmd->device_sn,
                'command' => $cmd->command,
                'status' => $cmd->status,
                'time' => $cmd->created_at->diffForHumans(),
            ];
        }));
    }

    public function settings()
    {
        return view('admin.settings');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'min:8', 'confirmed'],
        ]);

        $request->user()->update([
            'password' => Hash::make($request->password),
        ]);

        return back()->with('success', 'Password updated successfully.');
    }
}
