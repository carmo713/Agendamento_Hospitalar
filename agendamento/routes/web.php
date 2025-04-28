<?php

use App\Http\Controllers\ProfileController;
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

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/admin/dashboard', function () {
        return view('admin.dashboard');
    })->name('admin.dashboard');
});

Route::middleware(['auth', 'role:doctor'])->group(function () {
    Route::get('/doctor/dashboard', function () {
        return view('doctor.dashboard');
    })->name('doctor.dashboard');
});

Route::middleware(['auth', 'role:patient'])->group(function () {
    Route::get('/patient/dashboard', function () {
        return view('patient.dashboard');
    })->name('patient.dashboard');
}); 

// Admin Routes - Specialty Management

Route::middleware(['auth', 'role:admin'])->group(function () {
    route::get('/admin/specialties', [\App\Http\Controllers\Admin\SpecialtyController::class, 'index'])->name('admin.specialties.index');
    route::get('/admin/specialties/create', [\App\Http\Controllers\Admin\SpecialtyController::class, 'create'])->name('admin.specialties.create');
    route::post('/admin/specialties', [\App\Http\Controllers\Admin\SpecialtyController::class, 'store'])->name('admin.specialties.store');
    route::get('/admin/specialties/{specialty}/edit', [\App\Http\Controllers\Admin\SpecialtyController::class, 'edit'])->name('admin.specialties.edit');
    route::put('/admin/specialties/{specialty}', [\App\Http\Controllers\Admin\SpecialtyController::class, 'update'])->name('admin.specialties.update');
    route::delete('/admin/specialties/{specialty}', [\App\Http\Controllers\Admin\SpecialtyController::class, 'destroy'])->name('admin.specialties.destroy');
});

require __DIR__.'/auth.php';
