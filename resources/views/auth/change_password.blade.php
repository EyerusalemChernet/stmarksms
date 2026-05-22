@extends('layouts.login_master')
@section('content')

<div class="login-cover d-flex align-items-center justify-content-center" style="min-height:100vh;">
    <div style="width:100%;max-width:440px;padding:20px;">

        {{-- Brand --}}
        <div class="text-center mb-4">
            <div style="width:64px;height:64px;background:rgba(255,255,255,.15);border-radius:16px;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
                <i class="bi bi-shield-lock-fill text-white" style="font-size:30px;"></i>
            </div>
            <h3 style="color:#fff;font-weight:700;margin-bottom:4px;">{{ Qs::getSystemName() }}</h3>
            <p style="color:rgba(255,255,255,.6);font-size:13px;margin:0;">School Management System</p>
        </div>

        {{-- Card --}}
        <div class="card" style="border-radius:16px;border:none;box-shadow:0 20px 60px rgba(0,0,0,.3);">
            <div class="card-body" style="padding:32px;">

                {{-- Header --}}
                <div style="text-align:center;margin-bottom:24px;">
                    <div style="width:52px;height:52px;background:#fef3c7;border-radius:12px;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">
                        <i class="bi bi-key-fill" style="font-size:22px;color:#d97706;"></i>
                    </div>
                    <h5 style="font-weight:700;color:#1e293b;margin-bottom:6px;">Set Your Password</h5>
                    <p style="color:#64748b;font-size:13px;margin:0;">
                        Your account was created by an administrator.<br>
                        Please set a personal password before continuing.
                    </p>
                </div>

                {{-- Errors --}}
                @if($errors->any())
                <div class="alert alert-danger mb-3" style="border-radius:8px;">
                    <i class="bi bi-exclamation-triangle mr-2"></i>
                    {{ $errors->first() }}
                </div>
                @endif

                <form method="POST" action="{{ route('password.change.update') }}">
                    @csrf

                    <div class="form-group">
                        <label style="font-size:13px;font-weight:600;color:#374151;">New Password</label>
                        <div style="position:relative;">
                            <i class="bi bi-lock" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:15px;pointer-events:none;"></i>
                            <input type="password" name="password" required
                                   class="form-control @error('password') is-invalid @enderror"
                                   placeholder="At least 6 characters"
                                   style="padding-left:40px;border-radius:8px;"
                                   id="new-password">
                        </div>
                        <small style="font-size:11px;color:#94a3b8;">Minimum 6 characters</small>
                    </div>

                    <div class="form-group">
                        <label style="font-size:13px;font-weight:600;color:#374151;">Confirm Password</label>
                        <div style="position:relative;">
                            <i class="bi bi-lock-fill" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:15px;pointer-events:none;"></i>
                            <input type="password" name="password_confirmation" required
                                   class="form-control"
                                   placeholder="Repeat your new password"
                                   style="padding-left:40px;border-radius:8px;"
                                   id="confirm-password">
                        </div>
                        <small id="match-msg" style="font-size:11px;display:none;"></small>
                    </div>

                    {{-- Strength bar --}}
                    <div class="mb-3">
                        <div style="height:4px;background:#e2e8f0;border-radius:4px;overflow:hidden;">
                            <div id="strength-bar" style="height:100%;width:0%;border-radius:4px;transition:all .3s;"></div>
                        </div>
                        <small id="strength-label" style="font-size:11px;color:#94a3b8;"></small>
                    </div>

                    <button type="submit" id="submit-btn" class="btn btn-primary btn-block"
                            style="padding:10px;font-size:14px;font-weight:600;border-radius:8px;">
                        <i class="bi bi-check-circle mr-2"></i>Set Password &amp; Continue
                    </button>
                </form>

                <div class="text-center mt-3">
                    <form method="POST" action="{{ route('logout') }}" style="display:inline;">
                        @csrf
                        <button type="submit" style="background:none;border:none;color:#94a3b8;font-size:12px;cursor:pointer;text-decoration:underline;">
                            Sign out instead
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <p class="text-center mt-3" style="color:rgba(255,255,255,.4);font-size:12px;">
            &copy; {{ date('Y') }} {{ Qs::getSystemName() }}. All rights reserved.
        </p>
    </div>
</div>

@section('scripts')
<script>
(function () {
    var pw   = document.getElementById('new-password');
    var conf = document.getElementById('confirm-password');
    var bar  = document.getElementById('strength-bar');
    var lbl  = document.getElementById('strength-label');
    var msg  = document.getElementById('match-msg');
    var btn  = document.getElementById('submit-btn');

    function strength(v) {
        var s = 0;
        if (v.length >= 6)  s++;
        if (v.length >= 10) s++;
        if (/[A-Z]/.test(v)) s++;
        if (/[0-9]/.test(v)) s++;
        if (/[^A-Za-z0-9]/.test(v)) s++;
        return s;
    }

    pw.addEventListener('input', function () {
        var s = strength(this.value);
        var colors = ['#ef4444','#f97316','#eab308','#22c55e','#10b981'];
        var labels = ['Very weak','Weak','Fair','Strong','Very strong'];
        var pct    = [20, 40, 60, 80, 100];
        if (this.value.length === 0) { bar.style.width = '0%'; lbl.textContent = ''; return; }
        var i = Math.min(s - 1, 4);
        bar.style.width = pct[i] + '%';
        bar.style.background = colors[i];
        lbl.textContent = labels[i];
        lbl.style.color = colors[i];
        checkMatch();
    });

    conf.addEventListener('input', checkMatch);

    function checkMatch() {
        if (!conf.value) { msg.style.display = 'none'; return; }
        if (pw.value === conf.value) {
            msg.textContent = '✓ Passwords match';
            msg.style.color = '#10b981';
        } else {
            msg.textContent = '✗ Passwords do not match';
            msg.style.color = '#ef4444';
        }
        msg.style.display = 'block';
    }
})();
</script>
@endsection

@endsection
