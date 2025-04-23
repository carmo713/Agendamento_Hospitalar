<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Doctor\DoctorDashboardController;
use App\Http\Controllers\Patient\PatientDashboardController;
use Illuminate\Support\Facades\Route;


Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Rotas autenticadas
Route::middleware(['auth'])->group(function () {
    // Grupo para admin
    Route::middleware(['role:admin'])->group(function () {
        Route::get('/admin/dashboard', [AdminDashboardController::class, 'index'])
            ->name('admin.dashboard'); // Nome da rota deve corresponder
    });
    
    // Grupo para doctor
    Route::middleware(['role:doctor'])->group(function () {
        Route::get('/doctor/dashboard', [DoctorDashboardController::class, 'index'])
            ->name('doctor.dashboard');
    });
    
    // Grupo para patient
    Route::middleware(['role:patient'])->group(function () {
        Route::get('/patient/dashboard', [PatientDashboardController::class, 'index'])
            ->name('patient.dashboard');
    });
});

require __DIR__.'/auth.php';
