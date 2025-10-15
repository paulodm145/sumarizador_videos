<?php

use App\Http\Controllers\ResumoController;
use Illuminate\Support\Facades\Route;

Route::get('/', ResumoController::class . '@index')->name('index');
