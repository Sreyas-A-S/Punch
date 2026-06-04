<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <style>
        :root {
            /* Default Light Theme */
            --bg-color: #f4f7fe;
            --text-color: #2b3674;
            --text-muted: #a3aed1;
            --card-bg: #ffffff;
            --primary-color: #4318FF;
            --border-color: #e2e8f0;
            --sidebar-bg: #ffffff;
            --shadow: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -1px rgba(0,0,0,0.03);
            --transition: all 0.3s ease;
        }

        [data-theme="dark"] {
            /* Dark Theme */
            --bg-color: #0b1437;
            --text-color: #ffffff;
            --text-muted: #8f9bba;
            --card-bg: #111c44;
            --border-color: #1b2559;
            --sidebar-bg: #111c44;
            --shadow: 0 4px 6px -1px rgba(0,0,0,0.5);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-color);
            transition: var(--transition);
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 250px;
            background-color: var(--sidebar-bg);
            border-right: 1px solid var(--border-color);
            padding: 2rem 1rem;
            display: flex;
            flex-direction: column;
            transition: var(--transition);
        }

        .brand {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
            text-align: center;
            margin-bottom: 2rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding-bottom: 1rem;
            border-bottom: 2px dashed var(--primary-color);
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            color: var(--text-color);
            text-decoration: none;
            border-radius: 8px;
            margin-bottom: 0.5rem;
            font-weight: 500;
            transition: var(--transition);
        }

        .nav-link:hover, .nav-link.active {
            background-color: var(--primary-color);
            color: #ffffff;
        }

        /* Main Content */
        .main-wrapper {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .topbar {
            height: 70px;
            background-color: var(--card-bg);
            border-bottom: 2px solid var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 2rem;
            transition: var(--transition);
        }

        .topbar-actions {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .theme-toggle {
            background: none;
            border: none;
            color: var(--text-color);
            cursor: pointer;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
        }

        .color-picker-wrapper {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .color-picker {
            cursor: pointer;
            width: 35px;
            height: 30px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            padding: 0;
            background-color: var(--card-bg);
        }

        .btn-logout {
            background-color: transparent;
            color: var(--text-muted);
            border: 1px solid var(--border-color);
            padding: 0.5rem 1rem;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: var(--transition);
        }
        .btn-logout:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }

        .content {
            padding: 2rem;
            flex: 1;
            overflow-y: auto;
        }

        /* Components */
        .card {
            background-color: var(--card-bg);
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
            transition: var(--transition);
        }

        .btn {
            background-color: var(--primary-color);
            color: #ffffff;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
        }

        .btn:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            background-color: rgba(67, 24, 255, 0.1);
            color: var(--primary-color);
            border: 1px solid var(--primary-color);
        }

        input, select {
            width: 100%;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            background-color: var(--bg-color);
            color: var(--text-color);
            margin-bottom: 1rem;
            transition: var(--transition);
        }
        
        input:focus, select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(67, 24, 255, 0.1);
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--text-muted);
        }

        /* Global DataTable Custom Styling */
        .dataTables_wrapper {
            padding: 1rem 0;
        }

        table.dataTable {
            border-collapse: collapse !important;
            margin-top: 1rem !important;
            border-bottom: none !important;
        }

        table.dataTable thead th {
            color: var(--text-muted);
            font-weight: 600;
            text-transform: uppercase;
            background-color: rgba(0,0,0,0.02);
            border-bottom: 1px solid var(--border-color) !important;
            padding: 1rem !important;
            font-size: 0.75rem;
        }

        [data-theme="dark"] table.dataTable thead th {
            background-color: rgba(255,255,255,0.02);
        }

        table.dataTable tbody td {
            padding: 1rem !important;
            border-bottom: 1px solid var(--border-color) !important;
            font-size: 0.875rem;
            color: var(--text-color);
            background-color: transparent !important;
        }

        .dataTables_filter input, .dataTables_length select {
            border: 1px solid var(--border-color) !important;
            background-color: var(--bg-color) !important;
            color: var(--text-color) !important;
            border-radius: 8px !important;
            padding: 0.4rem 0.8rem !important;
        }

        .dataTables_length label {
            display: flex !important;
            align-items: center !important;
            gap: 0.5rem !important;
            white-space: nowrap !important;
            color: var(--text-muted) !important;
            font-size: 0.875rem !important;
        }

        .dataTables_length select {
            width: auto !important;
            margin: 0 !important;
        }

        .dataTables_info {
            color: var(--text-muted) !important;
            font-size: 0.875rem !important;
            padding-top: 1.5rem !important;
        }

        .dataTables_paginate {
            padding-top: 1.5rem !important;
        }

        .dataTables_paginate .paginate_button {
            border-radius: 8px !important;
            border: 1px solid var(--border-color) !important;
            background: var(--card-bg) !important;
            color: var(--text-color) !important;
        }

        .dataTables_paginate .paginate_button.current {
            background: var(--primary-color) !important;
            color: white !important;
            border-color: var(--primary-color) !important;
        }
    </style>
</head>
<body>

    @if(Auth::check())
    <div class="sidebar">
        <div class="brand">Admin Panel</div>
        <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">Dashboard</a>
        <a href="{{ route('admin.attendance') }}" class="nav-link {{ request()->routeIs('admin.attendance') ? 'active' : '' }}">Attendance</a>
        <a href="{{ route('admin.settings') }}" class="nav-link {{ request()->routeIs('admin.settings') ? 'active' : '' }}">General Settings</a>
    </div>
    @endif

    <div class="main-wrapper">
        @if(Auth::check())
        <div class="topbar">
            <div>
                <h2 style="font-size: 1.25rem; font-weight: 600;">Welcome, {{ Auth::user()->name }}</h2>
            </div>
            <div class="topbar-actions">
                
                
                <button class="theme-toggle" id="themeToggle" title="Toggle Light/Dark Mode">
                    🌓
                </button>

                <form method="POST" action="{{ route('admin.logout') }}" style="margin: 0;">
                    @csrf
                    <button type="submit" class="btn-logout">Logout</button>
                </form>
            </div>
        </div>
        @endif

        <div class="content" style="@if(!Auth::check()) display: flex; align-items: center; justify-content: center; height: 100vh; @endif">
            @yield('content')
        </div>
    </div>

    <!-- jQuery and DataTables JS -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>

    <script>
        // Theme Toggle Logic
        const themeToggle = document.getElementById('themeToggle');
        const themeColorPicker = document.getElementById('themeColor');
        const root = document.documentElement;

        // Load saved theme
        const savedTheme = localStorage.getItem('theme') || 'light';
        root.setAttribute('data-theme', savedTheme);

        // Load saved color
        const savedColor = localStorage.getItem('primaryColor') || '#4318FF';
        root.style.setProperty('--primary-color', savedColor);

        if(themeToggle) {
            themeToggle.addEventListener('click', () => {
                const currentTheme = root.getAttribute('data-theme');
                const newTheme = currentTheme === 'light' ? 'dark' : 'light';
                root.setAttribute('data-theme', newTheme);
                localStorage.setItem('theme', newTheme);
            });
        }

        window.changeThemeColor = function(color) {
            root.style.setProperty('--primary-color', color);
            localStorage.setItem('primaryColor', color);
        }
    </script>
</body>
</html>
