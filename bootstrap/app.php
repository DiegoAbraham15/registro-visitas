<?php

use App\Http\Middleware\ControlArea;
use App\Http\Middleware\EnsureAdminCafeteria;
use App\Http\Middleware\EnsureCanManageCatalogos;
use App\Http\Middleware\EnsureCanManageMedicos;
use App\Http\Middleware\EnsureIsAdmin;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\PreventBackHistoryCache;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            PreventBackHistoryCache::class,
        ]);

        $middleware->alias([
            'area' => ControlArea::class,
            'admin' => EnsureIsAdmin::class,
            'admin.cafeteria' => EnsureAdminCafeteria::class,
            'catalogos' => EnsureCanManageCatalogos::class,
            'medicos' => EnsureCanManageMedicos::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
