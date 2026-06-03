@extends('layouts.admin')

@section('content')

<style>
    .settings-section {
        max-width: 600px;
        margin-bottom: 2rem;
    }

    .color-swatches {
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
        margin-top: 1rem;
        margin-bottom: 1.5rem;
    }

    .swatch {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        cursor: pointer;
        border: 2px solid transparent;
        transition: var(--transition);
        box-shadow: var(--shadow);
    }

    .swatch:hover {
        transform: scale(1.1);
    }

    .custom-color-picker {
        cursor: pointer;
        width: 40px;
        height: 40px;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        padding: 0;
        background-color: var(--card-bg);
    }

</style>

@if(session('success'))
    <div class="alert" style="background-color: rgba(16, 185, 129, 0.1); color: #10B981; border-color: #10B981; max-width: 600px;">
        {{ session('success') }}
    </div>
@endif

@if($errors->any())
    <div class="alert" style="background-color: rgba(239, 68, 68, 0.1); color: #EF4444; border-color: #EF4444; max-width: 600px;">
        <ul style="list-style: none; padding: 0; margin: 0;">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="settings-section">
    <div class="card">
        <h3 style="margin-bottom: 1.5rem; font-size: 1.25rem;">Update Password</h3>
        <form action="{{ route('admin.settings.password') }}" method="POST">
            @csrf
            
            <div>
                <label for="current_password">Current Password</label>
                <input type="password" id="current_password" name="current_password" required>
            </div>
            
            <div>
                <label for="password">New Password</label>
                <input type="password" id="password" name="password" required>
            </div>

            <div>
                <label for="password_confirmation">Confirm New Password</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required>
            </div>

            <button type="submit" class="btn">Update Password</button>
        </form>
    </div>
</div>

<div class="settings-section">
    <div class="card">
        <h3 style="margin-bottom: 1rem; font-size: 1.25rem;">Theme Settings</h3>
        <p style="color: var(--text-muted); font-size: 0.875rem;">Change the primary color of the dashboard to match your brand.</p>
        
        <div class="color-swatches">
            <!-- Predefined Colors -->
            <div class="swatch" style="background-color: #4318FF;" onclick="updateThemeColor('#4318FF')" title="Default Blue"></div>
            <div class="swatch" style="background-color: #10B981;" onclick="updateThemeColor('#10B981')" title="Emerald Green"></div>
            <div class="swatch" style="background-color: #EF4444;" onclick="updateThemeColor('#EF4444')" title="Ruby Red"></div>
            <div class="swatch" style="background-color: #8B5CF6;" onclick="updateThemeColor('#8B5CF6')" title="Amethyst Purple"></div>
            <div class="swatch" style="background-color: #F97316;" onclick="updateThemeColor('#F97316')" title="Amber Orange"></div>
            
            <!-- Optional Custom Color Box -->
            <div style="display: flex; align-items: center; gap: 0.5rem; margin-left: auto;">
                <label for="customColor" style="margin:0;">Custom:</label>
                <input type="color" id="customColor" class="custom-color-picker" title="Pick a custom color">
            </div>
        </div>
    </div>
</div>

<script>
    function updateThemeColor(color) {
        window.changeThemeColor(color);
        const customPicker = document.getElementById('customColor');
        if(customPicker) {
            customPicker.value = color;
        }
    }

    // Initialize custom picker with current saved color
    document.addEventListener('DOMContentLoaded', () => {
        const savedColor = localStorage.getItem('primaryColor') || '#4318FF';
        const customPicker = document.getElementById('customColor');
        if(customPicker) {
            customPicker.value = savedColor;
            customPicker.addEventListener('input', (e) => {
                window.changeThemeColor(e.target.value);
            });
        }
    });
</script>

@endsection
