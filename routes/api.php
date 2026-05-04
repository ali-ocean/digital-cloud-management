<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CloudProviderController;
use App\Http\Controllers\VirtualMachineController;
use App\Http\Controllers\MonitoringController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Cloud Provider Routes
Route::apiResource('cloud-providers', CloudProviderController::class);
Route::prefix('cloud-providers/{cloud_provider}')->group(function () {
    Route::post('test-connection', [CloudProviderController::class, 'testConnection']);
    Route::get('regions', [CloudProviderController::class, 'getRegions']);
    Route::get('instance-types', [CloudProviderController::class, 'getInstanceTypes']);
    Route::post('cost-estimate', [CloudProviderController::class, 'getCostEstimate']);
});

// Virtual Machine Routes
Route::apiResource('virtual-machines', VirtualMachineController::class);
Route::prefix('virtual-machines/{virtual_machine}')->group(function () {
    Route::post('start', [VirtualMachineController::class, 'start']);
    Route::post('stop', [VirtualMachineController::class, 'stop']);
    Route::post('restart', [VirtualMachineController::class, 'restart']);
    Route::post('sync', [VirtualMachineController::class, 'sync']);
    Route::get('metrics', [VirtualMachineController::class, 'getMetrics']);
    Route::get('utilization', [VirtualMachineController::class, 'getUtilization']);
    Route::post('monitoring', [VirtualMachineController::class, 'toggleMonitoring']);
});

// Monitoring Routes
Route::prefix('monitoring')->group(function () {
    Route::get('dashboard', [MonitoringController::class, 'index']);
    Route::get('vms/{virtual_machine}/metrics', [MonitoringController::class, 'getVmMetrics']);
    Route::get('vms/{virtual_machine}/cost-efficiency', [MonitoringController::class, 'getCostEfficiency']);
    Route::get('cost-efficiency', [MonitoringController::class, 'getAllCostEfficiency']);
    Route::post('collect-metrics', [MonitoringController::class, 'collectMetrics']);
    Route::get('metrics-history', [MonitoringController::class, 'getMetricsHistory']);
    Route::delete('cleanup-metrics', [MonitoringController::class, 'cleanupMetrics']);
});
