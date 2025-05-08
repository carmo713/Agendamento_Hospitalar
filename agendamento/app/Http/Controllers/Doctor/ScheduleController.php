<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Schedule;
use App\Models\ScheduleException;
use App\Models\Specialty;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Validation\ValidationException;

class ScheduleController extends Controller
{
    /**
     * Exibe a página principal do calendário/agenda do médico
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $doctor = Auth::user()->doctor;
        $date = $request->input('date') ? Carbon::parse($request->input('date')) : Carbon::today();
        $view = $request->input('view', 'day'); // day, week, month
        
        // Obter consultas conforme a visualização selecionada
        if ($view == 'day') {
            $appointments = $this->getDayAppointments($doctor->id, $date);
            $dateRange = [$date->copy()->startOfDay(), $date->copy()->endOfDay()];
        } 
        elseif ($view == 'week') {
            $startOfWeek = $date->copy()->startOfWeek();
            $endOfWeek = $date->copy()->endOfWeek();
            $appointments = $this->getWeekAppointments($doctor->id, $startOfWeek, $endOfWeek);
            $dateRange = [$startOfWeek, $endOfWeek];
        } 
        else {
            $startOfMonth = $date->copy()->startOfMonth();
            $endOfMonth = $date->copy()->endOfMonth();
            $appointments = $this->getMonthAppointments($doctor->id, $startOfMonth, $endOfMonth);
            $dateRange = [$startOfMonth, $endOfMonth];
        }
        
        // Obter horários regulares do médico
        $schedules = Schedule::where('doctor_id', $doctor->id)
                            ->orderBy('day_of_week')
                            ->orderBy('start_time')
                            ->get();
        
        // Obter exceções para os horários
        $exceptions = $this->getExceptionsForDateRange($schedules->pluck('id'), $dateRange[0], $dateRange[1]);
        
        return view('doctors.schedule.index', [
            'doctor' => $doctor,
            'date' => $date,
            'view' => $view,
            'appointments' => $appointments,
            'schedules' => $schedules,
            'exceptions' => $exceptions
        ]);
    }
    
    /**
     * Exibir página para configurar horários de atendimento
     *
     * @return \Illuminate\View\View
     */
    public function edit()
    {
        $doctor = Auth::user()->doctor;
        $schedules = Schedule::where('doctor_id', $doctor->id)
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();
        
        $specialties = $doctor->specialties;
        
        // Agrupar horários por dia da semana
        $schedulesByDay = [];
        foreach ($schedules as $schedule) {
            $schedulesByDay[$schedule->day_of_week][] = $schedule;
        }
        
        return view('doctor.schedule.edit', [
            'doctor' => $doctor,
            'schedulesByDay' => $schedulesByDay,
            'specialties' => $specialties
        ]);
    }
    
    /**
     * Salvar configurações de horários
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'day_of_week' => 'required|array',
            'day_of_week.*' => 'required|integer|between:0,6',
            'start_time' => 'required|array',
            'start_time.*' => 'required|date_format:H:i',
            'end_time' => 'required|array',
            'end_time.*' => 'required|date_format:H:i|after_or_equal:start_time.*',
            'specialty_id' => 'required|array',
            'specialty_id.*' => 'required|exists:specialties,id',
        ]);
        
        $doctor = Auth::user()->doctor;
        
        // Antes de adicionar novos horários, remova os antigos
        Schedule::where('doctor_id', $doctor->id)->delete();
        
        // Adicionar novos horários
        for ($i = 0; $i < count($request->day_of_week); $i++) {
            Schedule::create([
                'doctor_id' => $doctor->id,
                'day_of_week' => $request->day_of_week[$i],
                'start_time' => $request->start_time[$i],
                'end_time' => $request->end_time[$i],
                'specialty_id' => $request->specialty_id[$i],
                'is_available' => true,
            ]);
        }
        
        return redirect()->route('doctor.schedule.edit')
            ->with('success', 'Horários de atendimento atualizados com sucesso.');
    }
    
    /**
     * Exibir página para gerenciar exceções (folgas, férias)
     *
     * @return \Illuminate\View\View
     */
    public function exceptions()
    {
        $doctor = Auth::user()->doctor;
        
        // Obtém todos os horários do médico
        $schedules = Schedule::where('doctor_id', $doctor->id)->get();
        
        // Obter exceções futuras
        $futureExceptions = ScheduleException::whereIn('schedule_id', $schedules->pluck('id'))
            ->whereDate('exception_date', '>=', Carbon::today())
            ->orderBy('exception_date')
            ->get();
        
        // Obter exceções passadas
        $pastExceptions = ScheduleException::whereIn('schedule_id', $schedules->pluck('id'))
            ->whereDate('exception_date', '<', Carbon::today())
            ->orderBy('exception_date', 'desc')
            ->limit(20) // Mostrar apenas as 20 exceções mais recentes
            ->get();
        
        return view('doctor.schedule.exceptions', [
            'doctor' => $doctor,
            'schedules' => $schedules,
            'futureExceptions' => $futureExceptions,
            'pastExceptions' => $pastExceptions
        ]);
    }
    
