<?php

use App\Http\Controllers\ResumoController;
use Illuminate\Support\Facades\Route;

Route::get('/', ResumoController::class . '@index')->name('index');
Route::post('/resumos', ResumoController::class . '@resumir')->name('resumos.resumir');

