<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Display the patient dashboard.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $patient = Auth::user()->patient;
        
        // Próximas consultas
        $upcomingAppointments = Appointment::where('patient_id', $patient->id)
            ->where('start_time', '>=', now())
            ->where('status', 'scheduled')
            ->orderBy('start_time', 'asc')
            ->take(5)
            ->with(['doctor.user', 'specialty'])
            ->get();
        
        // Notificações não lidas
        $notifications = Notification::where('user_id', Auth::id())
            ->whereNull('read_at')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();
        
        // Consultas recentes
        $recentAppointments = Appointment::where('patient_id', $patient->id)
            ->where('start_time', '<', now())
            ->where('status', 'completed')
            ->orderBy('start_time', 'desc')
            ->take(3)
            ->with(['doctor.user', 'specialty'])
            ->get();
        
        // Estatísticas do paciente
        $stats = [
            'total_appointments' => Appointment::where('patient_id', $patient->id)->count(),
            'completed_appointments' => Appointment::where('patient_id', $patient->id)
                                        ->where('status', 'completed')
                                        ->count(),
            'upcoming_appointments' => Appointment::where('patient_id', $patient->id)
                                        ->where('status', 'scheduled')
                                        ->where('start_time', '>=', now())
                                        ->count()
        ];
        
        return view('patient.dashboard', [
            'upcomingAppointments' => $upcomingAppointments,
            'notifications' => $notifications,
            'recentAppointments' => $recentAppointments,
            'stats' => $stats
        ]);
    }
}