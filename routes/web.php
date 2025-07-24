<?php

use Illuminate\Support\Facades\Route;

/*
Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('pages.dashboard');
});

Route::get('/sales/quotation', function () {
    return view('pages.sales.quotation');
});
*/

Route::get('/test', function () {
    return "Laravel is working!";
});

Route::get('/test-module-access', function () {
    return "Module access middleware working!";
})->middleware('module_access:inventory');
