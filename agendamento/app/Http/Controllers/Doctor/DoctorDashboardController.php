<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\MedicalRecord;
use App\Models\Patient;
use App\Models\Feedback;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DoctorDashboardController extends Controller
{
    /**
     * Display the doctor dashboard.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $doctor = Auth::user();
        $appointments = Appointment::where('doctor_id', $doctor->id)->get();
        $patients = Patient::where('doctor_id', $doctor->id)->get();
        $medicalRecords = MedicalRecord::where('doctor_id', $doctor->id)->get();
        $feedbacks = Feedback::where('doctor_id', $doctor->id)->get();
        $notifications = Notification::where('user_id', $doctor->id)->get();

        return view('Doctor.dashboard', compact('appointments', 'patients', 'medicalRecords', 'feedbacks', 'notifications'));
    }
}