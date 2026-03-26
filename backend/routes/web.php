<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return ['name' => 'CarneShop API', 'version' => '1.0.0'];
});

Route::get('/health', function () {
    return response()->json(['status' => 'ok', 'timestamp' => now()]);
});
