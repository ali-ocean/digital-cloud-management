<?php

use Illuminate\Support\Facades\Route;

// Dashboard route
Route::get('/', function () {
    return view('dashboard');
});

// API routes for frontend
Route::get('/api/dashboard/stats', function () {
    return response()->json([
        'data' => [
            'cloudProviders' => 3,
            'activeProviders' => 3,
            'totalVms' => 12,
            'runningVms' => 10,
            'activeAlerts' => 5,
            'criticalAlerts' => 2,
            'monthlyCost' => 1234.56,
            'costSavings' => 156.78
        ]
    ]);
});

// Fallback route
Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*');
