<?php

use Illuminate\Support\Facades\Route;

$serveSpa = function () {
    $spaEntry = public_path('index.html');

    if (! file_exists($spaEntry)) {
        if (request()->getPathInfo() !== '/') {
            abort(404, 'Frontend web build is not available.');
        }

        return response()->json([
            'name' => 'CarneShop API',
            'version' => '1.0.0',
        ]);
    }

    return response()->file($spaEntry, [
        'Cache-Control' => 'no-store, no-cache, must-revalidate',
    ]);
};

Route::get('/', $serveSpa);

Route::get('/health', function () {
    return response()->json(['status' => 'ok', 'timestamp' => now()]);
});

Route::get('/{any}', $serveSpa)
    ->where('any', '^(?!api(?:/|$)|health(?:/|$)|up(?:/|$)).*');
