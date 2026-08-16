<?php

use App\Http\Controllers\Api\V1\AuthTokenController;
use App\Http\Controllers\Api\V1\DepartmentController;
use App\Http\Controllers\Api\V1\TeamController;
use App\Http\Controllers\Api\V1\TicketCategoryController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::post('auth/tokens', [AuthTokenController::class, 'store'])->name('auth.tokens.store');

    Route::middleware('auth:sanctum')->group(function () {
        Route::delete('auth/tokens/current', [AuthTokenController::class, 'destroy'])->name('auth.tokens.destroy');

        Route::apiResource('departments', DepartmentController::class);
        Route::apiResource('teams', TeamController::class);
        Route::apiResource('ticket-categories', TicketCategoryController::class);
    });
});
