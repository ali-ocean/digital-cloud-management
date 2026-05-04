<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('dashboard');
});

Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*');
