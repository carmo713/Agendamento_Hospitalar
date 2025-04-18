<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $doctor = Auth::user()->doctor;
        
        $appointments = [
            'today' => Appointment::where('doctor_id', $doctor->id)
                                ->whereDate('start_time', today())
                                ->orderBy('start_time')
                                ->get(),
            'upcoming' => Appointment::where('doctor_id', $doctor->id)
                                ->where('start_time', '>', now())
                                ->whereDate('start_time', '>', today())
                                ->orderBy('start_time')
                                ->limit(5)
                                ->get(),
        ];
        
        return view('doctor.dashboard', compact('appointments'));
    }
}