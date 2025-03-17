<?php

use App\Http\Controllers\GoogleSheetController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/google-sheet', [GoogleSheetController::class, 'index']);

Route::get('/test', [GoogleSheetController::class, 'getSentEmails']);

Route::get('/auth/google', [GoogleSheetController::class, 'redirectToGoogle'])->name('gmail.redirect');
Route::get('/auth/google/callback', [GoogleSheetController::class, 'handleGoogleCallback'])->name('gmail.callback');
Route::get('/gmail/sent', [GoogleSheetController::class, 'getSentEmails'])->name('gmail.sentEmails');
