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
        $query = AttendanceLog::query();

        if ($request->filled('name')) {
            $query->where('employee_name', 'like', '%' . $request->name . '%');
        }

        if ($request->filled('date')) {
            $query->whereDate('timestamp', $request->date);
        }

        if ($request->filled('device_sn')) {
            $query->where('device_sn', $request->device_sn);
        }

        $logs = $query->orderBy('timestamp', 'desc')->paginate(20)->withQueryString();
        $devices = SslDevice::all();

        return view('admin.attendance', compact('logs', 'devices'));
    }

    public function storeDevice(Request $request)
    {
        $request->validate([
            'display_name' => 'required|string|max:255',
            'serial_number' => 'required|string|max:255|unique:ssl_devices',
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
            'serial_number' => 'required|string|max:255|unique:ssl_devices,serial_number,' . $device->id,
        ]);

        $device->update([
            'display_name' => $request->display_name,
            'serial_number' => $request->serial_number,
        ]);

        return redirect()->route('admin.dashboard')->with('success', 'Device updated successfully.');
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
