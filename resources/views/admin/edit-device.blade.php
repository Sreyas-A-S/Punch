@extends('layouts.admin')

@section('content')

<div class="card" style="max-width: 600px;">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem;">
        <h3 style="font-size: 1.25rem; margin: 0;">Edit SSL Device</h3>
        <a href="{{ route('admin.dashboard') }}" style="color: var(--text-muted); text-decoration: none; font-size: 0.875rem; font-weight: 500;">&larr; Back to Dashboard</a>
    </div>

    @if($errors->any())
        <div class="alert" style="background-color: rgba(239, 68, 68, 0.1); color: #EF4444; border-color: #EF4444;">
            <ul style="list-style: none; padding: 0; margin: 0;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.devices.update', $device->id) }}" method="POST">
        @csrf
        @method('PUT')
        
        <div>
            <label for="display_name">Display Name</label>
            <input type="text" id="display_name" name="display_name" value="{{ old('display_name', $device->display_name) }}" required>
        </div>
        
        <div>
            <label for="serial_number">Serial Number</label>
            <input type="text" id="serial_number" name="serial_number" value="{{ old('serial_number', $device->serial_number) }}" required>
        </div>

        <button type="submit" class="btn">Update Device</button>
    </form>
</div>

@endsection
