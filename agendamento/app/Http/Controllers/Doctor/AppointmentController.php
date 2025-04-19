<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\MedicalRecord;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AppointmentController extends Controller
{
    /**
     * Display a listing of appointments.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $doctor = Auth::user()->doctor;
        
        $status = $request->query('status') ? explode(',', $request->query('status')) : ['scheduled', 'confirmed', 'completed', 'cancelled', 'no_show'];
        $startDate = $request->query('start_date') ? Carbon::parse($request->query('start_date')) : Carbon::today();
        $endDate = $request->query('end_date') ? Carbon::parse($request->query('end_date'))->endOfDay() : Carbon::today()->addMonths(3)->endOfDay();
        $search = $request->query('search');
        
        $query = Appointment::with(['patient.user', 'specialty'])
            ->where('doctor_id', $doctor->id)
            ->whereIn('status', $status)
            ->whereBetween('start_time', [$startDate, $endDate]);
        
        if ($search) {
            $query->whereHas('patient.user', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }
        
        $appointments = $query->orderBy('start_time')
            ->paginate(15)
            ->withQueryString();
        
        $statusCounts = [
            'scheduled' => Appointment::where('doctor_id', $doctor->id)->where('status', 'scheduled')->count(),
            'confirmed' => Appointment::where('doctor_id', $doctor->id)->where('status', 'confirmed')->count(),
            'completed' => Appointment::where('doctor_id', $doctor->id)->where('status', 'completed')->count(),
            'cancelled' => Appointment::where('doctor_id', $doctor->id)->where('status', 'cancelled')->count(),
            'no_show' => Appointment::where('doctor_id', $doctor->id)->where('status', 'no_show')->count(),
        ];
        
        return view('doctor.appointments.index', [
            'appointments' => $appointments,
            'statusCounts' => $statusCounts,
            'filters' => [
                'status' => $status,
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'search' => $search,
            ]
        ]);
    }
    
    /**
     * Display appointments for today.
     *
     * @return \Illuminate\View\View
     */
    public function today()
    {
        $doctor = Auth::user()->doctor;
        $today = Carbon::today();
        
        $appointments = Appointment::where('doctor_id', $doctor->id)
            ->whereDate('start_time', $today)
            ->with(['patient.user', 'specialty'])
            ->orderBy('start_time')
            ->get();
        
        $waitingAppointments = $appointments->where('status', 'confirmed');
        $inProgressAppointments = $appointments->where('status', 'in_progress');
        $completedAppointments = $appointments->where('status', 'completed');
        $cancelledAppointments = $appointments->where('status', 'cancelled');
        $noShowAppointments = $appointments->where('status', 'no_show');
        
        // Salas disponíveis para atendimento
        $rooms = Room::where('is_active', true)
            ->orderBy('name')
            ->get();
        
        return view('doctor.appointments.today', [
            'appointments' => $appointments,
            'waitingAppointments' => $waitingAppointments,
            'inProgressAppointments' => $inProgressAppointments,
            'completedAppointments' => $completedAppointments,
            'cancelledAppointments' => $cancelledAppointments,
            'noShowAppointments' => $noShowAppointments,
            'rooms' => $rooms
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
        $doctor = Auth::user()->doctor;
        
        $appointment = Appointment::where('doctor_id', $doctor->id)
            ->with(['patient.user', 'specialty', 'room', 'medicalRecords'])
            ->findOrFail($id);
        
        // Verificar se existe registro médico para esta consulta
        $hasMedicalRecord = $appointment->medicalRecords->isNotEmpty();
        
        // Buscar consultas anteriores do paciente com este médico
        $previousAppointments = Appointment::where('doctor_id', $doctor->id)
            ->where('patient_id', $appointment->patient_id)
            ->where('id', '!=', $id)
            ->where('status', 'completed')
            ->with('medicalRecords')
            ->orderBy('start_time', 'desc')
            ->get();
        
        // Salas disponíveis para atendimento
        $rooms = Room::where('is_active', true)
            ->orderBy('name')
            ->get();
        
        return view('doctor.appointments.show', [
            'appointment' => $appointment,
            'hasMedicalRecord' => $hasMedicalRecord,
            'previousAppointments' => $previousAppointments,
            'rooms' => $rooms
        ]);
    }
    
    /**
     * Update appointment status.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:confirmed,in_progress,completed,cancelled,no_show',
            'room_id' => 'nullable|exists:rooms,id',
            'cancellation_reason' => 'required_if:status,cancelled',
        ]);
        
        $doctor = Auth::user()->doctor;
        
        $appointment = Appointment::where('doctor_id', $doctor->id)
            ->findOrFail($id);
            
        $oldStatus = $appointment->status;
        
        try {
            $appointment->status = $request->status;
            
            if ($request->status == 'in_progress' && $request->room_id) {
                $appointment->room_id = $request->room_id;
                $appointment->started_at = Carbon::now();
            }
            
            if ($request->status == 'completed') {
                $appointment->completed_at = Carbon::now();
            }
            
            if ($request->status == 'cancelled') {
                $appointment->cancellation_reason = $request->cancellation_reason;
                $appointment->cancelled_at = Carbon::now();
                $appointment->cancelled_by = 'doctor';
            }
            
            if ($request->status == 'no_show') {
                $appointment->no_show_at = Carbon::now();
            }
            
            $appointment->save();
            
            // Notificar o paciente
            $notification = new \App\Models\Notification();
            $notification->user_id = $appointment->patient->user_id;
            
            switch ($request->status) {
                case 'confirmed':
                    $notification->title = 'Consulta Confirmada';
                    $notification->message = 'Sua consulta com Dr. ' . $doctor->user->name . ' foi confirmada para ' . Carbon::parse($appointment->start_time)->format('d/m/Y H:i') . '.';
                    break;
                case 'in_progress':
                    $notification->title = 'Consulta Iniciada';
                    $notification->message = 'Sua consulta com Dr. ' . $doctor->user->name . ' foi iniciada. ' . ($request->room_id ? 'Por favor, dirija-se à sala ' . Room::find($request->room_id)->name . '.' : '');
                    break;
                case 'completed':
                    $notification->title = 'Consulta Finalizada';
                    $notification->message = 'Sua consulta com Dr. ' . $doctor->user->name . ' foi finalizada. Obrigado por sua visita!';
                    break;
                case 'cancelled':
                    $notification->title = 'Consulta Cancelada';
                    $notification->message = 'Infelizmente sua consulta com Dr. ' . $doctor->user->name . ' foi cancelada. Motivo: ' . $request->cancellation_reason;
                    break;
                case 'no_show':
                    $notification->title = 'Ausência em Consulta';
                    $notification->message = 'Registramos sua ausência na consulta com Dr. ' . $doctor->user->name . ' agendada para ' . Carbon::parse($appointment->start_time)->format('d/m/Y H:i') . '.';
                    break;
            }
            
            $notification->type = 'appointment_' . $request->status;
            $notification->data = json_encode(['appointment_id' => $appointment->id]);
            $notification->save();
            
            $message = 'Status da consulta atualizado para ' . $this->getStatusName($request->status) . ' com sucesso!';
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'appointment' => $appointment
                ]);
            }
            
            return back()->with('success', $message);
            
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro ao atualizar status: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->with('error', 'Ocorreu um erro ao atualizar o status: ' . $e->getMessage())
                ->withInput();
        }
    }
    
    /**
     * Display the waiting room.
     *
     * @return \Illuminate\View\View
     */
    public function waitingRoom()
    {
        $doctor = Auth::user()->doctor;
        $today = Carbon::today();
        
        $confirmedAppointments = Appointment::where('doctor_id', $doctor->id)
            ->whereDate('start_time', $today)
            ->where('status', 'confirmed')
            ->with(['patient.user', 'specialty'])
            ->orderBy('start_time')
            ->get();
            
        $inProgressAppointments = Appointment::where('doctor_id', $doctor->id)
            ->whereDate('start_time', $today)
            ->where('status', 'in_progress')
            ->with(['patient.user', 'specialty', 'room'])
            ->orderBy('start_time')
            ->get();
            
        $upcomingAppointments = Appointment::where('doctor_id', $doctor->id)
            ->whereDate('start_time', $today)
            ->where('status', 'scheduled')
            ->where('start_time', '>', Carbon::now())
            ->with(['patient.user', 'specialty'])
            ->orderBy('start_time')
            ->get();
            
        $completedAppointments = Appointment::where('doctor_id', $doctor->id)
            ->whereDate('start_time', $today)
            ->where('status', 'completed')
            ->with(['patient.user', 'specialty'])
            ->orderBy('start_time')
            ->get();
            
        // Salas disponíveis para atendimento
        $rooms = Room::where('is_active', true)
            ->orderBy('name')
            ->get();
            
        return view('doctor.appointments.waiting_room', [
            'confirmedAppointments' => $confirmedAppointments,
            'inProgressAppointments' => $inProgressAppointments,
            'upcomingAppointments' => $upcomingAppointments,
            'completedAppointments' => $completedAppointments,
            'rooms' => $rooms
        ]);
    }
    
    /**
     * Show the form for creating a new appointment.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function create(Request $request)
    {
        $doctor = Auth::user()->doctor;
        $patientId = $request->query('patient_id');
        
        if ($patientId) {
            $patient = Patient::with('user')->findOrFail($patientId);
        }
        
        $rooms = Room::where('is_active', true)->orderBy('name')->get();
        $specialties = $doctor->specialties;
        
        return view('doctor.appointments.create', [
            'doctor' => $doctor,
            'patient' => $patient ?? null,
            'rooms' => $rooms,
            'specialties' => $specialties
        ]);
    }
    
    /**
     * Store a newly created appointment.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'specialty_id' => 'required|exists:specialties,id',
            'start_time' => 'required|date|after_or_equal:today',
            'duration' => 'required|integer|min:5|max:240',
            'reason' => 'required|string|max:500',
            'room_id' => 'nullable|exists:rooms,id',
        ]);
        
        $doctor = Auth::user()->doctor;
        
        // Verificar se o médico tem a especialidade selecionada
        $hasSpecialty = $doctor->specialties()->where('specialty_id', $request->specialty_id)->exists();
        
        if (!$hasSpecialty) {
            return back()->with('error', 'Você não tem a especialidade selecionada registrada em seu perfil.')
                ->withInput();
        }
        
        // Calcular horário de término
        $startTime = Carbon::parse($request->start_time);
        $endTime = (clone $startTime)->addMinutes($request->duration);
        
        // Verificar se horário está disponível
        $isAvailable = $this->checkSlotAvailability($doctor->id, $startTime, $endTime, null);
        
        if (!$isAvailable) {
            return back()->with('error', 'Este horário não está disponível. Por favor, escolha outro horário.')
                ->withInput();
        }
        
        try {
            $appointment = new Appointment();
            $appointment->doctor_id = $doctor->id;
            $appointment->patient_id = $request->patient_id;
            $appointment->specialty_id = $request->specialty_id;
            $appointment->start_time = $startTime;
            $appointment->end_time = $endTime;
            $appointment->reason = $request->reason;
            $appointment->status = 'scheduled';
            $appointment->room_id = $request->room_id;
            $appointment->notes = $request->notes;
            $appointment->created_by = 'doctor';
            $appointment->save();
            
            // Notificar o paciente
            $notification = new \App\Models\Notification();
            $notification->user_id = Patient::find($request->patient_id)->user_id;
            $notification->title = 'Nova Consulta Agendada';
            $notification->message = 'Uma nova consulta foi agendada com Dr. ' . $doctor->user->name . ' para ' . $startTime->format('d/m/Y H:i') . '.';
            $notification->type = 'appointment_created';
            $notification->data = json_encode(['appointment_id' => $appointment->id]);
            $notification->save();
            
            return redirect()->route('doctor.appointments.show', $appointment->id)
                ->with('success', 'Consulta agendada com sucesso!');
                
        } catch (\Exception $e) {
            return back()->with('error', 'Ocorreu um erro ao agendar a consulta: ' . $e->getMessage())
                ->withInput();
        }
    }
    
    /**
     * Show the form for rescheduling an appointment.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function reschedule($id)
    {
        $doctor = Auth::user()->doctor;
        
        $appointment = Appointment::where('doctor_id', $doctor->id)
            ->with(['patient.user', 'specialty'])
            ->findOrFail($id);
            
        if (!in_array($appointment->status, ['scheduled', 'confirmed'])) {
            return back()->with('error', 'Apenas consultas agendadas ou confirmadas podem ser reagendadas.');
        }
        
        $rooms = Room::where('is_active', true)->orderBy('name')->get();
        
        return view('doctor.appointments.reschedule', [
            'appointment' => $appointment,
            'rooms' => $rooms
        ]);
    }
    
    /**
     * Update the appointment date and time.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateSchedule(Request $request, $id)
    {
        $request->validate([
            'start_time' => 'required|date|after_or_equal:today',
            'duration' => 'required|integer|min:5|max:240',
            'room_id' => 'nullable|exists:rooms,id',
            'reason' => 'required|string|max:500'
        ]);
        
        $doctor = Auth::user()->doctor;
        
        $appointment = Appointment::where('doctor_id', $doctor->id)
            ->findOrFail($id);
            
        if (!in_array($appointment->status, ['scheduled', 'confirmed'])) {
            return back()->with('error', 'Apenas consultas agendadas ou confirmadas podem ser reagendadas.');
        }
        
        // Calcular horário de término
        $startTime = Carbon::parse($request->start_time);
        $endTime = (clone $startTime)->addMinutes($request->duration);
        
        // Verificar se horário está disponível
        $isAvailable = $this->checkSlotAvailability($doctor->id, $startTime, $endTime, $appointment->id);
        
        if (!$isAvailable) {
            return back()->with('error', 'Este horário não está disponível. Por favor, escolha outro horário.')
                ->withInput();
        }
        
        try {
            $oldStartTime = $appointment->start_time;
            
            $appointment->start_time = $startTime;
            $appointment->end_time = $endTime;
            $appointment->reason = $request->reason;
            $appointment->room_id = $request->room_id;
            $appointment->notes = $request->notes;
            $appointment->rescheduled_at = Carbon::now();
            $appointment->rescheduled_by = 'doctor';
            $appointment->rescheduled_reason = $request->reschedule_reason;
            $appointment->save();
            
            // Notificar o paciente
            $notification = new \App\Models\Notification();
            $notification->user_id = $appointment->patient->user_id;
            $notification->title = 'Consulta Reagendada';
            $notification->message = 'Sua consulta com Dr. ' . $doctor->user->name . ' foi reagendada de ' . Carbon::parse($oldStartTime)->format('d/m/Y H:i') . ' para ' . $startTime->format('d/m/Y H:i') . '.';
            $notification->type = 'appointment_rescheduled';
            $notification->data = json_encode(['appointment_id' => $appointment->id]);
            $notification->save();
            
            return redirect()->route('doctor.appointments.show', $appointment->id)
                ->with('success', 'Consulta reagendada com sucesso!');
                
        } catch (\Exception $e) {
            return back()->with('error', 'Ocorreu um erro ao reagendar a consulta: ' . $e->getMessage())
                ->withInput();
        }
    }
    
    /**
     * Show the appointment summary form.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function summary($id)
    {
        $doctor = Auth::user()->doctor;
        
        $appointment = Appointment::where('doctor_id', $doctor->id)
            ->with(['patient.user', 'specialty', 'medicalRecords'])
            ->findOrFail($id);
            
        if ($appointment->status != 'in_progress' && $appointment->status != 'completed') {
            return back()->with('error', 'Apenas consultas em andamento ou já finalizadas podem ter resumo.');
        }
        
        // Buscar ou criar registro médico para esta consulta
        $medicalRecord = $appointment->medicalRecords->first();
        
        if (!$medicalRecord) {
            // Buscar o último registro médico do paciente
            $lastRecord = MedicalRecord::where('patient_id', $appointment->patient_id)
                ->where('doctor_id', $doctor->id)
                ->orderBy('created_at', 'desc')
                ->first();
                
            // Dados iniciais para o novo registro
            $initialData = [
                'height' => $lastRecord->height ?? null,
                'weight' => $lastRecord->weight ?? null,
                'blood_pressure' => $lastRecord->blood_pressure ?? null,
                'heart_rate' => $lastRecord->heart_rate ?? null,
                'temperature' => $lastRecord->temperature ?? null,
                'allergies' => $lastRecord->allergies ?? null,
                'chronic_diseases' => $lastRecord->chronic_diseases ?? null,
                'current_medications' => $lastRecord->current_medications ?? null,
            ];
        } else {
            $initialData = [
                'height' => $medicalRecord->height,
                'weight' => $medicalRecord->weight,
                'blood_pressure' => $medicalRecord->blood_pressure,
                'heart_rate' => $medicalRecord->heart_rate,
                'temperature' => $medicalRecord->temperature,
                'allergies' => $medicalRecord->allergies,
                'chronic_diseases' => $medicalRecord->chronic_diseases,
                'current_medications' => $medicalRecord->current_medications,
                'symptoms' => $medicalRecord->symptoms,
                'diagnosis' => $medicalRecord->diagnosis,
                'treatment' => $medicalRecord->treatment,
                'observations' => $medicalRecord->observations,
            ];
        }
        
        return view('doctor.appointments.summary', [
            'appointment' => $appointment,
            'medicalRecord' => $medicalRecord,
            'initialData' => $initialData
        ]);
    }
    
    /**
     * Store or update the appointment summary and medical record.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function saveSummary(Request $request, $id)
    {
        $request->validate([
            'symptoms' => 'required|string',
            'diagnosis' => 'required|string',
            'treatment' => 'required|string',
            'height' => 'nullable|numeric|min:0',
            'weight' => 'nullable|numeric|min:0',
            'blood_pressure' => 'nullable|string|max:50',
            'heart_rate' => 'nullable|integer|min:0',
            'temperature' => 'nullable|numeric|min:0',
            'observations' => 'nullable|string',
            'allergies' => 'nullable|string',
            'chronic_diseases' => 'nullable|string',
            'current_medications' => 'nullable|string',
        ]);
        
        $doctor = Auth::user()->doctor;
        
        $appointment = Appointment::where('doctor_id', $doctor->id)
            ->findOrFail($id);
            
        try {
            // Buscar ou criar registro médico para esta consulta
            $medicalRecord = MedicalRecord::firstOrNew([
                'appointment_id' => $appointment->id,
                'patient_id' => $appointment->patient_id,
                'doctor_id' => $doctor->id
            ]);
            
            // Preencher dados do registro médico
            $medicalRecord->symptoms = $request->symptoms;
            $medicalRecord->diagnosis = $request->diagnosis;
            $medicalRecord->treatment = $request->treatment;
            $medicalRecord->height = $request->height;
            $medicalRecord->weight = $request->weight;
            $medicalRecord->blood_pressure = $request->blood_pressure;
            $medicalRecord->heart_rate = $request->heart_rate;
            $medicalRecord->temperature = $request->temperature;
            $medicalRecord->observations = $request->observations;
            $medicalRecord->allergies = $request->allergies;
            $medicalRecord->chronic_diseases = $request->chronic_diseases;
            $medicalRecord->current_medications = $request->current_medications;
            $medicalRecord->save();
            
            // Se solicitado, finalizar a consulta
            if ($request->has('finish_appointment') && $request->finish_appointment) {
                $appointment->status = 'completed';
                $appointment->completed_at = Carbon::now();
                $appointment->save();
                
                // Notificar o paciente
                $notification = new \App\Models\Notification();
                $notification->user_id = $appointment->patient->user_id;
                $notification->title = 'Consulta Finalizada';
                $notification->message = 'Sua consulta com Dr. ' . $doctor->user->name . ' foi finalizada. O prontuário médico está disponível para consulta.';
                $notification->type = 'appointment_completed';
                $notification->data = json_encode(['appointment_id' => $appointment->id]);
                $notification->save();
            }
            
            return redirect()->route('doctor.appointments.show', $appointment->id)
                ->with('success', 'Resumo e prontuário salvos com sucesso!');
                
        } catch (\Exception $e) {
            return back()->with('error', 'Ocorreu um erro ao salvar o resumo: ' . $e->getMessage())
                ->withInput();
        }
    }
    
    /**
     * Get available slots in a date range.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAvailableSlots(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'appointment_id' => 'nullable|exists:appointments,id'
        ]);
        
        $doctor = Auth::user()->doctor;
        $startDate = Carbon::parse($request->start_date)->startOfDay();
        $endDate = Carbon::parse($request->end_date)->endOfDay();
        $appointmentId = $request->appointment_id;
        
        $slots = $this->calculateAvailableSlots($doctor->id, $startDate, $endDate, $appointmentId);
        
        return response()->json([
            'success' => true,
            'slots' => $slots
        ]);
    }
    
    /**
     * Calculate available appointment slots.
     *
     * @param  int  $doctorId
     * @param  \Carbon\Carbon  $startDate
     * @param  \Carbon\Carbon  $endDate
     * @param  int|null  $excludeAppointmentId
     * @return array
     */
    private function calculateAvailableSlots($doctorId, $startDate, $endDate, $excludeAppointmentId = null)
    {
        $doctor = \App\Models\Doctor::find($doctorId);
        $slots = [];
        
        // Buscar horários do médico
        $schedules = $doctor->schedules;
        
        // Buscar consultas já agendadas
        $appointments = Appointment::where('doctor_id', $doctorId)
            ->whereIn('status', ['scheduled', 'confirmed', 'in_progress'])
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('start_time', [$startDate, $endDate])
                    ->orWhereBetween('end_time', [$startDate, $endDate]);
            });
            
        if ($excludeAppointmentId) {
            $appointments = $appointments->where('id', '!=', $excludeAppointmentId);
        }
        
        $appointments = $appointments->get();
        
        // Buscar exceções (férias, folgas)
        $exceptions = $doctor->scheduleExceptions()
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate])
                    ->orWhere(function ($q) use ($startDate, $endDate) {
                        $q->where('start_date', '<=', $startDate)
                            ->where('end_date', '>=', $endDate);
                    });
            })
            ->get();
        
        // Para cada dia no período
        for ($date = clone $startDate; $date <= $endDate; $date->addDay()) {
            $dayOfWeek = $date->dayOfWeek;
            $dateString = $date->format('Y-m-d');
            $slots[$dateString] = [];
            
            // Verificar se não é uma exceção (férias, folga)
            $isException = false;
            foreach ($exceptions as $exception) {
                if ($date->between(Carbon::parse($exception->start_date), Carbon::parse($exception->end_date))) {
                    $isException = true;
                    break;
                }
            }
            
            if ($isException) {
                continue;
            }
            
            // Obter horários para este dia da semana
            $daySchedules = $schedules->where('day_of_week', $dayOfWeek);
            
            foreach ($daySchedules as $schedule) {
                $startTime = Carbon::parse($date->format('Y-m-d') . ' ' . $schedule->start_time);
                $endTime = Carbon::parse($date->format('Y-m-d') . ' ' . $schedule->end_time);
                
                // Se o dia já passou, pular
                if ($date->isToday() && Carbon::now()->gt($startTime)) {
                    continue;
                }
                
                // Duração padrão da consulta em minutos
                $duration = $doctor->consultation_duration;
                
                // Gerar slots com base no horário de funcionamento
                for ($time = clone $startTime; $time->addMinutes($duration) <= $endTime; $time = (clone $time)->addMinutes($duration)) {
                    $slotStart = clone $time;
                    $slotEnd = (clone $slotStart)->addMinutes($duration);
                    
                    // Verificar se este slot está disponível
                    $available = true;
                    foreach ($appointments as $appointment) {
                        $appointmentStart = Carbon::parse($appointment->start_time);
                        $appointmentEnd = Carbon::parse($appointment->end_time);
                        
                        if ($slotStart->lt($appointmentEnd) && $slotEnd->gt($appointmentStart)) {
                            $available = false;
                            break;
                        }
                    }
                    
                    if ($available) {
                        $slots[$dateString][] = [
                            'start' => $slotStart->format('H:i'),
                            'end' => $slotEnd->format('H:i'),
                            'datetime' => $slotStart->format('Y-m-d H:i:s')
                        ];
                    }
                }
            }
        }
        
        return $slots;
    }
    
    /**
     * Check if a time slot is available.
     *
     * @param  int  $doctorId
     * @param  \Carbon\Carbon  $startTime
     * @param  \Carbon\Carbon  $endTime
     * @param  int|null  $excludeAppointmentId
     * @return bool
     */
    private function checkSlotAvailability($doctorId, $startTime, $endTime, $excludeAppointmentId = null)
    {
        $doctor = \App\Models\Doctor::find($doctorId);
        
        // Verificar se é um dia e horário que o médico atende
        $dayOfWeek = $startTime->dayOfWeek;
        $hasSchedule = $doctor->schedules()
            ->where('day_of_week', $dayOfWeek)
            ->where('start_time', '<=', $startTime->format('H:i:s'))
            ->where('end_time', '>=', $endTime->format('H:i:s'))
            ->exists();
            
        if (!$hasSchedule) {
            return false;
        }
        
        // Verificar se não é uma exceção (férias, folga)
        $isException = $doctor->scheduleExceptions()
            ->where(function ($query) use ($startTime) {
                $query->where('start_date', '<=', $startTime->format('Y-m-d'))
                    ->where('end_date', '>=', $startTime->format('Y-m-d'));
            })
            ->exists();
            
        if ($isException) {
            return false;
        }
        
        // Verificar se não conflita com outra consulta
        $query = Appointment::where('doctor_id', $doctorId)
            ->whereIn('status', ['scheduled', 'confirmed', 'in_progress'])
            ->where(function ($query) use ($startTime, $endTime) {
                $query->whereBetween('start_time', [$startTime, $endTime])
                    ->orWhereBetween('end_time', [$startTime, $endTime])
                    ->orWhere(function ($q) use ($startTime, $endTime) {
                        $q->where('start_time', '<=', $startTime)
                            ->where('end_time', '>=', $endTime);
                    });
            });
            
        if ($excludeAppointmentId) {
            $query->where('id', '!=', $excludeAppointmentId);
        }
        
        $hasConflict = $query->exists();
        
        return !$hasConflict;
    }
    
    /**
     * Get the display name for an appointment status.
     *
     * @param  string  $status
     * @return string
     */
    private function getStatusName($status)
    {
        $statuses = [
            'scheduled' => 'Agendada',
            'confirmed' => 'Confirmada',
            'in_progress' => 'Em Andamento',
            'completed' => 'Finalizada',
            'cancelled' => 'Cancelada',
            'no_show' => 'Não Compareceu'
        ];
        
        return $statuses[$status] ?? $status;
    }
}