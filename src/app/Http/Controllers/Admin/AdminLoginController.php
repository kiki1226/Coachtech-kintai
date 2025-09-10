<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminLoginRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;

class AdminLoginController extends Controller
{
    // ログイン画面
    public function create()
    {
        return view('admin.login'); // ← ファイルは resources/views/admin/login.blade.php
    }

    public function store(\App\Http\Requests\Admin\AdminLoginRequest $request)
    {
        $request->authenticate();
        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }

    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('admin.login.form');
    }


    public function authenticate(): void
    {
        $email    = (string) $this->input('email');
        $password = (string) $this->input('password');
        $remember = (bool) $this->input('remember', false);

        $user = User::where('email', $email)->first();

        // メール or パスワード不一致
        if (!$user || !Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => 'ログイン情報が登録されていません',
            ]);
        }

        // --- 管理者判定（users テーブルのどちらかのカラムを採用） ---
        // is_admin（0/1）または role='admin'
        $attrs   = $user->getAttributes();                     // ← ここがポイント
        $isAdmin = (array_key_exists('is_admin', $attrs) && (int)$attrs['is_admin'] === 1)
                || (array_key_exists('role',     $attrs) && (string)$attrs['role'] === 'admin');

        if (!$isAdmin) {
            throw ValidationException::withMessages([
                'email' => '管理者権限がありません。',
            ]);
        }

        Auth::login($user, $remember);
    }

    public function logout(Request $request)
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login.form');
    }
}
