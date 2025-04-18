<?php

// app/Http/Controllers/Patient/DashboardController.php
namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Illuminate\Support\Facades\Auth;

class PatientDashboardController extends Controller
{
    public function index()
    {
        $patient = Auth::user()->patient;
        
        $appointments = [
            'upcoming' => Appointment::where('patient_id', $patient->id)
                                ->where('start_time', '>', now())
                                ->orderBy('start_time')
                                ->limit(3)
                                ->get(),
            'recent' => Appointment::where('patient_id', $patient->id)
                                ->where('end_time', '<', now())
                                ->orderBy('start_time', 'desc')
                                ->limit(3)
                                ->get(),
        ];
        
        return view('patient.dashboard', compact('appointments'));
    }
}   