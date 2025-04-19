<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\DoctorSchedule;
use App\Models\ScheduleException;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ScheduleController extends Controller
{
    /**
     * Display the doctor's schedule.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $doctor = Auth::user()->doctor;
        $selectedDate = $request->query('date') ? Carbon::parse($request->query('date')) : Carbon::today();
        $startOfWeek = (clone $selectedDate)->startOfWeek();
        
        // Obter horários regulares
        $schedules = DoctorSchedule::where('doctor_id', $doctor->id)
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();
        
        // Obter exceções (férias, folgas, etc)
        $exceptions = ScheduleException::where('doctor_id', $doctor->id)
            ->where('end_date', '>=', Carbon::today())
            ->orderBy('start_date')
            ->get();
        
        // Obter consultas para a semana selecionada
        $weekStart = (clone $selectedDate)->startOfWeek();
        $weekEnd = (clone $selectedDate)->endOfWeek();
        
        $appointments = Appointment::where('doctor_id', $doctor->id)
            ->whereBetween('start_time', [$weekStart, $weekEnd])
            ->with('patient.user')
            ->orderBy('start_time')
            ->get();
        
        // Preparar dados para visualização do calendário
        $calendarData = [];
        
        // Para cada dia da semana
        for ($day = 0; $day < 7; $day++) {
            $currentDate = (clone $weekStart)->addDays($day);
            $dayOfWeek = $currentDate->dayOfWeek;
            
            // Verificar se é uma exceção
            $isException = false;
            $exceptionReason = null;
            
            foreach ($exceptions as $exception) {
                if ($currentDate->between(
                    Carbon::parse($exception->start_date),
                    Carbon::parse($exception->end_date)
                )) {
                    $isException = true;
                    $exceptionReason = $exception->reason;
                    break;
                }
            }
            
            // Obter horários para este dia da semana
            $daySchedules = $schedules->where('day_of_week', $dayOfWeek);
            
            // Obter consultas para este dia
            $dayAppointments = $appointments->filter(function($appointment) use ($currentDate) {
                return Carbon::parse($appointment->start_time)->isSameDay($currentDate);
            });
            
            $calendarData[$day] = [
                'date' => $currentDate->format('Y-m-d'),
                'dayName' => $currentDate->format('l'),
                'isToday' => $currentDate->isToday(),
                'isException' => $isException,
                'exceptionReason' => $exceptionReason,
                'schedules' => $daySchedules,
                'appointments' => $dayAppointments
            ];
        }
        
        return view('doctor.schedule.index', [
            'doctor' => $doctor,
            'calendarData' => $calendarData,
            'selectedDate' => $selectedDate,
            'exceptions' => $exceptions,
            'schedules' => $schedules->groupBy('day_of_week')
        ]);
    }
    
    /**
     * Show the form for editing the doctor's schedule.
     *
     * @return \Illuminate\View\View
     */
    public function edit()
    {
        $doctor = Auth::user()->doctor;
        
        // Obter horários atuais
        $schedules = DoctorSchedule::where('doctor_id', $doctor->id)
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get()
            ->groupBy('day_of_week');
        
        $daysOfWeek = [
            0 => 'Domingo',
            1 => 'Segunda-feira',
            2 => 'Terça-feira',
            3 => 'Quarta-feira',
            4 => 'Quinta-feira',
            5 => 'Sexta-feira',
            6 => 'Sábado'
        ];
        
        return view('doctor.schedule.edit', [
            'doctor' => $doctor,
            'schedules' => $schedules,
            'daysOfWeek' => $daysOfWeek
        ]);
    }
    
    /**
     * Update the doctor's regular schedule.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request)
    {
        $request->validate([
            'schedules' => 'required|array',
            'schedules.*.day_of_week' => 'required|integer|min:0|max:6',
            'schedules.*.start_time' => 'required|date_format:H:i',
            'schedules.*.end_time' => 'required|date_format:H:i|after:schedules.*.start_time'
        ]);
        
        $doctor = Auth::user()->doctor;
        
        try {
            // Excluir horários existentes
            DoctorSchedule::where('doctor_id', $doctor->id)->delete();
            
            // Criar novos horários
            foreach ($request->schedules as $schedule) {
                DoctorSchedule::create([
                    'doctor_id' => $doctor->id,
                    'day_of_week' => $schedule['day_of_week'],
                    'start_time' => $schedule['start_time'],
                    'end_time' => $schedule['end_time']
                ]);
            }
            
            return redirect()->route('doctor.schedule.index')
                ->with('success', 'Horários atualizados com sucesso!');
                
        } catch (\Exception $e) {
            return back()->with('error', 'Ocorreu um erro ao atualizar os horários: ' . $e->getMessage())
                ->withInput();
        }
    }
    
    /**
     * Show the form for creating a schedule exception.
     *
     * @return \Illuminate\View\View
     */
    public function createException()
    {
        $doctor = Auth::user()->doctor;
        $exceptionTypes = [
            'vacation' => 'Férias',
            'day_off' => 'Folga',
            'conference' => 'Conferência/Evento',
            'personal' => 'Compromisso pessoal',
            'other' => 'Outro'
        ];
        
        return view('doctor.schedule.create_exception', [
            'doctor' => $doctor,
            'exceptionTypes' => $exceptionTypes
        ]);
    }
    
    /**
     * Store a new schedule exception.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeException(Request $request)
    {
        $request->validate([
            'type' => 'required|string',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|max:255'
        ]);
        
        $doctor = Auth::user()->doctor;
        
        try {
            // Verificar se há consultas agendadas no período
            $hasAppointments = Appointment::where('doctor_id', $doctor->id)
                ->whereIn('status', ['scheduled', 'confirmed'])
                ->where('start_time', '>=', $request->start_date)
                ->where('start_time', '<=', Carbon::parse($request->end_date)->endOfDay())
                ->exists();
            
            if ($hasAppointments) {
                return back()->with('warning', 'Existem consultas agendadas no período selecionado. Por favor, verifique e reagende as consultas antes de criar a exceção.')
                    ->withInput();
            }
            
            // Criar a exceção
            ScheduleException::create([
                'doctor_id' => $doctor->id,
                'type' => $request->type,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'reason' => $request->reason
            ]);
            
            return redirect()->route('doctor.schedule.exceptions')
                ->with('success', 'Exceção adicionada com sucesso!');
                
        } catch (\Exception $e) {
            return back()->with('error', 'Ocorreu um erro ao criar a exceção: ' . $e->getMessage())
                ->withInput();
        }
    }
    
    /**
     * Display the list of schedule exceptions.
     *
     * @return \Illuminate\View\View
     */
    public function exceptions()
    {
        $doctor = Auth::user()->doctor;
        
        $activeExceptions = ScheduleException::where('doctor_id', $doctor->id)
            ->where('end_date', '>=', Carbon::today())
            ->orderBy('start_date')
            ->get();
            
        $pastExceptions = ScheduleException::where('doctor_id', $doctor->id)
            ->where('end_date', '<', Carbon::today())
            ->orderBy('start_date', 'desc')
            ->paginate(10);
            
        $exceptionTypes = [
            'vacation' => 'Férias',
            'day_off' => 'Folga',
            'conference' => 'Conferência/Evento',
            'personal' => 'Compromisso pessoal',
            'other' => 'Outro'
        ];
        
        return view('doctor.schedule.exceptions', [
            'doctor' => $doctor,
            'activeExceptions' => $activeExceptions,
            'pastExceptions' => $pastExceptions,
            'exceptionTypes' => $exceptionTypes
        ]);
    }
    
    /**
     * Delete a schedule exception.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroyException($id)
    {
        $doctor = Auth::user()->doctor;
        
        $exception = ScheduleException::where('doctor_id', $doctor->id)
            ->findOrFail($id);
            
        try {
            $exception->delete();
            
            return back()->with('success', 'Exceção removida com sucesso!');
            
        } catch (\Exception $e) {
            return back()->with('error', 'Ocorreu um erro ao remover a exceção: ' . $e->getMessage());
        }
    }
}