    /**
     * Adicionar uma nova exceção (folga, férias)
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeException(Request $request)
    {
        $this->validate($request, [
            'schedule_id' => 'required|exists:schedules,id',
            'exception_date' => 'required|date|after_or_equal:today',
            'is_available' => 'required|boolean',
            'start_time' => 'nullable|required_if:is_available,1|date_format:H:i',
            'end_time' => 'nullable|required_if:is_available,1|date_format:H:i|after:start_time',
            'reason' => 'nullable|string|max:255',
        ]);
        
        $doctor = Auth::user()->doctor;
        $schedule = Schedule::findOrFail($request->schedule_id);
        
        // Verificar se o horário pertence ao médico
        if ($schedule->doctor_id != $doctor->id) {
            return redirect()->back()->with('error', 'Você não possui permissão para modificar este horário.');
        }
        
        // Verificar se já existe uma exceção para este horário e data
        $existingException = ScheduleException::where('schedule_id', $request->schedule_id)
            ->whereDate('exception_date', $request->exception_date)
            ->first();
            
        if ($existingException) {
            return redirect()->back()->with('error', 'Já existe uma exceção cadastrada para este horário nesta data.');
        }
        
        // Se o horário não estará disponível, verificar conflito com consultas
        if ($request->is_available == 0) {
            $exceptionDate = Carbon::parse($request->exception_date);
            $dayOfWeek = $exceptionDate->dayOfWeek;
            
            // Verificar se é o mesmo dia da semana do horário
            if ($dayOfWeek == $schedule->day_of_week) {
                // Verificar se há consultas agendadas neste horário
                $appointments = Appointment::where('doctor_id', $doctor->id)
                    ->whereDate('start_time', $exceptionDate)
                    ->whereTime('start_time', '>=', $schedule->start_time)
                    ->whereTime('start_time', '<', $schedule->end_time)
                    ->whereIn('status', ['scheduled', 'confirmed'])
                    ->count();
                    
                if ($appointments > 0) {
                    return redirect()->back()->with('error', 'Existem consultas agendadas neste horário. Por favor, remarcque ou cancele as consultas antes de adicionar uma exceção.');
                }
            }
        }
        
        // Criar a exceção
        ScheduleException::create([
            'schedule_id' => $request->schedule_id,
            'exception_date' => $request->exception_date,
            'is_available' => $request->is_available,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'reason' => $request->reason,
        ]);
        
        return redirect()->route('doctor.schedule.exceptions')
            ->with('success', 'Exceção de horário adicionada com sucesso.');
    }
    
    /**
     * Remover uma exceção
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroyException($id)
    {
        $doctor = Auth::user()->doctor;
        
        // Buscar a exceção e verificar se pertence a um horário do médico
        $exception = ScheduleException::findOrFail($id);
        $schedule = Schedule::findOrFail($exception->schedule_id);
        
        if ($schedule->doctor_id != $doctor->id) {
            return redirect()->back()->with('error', 'Você não possui permissão para remover esta exceção.');
        }
        
        // Verificar se a exceção é para uma data futura
        if ($exception->exception_date <= Carbon::today()) {
            return redirect()->back()->with('error', 'Não é possível remover exceções para datas passadas ou para hoje.');
        }
        
        $exception->delete();
        
        return redirect()->route('doctor.schedule.exceptions')
            ->with('success', 'Exceção de horário removida com sucesso.');
    }
    
    /**
     * Obter dados de calendário via AJAX
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCalendarData(Request $request)
    {
        $doctor = Auth::user()->doctor;
        $startDate = Carbon::parse($request->start);
        $endDate = Carbon::parse($request->end);
        
        // Obter horários e exceções
        $schedules = Schedule::where('doctor_id', $doctor->id)->get();
        $scheduleIds = $schedules->pluck('id');
        
        // Obter exceções para o período
        $exceptions = $this->getExceptionsForDateRange($scheduleIds, $startDate, $endDate);
        
        // Obter consultas para o período
        $appointments = Appointment::where('doctor_id', $doctor->id)
            ->whereBetween('start_time', [$startDate, $endDate])
            ->with(['patient.user', 'specialty'])
            ->get();
            
        // Formatar dados para o calendário
        $events = [];
        
        // Adicionar horários regulares como eventos recorrentes
        foreach ($schedules as $schedule) {
            // Mapear dia da semana para o formato do FullCalendar (0=domingo, 1=segunda, etc)
            $dayOfWeek = $schedule->day_of_week;
            
            $events[] = [
                'id' => 'schedule_' . $schedule->id,
                'title' => $schedule->specialty->name,
                'daysOfWeek' => [$dayOfWeek],
                'startTime' => $schedule->start_time,
                'endTime' => $schedule->end_time,
                'startRecur' => $startDate->format('Y-m-d'),
                'endRecur' => $endDate->format('Y-m-d'),
                'backgroundColor' => '#b3e6cc', // verde claro
                'borderColor' => '#66cc99',
                'textColor' => '#333333',
                'extendedProps' => [
                    'type' => 'schedule'
                ]
            ];
        }
        
        // Adicionar exceções
        foreach ($exceptions as $exception) {
            $schedule = $schedules->firstWhere('id', $exception->schedule_id);
            
            if (!$schedule) continue;
            
            if ($exception->is_available) {
                // Exceção que modifica o horário regular
                $events[] = [
                    'id' => 'exception_' . $exception->id,
                    'title' => 'Horário Modificado',
                    'start' => $exception->exception_date->format('Y-m-d') . ' ' . $exception->start_time->format('H:i:s'),
                    'end' => $exception->exception_date->format('Y-m-d') . ' ' . $exception->end_time->format('H:i:s'),
                    'backgroundColor' => '#ffeb99', // amarelo claro
                    'borderColor' => '#ffcc00',
                    'textColor' => '#333333',
                    'extendedProps' => [
                        'type' => 'modified_schedule',
                        'reason' => $exception->reason
                    ]
                ];
            } else {
                // Exceção que torna o horário indisponível
                $events[] = [
                    'id' => 'exception_' . $exception->id,
                    'title' => $exception->reason ?? 'Horário Indisponível',
                    'start' => $exception->exception_date->format('Y-m-d'),
                    'allDay' => true,
                    'backgroundColor' => '#ffcccc', // vermelho claro
                    'borderColor' => '#ff6666',
                    'textColor' => '#333333',
                    'extendedProps' => [
                        'type' => 'unavailable',
                        'reason' => $exception->reason
                    ]
                ];
            }
        }
        
        // Adicionar consultas
        foreach ($appointments as $appointment) {
            $backgroundColor = $this->getAppointmentColor($appointment->status);
            
            $events[] = [
                'id' => 'appointment_' . $appointment->id,
                'title' => $appointment->patient->user->name,
                'start' => $appointment->start_time->format('Y-m-d H:i:s'),
                'end' => $appointment->end_time->format('Y-m-d H:i:s'),
                'backgroundColor' => $backgroundColor,
                'borderColor' => $backgroundColor,
                'extendedProps' => [
                    'status' => $appointment->status,
                    'specialty' => $appointment->specialty->name,
                    'reason' => $appointment->reason,
                    'type' => 'appointment'
                ]
            ];
        }
        
        return response()->json($events);
    }
    
    /**
     * Obter consultas para um dia específico
     *
     * @param  int  $doctorId
     * @param  \Carbon\Carbon  $date
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function getDayAppointments($doctorId, $date)
    {
        return Appointment::where('doctor_id', $doctorId)
            ->whereBetween('start_time', [
                $date->copy()->startOfDay(),
                $date->copy()->endOfDay()
            ])
            ->with(['patient.user', 'specialty'])
            ->orderBy('start_time')
            ->get();
    }
    
    /**
     * Obter consultas para uma semana específica
     *
     * @param  int  $doctorId
     * @param  \Carbon\Carbon  $startOfWeek
     * @param  \Carbon\Carbon  $endOfWeek
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function getWeekAppointments($doctorId, $startOfWeek, $endOfWeek)
    {
        return Appointment::where('doctor_id', $doctorId)
            ->whereBetween('start_time', [
                $startOfWeek->copy()->startOfDay(),
                $endOfWeek->copy()->endOfDay()
            ])
            ->with(['patient.user', 'specialty'])
            ->orderBy('start_time')
            ->get();
    }
    
    /**
     * Obter consultas para um mês específico
     *
     * @param  int  $doctorId
     * @param  \Carbon\Carbon  $startOfMonth
     * @param  \Carbon\Carbon  $endOfMonth
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function getMonthAppointments($doctorId, $startOfMonth, $endOfMonth)
    {
        return Appointment::where('doctor_id', $doctorId)
            ->whereBetween('start_time', [
                $startOfMonth->copy()->startOfDay(),
                $endOfMonth->copy()->endOfDay()
            ])
            ->with(['patient.user', 'specialty'])
            ->orderBy('start_time')
            ->get();
    }
    
    /**
     * Obter exceções para um intervalo de datas
     *
     * @param  array  $scheduleIds
     * @param  \Carbon\Carbon  $startDate
     * @param  \Carbon\Carbon  $endDate
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function getExceptionsForDateRange($scheduleIds, $startDate, $endDate)
    {
        return ScheduleException::whereIn('schedule_id', $scheduleIds)
            ->whereBetween('exception_date', [$startDate, $endDate])
            ->get();
    }
    
    /**
     * Obter cor para o status da consulta
     *
     * @param  string  $status
     * @return string
     */
    private function getAppointmentColor($status)
    {
        $colors = [
            'scheduled' => '#3B82F6', // blue-500
            'confirmed' => '#10B981', // green-500
            'in_progress' => '#F59E0B', // amber-500
            'completed' => '#8B5CF6', // purple-500
            'cancelled' => '#EF4444', // red-500
            'no_show' => '#6B7280', // gray-500
        ];
        
        return $colors[$status] ?? '#6B7280';
    }
}