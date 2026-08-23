<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function showLoginForm(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->intended(route('material.items.index'));
        }

        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $request->validate([
            'login_name' => ['required', 'string'],
            'password'   => ['required', 'string'],
        ], [
            'login_name.required' => 'กรุณากรอกชื่อผู้ใช้งาน',
            'password.required'   => 'กรุณากรอกรหัสผ่าน',
        ]);

        $throttleKey = Str::lower(trim($request->input('login_name'))) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return back()
                ->withInput($request->only('login_name'))
                ->withErrors(['form' => "พยายามเข้าสู่ระบบมากเกินไป กรุณารอ {$seconds} วินาที"]);
        }

        $credentials = [
            'login_name' => trim($request->input('login_name')),
            'password'   => $request->input('password'), // never stored; used only for validateCredentials
        ];

        if (Auth::attempt($credentials, remember: false)) {
            RateLimiter::clear($throttleKey);
            $request->session()->regenerate();

            return redirect()->intended(route('material.items.index'));
        }

        RateLimiter::hit($throttleKey, 60);

        return back()
            ->withInput($request->only('login_name'))
            ->withErrors(['form' => 'ชื่อผู้ใช้งานหรือรหัสผ่านไม่ถูกต้อง']);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
