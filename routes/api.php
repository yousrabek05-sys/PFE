<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RDVController;
use App\Http\Controllers\FolderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductsController;
use App\Http\Controllers\NotificationController;


Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);


Route::middleware('auth:sanctum')->group(function () {

    
    Route::post('/logout',          [AuthController::class, 'logout']);
    Route::get('/profile',          [AuthController::class, 'profile']);
    Route::put('/profile',          [AuthController::class, 'updateProfile']);

    
    Route::get('/appointments',             [RDVController::class, 'index']);
    Route::post('/appointments',            [RDVController::class, 'store']);
    Route::put('/appointments/{id}',        [RDVController::class, 'update']);
    Route::delete('/appointments/{id}',     [RDVController::class, 'cancel']);
    Route::put('/appointments/{id}/status', [RDVController::class, 'updateStatus']);

    
    Route::get('/folders',                      [FolderController::class, 'index']);
    Route::post('/folders',                     [FolderController::class, 'store']);
    Route::get('/folders/{id}',                 [FolderController::class, 'show']);
    Route::put('/folders/{id}',                 [FolderController::class, 'update']);
    Route::post('/folders/{id}/diagnostics',    [FolderController::class, 'addDiagnostic']);
    Route::post('/folders/{id}/treatments',     [FolderController::class, 'addTreatment']);
    Route::post('/folders/{id}/images',         [FolderController::class, 'addImage']);

    
    Route::get('/payments',             [PaymentController::class, 'index']);
    Route::post('/payments',            [PaymentController::class, 'store']);
    Route::get('/payments/{id}/receipt',[PaymentController::class, 'generateReceipt']);

    
    Route::get('/products',         [ProductsController::class, 'index']);
    Route::post('/products',        [ProductsController::class, 'store']);
    Route::put('/products/{id}',    [ProductsController::class, 'update']);
    Route::delete('/products/{id}', [ProductsController::class, 'destroy']);

    // Notifications
    Route::get('/notifications',                [NotificationController::class, 'index']);
    Route::put('/notifications/{id}/read',      [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/send',          [NotificationController::class, 'send']);

});

Route::put('/products/{id}/use' [ProductsController::class, 'reduceStock']);

Route::put('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);