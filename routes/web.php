<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'status' => 'online',
        'app' => 'Berkah Store Backend API',
        'version' => '2.0.0',
    ]);
});
