<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ChangePasswordController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function showForm()
    {
        // If they don't need to change, send them home
        if (!Auth::user()->must_change_password) {
            return redirect()->route('dashboard');
        }
        return view('auth.change_password');
    }

    public function update(Request $request)
    {
        $request->validate([
            'password'              => 'required|string|min:6|confirmed',
            'password_confirmation' => 'required',
        ], [
            'password.min'       => 'Password must be at least 6 characters.',
            'password.confirmed' => 'Passwords do not match.',
        ]);

        // Prevent reusing the same default password
        if (Hash::check($request->password, Auth::user()->password)) {
            return back()->withErrors(['password' => 'Your new password cannot be the same as your current password.']);
        }

        Auth::user()->update([
            'password'             => Hash::make($request->password),
            'must_change_password' => false,
        ]);

        return redirect()->route('dashboard')
            ->with('flash_success', 'Password changed successfully. Welcome!');
    }
}
