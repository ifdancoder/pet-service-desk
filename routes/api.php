<?php

use App\Http\Controllers\Api\V1\AuthTokenController;
use App\Http\Controllers\Api\V1\DepartmentController;
use App\Http\Controllers\Api\V1\SavedFilterController;
use App\Http\Controllers\Api\V1\SlaPolicyController;
use App\Http\Controllers\Api\V1\TagController;
use App\Http\Controllers\Api\V1\TeamController;
use App\Http\Controllers\Api\V1\TicketAttachmentController;
use App\Http\Controllers\Api\V1\TicketCategoryController;
use App\Http\Controllers\Api\V1\TicketCommentController;
use App\Http\Controllers\Api\V1\TicketController;
use App\Http\Controllers\Api\V1\TicketTagController;
use App\Http\Controllers\Api\V1\TicketWatcherController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::post('auth/tokens', [AuthTokenController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('auth.tokens.store');

    Route::middleware('auth:sanctum')->group(function () {
        Route::delete('auth/tokens/current', [AuthTokenController::class, 'destroy'])->name('auth.tokens.destroy');

        Route::apiResource('departments', DepartmentController::class);
        Route::apiResource('teams', TeamController::class);
        Route::apiResource('ticket-categories', TicketCategoryController::class);
        Route::apiResource('tags', TagController::class);
        Route::apiResource('sla-policies', SlaPolicyController::class);
        Route::apiResource('saved-filters', SavedFilterController::class);
        Route::post('tickets/{ticket}/assign', [TicketController::class, 'assign'])->name('tickets.assign');
        Route::post('tickets/{ticket}/close', [TicketController::class, 'close'])->name('tickets.close');
        Route::post('tickets/{ticket}/reopen', [TicketController::class, 'reopen'])->name('tickets.reopen');
        Route::patch('tickets/{ticket}/priority', [TicketController::class, 'changePriority'])->name('tickets.priority');
        Route::get('tickets/search', [TicketController::class, 'search'])->name('tickets.search');
        Route::apiResource('tickets', TicketController::class);
        Route::apiResource('tickets.comments', TicketCommentController::class)->only(['index', 'store', 'destroy']);
        Route::apiResource('tickets.watchers', TicketWatcherController::class)->only(['index', 'store', 'destroy']);
        Route::put('tickets/{ticket}/tags', [TicketTagController::class, 'update'])->name('tickets.tags.update');
        Route::get('tickets/{ticket}/attachments/{attachment}/download', [TicketAttachmentController::class, 'download'])
            ->name('tickets.attachments.download');
        Route::apiResource('tickets.attachments', TicketAttachmentController::class)->only(['index', 'store', 'destroy']);
    });
});
