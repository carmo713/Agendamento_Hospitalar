<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use App\Models\Specialty;
use App\Models\Clinic;
use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class AdminDashboardController extends Controller
{
    /**
     * Exibir o painel de controle administrativo
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // Estatísticas gerais
        $stats = [
            'total_appointments' => Appointment::count(),
            'today_appointments' => Appointment::whereDate('start_time', Carbon::today())->count(),
            'upcoming_appointments' => Appointment::where('start_time', '>', Carbon::now())
                                             ->where('status', 'confirmed')
                                             ->count(),
            'cancelled_appointments' => Appointment::where('status', 'cancelled')->count(),
            'total_doctors' => Doctor::count(),
            'active_doctors' => Doctor::whereHas('user', function($query) {
                                    $query->where('status', 'active');
                                })->count(),
            'total_patients' => Patient::count(),
            'new_patients' => Patient::whereDate('created_at', '>', Carbon::now()->subDays(30))->count(),
            'total_specialties' => Specialty::count()
        ];
        
        // Consultas para hoje
        $todayAppointments = Appointment::with(['doctor.user', 'patient.user'])
            ->whereDate('start_time', Carbon::today())
            ->orderBy('start_time')
            ->take(10)
            ->get();
            
        // Estatísticas de consultas nos últimos 7 dias
        $last7Days = collect();
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $count = Appointment::whereDate('start_time', $date)->count();
            $last7Days->push([
                'date' => Carbon::now()->subDays($i)->format('d/m'),
                'count' => $count
            ]);
        }
        
        // Médicos mais requisitados
        $topDoctors = Doctor::withCount(['appointments' => function($query) {
                            $query->where('start_time', '>', Carbon::now()->subDays(30));
                        }])
                        ->with('user', 'specialties')
                        ->orderByDesc('appointments_count')
                        ->take(5)
                        ->get();
                        
        // Especialidades mais procuradas
        $topSpecialties = Specialty::withCount(['appointments' => function($query) {
                                $query->where('start_time', '>', Carbon::now()->subDays(30));
                            }])
                            ->orderByDesc('appointments_count')
                            ->take(5)
                            ->get();
                            
        // Distribuição de status das consultas
        $appointmentStatusDistribution = Appointment::select('status', DB::raw('count(*) as count'))
                                              ->where('start_time', '>', Carbon::now()->subDays(30))
                                              ->groupBy('status')
                                              ->pluck('count', 'status')
                                              ->toArray();
                                              
        // Atividade recente - logs de auditoria
        $recentActivity = AuditLog::with('user')
                              ->orderByDesc('created_at')
                              ->take(10)
                              ->get();
                              
        // Lista de usuários recentes para aprovação (se houver processo de aprovação)
        $pendingUsers = User::where('status', 'pending')
                         ->orderByDesc('created_at')
                         ->take(5)
                         ->get();
        
        return view('admin.dashboard', compact(
            'stats',
            'todayAppointments',
            'last7Days',
            'topDoctors',
            'topSpecialties',
            'appointmentStatusDistribution',
            'recentActivity',
            'pendingUsers'
        ));
    }
    
    /**
     * Mostrar estatísticas detalhadas
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function statistics(Request $request)
    {
        $period = $request->period ?? 'month';
        
        switch ($period) {
            case 'week':
                $startDate = Carbon::now()->startOfWeek();
                $endDate = Carbon::now()->endOfWeek();
                $groupFormat = 'Y-m-d';
                $labelFormat = 'd/m';
                break;
            case 'month':
                $startDate = Carbon::now()->startOfMonth();
                $endDate = Carbon::now()->endOfMonth();
                $groupFormat = 'Y-m-d';
                $labelFormat = 'd/m';
                break;
            case 'year':
                $startDate = Carbon::now()->startOfYear();
                $endDate = Carbon::now()->endOfYear();
                $groupFormat = 'Y-m';
                $labelFormat = 'm/Y';
                break;
            default:
                $startDate = Carbon::now()->subMonths(3);
                $endDate = Carbon::now();
                $groupFormat = 'Y-m';
                $labelFormat = 'm/Y';
        }
        
        // Dados para gráficos
        $appointmentsOverTime = $this->getAppointmentsOverTime($startDate, $endDate, $groupFormat, $labelFormat);
        $patientRegistrationsOverTime = $this->getPatientRegistrationsOverTime($startDate, $endDate, $groupFormat, $labelFormat);
        
        // Especialidades e médicos por volume
        $specialtyDistribution = Specialty::withCount(['appointments' => function($query) use ($startDate, $endDate) {
                                    $query->whereBetween('start_time', [$startDate, $endDate]);
                                }])
                                ->orderByDesc('appointments_count')
                                ->get();
                                
        $doctorDistribution = Doctor::withCount(['appointments' => function($query) use ($startDate, $endDate) {
                                $query->whereBetween('start_time', [$startDate, $endDate]);
                            }])
                            ->with('user', 'specialties')
                            ->orderByDesc('appointments_count')
                            ->get();
        
        // Análise de status
        $statusDistribution = Appointment::select('status', DB::raw('count(*) as count'))
                                   ->whereBetween('start_time', [$startDate, $endDate])
                                   ->groupBy('status')
                                   ->pluck('count', 'status')
                                   ->toArray();
                                   
        // Horários mais populares
        $popularTimeSlots = Appointment::select(DB::raw('HOUR(start_time) as hour'), DB::raw('count(*) as count'))
                                 ->whereBetween('start_time', [$startDate, $endDate])
                                 ->groupBy(DB::raw('HOUR(start_time)'))
                                 ->orderBy('hour')
                                 ->pluck('count', 'hour')
                                 ->toArray();
        
        // Distribuição por dia da semana
        $weekdayDistribution = Appointment::select(DB::raw('WEEKDAY(start_time) as weekday'), DB::raw('count(*) as count'))
                                    ->whereBetween('start_time', [$startDate, $endDate])
                                    ->groupBy(DB::raw('WEEKDAY(start_time)'))
                                    ->orderBy('weekday')
                                    ->pluck('count', 'weekday')
                                    ->toArray();
                                    
        $weekdayLabels = ['Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado', 'Domingo'];
        $weekdayStats = [];
        
        foreach (range(0, 6) as $day) {
            $weekdayStats[$weekdayLabels[$day]] = $weekdayDistribution[$day] ?? 0;
        }
                                    
        return view('admin.statistics', compact(
            'period',
            'appointmentsOverTime',
            'patientRegistrationsOverTime',
            'specialtyDistribution',
            'doctorDistribution',
            'statusDistribution',
            'popularTimeSlots',
            'weekdayStats'
        ));
    }
    
    /**
     * Obter número de consultas ao longo do tempo
     *
     * @param  Carbon  $startDate
     * @param  Carbon  $endDate
     * @param  string  $groupFormat
     * @param  string  $labelFormat
     * @return array
     */
    private function getAppointmentsOverTime($startDate, $endDate, $groupFormat, $labelFormat)
    {
        $appointments = Appointment::select(
            DB::raw("DATE_FORMAT(start_time, '{$groupFormat}') as date"),
            DB::raw('count(*) as count')
        )
        ->whereBetween('start_time', [$startDate, $endDate])
        ->groupBy(DB::raw("DATE_FORMAT(start_time, '{$groupFormat}')"))
        ->orderBy('date')
        ->get();
        
        $formattedData = [];
        
        foreach ($appointments as $appointment) {
            $date = Carbon::createFromFormat($groupFormat, $appointment->date);
            $formattedData[] = [
                'date' => $date->format($labelFormat),
                'count' => $appointment->count
            ];
        }
        
        return $formattedData;
    }
    
    /**
     * Obter número de registros de pacientes ao longo do tempo
     *
     * @param  Carbon  $startDate
     * @param  Carbon  $endDate
     * @param  string  $groupFormat
     * @param  string  $labelFormat
     * @return array
     */
    private function getPatientRegistrationsOverTime($startDate, $endDate, $groupFormat, $labelFormat)
    {
        $registrations = Patient::select(
            DB::raw("DATE_FORMAT(created_at, '{$groupFormat}') as date"),
            DB::raw('count(*) as count')
        )
        ->whereBetween('created_at', [$startDate, $endDate])
        ->groupBy(DB::raw("DATE_FORMAT(created_at, '{$groupFormat}')"))
        ->orderBy('date')
        ->get();
        
        $formattedData = [];
        
        foreach ($registrations as $registration) {
            $date = Carbon::createFromFormat($groupFormat, $registration->date);
            $formattedData[] = [
                'date' => $date->format($labelFormat),
                'count' => $registration->count
            ];
        }
        
        return $formattedData;
    }
    
    /**
     * Mostrar relatório de atividades do sistema
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function activityLog(Request $request)
    {
        $query = AuditLog::with('user');
        
        // Filtrar por tipo de ação
        if ($request->has('action') && $request->action) {
            $query->where('action', $request->action);
        }
        
        // Filtrar por usuário
        if ($request->has('user_id') && $request->user_id) {
            $query->where('user_id', $request->user_id);
        }
        
        // Filtrar por modelo
        if ($request->has('model_type') && $request->model_type) {
            $query->where('model_type', $request->model_type);
        }
        
        // Filtrar por período
        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('created_at', '>=', Carbon::parse($request->date_from));
        }
        
        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('created_at', '<=', Carbon::parse($request->date_to));
        }
        
        $logs = $query->orderBy('created_at', 'desc')
                   ->paginate(20)
                   ->withQueryString();
                   
        // Lista de usuários e tipos de modelo para os filtros
        $users = User::orderBy('name')->get();
        $modelTypes = AuditLog::distinct('model_type')->pluck('model_type');
        $actions = AuditLog::distinct('action')->pluck('action');
        
        return view('admin.activity_log', compact(
            'logs',
            'users',
            'modelTypes',
            'actions'
        ));
    }
    
    /**
     * Exibir resumo do sistema
     *
     * @return \Illuminate\View\View
     */
    public function systemSummary()
    {
        // Informações sobre as clínicas
        $clinics = Clinic::withCount('rooms')->get();
        
        // Disponibilidade de médicos
        $availableDoctorsToday = Doctor::whereHas('schedule', function($query) {
            $today = Carbon::today()->format('l');
            $query->where(strtolower($today), true);
        })->count();
        
        $specialtyCoverage = Specialty::withCount('doctors')->orderBy('name')->get();
        
        // Top pacientes por número de consultas
        $topPatientsByAppointments = Patient::withCount('appointments')
                                       ->with('user')
                                       ->orderByDesc('appointments_count')
                                       ->take(10)
                                       ->get();
        
        return view('admin.system_summary', compact(
            'clinics',
            'availableDoctorsToday',
            'specialtyCoverage',
            'topPatientsByAppointments'
        ));
    }
}