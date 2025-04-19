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

class DashboardController extends Controller
{
    /**
     * Display the doctor dashboard.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $doctor = Auth::user()->doctor;
        $today = Carbon::today();
        
        // Consultas para hoje
        $todayAppointments = Appointment::where('doctor_id', $doctor->id)
            ->whereDate('start_time', $today)
            ->orderBy('start_time')
            ->with(['patient.user'])
            ->get();
        
        // Dividindo as consultas de hoje em categorias
        $waitingAppointments = $todayAppointments->where('status', 'confirmed');
        $inProgressAppointments = $todayAppointments->where('status', 'in_progress');
        $completedAppointments = $todayAppointments->where('status', 'completed');
        
        // Próximas consultas (dias futuros)
        $upcomingAppointments = Appointment::where('doctor_id', $doctor->id)
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->where('start_time', '>', Carbon::today()->endOfDay())
            ->orderBy('start_time')
            ->with(['patient.user'])
            ->limit(5)
            ->get();
        
        // Notificações não lidas
        $unreadNotifications = Notification::where('user_id', Auth::id())
            ->whereNull('read_at')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
        
        // Estatísticas
        $totalPatientsCount = Appointment::where('doctor_id', $doctor->id)
            ->whereIn('status', ['completed', 'confirmed', 'in_progress'])
            ->distinct('patient_id')
            ->count('patient_id');
        
        $totalAppointmentsCount = Appointment::where('doctor_id', $doctor->id)
            ->whereIn('status', ['completed', 'confirmed', 'in_progress'])
            ->count();
        
        $appointmentsThisMonth = Appointment::where('doctor_id', $doctor->id)
            ->whereIn('status', ['completed', 'confirmed', 'in_progress'])
            ->whereYear('start_time', Carbon::now()->year)
            ->whereMonth('start_time', Carbon::now()->month)
            ->count();
        
        $averageRating = Feedback::where('doctor_id', $doctor->id)
            ->avg('rating') ?? 0;
        
        // Tarefas pendentes (receitas, atestados, etc)
        $pendingPrescriptionRenewals = Notification::where('user_id', Auth::id())
            ->where('type', 'prescription_renewal')
            ->whereNull('read_at')
            ->count();
        
        $pendingCertificateRequests = Notification::where('user_id', Auth::id())
            ->where('type', 'certificate_request')
            ->whereNull('read_at')
            ->count();
        
        // Dados para gráfico de consultas por dia da semana
        $appointmentsByWeekday = Appointment::where('doctor_id', $doctor->id)
            ->whereIn('status', ['completed'])
            ->where('start_time', '>=', Carbon::now()->subDays(30))
            ->get()
            ->groupBy(function($appointment) {
                return Carbon::parse($appointment->start_time)->format('w'); // 0 (Sun) - 6 (Sat)
            })
            ->map(function($appointments) {
                return count($appointments);
            });
        
        // Garantir que todos os dias da semana estão representados
        $weekdays = [0, 1, 2, 3, 4, 5, 6];
        foreach ($weekdays as $day) {
            if (!isset($appointmentsByWeekday[$day])) {
                $appointmentsByWeekday[$day] = 0;
            }
        }
        
        // Ordenar por dia da semana
        ksort($appointmentsByWeekday);
        
        return view('doctor.dashboard', [
            'doctor' => $doctor,
            'todayAppointments' => $todayAppointments,
            'waitingAppointments' => $waitingAppointments,
            'inProgressAppointments' => $inProgressAppointments,
            'completedAppointments' => $completedAppointments,
            'upcomingAppointments' => $upcomingAppointments,
            'unreadNotifications' => $unreadNotifications,
            'totalPatientsCount' => $totalPatientsCount,
            'totalAppointmentsCount' => $totalAppointmentsCount,
            'appointmentsThisMonth' => $appointmentsThisMonth,
            'averageRating' => $averageRating,
            'pendingPrescriptionRenewals' => $pendingPrescriptionRenewals,
            'pendingCertificateRequests' => $pendingCertificateRequests,
            'appointmentsByWeekday' => $appointmentsByWeekday
        ]);
    }
    
    /**
     * Mark all notifications as read.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function markAllNotificationsAsRead()
    {
        Notification::where('user_id', Auth::id())
            ->whereNull('read_at')
            ->update(['read_at' => Carbon::now()]);
        
        return back()->with('success', 'Todas as notificações foram marcadas como lidas.');
    }
    
    /**
     * Display analytics and statistics.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function analytics(Request $request)
    {
        $doctor = Auth::user()->doctor;
        $period = $request->query('period', 'month');
        
        // Definir período de análise
        switch ($period) {
            case 'week':
                $startDate = Carbon::now()->subWeek();
                break;
            case 'month':
                $startDate = Carbon::now()->subMonth();
                break;
            case 'quarter':
                $startDate = Carbon::now()->subQuarter();
                break;
            case 'year':
                $startDate = Carbon::now()->subYear();
                break;
            default:
                $startDate = Carbon::now()->subMonth();
        }
        
        $endDate = Carbon::now();
        
        // Total de consultas no período
        $totalAppointments = Appointment::where('doctor_id', $doctor->id)
            ->whereBetween('start_time', [$startDate, $endDate])
            ->count();
        
        // Consultas por status
        $appointmentsByStatus = Appointment::where('doctor_id', $doctor->id)
            ->whereBetween('start_time', [$startDate, $endDate])
            ->get()
            ->groupBy('status')
            ->map(function($items) {
                return count($items);
            });
        
        // Novos pacientes no período
        $newPatientsCount = Appointment::where('doctor_id', $doctor->id)
            ->whereBetween('start_time', [$startDate, $endDate])
            ->distinct('patient_id')
            ->count('patient_id');
        
        // Distribuição de avaliações
        $ratingsDistribution = Feedback::where('doctor_id', $doctor->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get()
            ->groupBy('rating')
            ->map(function($items) {
                return count($items);
            });
        
        // Garantir que todas as avaliações estão representadas
        for ($i = 1; $i <= 5; $i++) {
            if (!isset($ratingsDistribution[$i])) {
                $ratingsDistribution[$i] = 0;
            }
        }
        
        // Ordenar por nota
        ksort($ratingsDistribution);
        
        // Consultas por dia
        $appointmentsByDay = Appointment::where('doctor_id', $doctor->id)
            ->whereBetween('start_time', [$startDate, $endDate])
            ->get()
            ->groupBy(function($appointment) {
                return Carbon::parse($appointment->start_time)->format('Y-m-d');
            })
            ->map(function($items) {
                return count($items);
            });
        
        return view('doctor.analytics', [
            'doctor' => $doctor,
            'period' => $period,
            'totalAppointments' => $totalAppointments,
            'appointmentsByStatus' => $appointmentsByStatus,
            'newPatientsCount' => $newPatientsCount,
            'ratingsDistribution' => $ratingsDistribution,
            'appointmentsByDay' => $appointmentsByDay,
            'startDate' => $startDate->format('Y-m-d'),
            'endDate' => $endDate->format('Y-m-d')
        ]);
    }
}