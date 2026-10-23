<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Diagnostic check for shared hosting environment
if (!file_exists(__DIR__.'/../vendor/autoload.php')) {
    header('Content-Type: application/json', true, 503);
    echo json_encode([
        'status' => 'error',
        'message' => 'Folder vendor/ belum ada di repositories/berkah_store_backend/. Harap salin folder vendor/ dari repositories/api_store/vendor ke repositories/berkah_store_backend/vendor via cPanel File Manager.',
    ], JSON_PRETTY_PRINT);
    exit;
}

if (!file_exists(__DIR__.'/../.env')) {
    header('Content-Type: application/json', true, 503);
    echo json_encode([
        'status' => 'error',
        'message' => 'File .env belum ditemukan. Harap buat file .env di folder repositories/berkah_store_backend/ (bisa copy dari .env.example).',
    ], JSON_PRETTY_PRINT);
    exit;
}

// Maintenance mode
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Composer autoloader
require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';

try {
    if (method_exists($app, 'handleRequest')) {
        // Laravel 11 handle request
        $app->handleRequest(Request::capture());
    } else {
        // Laravel 10 kernel handle request
        $kernel = $app->make(Kernel::class);
        $response = $kernel->handle(
            $request = Request::capture()
        )->send();
        $kernel->terminate($request, $response);
    }
} catch (\Throwable $e) {
    header('Content-Type: application/json', true, 500);
    echo json_encode([
        'success' => false,
        'error_type' => get_class($e),
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
    ], JSON_PRETTY_PRINT);
    exit;
}
