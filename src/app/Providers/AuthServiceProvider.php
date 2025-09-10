<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use App\Models\User;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    public function boot(): void
    {
        Gate::define('manage', function (User $user) {
            // role が manager / hr / admin のいずれかなら管理OK
            return in_array($user->role, ['admin', 'manager', 'hr']); 
        });

        // 完全管理者は何でも許可（任意）
        Gate::before(function (User $user) {
            return $user->role === 'admin' ? true : null;
        });
    }
}
