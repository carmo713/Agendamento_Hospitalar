<?php
// filepath: /home/carmo/Documentos/trabalhofinal_agendamentohospitalar/agendamento/app/Http/Controllers/Admin/AdminDashboardController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Specialty;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    /**
     * Display the admin dashboard.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $now = Carbon::now();

        // Estatísticas gerais
        $stats = [
            'total_appointments' => Appointment::count(),
            'today_appointments' => Appointment::whereDate('start_time', Carbon::today())->count(),
            'total_doctors' => Doctor::count(),
            'total_patients' => Patient::count(),
        ];
        
        // Consultas recentes (consultas que já ocorreram - passadas)
        $recentAppointments = Appointment::with(['patient.user', 'doctor.user', 'specialty'])
            ->where('start_time', '<', $now)  // Apenas consultas passadas
            ->orderBy('start_time', 'desc')   // Ordenar pela mais recente primeiro
            ->take(5)
            ->get();
            
        // Próximas consultas (consultas que ainda vão ocorrer - futuras)
        $upcomingAppointments = Appointment::with(['patient.user', 'doctor.user', 'specialty'])
            ->where('start_time', '>=', $now) // Apenas consultas futuras
            ->where('status', '!=', 'canceled') // Excluir consultas canceladas
            ->orderBy('start_time', 'asc')    // Ordenar pela próxima primeiro
            ->take(5)
            ->get();
            
        // Médicos com mais consultas
        $topDoctors = Doctor::withCount('appointments')
            ->with('user')
            ->orderByDesc('appointments_count')
            ->take(5)
            ->get();
            
        // Especialidades mais procuradas
        $topSpecialties = Specialty::withCount('appointments')
            ->orderByDesc('appointments_count')
            ->take(5)
            ->get();
        
        return view('admin.dashboard', compact(
            'stats',
            'recentAppointments',
            'upcomingAppointments',
            'topDoctors',
            'topSpecialties'
        ));
    }
}