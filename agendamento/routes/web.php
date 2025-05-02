<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    if (auth()->user()->hasRole('admin')) {
        return redirect()->route('admin.dashboard');
    } elseif (auth()->user()->hasRole('doctor')) {
        return redirect()->route('doctors.dashboard'); // Alterado de doctors.dashboard para doctor.dashboard
    } elseif (auth()->user()->hasRole('patient')) {
        return redirect()->route('patient.dashboard');
    }
    abort(403); // Forbidden if no valid role
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/admin/dashboard', [\App\Http\Controllers\Admin\AdminDashboardController::class, 'index'])->name('admin.dashboard');
});

Route::middleware(['auth', 'role:doctor'])->group(function () {
    route::get('/doctor/dashboard', [\App\Http\Controllers\Doctor\DoctorDashboardController::class, 'index'])->name('doctor.dashboard');    
});

Route::middleware(['auth', 'role:patient'])->group(function () {
    Route::get('/patient/dashboard', function () {
        return view('patients.dashboard');
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

// Admin Routes - Clinic Management
Route::middleware(['auth', 'role:admin'])->group(function () {
    route::get('/admin/clinics', [\App\Http\Controllers\Admin\ClinicController::class, 'index'])->name('admin.clinics.index');
    route::get('/admin/clinics/create', [\App\Http\Controllers\Admin\ClinicController::class, 'create'])->name('admin.clinics.create');
    route::post('/admin/clinics', [\App\Http\Controllers\Admin\ClinicController::class, 'store'])->name('admin.clinics.store');
    route::get('/admin/clinics/{clinic}/edit', [\App\Http\Controllers\Admin\ClinicController::class, 'edit'])->name('admin.clinics.edit');
    route::put('/admin/clinics/{clinic}', [\App\Http\Controllers\Admin\ClinicController::class, 'update'])->name('admin.clinics.update');
    route::delete('/admin/clinics/{clinic}', [\App\Http\Controllers\Admin\ClinicController::class, 'destroy'])->name('admin.clinics.destroy');
    route::get('/admin/clinics/{clinic}', [\App\Http\Controllers\Admin\ClinicController::class, 'show'])->name('admin.clinics.show');
});
// Admin Routes - Room Management
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/admin/rooms', [\App\Http\Controllers\Admin\RoomController::class, 'index'])->name('admin.rooms.index');
    Route::get('/admin/rooms/create', [\App\Http\Controllers\Admin\RoomController::class, 'create'])->name('admin.rooms.create');
    Route::post('/admin/rooms', [\App\Http\Controllers\Admin\RoomController::class, 'store'])->name('admin.rooms.store');
    Route::get('/admin/rooms/{room}', [\App\Http\Controllers\Admin\RoomController::class, 'show'])->name('admin.rooms.show');
    Route::get('/admin/rooms/{room}/edit', [\App\Http\Controllers\Admin\RoomController::class, 'edit'])->name('admin.rooms.edit');
    Route::put('/admin/rooms/{room}', [\App\Http\Controllers\Admin\RoomController::class, 'update'])->name('admin.rooms.update');
    Route::delete('/admin/rooms/{room}', [\App\Http\Controllers\Admin\RoomController::class, 'destroy'])->name('admin.rooms.destroy');
    Route::get('/admin/clinics/{clinic}/rooms', [\App\Http\Controllers\Admin\RoomController::class, 'byClinic'])->name('admin.clinics.rooms');
});

// Admin Routes - Doctor Management
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/admin/doctors', [\App\Http\Controllers\Admin\DoctorController::class, 'index'])->name('admin.doctors.index');
    Route::get('/admin/doctors/create', [\App\Http\Controllers\Admin\DoctorController::class, 'create'])->name('admin.doctors.create');
    Route::post('/admin/doctors', [\App\Http\Controllers\Admin\DoctorController::class, 'store'])->name('admin.doctors.store');
    Route::get('/admin/doctors/{doctor}', [\App\Http\Controllers\Admin\DoctorController::class, 'show'])->name('admin.doctors.show');
    Route::get('/admin/doctors/{doctor}/edit', [\App\Http\Controllers\Admin\DoctorController::class, 'edit'])->name('admin.doctors.edit');
    Route::put('/admin/doctors/{doctor}', [\App\Http\Controllers\Admin\DoctorController::class, 'update'])->name('admin.doctors.update');
    Route::delete('/admin/doctors/{doctor}', [\App\Http\Controllers\Admin\DoctorController::class, 'destroy'])->name('admin.doctors.destroy');
});

// Admin Routes - Patient Management
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/admin/patients', [\App\Http\Controllers\Admin\PatientController::class, 'index'])->name('admin.patients.index');
    Route::get('/admin/patients/create', [\App\Http\Controllers\Admin\PatientController::class, 'create'])->name('admin.patients.create');
    Route::post('/admin/patients', [\App\Http\Controllers\Admin\PatientController::class, 'store'])->name('admin.patients.store');
    Route::get('/admin/patients/{patient}', [\App\Http\Controllers\Admin\PatientController::class, 'show'])->name('admin.patients.show');
    Route::get('/admin/patients/{patient}/edit', [\App\Http\Controllers\Admin\PatientController::class, 'edit'])->name('admin.patients.edit');
    Route::put('/admin/patients/{patient}', [\App\Http\Controllers\Admin\PatientController::class, 'update'])->name('admin.patients.update');
    Route::delete('/admin/patients/{patient}', [\App\Http\Controllers\Admin\PatientController::class, 'destroy'])->name('admin.patients.destroy');
});

// Admin Routes - Appointment Management
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/admin/appointments', [\App\Http\Controllers\Admin\AppointmentController::class, 'index'])->name('admin.appointments.index');
    Route::get('/admin/appointments/create', [\App\Http\Controllers\Admin\AppointmentController::class, 'create'])->name('admin.appointments.create');
    Route::post('/admin/appointments', [\App\Http\Controllers\Admin\AppointmentController::class, 'store'])->name('admin.appointments.store');
    Route::get('/admin/appointments/{appointment}', [\App\Http\Controllers\Admin\AppointmentController::class, 'show'])->name('admin.appointments.show');
    Route::get('/admin/appointments/{appointment}/edit', [\App\Http\Controllers\Admin\AppointmentController::class, 'edit'])->name('admin.appointments.edit');
    Route::put('/admin/appointments/{appointment}', [\App\Http\Controllers\Admin\AppointmentController::class, 'update'])->name('admin.appointments.update');
    Route::delete('/admin/appointments/{appointment}', [\App\Http\Controllers\Admin\AppointmentController::class, 'destroy'])->name('admin.appointments.destroy');
    Route::patch('/admin/appointments/{appointment}/status', [\App\Http\Controllers\Admin\AppointmentController::class, 'updateStatus'])->name('admin.appointments.update-status');
});

require __DIR__.'/auth.php';
