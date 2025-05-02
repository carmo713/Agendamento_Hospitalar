<?php
// filepath: /home/carmo/Documentos/trabalhofinal_agendamentohospitalar/agendamento/app/Http/Controllers/Doctor/DoctorDashboardController.php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\MedicalRecord;
use App\Models\Patient;
use App\Models\Feedback;
use App\Models\Notification;
use App\Models\Prescription;
use App\Models\ExamRequest;
use App\Models\Specialty;
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
        // Buscar o usuário autenticado e seu perfil de médico
        $user = Auth::user();
        $doctor = Doctor::where('user_id', $user->id)->first();
        
        if (!$doctor) {
            return redirect()->route('login')
                ->with('error', 'Perfil de médico não encontrado para este usuário.');
        }

        // Obter a data atual para exibir no dashboard
        $today = Carbon::now();
        
        // Estatísticas para os cards
       
            
        // Próximas consultas do dia
        $upcomingAppointments = Appointment::where('doctor_id', $doctor->id)
            ->whereDate('start_time', $today)
            ->where('start_time', '>=', $today)
            ->where('status', 'confirmed')
            ->with('patient.user')
            ->orderBy('start_time', 'asc')
            ->take(5)
            ->get();
            
        // Consultas recentes (últimos 7 dias)
        $recentAppointments = Appointment::where('doctor_id', $doctor->id)
            ->whereDate('start_time', '>=', $today->copy()->subDays(7))
            ->whereDate('start_time', '<', $today)
            ->with('patient.user')
            ->orderBy('start_time', 'desc')
            ->take(10)
            ->get();
            
        // Consultas por especialidade
        $specialtyAppointments = [];
        foreach ($doctor->specialties as $specialty) {
            $count = Appointment::where('doctor_id', $doctor->id)
                ->whereHas('specialty', function ($query) use ($specialty) {
                    $query->where('specialty_id', $specialty->id);
                })
                ->count();
            $specialtyAppointments[$specialty->name] = $count;
        }
        
      
        
        // Prontuários recentes
        $recentMedicalRecords = MedicalRecord::where('doctor_id', $doctor->id)
            ->with('patient.user')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();
            
        // Dados do médico
        $doctorData = [
            'name' => $doctor->full_name, // usando o accessor definido no modelo
            'specialties' => $doctor->specialties->pluck('name')->join(', '),
            'crm' => $doctor->crm . '-' . $doctor->crm_state,
            'bio' => $doctor->bio,
            'consultation_duration' => $doctor->consultation_duration,
            'average_rating' => $doctor->average_rating, // usando o accessor definido no modelo
            'date' => $today->format('d/m/Y'),
            'time' => $today->format('H:i'),
        ];

        return view('doctors.dashboard', compact(
            'doctorData',
            
            'upcomingAppointments',
            'recentAppointments',
            'specialtyAppointments',
            
            'recentMedicalRecords'
        ));
    }
}