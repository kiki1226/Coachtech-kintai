<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    /** ログイン後の遷移先 */
    public const HOME = '/admin/attendances';

    public function boot(): void
    {
        // いまは何も不要（バインディング等が必要になったらここに追加）
    }
}
