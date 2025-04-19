<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Specialty;
use App\Models\Schedule;
use App\Models\ScheduleException;
use App\Notifications\AppointmentCanceled;
use App\Notifications\AppointmentCreated;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class AppointmentController extends Controller
{
    /**
     * Display a listing of the patient's appointments.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $patient = Auth::user()->patient;
        $status = $request->query('status', 'all');
        
        $query = Appointment::where('patient_id', $patient->id)
            ->with(['doctor.user', 'specialty']);
        
        // Filtrar por status se necessário
        if ($status !== 'all') {
            $query->where('status', $status);
        }
        
        $appointments = $query->orderBy('start_time', 'desc')
            ->paginate(10)
            ->withQueryString();
        
        return view('patient.appointments.index', [
            'appointments' => $appointments,
            'status' => $status
        ]);
    }
    
    /**
     * Show the form to search for doctors and specialties.
     *
     * @return \Illuminate\View\View
     */
    public function search()
    {
        $specialties = Specialty::orderBy('name')->get();
        
        return view('patient.appointments.search', [
            'specialties' => $specialties
        ]);
    }
    
    /**
     * Display a list of doctors.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function doctors(Request $request)
    {
        $specialtyId = $request->query('specialty');
        $name = $request->query('name');
        
        $query = Doctor::with(['user', 'specialties'])
            ->withAvg('feedbacks', 'rating');
        
        if ($specialtyId) {
            $query->whereHas('specialties', function($q) use ($specialtyId) {
                $q->where('specialty_id', $specialtyId);
            });
        }
        
        if ($name) {
            $query->whereHas('user', function($q) use ($name) {
                $q->where('name', 'like', "%{$name}%");
            });
        }
        
        $doctors = $query->paginate(9)->withQueryString();
        $specialties = Specialty::orderBy('name')->get();
        
        return view('patient.appointments.doctors', [
            'doctors' => $doctors,
            'specialties' => $specialties,
            'selectedSpecialty' => $specialtyId,
            'searchName' => $name
        ]);
    }
    
    /**
     * Display a doctor's profile.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function doctorProfile($id)
    {
        $doctor = Doctor::with(['user', 'specialties', 'feedbacks.patient.user'])
            ->withAvg('feedbacks', 'rating')
            ->withCount('feedbacks')
            ->findOrFail($id);
        
        return view('patient.appointments.doctor-profile', [
            'doctor' => $doctor
        ]);
    }
    
    /**
     * Show the calendar with available slots.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $doctorId
     * @return \Illuminate\View\View
     */
    public function calendar(Request $request, $doctorId)
    {
        $specialtyId = $request->query('specialty_id');
        
        $doctor = Doctor::with(['user', 'specialties', 'schedules'])
            ->findOrFail($doctorId);
        
        if (!$specialtyId || !$doctor->specialties->contains('id', $specialtyId)) {
            // Se a especialidade não for fornecida ou o médico não tiver a especialidade
            return redirect()->route('patient.appointments.doctor-profile', $doctorId)
                ->with('error', 'Por favor, selecione uma especialidade válida.');
        }
        
        $specialty = Specialty::findOrFail($specialtyId);
        
        // Gerar slots disponíveis para as próximas duas semanas
        $availableSlots = $this->getAvailableSlots($doctor, 14);
        
        return view('patient.appointments.calendar', [
            'doctor' => $doctor,
            'specialty' => $specialty,
            'availableSlots' => $availableSlots
        ]);
    }
    
    /**
     * Show the form to create a new appointment.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $doctorId
     * @return \Illuminate\View\View
     */
    public function create(Request $request, $doctorId)
    {
        $doctor = Doctor::with('user')->findOrFail($doctorId);
        $specialtyId = $request->query('specialty_id');
        $specialty = Specialty::findOrFail($specialtyId);
        $startTime = Carbon::parse($request->query('start_time'));
        $endTime = (clone $startTime)->addMinutes($doctor->consultation_duration);
        
        // Verificar se o horário ainda está disponível
        $isAvailable = $this->checkSlotAvailability($doctor, $startTime, $endTime);
        
        if (!$isAvailable) {
            return redirect()->route('patient.appointments.calendar', [
                'doctorId' => $doctorId,
                'specialty_id' => $specialtyId
            ])->with('error', 'Este horário não está mais disponível. Por favor, escolha outro horário.');
        }
        
        return view('patient.appointments.create', [
            'doctor' => $doctor,
            'specialty' => $specialty,
            'startTime' => $startTime,
            'endTime' => $endTime
        ]);
    }
    
    /**
     * Store a newly created appointment.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $doctorId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request, $doctorId)
    {
        $request->validate([
            'specialty_id' => 'required|exists:specialties,id',
            'reason' => 'required|string|max:500',
            'start_time' => 'required|date_format:Y-m-d H:i:s',
        ]);
        
        $patient = Auth::user()->patient;
        $doctor = Doctor::findOrFail($doctorId);
        $startTime = Carbon::parse($request->start_time);
        $endTime = (clone $startTime)->addMinutes($doctor->consultation_duration);
        
        // Verificar novamente se o horário ainda está disponível
        $isAvailable = $this->checkSlotAvailability($doctor, $startTime, $endTime);
        
        if (!$isAvailable) {
            return back()->with('error', 'Este horário não está mais disponível. Por favor, escolha outro horário.');
        }
        
        DB::beginTransaction();
        
        try {
            $appointment = new Appointment();
            $appointment->patient_id = $patient->id;
            $appointment->doctor_id = $doctor->id;
            $appointment->specialty_id = $request->specialty_id;
            $appointment->start_time = $startTime;
            $appointment->end_time = $endTime;
            $appointment->reason = $request->reason;
            $appointment->status = 'scheduled';
            $appointment->save();
            
            // Notificar o médico sobre o novo agendamento
            $doctor->user->notify(new AppointmentCreated($appointment));
            
            DB::commit();
            
            return redirect()->route('patient.appointments.confirmation', $appointment->id)
                ->with('success', 'Consulta agendada com sucesso!');
                
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Ocorreu um erro ao agendar a consulta. Por favor, tente novamente.');
        }
    }
    
    /**
     * Display the appointment confirmation.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function confirmation($id)
    {
        $appointment = Appointment::with(['doctor.user', 'specialty', 'patient.user'])
            ->where('patient_id', Auth::user()->patient->id)
            ->findOrFail($id);
        
        return view('patient.appointments.confirmation', [
            'appointment' => $appointment
        ]);
    }
    
    /**
     * Display the specified appointment.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function show($id)
    {
        $appointment = Appointment::with(['doctor.user', 'specialty', 'patient.user', 'feedback'])
            ->where('patient_id', Auth::user()->patient->id)
            ->findOrFail($id);
        
        return view('patient.appointments.show', [
            'appointment' => $appointment
        ]);
    }
    
    /**
     * Show the form to reschedule an appointment.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function reschedule($id)
    {
        $appointment = Appointment::with(['doctor.user', 'specialty'])
            ->where('patient_id', Auth::user()->patient->id)
            ->where('status', 'scheduled')
            ->where('start_time', '>', now()->addHours(24))
            ->findOrFail($id);
        
        // Gerar slots disponíveis para as próximas duas semanas
        $availableSlots = $this->getAvailableSlots($appointment->doctor, 14);
        
        return view('patient.appointments.reschedule', [
            'appointment' => $appointment,
            'availableSlots' => $availableSlots
        ]);
    }
    
    /**
     * Update the rescheduled appointment.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateReschedule(Request $request, $id)
    {
        $request->validate([
            'start_time' => 'required|date_format:Y-m-d H:i:s',
        ]);
        
        $appointment = Appointment::with('doctor')
            ->where('patient_id', Auth::user()->patient->id)
            ->where('status', 'scheduled')
            ->where('start_time', '>', now()->addHours(24))
            ->findOrFail($id);
        
        $startTime = Carbon::parse($request->start_time);
        $endTime = (clone $startTime)->addMinutes($appointment->doctor->consultation_duration);
        
        // Verificar se o novo horário está disponível
        $isAvailable = $this->checkSlotAvailability($appointment->doctor, $startTime, $endTime, $appointment->id);
        
        if (!$isAvailable) {
            return back()->with('error', 'Este horário não está mais disponível. Por favor, escolha outro horário.');
        }
        
        DB::beginTransaction();
        
        try {
            $oldStartTime = $appointment->start_time;
            
            $appointment->start_time = $startTime;
            $appointment->end_time = $endTime;
            $appointment->save();
            
            // Notificar o médico sobre o reagendamento
            // Implementar notificação de reagendamento
            
            DB::commit();
            
            return redirect()->route('patient.appointments.show', $appointment->id)
                ->with('success', 'Consulta reagendada com sucesso!');
                
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Ocorreu um erro ao reagendar a consulta. Por favor, tente novamente.');
        }
    }
    
    /**
     * Show the form to cancel an appointment.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function cancel($id)
    {
        $appointment = Appointment::with(['doctor.user', 'specialty'])
            ->where('patient_id', Auth::user()->patient->id)
            ->where('status', 'scheduled')
            ->findOrFail($id);
        
        return view('patient.appointments.cancel', [
            'appointment' => $appointment
        ]);
    }
    
    /**
     * Update the appointment to canceled status.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateCancel(Request $request, $id)
    {
        $request->validate([
            'cancellation_reason' => 'required|string|max:500',
        ]);
        
        $appointment = Appointment::with('doctor.user')
            ->where('patient_id', Auth::user()->patient->id)
            ->where('status', 'scheduled')
            ->findOrFail($id);
        
        DB::beginTransaction();
        
        try {
            $appointment->status = 'canceled';
            $appointment->cancellation_reason = $request->cancellation_reason;
            $appointment->canceled_by = Auth::id();
            $appointment->save();
            
            // Notificar o médico sobre o cancelamento
            $appointment->doctor->user->notify(new AppointmentCanceled($appointment));
            
            DB::commit();
            
            return redirect()->route('patient.appointments.index')
                ->with('success', 'Consulta cancelada com sucesso!');
                
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Ocorreu um erro ao cancelar a consulta. Por favor, tente novamente.');
        }
    }
    
    /**
     * Get available time slots for a doctor.
     *
     * @param  \App\Models\Doctor  $doctor
     * @param  int  $daysAhead
     * @return array
     */
    private function getAvailableSlots(Doctor $doctor, $daysAhead = 14)
    {
        $slots = [];
        $startDate = Carbon::today();
        $endDate = Carbon::today()->addDays($daysAhead);
        $slotDuration = $doctor->consultation_duration;
        
        // Obter horários regulares do médico
        $schedules = $doctor->schedules;
        
        // Obter exceções de agenda
        $exceptions = ScheduleException::where('schedule_id', $schedules->pluck('id'))
            ->where('exception_date', '>=', $startDate)
            ->where('exception_date', '<=', $endDate)
            ->get();
        
        // Obter consultas já agendadas
        $appointments = Appointment::where('doctor_id', $doctor->id)
            ->where('status', 'scheduled')
            ->where('start_time', '>=', $startDate)
            ->where('start_time', '<=', $endDate)
            ->get();
        
        // Para cada dia no período
        for ($date = $startDate; $date <= $endDate; $date = $date->copy()->addDay()) {
            $dayOfWeek = $date->dayOfWeek;
            
            // Verificar se o médico trabalha neste dia da semana
            $daySchedules = $schedules->filter(function ($schedule) use ($dayOfWeek) {
                return $schedule->day_of_week == $dayOfWeek;
            });
            
            if ($daySchedules->isEmpty()) {
                continue;
            }
            
            // Verificar se existe alguma exceção para este dia
            $dayException = $exceptions->first(function ($exception) use ($date) {
                return $exception->exception_date->isSameDay($date);
            });
            
            if ($dayException && !$dayException->is_available) {
                continue; // Médico não está disponível neste dia
            }
            
            // Para cada horário de trabalho do médico neste dia
            foreach ($daySchedules as $schedule) {
                $startTime = Carbon::parse($date->format('Y-m-d') . ' ' . $schedule->start_time);
                $endTime = Carbon::parse($date->format('Y-m-d') . ' ' . $schedule->end_time);
                
                // Se o dia já passou ou é hoje mas o horário já passou, pular
                if ($date->isToday() && now()->gt($endTime)) {
                    continue;
                }
                
                // Dividir o horário em slots baseados na duração da consulta
                for ($time = $startTime; $time->lt($endTime->subMinutes($slotDuration)); $time = $time->copy()->addMinutes($slotDuration)) {
                    $slotEndTime = $time->copy()->addMinutes($slotDuration);
                    
                    // Verificar se este slot já está ocupado
                    $isOccupied = $appointments->contains(function ($appointment) use ($time, $slotEndTime) {
                        return ($appointment->start_time < $slotEndTime && $appointment->end_time > $time);
                    });
                    
                    if (!$isOccupied) {
                        // Formato para o front-end: data + horários formatados
                        $slots[$date->format('Y-m-d')][] = [
                            'start' => $time->format('H:i'),
                            'end' => $slotEndTime->format('H:i'),
                            'datetime' => $time->format('Y-m-d H:i:s')
                        ];
                    }
                }
            }
        }
        
        return $slots;
    }
    
    /**
     * Check if a specific time slot is available.
     *
     * @param  \App\Models\Doctor  $doctor
     * @param  \Carbon\Carbon  $startTime
     * @param  \Carbon\Carbon  $endTime
     * @param  int|null  $excludeAppointmentId
     * @return bool
     */
    private function checkSlotAvailability(Doctor $doctor, Carbon $startTime, Carbon $endTime, $excludeAppointmentId = null)
    {
        // Verificar se o horário está dentro da agenda regular do médico
        $dayOfWeek = $startTime->dayOfWeek;
        $timeStart = $startTime->format('H:i:s');
        $timeEnd = $endTime->format('H:i:s');
        
        $schedule = $doctor->schedules()
            ->where('day_of_week', $dayOfWeek)
            ->where('start_time', '<=', $timeStart)
            ->where('end_time', '>=', $timeEnd)
            ->first();
        
        if (!$schedule) {
            return false; // Horário fora da agenda regular
        }
        
        // Verificar se não há exceções para este dia
        $hasException = ScheduleException::where('schedule_id', $schedule->id)
            ->where('exception_date', $startTime->format('Y-m-d'))
            ->where('is_available', false)
            ->exists();
        
        if ($hasException) {
            return false; // Médico não disponível neste dia
        }
        
        // Verificar se não há agendamentos conflitantes
        $query = Appointment::where('doctor_id', $doctor->id)
            ->where('status', 'scheduled')
            ->where(function ($q) use ($startTime, $endTime) {
                $q->where(function ($q2) use ($startTime, $endTime) {
                    $q2->where('start_time', '<', $endTime)
                        ->where('end_time', '>', $startTime);
                });
            });
        
        if ($excludeAppointmentId) {
            $query->where('id', '!=', $excludeAppointmentId);
        }
        
        $hasConflict = $query->exists();
        
        return !$hasConflict; // Retorna true se não houver conflitos
    }
}