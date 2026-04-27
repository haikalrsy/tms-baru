<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\GoogleAuthController;

Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback']);

// Email Verification — signed URL
Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
    ->middleware('signed')
->name('verification.verify');