<?php

use App\Http\Controllers\BalanceController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserTransactionController;
use Illuminate\Support\Facades\Route;

Route::post('/users', [UserController::class, 'register']);
Route::post('/users/login', [UserController::class, 'login']);
Route::get('/users/{userId}', [UserController::class, 'get']);

Route::get('/balance/{userId}', [BalanceController::class, 'get']);

Route::post('/transactions', [UserTransactionController::class, 'create']);
Route::get('/transactions/user/{userId}', [UserTransactionController::class, 'get']);
Route::put('/transactions/{transactionId}', [UserTransactionController::class, 'put']);
Route::delete('/transactions/{transactionId}', [UserTransactionController::class, 'delete']);
