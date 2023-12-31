<?php

use App\Http\Controllers\FileController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::post('push-notification', [ProfileController::class, 'pushNotification']);

Route::controller(FileController::class)->middleware(['auth', 'verified'])->group(function () {
    Route::get('/my-files/{folder?}', 'myFiles')->where('folder', '(.*)')->name('myFiles');
    Route::post('/folder/create', 'createFolder')->name('folder.create');
    Route::get('/folder/trash', 'trash')->name('folder.trash');
    Route::post('/file', 'store')->name('file.store');
    Route::delete('/file', 'destroy')->name('file.destroy');
    Route::post('/file/restore', 'restore')->name('file.restore');
    Route::delete('/file/delete-forever', 'deleteForever')->name('file.deleteForever');
    Route::post('/file/add-to-favourites', 'addToFavourites')->name('file.addToFavourites');
    Route::post('/file/share', 'share')->name('file.share');
    Route::get('/file/share-with-me', 'sharedWithMe')->name('file.share.with.me');
    Route::get('/file/share-by-me', 'sharedByMe')->name('file.share.by.me');
    Route::get('/file/download', 'download')->name('file.download');
    Route::get('/file/download-shared-with-me', 'downloadSharedWithMe')->name('file.download.shared.with.me');
    Route::get('/file/download-shared-by-me', 'downloadSharedByMe')->name('file.download.shared.by.me');
});

Route::get('/dashboard', function () {
    // return Inertia::render('Dashboard');
    phpinfo();
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/test', [ProfileController::class, 'test'])->name('profile.test');
});

require __DIR__.'/auth.php';
