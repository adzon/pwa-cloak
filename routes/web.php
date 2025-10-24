<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FilamentCommentController;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('filament/comments')->group(function () {
    Route::post('/save', [FilamentCommentController::class, 'save']);
    Route::delete('/delete/{id}', [FilamentCommentController::class, 'delete']);
});
