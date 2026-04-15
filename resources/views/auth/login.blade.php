@extends('layouts.login_master')
@section('content')

<div class="login-cover d-flex align-items-center justify-content-center" style="min-height:100vh;">
    <div style="width:100%;max-width:420px;padding:20px;">

        {{-- Logo / Brand --}}
        <div class="text-center mb-4">
            <div style="width:64px;height:64px;background:rgba(255,255,255,.15);border-radius:16px;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
                <i class="bi bi-mortarboard-fill text-white" style="font-size:30px;"></i>
            </div>
            <h3 style="color:#fff;font-weight:700;margin-bottom:4px;">{{ Qs::getSystemName() }}</h3>
            <p style="color:rgba(255,255,255,.6);font-size:13px;margin:0;">School Management System</p>
        </div>

        {{-- Card --}}
        <div class="card" style="border-radius:16px;border:none;box-shadow:0 20px 60px rgba(0,0,0,.3);">
            <div class="card-body" style="padding:32px;">
                <h5 style="font-weight:700;color:#1e293b;margin-bottom:4px;">Welcome back</h5>
                <p style="color:#64748b;font-size:13px;margin-bottom:24px;">Sign in to your account to continue</p>

                @if($errors->any())
                <div class="alert alert-danger mb-3">
                    <i class="bi bi-exclamation-triangle mr-2"></i>
                    {{ $errors->first() }}
                </div>
                @endif

                @if(session('flash_danger'))
                <div class="alert alert-danger mb-3">
                    <i class="bi bi-lock mr-2"></i>
                    {{ session('flash_danger') }}
                </div>
                @endif

                <form method="POST" action="{{ route('login') }}">
                    @csrf
                    <div class="form-group">
                        <label>Login ID or Email</label>
                        <div style="position:relative;">
                            <i class="bi bi-person" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:15px;pointer-events:none;z-index:1;"></i>
                            <input type="text" name="identity" value="{{ old('identity') }}" required
                                   class="form-control" placeholder="Username or email"
                                   style="padding-left:40px;position:relative;">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Password</label>
                        <div style="position:relative;">
                            <i class="bi bi-lock" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:15px;pointer-events:none;z-index:1;"></i>
                            <input type="password" name="password" required
                                   class="form-control" placeholder="Your password"
                                   style="padding-left:40px;position:relative;">
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <label class="d-flex align-items-center" style="gap:6px;font-size:13px;color:#64748b;cursor:pointer;font-weight:400;text-transform:none;letter-spacing:0;">
                            <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}>
                            Remember me
                        </label>
                        <a href="{{ route('password.request') }}" style="font-size:13px;color:#4f46e5;text-decoration:none;">Forgot password?</a>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block" style="padding:10px;font-size:14px;font-weight:600;border-radius:8px;">
                        <i class="bi bi-box-arrow-in-right mr-2"></i>Sign In
                    </button>
                </form>
            </div>
        </div>

        <p class="text-center mt-3" style="color:rgba(255,255,255,.4);font-size:12px;">
            &copy; {{ date('Y') }} {{ Qs::getSystemName() }}. All rights reserved.
        </p>
    </div>
</div>
@endsection
