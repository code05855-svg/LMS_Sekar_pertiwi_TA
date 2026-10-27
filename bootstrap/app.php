<?php

use App\Http\Middleware\AuthenticateN8nToken;
use App\Http\Middleware\BuangQueryArray;
use App\Http\Middleware\EnsurePeran;
use App\Http\Middleware\PastikanAkunAktif;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'peran' => EnsurePeran::class,
            'n8n.token' => AuthenticateN8nToken::class,
        ]);

        // Akun nonaktif dikeluarkan pada permintaan web berikutnya.
        $middleware->web(append: [BuangQueryArray::class, PastikanAkunAktif::class]);

        // Tamu yang membuka halaman terlindungi dikirim ke login;
        // pengguna yang sudah login dan membuka /login dikirim ke dashboard.
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
