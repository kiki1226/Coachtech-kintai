<?php

namespace App\Http\Controllers\Auth;

use App\Http\Requests\LoginRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /** ログイン画面 */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /** ログイン処理 */
    public function login(LoginRequest $request)
    {
        $credentials = $request->only('email', 'password');
        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate(); // セッション固定化対策
            return redirect()->intended(route('attendance.register')); // ← ログイン後の遷移先
        }

        throw ValidationException::withMessages([
            'email' => 'ログイン情報が登録されていません。',
        ]);
    }

    /** ログアウト処理 */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'ログアウトしました。');
    }
}