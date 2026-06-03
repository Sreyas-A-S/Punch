@extends('layouts.admin')

@section('content')
<div class="card" style="width: 100%; max-width: 400px; padding: 2.5rem;">
    <h2 style="text-align: center; margin-bottom: 2rem; color: var(--primary-color);">Admin Login</h2>

    @if ($errors->any())
        <div class="alert">
            <ul style="list-style: none; padding: 0; margin: 0;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.authenticate') }}">
        @csrf
        <div>
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus>
        </div>

        <div>
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>

        <button type="submit" class="btn" style="width: 100%; margin-top: 1rem;">Login</button>
    </form>
</div>
@endsection
