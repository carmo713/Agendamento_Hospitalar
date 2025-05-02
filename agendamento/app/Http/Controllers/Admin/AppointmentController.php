<?php
// filepath: /home/carmo/Documentos/trabalhofinal_agendamentohospitalar/agendamento/app/Http/Controllers/Admin/AppointmentController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Specialty;
// Removed unnecessary use directive
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AppointmentController extends Controller
{
    /**
     * Display a listing of the appointments.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $doctorId = $request->query('doctor_id');
        $patientId = $request->query('patient_id');
        $specialtyId = $request->query('specialty_id');
        $status = $request->query('status');
        $dateStart = $request->query('date_start');
        $dateEnd = $request->query('date_end');
        
        $query = Appointment::with(['doctor.user', 'patient.user', 'specialty']);
        
        // Filtrar por doutor
        if ($doctorId) {
            $query->where('doctor_id', $doctorId);
        }
        
        // Filtrar por paciente
        if ($patientId) {
            $query->where('patient_id', $patientId);
        }
        
        // Filtrar por especialidade
        if ($specialtyId) {
            $query->where('specialty_id', $specialtyId);
        }
        
        // Filtrar por status
        if ($status) {
            $query->where('status', $status);
        }
        
        // Filtrar por data inicial
        if ($dateStart) {
            $query->whereDate('start_time', '>=', $dateStart);
        }
        
        // Filtrar por data final
        if ($dateEnd) {
            $query->whereDate('start_time', '<=', $dateEnd);
        }
        
        // Ordenar por data/hora de início mais recente por padrão
        $query->orderBy('start_time', 'desc');
        
        $appointments = $query->paginate(15);
        
        // Dados para os filtros
        $doctors = Doctor::with('user')->get();
        $patients = Patient::with('user')->get();
        $specialties = Specialty::orderBy('name')->get();
        
        return view('admin.appointments.index', compact(
            'appointments',
            'doctors',
            'patients',
            'specialties',
            'doctorId',
            'patientId',
            'specialtyId',
            'status',
            'dateStart',
            'dateEnd'
        ));
    }

    /**
     * Show the form for creating a new appointment.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function create(Request $request)
    {
        // Se um paciente já estiver pré-selecionado
        $selectedPatientId = $request->query('patient_id');
        $selectedDoctorId = $request->query('doctor_id');
        $selectedSpecialtyId = $request->query('specialty_id');
        
        $patients = Patient::with('user')->get();
        $doctors = Doctor::with(['user', 'specialties'])->get();
        $specialties = Specialty::orderBy('name')->get();
        
        return view('admin.appointments.create', compact(
            'patients',
            'doctors',
            'specialties',
            'selectedPatientId',
            'selectedDoctorId',
            'selectedSpecialtyId'
        ));
    }

    /**
     * Store a newly created appointment in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'required|exists:doctors,id',
            'specialty_id' => 'required|exists:specialties,id',
            'start_date' => 'required|date',
            'start_time' => 'required',
            'duration' => 'required|integer|min:5',
            'status' => 'required|in:scheduled,confirmed,completed,canceled,no_show',
            'reason' => 'required|string|max:255',
            'notes' => 'nullable|string',
        ]);
        
        if ($validator->fails()) {
            return redirect()->route('admin.appointments.create')
                ->withErrors($validator)
                ->withInput();
        }
        
        // Combinar data e hora
        $startDateTime = Carbon::parse("{$request->start_date} {$request->start_time}");
        
        // Calcular a hora de término
        $endDateTime = (clone $startDateTime)->addMinutes((int)$request->duration);
        
        // Verificar disponibilidade do médico
        $existingAppointment = Appointment::where('doctor_id', $request->doctor_id)
            ->where(function($query) use ($startDateTime, $endDateTime) {
                $query->whereBetween('start_time', [$startDateTime, $endDateTime])
                    ->orWhereBetween('end_time', [$startDateTime, $endDateTime])
                    ->orWhere(function($q) use ($startDateTime, $endDateTime) {
                        $q->where('start_time', '<=', $startDateTime)
                          ->where('end_time', '>=', $endDateTime);
                    });
            })
            ->where('status', '!=', 'canceled')
            ->first();
            
        if ($existingAppointment) {
            return redirect()->route('admin.appointments.create')
                ->with('error', 'O médico já possui um agendamento neste horário.')
                ->withInput();
        }
        
        // Verificar disponibilidade do paciente
        $existingPatientAppointment = Appointment::where('patient_id', $request->patient_id)
            ->where(function($query) use ($startDateTime, $endDateTime) {
                $query->whereBetween('start_time', [$startDateTime, $endDateTime])
                    ->orWhereBetween('end_time', [$startDateTime, $endDateTime])
                    ->orWhere(function($q) use ($startDateTime, $endDateTime) {
                        $q->where('start_time', '<=', $startDateTime)
                          ->where('end_time', '>=', $endDateTime);
                    });
            })
            ->where('status', '!=', 'canceled')
            ->first();
            
        if ($existingPatientAppointment) {
            return redirect()->route('admin.appointments.create')
                ->with('error', 'O paciente já possui um agendamento neste horário.')
                ->withInput();
        }
        
        try {
            DB::beginTransaction();
            
            // Criar o agendamento
            Appointment::create([
                'patient_id' => $request->patient_id,
                'doctor_id' => $request->doctor_id,
                'specialty_id' => $request->specialty_id,
                'start_time' => $startDateTime,
                'end_time' => $endDateTime,
                'status' => $request->status,
                'reason' => $request->reason,
                'notes' => $request->notes,
            ]);
            
            DB::commit();
            
            return redirect()->route('admin.appointments.index')
                ->with('success', 'Consulta agendada com sucesso.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->route('admin.appointments.create')
                ->with('error', 'Erro ao agendar consulta: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified appointment.
     *
     * @param  \App\Models\Appointment  $appointment
     * @return \Illuminate\View\View
     */
    public function show(Appointment $appointment)
    {
        $appointment->load([
            'doctor.user', 
            'patient.user', 
            'specialty', 
            'medicalRecord',
            'payment',
            'feedback',
            'canceledBy'
        ]);
        
        return view('admin.appointments.show', compact('appointment'));
    }

    /**
     * Show the form for editing the specified appointment.
     *
     * @param  \App\Models\Appointment  $appointment
     * @return \Illuminate\View\View
     */
    public function edit(Appointment $appointment)
    {
        $appointment->load(['doctor.user', 'patient.user', 'specialty']);
        
        $patients = Patient::with('user')->get();
        $doctors = Doctor::with(['user', 'specialties'])->get();
        $specialties = Specialty::orderBy('name')->get();
        
        // Formatar a data e hora para os campos do formulário
        $startDate = $appointment->start_time->format('Y-m-d');
        $startTime = $appointment->start_time->format('H:i');
        $duration = $appointment->end_time->diffInMinutes($appointment->start_time);
        
        return view('admin.appointments.edit', compact(
            'appointment',
            'patients',
            'doctors',
            'specialties',
            'startDate',
            'startTime',
            'duration'
        ));
    }

    /**
     * Update the specified appointment in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Appointment  $appointment
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Appointment $appointment)
    {
        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'required|exists:doctors,id',
            'specialty_id' => 'required|exists:specialties,id',
            'start_date' => 'required|date',
            'start_time' => 'required',
            'duration' => 'required|integer|min:5',
            'status' => 'required|in:scheduled,confirmed,completed,canceled,no_show',
            'reason' => 'required|string|max:255',
            'notes' => 'nullable|string',
            'cancellation_reason' => 'nullable|required_if:status,canceled|string',
        ]);
        
        if ($validator->fails()) {
            return redirect()->route('admin.appointments.edit', $appointment->id)
                ->withErrors($validator)
                ->withInput();
        }
        
        // Combinar data e hora
        $startDateTime = Carbon::parse($request->start_date . ' ' . $request->start_time);
        
        // Calcular a hora de término
        $endDateTime = (clone $startDateTime)->addMinutes($request->duration);
        
        // Verificar disponibilidade do médico (excluindo esta consulta)
        $existingAppointment = Appointment::where('doctor_id', $request->doctor_id)
            ->where('id', '!=', $appointment->id)
            ->where(function($query) use ($startDateTime, $endDateTime) {
                $query->whereBetween('start_time', [$startDateTime, $endDateTime])
                    ->orWhereBetween('end_time', [$startDateTime, $endDateTime])
                    ->orWhere(function($q) use ($startDateTime, $endDateTime) {
                        $q->where('start_time', '<=', $startDateTime)
                          ->where('end_time', '>=', $endDateTime);
                    });
            })
            ->where('status', '!=', 'canceled')
            ->first();
            
        if ($existingAppointment) {
            return redirect()->route('admin.appointments.edit', $appointment->id)
                ->with('error', 'O médico já possui um agendamento neste horário.')
                ->withInput();
        }
        
        // Verificar disponibilidade do paciente (excluindo esta consulta)
        $existingPatientAppointment = Appointment::where('patient_id', $request->patient_id)
            ->where('id', '!=', $appointment->id)
            ->where(function($query) use ($startDateTime, $endDateTime) {
                $query->whereBetween('start_time', [$startDateTime, $endDateTime])
                    ->orWhereBetween('end_time', [$startDateTime, $endDateTime])
                    ->orWhere(function($q) use ($startDateTime, $endDateTime) {
                        $q->where('start_time', '<=', $startDateTime)
                          ->where('end_time', '>=', $endDateTime);
                    });
            })
            ->where('status', '!=', 'canceled')
            ->first();
            
        if ($existingPatientAppointment) {
            return redirect()->route('admin.appointments.edit', $appointment->id)
                ->with('error', 'O paciente já possui um agendamento neste horário.')
                ->withInput();
        }
        
        try {
            DB::beginTransaction();
            
            // Se o status foi alterado para cancelado
            if ($request->status === 'canceled' && $appointment->status !== 'canceled') {
                $appointment->canceled_by = Auth::id(); // ID do usuário que está cancelando
                $appointment->cancellation_reason = $request->cancellation_reason;
            }
            
            // Atualizar o agendamento
            $appointment->update([
                'patient_id' => $request->patient_id,
                'doctor_id' => $request->doctor_id,
                'specialty_id' => $request->specialty_id,
                'start_time' => $startDateTime,
                'end_time' => $endDateTime,
                'status' => $request->status,
                'reason' => $request->reason,
                'notes' => $request->notes,
                'cancellation_reason' => $request->cancellation_reason,
                'canceled_by' => $request->status === 'canceled' ? Auth::id() : $appointment->canceled_by,
            ]);
            
            DB::commit();
            
            return redirect()->route('admin.appointments.index')
                ->with('success', 'Consulta atualizada com sucesso.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->route('admin.appointments.edit', $appointment->id)
                ->with('error', 'Erro ao atualizar consulta: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified appointment from storage.
     *
     * @param  \App\Models\Appointment  $appointment
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Appointment $appointment)
    {
        try {
            $appointment->delete();
            
            return redirect()->route('admin.appointments.index')
                ->with('success', 'Consulta excluída com sucesso.');
        } catch (\Exception $e) {
            return redirect()->route('admin.appointments.index')
                ->with('error', 'Erro ao excluir consulta: ' . $e->getMessage());
        }
    }
    
    /**
     * Update the status of an appointment.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Appointment  $appointment
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateStatus(Request $request, Appointment $appointment)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:scheduled,confirmed,completed,canceled,no_show',
            'cancellation_reason' => 'nullable|required_if:status,canceled|string',
        ]);
        
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        
        try {
            // Se o status foi alterado para cancelado
            if ($request->status === 'canceled' && $appointment->status !== 'canceled') {
                $appointment->canceled_by = Auth::id();
                $appointment->cancellation_reason = $request->cancellation_reason;
            }
            
            $appointment->status = $request->status;
            $appointment->save();
            
            return redirect()->back()
                ->with('success', 'Status da consulta atualizado com sucesso.');
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Erro ao atualizar status da consulta: ' . $e->getMessage());
        }
    }
}