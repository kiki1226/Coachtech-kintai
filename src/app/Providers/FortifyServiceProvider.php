<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;
use App\Actions\Fortify\CreateNewUser;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;


class FortifyServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // ログイン/登録画面を指定（PG02/PG01）
        Fortify::loginView(fn () => view('auth.login'));
        Fortify::registerView(fn () => view('auth.register'));
        Fortify::createUsersUsing(CreateNewUser::class);
    
        // ビューの割り当て（必要に応じて）
        Fortify::loginView(fn () => view('auth.login'));
        Fortify::registerView(fn () => view('auth.register'));

        // ★ レートリミッター定義（これが無いと今回のエラーになります）
        RateLimiter::for('login', function (Request $request) {
            $email = (string) $request->input('email');

            // 例: 1分に5回まで。キーはメール+IP
            return Limit::perMinute(5)->by($email.$request->ip());
        });

        RateLimiter::for('two-factor', function (Request $request) {
            // 2FA の試行回数制限（任意）
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });
    }
}
