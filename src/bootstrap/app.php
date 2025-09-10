<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\EnsureAdmin;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // 既存の alias があればその配列に足す
        $middleware->alias([
            'admin' => \App\Http\Middleware\Admin::class,
        ]);
    })    
    ->withProviders([                   
        App\Providers\AuthServiceProvider::class,
        App\Providers\FortifyServiceProvider::class,
    ])
    ->withExceptions(function ($exceptions) {
        //
    })->create();
    
