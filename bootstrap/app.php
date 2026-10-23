<?php

use Illuminate\Foundation\Application;

if (method_exists(Application::class, 'configure')) {
    // Laravel 11 bootstrap
    return Application::configure(basePath: dirname(__DIR__))
        ->withRouting(
            api: __DIR__.'/../routes/api.php',
            commands: __DIR__.'/../routes/console.php',
            health: '/up',
        )
        ->withMiddleware(function ($middleware) {
            $middleware->alias([
                'role' => \App\Http\Middleware\CheckRole::class,
                'branch.scope' => \App\Http\Middleware\ScopeBranchAccess::class,
            ]);
        })->create();
}

// Laravel 10 bootstrap fallback
$app = new Application(
    $_ENV['APP_BASE_PATH'] ?? dirname(__DIR__)
);

$app->singleton(
    Illuminate\Contracts\Http\Kernel::class,
    App\Http\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

return $app;
