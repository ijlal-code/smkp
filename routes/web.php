<?php

use App\Http\Controllers\SmkpController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

// --- AUTHENTICATION ROUTES ---
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Redirect root ke index (akan dicek middleware nanti)
Route::get('/', function () {
    return redirect()->route('smkp.index');
});

// --- PROTECTED ROUTES (Harus Login) ---
Route::middleware(['auth'])->group(function () {
    
    // Route Index (Bisa diakses semua user yg login)
    Route::get('/smkp/{folder?}', [SmkpController::class, 'index'])->name('smkp.index');
    Route::get('/smkp/file/{id}', [SmkpController::class, 'download'])->name('smkp.download');

    // --- CONTOH PEMBATASAN AKSES BERDASARKAN ROLE ---
    // Gunakan middleware role:Role1,Role2 untuk membatasi akses upload/delete
    // Contoh di bawah: Semua role bisa akses, tapi Anda bisa ganti listnya.
    // Misal hanya 'Pengelola Sistem' dan 'KTT' yang boleh upload:
    // Route::middleware(['role:Pengelola Sistem,KTT'])->group(function () { ... });
    
    Route::post('/smkp/upload/{folder?}', [SmkpController::class, 'upload'])->name('smkp.upload');
    Route::post('/smkp/create-folder/{folder?}', [SmkpController::class, 'createFolder'])->name('smkp.create_folder');
    Route::post('/smkp/create-tab', [SmkpController::class, 'createTab'])->name('smkp.create_tab');
    Route::put('/smkp/folder/{id}', [SmkpController::class, 'updateFolder'])->name('smkp.update_folder');
    Route::delete('/smkp/folder/{id}', [SmkpController::class, 'deleteFolder'])->name('smkp.delete_folder');
    Route::delete('/smkp/file/{id}', [SmkpController::class, 'deleteFile'])->name('smkp.delete_file');
});