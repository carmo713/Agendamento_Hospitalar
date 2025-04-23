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

// Em routes/web.php
Route::middleware(['auth'])->group(function () {
    Route::get('/patient/dashboard', [PatientDashboardController::class, 'index'])
        ->middleware('role:patient')
        ->name('patient.dashboard');
        
    Route::get('/doctor/dashboard', [DoctorDashboardController::class, 'index'])
        ->middleware('role:doctor')
        ->name('doctor.dashboard');
        
    Route::get('/admin/dashboard', [AdminDashboardController::class, 'index'])
        ->middleware('role:admin')
        ->name('admin.dashboard');
}); 

require __DIR__.'/auth.php';
