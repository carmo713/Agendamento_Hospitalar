<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\ExamRequest;
use App\Models\ExamItem;
use App\Models\Patient;
use App\Models\MedicalRecord;
use App\Models\Appointment;
use App\Models\ExamResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use PDF;

class ExamRequestController extends Controller
{
    /**
     * Display a listing of exam requests.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $doctor = Auth::user()->doctor;
        
        // Base query
        $query = ExamRequest::where('doctor_id', $doctor->id)
            ->with(['patient.user']);
        
        // Filtro por paciente
        if ($request->has('patient_id') && $request->patient_id) {
            $query->where('patient_id', $request->patient_id);
        }
        
        // Filtro por período
        if ($request->has('start_date') && $request->start_date) {
            $query->where('request_date', '>=', $request->start_date);
        }
        
        if ($request->has('end_date') && $request->end_date) {
            $query->where('request_date', '<=', Carbon::parse($request->end_date)->endOfDay());
        }
        
        // Filtro por status
        if ($request->has('status') && $request->status != 'all') {
            $query->where('status', $request->status);
        }
        
        // Filtro por tipo de exame
        if ($request->has('exam_type') && $request->exam_type) {
            $query->whereHas('examItems', function($q) use ($request) {
                $q->where('exam_type', $request->exam_type);
            });
        }
        
        // Filtro por texto
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('notes', 'like', '%' . $search . '%')
                  ->orWhere('clinical_indication', 'like', '%' . $search . '%')
                  ->orWhereHas('patient.user', function($q) use ($search) {
                      $q->where('name', 'like', '%' . $search . '%');
                  })
                  ->orWhereHas('examItems', function($q) use ($search) {
                      $q->where('exam_name', 'like', '%' . $search . '%');
                  });
            });
        }
        
        // Ordenação
        $sortBy = $request->sort_by ?? 'request_date';
        $sortDirection = $request->sort_direction ?? 'desc';
        
        $examRequests = $query->orderBy($sortBy, $sortDirection)
            ->paginate(15)
            ->withQueryString();
        
        // Buscar pacientes do médico para filtro
        $patients = Patient::whereHas('appointments', function($q) use ($doctor) {
            $q->where('doctor_id', $doctor->id)
              ->where('status', 'completed');
        })
        ->with('user')
        ->orderBy('user_id')
        ->get();
        
        // Status para exibição
        $statusOptions = [
            'requested' => 'Solicitado',
            'scheduled' => 'Agendado',
            'completed' => 'Realizado',
            'cancelled' => 'Cancelado',
            'expired' => 'Expirado'
        ];
        
        // Tipos de exame comuns
        $examTypes = $this->getCommonExamTypes();
        
        return view('doctor.exam_requests.index', [
            'examRequests' => $examRequests,
            'patients' => $patients,
            'statusOptions' => $statusOptions,
            'examTypes' => $examTypes,
            'filters' => $request->only(['patient_id', 'start_date', 'end_date', 'status', 'exam_type', 'search', 'sort_by', 'sort_direction'])
        ]);
    }
    
    /**
     * Show the form for creating a new exam request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function create(Request $request)
    {
        $doctor = Auth::user()->doctor;
        $medicalRecordId = $request->query('medical_record_id');
        $patientId = $request->query('patient_id');
        $appointmentId = $request->query('appointment_id');
        
        // Se um ID de prontuário médico for fornecido, buscar o prontuário
        if ($medicalRecordId) {
            $medicalRecord = MedicalRecord::where('doctor_id', $doctor->id)
                ->with('patient.user')
                ->findOrFail($medicalRecordId);
                
            $patient = $medicalRecord->patient;
        }
        // Se um ID de consulta for fornecido, usar o paciente da consulta
        elseif ($appointmentId) {
            $appointment = Appointment::where('doctor_id', $doctor->id)
                ->with('patient.user')
                ->findOrFail($appointmentId);
                
            $patient = $appointment->patient;
            
            // Buscar ou criar um prontuário para esta consulta
            $medicalRecord = MedicalRecord::firstOrCreate(
                [
                    'doctor_id' => $doctor->id,
                    'patient_id' => $patient->id,
                    'appointment_id' => $appointmentId
                ],
                [
                    'record_type' => 'consultation',
                    'reason' => 'Consulta médica',
                    'notes' => 'Prontuário criado automaticamente para solicitação de exames'
                ]
            );
        }
        // Se apenas um ID de paciente for fornecido
        elseif ($patientId) {
            $patient = Patient::with('user')->findOrFail($patientId);
            
            // Verificar se o médico já atendeu este paciente
            $hasConsulted = MedicalRecord::where('doctor_id', $doctor->id)
                ->where('patient_id', $patientId)
                ->exists();
                
            if (!$hasConsulted) {
                return redirect()->route('doctor.patients.index')
                    ->with('error', 'Você só pode solicitar exames para pacientes que já atendeu.');
            }
            
            // Buscar o prontuário mais recente deste paciente feito por este médico
            $medicalRecord = MedicalRecord::where('doctor_id', $doctor->id)
                ->where('patient_id', $patientId)
                ->orderBy('created_at', 'desc')
                ->first();
        } else {
            // Se nem paciente nem prontuário forem fornecidos, redirecionar para selecionar
            return redirect()->route('doctor.exam_requests.select_patient');
        }
        
        // Exames frequentemente solicitados por este médico
        $commonExams = ExamItem::whereHas('examRequest', function($q) use ($doctor) {
                $q->where('doctor_id', $doctor->id);
            })
            ->select('exam_name', 'exam_type')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('exam_name', 'exam_type')
            ->orderByRaw('COUNT(*) DESC')
            ->limit(20)
            ->get();
            
        // Tipos de exames
        $examTypes = $this->getCommonExamTypes();
        
        // Exames recentes do paciente
        $recentExams = ExamRequest::where('patient_id', $patient->id)
            ->with('examItems')
            ->orderBy('request_date', 'desc')
            ->limit(5)
            ->get();
        
        return view('doctor.exam_requests.create', [
            'patient' => $patient,
            'medicalRecord' => $medicalRecord ?? null,
            'commonExams' => $commonExams,
            'examTypes' => $examTypes,
            'recentExams' => $recentExams
        ]);
    }
    
    /**
     * Display form to select a patient for a new exam request.
     *
     * @return \Illuminate\View\View
     */
    public function selectPatient()
    {
        $doctor = Auth::user()->doctor;
        
        // Buscar pacientes recentemente atendidos pelo médico
        $recentPatients = Patient::whereHas('appointments', function($q) use ($doctor) {
            $q->where('doctor_id', $doctor->id)
              ->where('status', 'completed')
              ->orderBy('start_time', 'desc');
        })
        ->with('user')
        ->limit(10)
        ->get();
        
        // Consultas do dia
        $todaysAppointments = Appointment::where('doctor_id', $doctor->id)
            ->whereDate('start_time', Carbon::today())
            ->whereIn('status', ['completed', 'confirmed'])
            ->with('patient.user')
            ->orderBy('start_time')
            ->get();
        
        return view('doctor.exam_requests.select_patient', [
            'recentPatients' => $recentPatients,
            'todaysAppointments' => $todaysAppointments
        ]);
    }
    
    /**
     * Store a newly created exam request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'medical_record_id' => 'nullable|exists:medical_records,id',
            'request_date' => 'required|date|before_or_equal:today',
            'validity_days' => 'required|integer|min:1|max:365',
            'clinical_indication' => 'required|string|max:2000',
            'priority' => 'required|in:normal,urgent,emergency',
            'notes' => 'nullable|string|max:2000',
            'exams' => 'required|array|min:1',
            'exams.*.name' => 'required|string|max:255',
            'exams.*.type' => 'required|string|max:100',
            'exams.*.instructions' => 'nullable|string|max:500',
            'fast_required' => 'nullable|boolean',
            'is_pregnant' => 'nullable|boolean',
            'pregnancy_weeks' => 'nullable|integer|min:1|max:42',
            'diabetic' => 'nullable|boolean',
            'allergy_contrast' => 'nullable|boolean'
        ]);
        
        $doctor = Auth::user()->doctor;
        $patientId = $request->patient_id;
        
        // Verificar se o médico já atendeu este paciente
        $hasConsulted = MedicalRecord::where('doctor_id', $doctor->id)
            ->where('patient_id', $patientId)
            ->exists();
            
        if (!$hasConsulted) {
            return back()->with('error', 'Você só pode solicitar exames para pacientes que já atendeu.')
                ->withInput();
        }
        
        try {
            // Criar a solicitação de exames
            $examRequest = new ExamRequest();
            $examRequest->doctor_id = $doctor->id;
            $examRequest->patient_id = $patientId;
            
            if ($request->medical_record_id) {
                $medicalRecord = MedicalRecord::where('doctor_id', $doctor->id)
                    ->where('id', $request->medical_record_id)
                    ->first();
                    
                if ($medicalRecord) {
                    $examRequest->medical_record_id = $medicalRecord->id;
                }
            }
            
            $examRequest->request_date = $request->request_date;
            $examRequest->expiration_date = Carbon::parse($request->request_date)->addDays($request->validity_days);
            $examRequest->clinical_indication = $request->clinical_indication;
            $examRequest->priority = $request->priority;
            $examRequest->notes = $request->notes;
            $examRequest->status = 'requested';
            $examRequest->fast_required = $request->has('fast_required') ? true : false;
            $examRequest->patient_conditions = [
                'is_pregnant' => $request->has('is_pregnant') ? true : false,
                'pregnancy_weeks' => $request->pregnancy_weeks,
                'diabetic' => $request->has('diabetic') ? true : false,
                'allergy_contrast' => $request->has('allergy_contrast') ? true : false
            ];
            $examRequest->save();
            
            // Adicionar os exames à solicitação
            foreach ($request->exams as $exam) {
                $examItem = new ExamItem();
                $examItem->exam_request_id = $examRequest->id;
                $examItem->exam_name = $exam['name'];
                $examItem->exam_type = $exam['type'];
                $examItem->instructions = $exam['instructions'] ?? null;
                $examItem->status = 'pending';
                $examItem->save();
            }
            
            // Registrar na trilha de auditoria
            \App\Models\AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'create',
                'model_type' => 'ExamRequest',
                'model_id' => $examRequest->id,
                'description' => 'Solicitação de exames criada'
            ]);
            
            // Notificar o paciente
            $patient = Patient::find($patientId);
            \App\Models\Notification::create([
                'user_id' => $patient->user_id,
                'type' => 'exam_request_created',
                'title' => 'Nova solicitação de exames',
                'message' => 'O Dr. ' . $doctor->user->name . ' solicitou exames para você.',
                'data' => json_encode([
                    'exam_request_id' => $examRequest->id
                ]),
                'status' => 'unread'
            ]);
            
            // Enviar email (opcional)
            if ($patient->user->notification_settings['email_exam_request'] ?? true) {
                \Mail::to($patient->user->email)->send(new \App\Mail\ExamRequestCreated(
                    $patient->user,
                    $doctor->user,
                    $examRequest
                ));
            }
            
            return redirect()->route('doctor.exam_requests.show', $examRequest->id)
                ->with('success', 'Solicitação de exames criada com sucesso!');
                
        } catch (\Exception $e) {
            return back()->with('error', 'Ocorreu um erro ao criar a solicitação de exames: ' . $e->getMessage())
                ->withInput();
        }
    }
    
    /**
     * Display the specified exam request.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function show($id)
    {
        $doctor = Auth::user()->doctor;
        
        // Buscar a solicitação de exames
        $examRequest = ExamRequest::where(function($query) use ($doctor) {
                // O médico pode ver solicitações que ele mesmo criou
                $query->where('doctor_id', $doctor->id)
                    // Ou solicitações de pacientes que ele já atendeu
                    ->orWhereHas('patient', function($q) use ($doctor) {
                        $q->whereHas('appointments', function($q) use ($doctor) {
                            $q->where('doctor_id', $doctor->id)
                              ->where('status', 'completed');
                        });
                    });
            })
            ->with([
                'patient.user', 
                'doctor.user', 
                'examItems',
                'examResults'
            ])
            ->findOrFail($id);
            
        // Verificar se a solicitação está ativa
        $isActive = Carbon::parse($examRequest->expiration_date)->isFuture();
        
        // Status da solicitação para exibição
        $statusLabels = [
            'requested' => 'Solicitado',
            'scheduled' => 'Agendado',
            'completed' => 'Realizado',
            'cancelled' => 'Cancelado',
            'expired' => 'Expirado'
        ];
        
        // Prioridades para exibição
        $priorityLabels = [
            'normal' => 'Normal',
            'urgent' => 'Urgente',
            'emergency' => 'Emergencial'
        ];
        
        return view('doctor.exam_requests.show', [
            'examRequest' => $examRequest,
            'isActive' => $isActive,
            'statusLabel' => $statusLabels[$examRequest->status] ?? 'Desconhecido',
            'priorityLabel' => $priorityLabels[$examRequest->priority] ?? 'Normal',
            'isOwner' => $examRequest->doctor_id === $doctor->id
        ]);
    }
    
    /**
     * Show the form for editing the specified exam request.
     *
     * @param  int  $id
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function edit($id)
    {
        $doctor = Auth::user()->doctor;
        
        // Apenas o médico que criou a solicitação pode editá-la
        $examRequest = ExamRequest::where('doctor_id', $doctor->id)
            ->with(['patient.user', 'examItems'])
            ->findOrFail($id);
            
        // Verificar se a solicitação foi criada há pouco tempo (menos de 24 horas)
        $editDeadline = Carbon::now()->subHours(24);
        if ($examRequest->created_at->lt($editDeadline)) {
            return redirect()->route('doctor.exam_requests.show', $examRequest->id)
                ->with('error', 'Não é possível editar uma solicitação após 24 horas da sua criação por questões de segurança.');
        }
        
        // Verificar se algum exame já foi realizado ou agendado
        $hasCompletedOrScheduled = $examRequest->examItems->contains(function($item) {
            return $item->status === 'completed' || $item->status === 'scheduled';
        });
        
        if ($hasCompletedOrScheduled) {
            return redirect()->route('doctor.exam_requests.show', $examRequest->id)
                ->with('error', 'Não é possível editar uma solicitação que já possui exames realizados ou agendados.');
        }
        
        // Exames frequentemente solicitados por este médico
        $commonExams = ExamItem::whereHas('examRequest', function($q) use ($doctor) {
                $q->where('doctor_id', $doctor->id);
            })
            ->select('exam_name', 'exam_type')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('exam_name', 'exam_type')
            ->orderByRaw('COUNT(*) DESC')
            ->limit(20)
            ->get();
            
        // Tipos de exames
        $examTypes = $this->getCommonExamTypes();
        
        return view('doctor.exam_requests.edit', [
            'examRequest' => $examRequest,
            'commonExams' => $commonExams,
            'examTypes' => $examTypes
        ]);
    }
    
    /**
     * Update the specified exam request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'request_date' => 'required|date|before_or_equal:today',
            'validity_days' => 'required|integer|min:1|max:365',
            'clinical_indication' => 'required|string|max:2000',
            'priority' => 'required|in:normal,urgent,emergency',
            'notes' => 'nullable|string|max:2000',
            'exams' => 'required|array|min:1',
            'exams.*.id' => 'nullable|exists:exam_items,id',
            'exams.*.name' => 'required|string|max:255',
            'exams.*.type' => 'required|string|max:100',
            'exams.*.instructions' => 'nullable|string|max:500',
            'fast_required' => 'nullable|boolean',
            'is_pregnant' => 'nullable|boolean',
            'pregnancy_weeks' => 'nullable|integer|min:1|max:42',
            'diabetic' => 'nullable|boolean',
            'allergy_contrast' => 'nullable|boolean'
        ]);
        
        $doctor = Auth::user()->doctor;
        
        // Apenas o médico que criou a solicitação pode editá-la
        $examRequest = ExamRequest::where('doctor_id', $doctor->id)
            ->with('examItems')
            ->findOrFail($id);
            
        // Verificar se a solicitação foi criada há pouco tempo (menos de 24 horas)
        $editDeadline = Carbon::now()->subHours(24);
        if ($examRequest->created_at->lt($editDeadline)) {
            return redirect()->route('doctor.exam_requests.show', $examRequest->id)
                ->with('error', 'Não é possível editar uma solicitação após 24 horas da sua criação por questões de segurança.');
        }
        
        // Verificar se algum exame já foi realizado ou agendado
        $hasCompletedOrScheduled = $examRequest->examItems->contains(function($item) {
            return $item->status === 'completed' || $item->status === 'scheduled';
        });
        
        if ($hasCompletedOrScheduled) {
            return redirect()->route('doctor.exam_requests.show', $examRequest->id)
                ->with('error', 'Não é possível editar uma solicitação que já possui exames realizados ou agendados.');
        }
        
        try {
            // Atualizar a solicitação de exames
            $examRequest->request_date = $request->request_date;
            $examRequest->expiration_date = Carbon::parse($request->request_date)->addDays($request->validity_days);
            $examRequest->clinical_indication = $request->clinical_indication;
            $examRequest->priority = $request->priority;
            $examRequest->notes = $request->notes;
            $examRequest->fast_required = $request->has('fast_required') ? true : false;
            $examRequest->patient_conditions = [
                'is_pregnant' => $request->has('is_pregnant') ? true : false,
                'pregnancy_weeks' => $request->pregnancy_weeks,
                'diabetic' => $request->has('diabetic') ? true : false,
                'allergy_contrast' => $request->has('allergy_contrast') ? true : false
            ];
            $examRequest->save();
            
            // Obter IDs atuais dos exames
            $currentItemIds = $examRequest->examItems->pluck('id')->toArray();
            $submittedItemIds = [];
            
            // Processar exames
            foreach ($request->exams as $exam) {
                if (!empty($exam['id'])) {
                    // Atualizar item existente
                    $item = ExamItem::where('exam_request_id', $examRequest->id)
                        ->find($exam['id']);
                        
                    if ($item) {
                        $item->exam_name = $exam['name'];
                        $item->exam_type = $exam['type'];
                        $item->instructions = $exam['instructions'] ?? null;
                        $item->save();
                        
                        $submittedItemIds[] = $item->id;
                    }
                } else {
                    // Criar novo item
                    $item = new ExamItem();
                    $item->exam_request_id = $examRequest->id;
                    $item->exam_name = $exam['name'];
                    $item->exam_type = $exam['type'];
                    $item->instructions = $exam['instructions'] ?? null;
                    $item->status = 'pending';
                    $item->save();
                    
                    $submittedItemIds[] = $item->id;
                }
            }
            
            // Remover itens que não foram incluídos no envio
            $itemsToDelete = array_diff($currentItemIds, $submittedItemIds);
            if (count($itemsToDelete) > 0) {
                ExamItem::whereIn('id', $itemsToDelete)->delete();
            }
            
            // Registrar na trilha de auditoria
            \App\Models\AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'update',
                'model_type' => 'ExamRequest',
                'model_id' => $examRequest->id,
                'description' => 'Solicitação de exames atualizada'
            ]);
            
            // Notificar o paciente sobre a atualização
            $patient = $examRequest->patient;
            \App\Models\Notification::create([
                'user_id' => $patient->user_id,
                'type' => 'exam_request_updated',
                'title' => 'Solicitação de exames atualizada',
                'message' => 'O Dr. ' . $doctor->user->name . ' atualizou sua solicitação de exames.',
                'data' => json_encode([
                    'exam_request_id' => $examRequest->id
                ]),
                'status' => 'unread'
            ]);
            
            return redirect()->route('doctor.exam_requests.show', $examRequest->id)
                ->with('success', 'Solicitação de exames atualizada com sucesso!');
                
        } catch (\Exception $e) {
            return back()->with('error', 'Ocorreu um erro ao atualizar a solicitação de exames: ' . $e->getMessage())
                ->withInput();
        }
    }
    
    /**
     * Generate PDF of the exam request.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function generatePdf($id)
    {
        $doctor = Auth::user()->doctor;
        
        // Médico pode gerar PDF de uma solicitação se ele criou ou se atendeu o paciente
        $examRequest = ExamRequest::where(function($query) use ($doctor) {
                $query->where('doctor_id', $doctor->id)
                  ->orWhereHas('patient', function($q) use ($doctor) {
                      $q->whereHas('appointments', function($q) use ($doctor) {
                          $q->where('doctor_id', $doctor->id)
                            ->where('status', 'completed');
                      });
                  });
            })
            ->with([
                'patient.user', 
                'doctor.user',
                'doctor.specialties',
                'examItems'
            ])
            ->findOrFail($id);
            
        // Status para exibição
        $statusLabels = [
            'requested' => 'Solicitado',
            'scheduled' => 'Agendado',
            'completed' => 'Realizado',
            'cancelled' => 'Cancelado',
            'expired' => 'Expirado'
        ];
        
        // Prioridades para exibição
        $priorityLabels = [
            'normal' => 'Normal',
            'urgent' => 'Urgente',
            'emergency' => 'Emergencial'
        ];
        
        $pdf = PDF::loadView('pdfs.exam_request', [
            'examRequest' => $examRequest,
            'statusLabel' => $statusLabels[$examRequest->status] ?? 'Desconhecido',
            'priorityLabel' => $priorityLabels[$examRequest->priority] ?? 'Normal',
            'clinic' => [
                'name' => config('app.name'),
                'address' => 'Av. Exemplo, 1000 - Centro',
                'city' => 'São Paulo - SP',
                'phone' => '(11) 1234-5678'
            ]
        ]);
        
        // Registrar no log de auditoria
        \App\Models\AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'pdf_generated',
            'model_type' => 'ExamRequest',
            'model_id' => $examRequest->id,
            'description' => 'PDF de solicitação de exames gerado'
        ]);
        
        return $pdf->download('pedido_exames_' . $id . '_' . $examRequest->patient->user->name . '.pdf');
    }
    
    /**
     * Cancel an exam request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function cancel(Request $request, $id)
    {
        $request->validate([
            'cancellation_reason' => 'required|string|min:10|max:500'
        ]);
        
        $doctor = Auth::user()->doctor;
        
        // Apenas o médico que criou a solicitação pode cancelá-la
        $examRequest = ExamRequest::where('doctor_id', $doctor->id)
            ->with('examItems')
            ->findOrFail($id);
            
        // Verificar se algum exame já foi realizado
        $hasCompleted = $examRequest->examItems->contains(function($item) {
            return $item->status === 'completed';
        });
        
        if ($hasCompleted) {
            return back()->with('error', 'Não é possível cancelar uma solicitação que já possui exames realizados.');
        }
        
        try {
            // Atualizar a solicitação para cancelada
            $examRequest->status = 'cancelled';
            $examRequest->cancellation_reason = $request->cancellation_reason;
            $examRequest->cancelled_at = Carbon::now();
            $examRequest->save();
            
            // Cancelar todos os itens pendentes
            foreach ($examRequest->examItems as $item) {
                if ($item->status === 'pending') {
                    $item->status = 'cancelled';
                    $item->save();
                }
            }
            
            // Registrar na trilha de auditoria
            \App\Models\AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'cancel',
                'model_type' => 'ExamRequest',
                'model_id' => $examRequest->id,
                'description' => 'Solicitação de exames cancelada: ' . $request->cancellation_reason
            ]);
            
            // Notificar o paciente
            $patient = $examRequest->patient;
            \App\Models\Notification::create([
                'user_id' => $patient->user_id,
                'type' => 'exam_request_cancelled',
                'title' => 'Solicitação de exames cancelada',
                'message' => 'Uma solicitação de exames do Dr. ' . $doctor->user->name . ' foi cancelada.',
                'data' => json_encode([
                    'exam_request_id' => $examRequest->id,
                    'reason' => $request->cancellation_reason
                ]),
                'status' => 'unread'
            ]);
            
            // Enviar email (opcional)
            if ($patient->user->notification_settings['email_exam_cancelled'] ?? true) {
                \Mail::to($patient->user->email)->send(new \App\Mail\ExamRequestCancelled(
                    $patient->user,
                    $doctor->user,
                    $examRequest,
                    $request->cancellation_reason
                ));
            }
            
            return redirect()->route('doctor.exam_requests.show', $examRequest->id)
                ->with('success', 'Solicitação de exames cancelada com sucesso.');
                
        } catch (\Exception $e) {
            return back()->with('error', 'Ocorreu um erro ao cancelar a solicitação: ' . $e->getMessage());
        }
    }
    
    /**
     * Display the exam results index.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function examResults(Request $request)
    {
        $doctor = Auth::user()->doctor;
        
        // Buscar resultados de exames dos pacientes deste médico
        $query = ExamResult::whereHas('examItem.examRequest', function($q) use ($doctor) {
                // De exames solicitados por este médico
                $q->where('doctor_id', $doctor->id)
                  // Ou de pacientes que este médico atendeu
                  ->orWhereHas('patient', function($q) use ($doctor) {
                      $q->whereHas('appointments', function($q) use ($doctor) {
                          $q->where('doctor_id', $doctor->id)
                            ->where('status', 'completed');
                      });
                  });
            })
            ->with([
                'examItem.examRequest.patient.user',
                'examItem.examRequest.doctor.user'
            ]);
        
        // Filtro por paciente
        if ($request->has('patient_id') && $request->patient_id) {
            $query->whereHas('examItem.examRequest', function($q) use ($request) {
                $q->where('patient_id', $request->patient_id);
            });
        }
        
        // Filtro por período
        if ($request->has('start_date') && $request->start_date) {
            $query->where('result_date', '>=', $request->start_date);
        }
        
        if ($request->has('end_date') && $request->end_date) {
            $query->where('result_date', '<=', Carbon::parse($request->end_date)->endOfDay());
        }
        
        // Filtro por status
        if ($request->has('status') && $request->status != 'all') {
            $query->where('status', $request->status);
        }
        
        // Filtro por tipo de exame
        if ($request->has('exam_type') && $request->exam_type) {
            $query->whereHas('examItem', function($q) use ($request) {
                $q->where('exam_type', $request->exam_type);
            });
        }
        
        // Filtro por texto
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('notes', 'like', '%' . $search . '%')
                  ->orWhere('result_summary', 'like', '%' . $search . '%')
                  ->orWhereHas('examItem', function($q) use ($search) {
                      $q->where('exam_name', 'like', '%' . $search . '%');
                  })
                  ->orWhereHas('examItem.examRequest.patient.user', function($q) use ($search) {
                      $q->where('name', 'like', '%' . $search . '%');
                  });
            });
        }
        
        // Ordenação
        $sortBy = $request->sort_by ?? 'result_date';
        $sortDirection = $request->sort_direction ?? 'desc';
        
        $examResults = $query->orderBy($sortBy, $sortDirection)
            ->paginate(15)
            ->withQueryString();
        
        // Buscar pacientes do médico para filtro
        $patients = Patient::whereHas('appointments', function($q) use ($doctor) {
            $q->where('doctor_id', $doctor->id)
              ->where('status', 'completed');
        })
        ->with('user')
        ->orderBy('user_id')
        ->get();
        
        // Status para exibição
        $statusOptions = [
            'normal' => 'Normal',
            'altered' => 'Alterado',
            'inconclusive' => 'Inconclusivo',
            'pending_analysis' => 'Pendente de análise'
        ];
        
        // Tipos de exame
        $examTypes = $this->getCommonExamTypes();
        
        return view('doctor.exam_requests.results_index', [
            'examResults' => $examResults,
            'patients' => $patients,
            'statusOptions' => $statusOptions,
            'examTypes' => $examTypes,
            'filters' => $request->only(['patient_id', 'start_date', 'end_date', 'status', 'exam_type', 'search', 'sort_by', 'sort_direction'])
        ]);
    }
    
    /**
     * Display details of an exam result.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function showExamResult($id)
    {
        $doctor = Auth::user()->doctor;
        
        // Buscar o resultado do exame
        $examResult = ExamResult::whereHas('examItem.examRequest', function($q) use ($doctor) {
                // De exames solicitados por este médico
                $q->where('doctor_id', $doctor->id)
                  // Ou de pacientes que este médico atendeu
                  ->orWhereHas('patient', function($q) use ($doctor) {
                      $q->whereHas('appointments', function($q) use ($doctor) {
                          $q->where('doctor_id', $doctor->id)
                            ->where('status', 'completed');
                      });
                  });
            })
            ->with([
                'examItem.examRequest.patient.user',
                'examItem.examRequest.doctor.user',
                'files'
            ])
            ->findOrFail($id);
        
        // Status para exibição
        $statusLabels = [
            'normal' => 'Normal',
            'altered' => 'Alterado',
            'inconclusive' => 'Inconclusivo',
            'pending_analysis' => 'Pendente de análise'
        ];
        
        // Verificar se este exame foi solicitado por este médico
        $isRequester = $examResult->examItem->examRequest->doctor_id === $doctor->id;
        
        // Exames anteriores do mesmo tipo para este paciente
        $patientId = $examResult->examItem->examRequest->patient_id;
        $examName = $examResult->examItem->exam_name;
        
        $previousExams = ExamResult::whereHas('examItem', function($q) use ($examName, $patientId) {
                $q->where('exam_name', $examName)
                  ->whereHas('examRequest', function($q) use ($patientId) {
                      $q->where('patient_id', $patientId);
                  });
            })
            ->where('id', '!=', $id)
            ->with('examItem.examRequest')
            ->orderBy('result_date', 'desc')
            ->limit(5)
            ->get();
        
        return view('doctor.exam_requests.show_result', [
            'examResult' => $examResult,
            'statusLabel' => $statusLabels[$examResult->status] ?? 'Desconhecido',
            'isRequester' => $isRequester,
            'previousExams' => $previousExams
        ]);
    }
    
    /**
     * Add analysis notes to an exam result.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function addAnalysisNotes(Request $request, $id)
    {
        $request->validate([
            'doctor_notes' => 'required|string|max:2000'
        ]);
        
        $doctor = Auth::user()->doctor;
        
        // Buscar o resultado do exame
        $examResult = ExamResult::whereHas('examItem.examRequest', function($q) use ($doctor) {
                // De exames solicitados por este médico
                $q->where('doctor_id', $doctor->id)
                  // Ou de pacientes que este médico atendeu
                  ->orWhereHas('patient', function($q) use ($doctor) {
                      $q->whereHas('appointments', function($q) use ($doctor) {
                          $q->where('doctor_id', $doctor->id)
                            ->where('status', 'completed');
                      });
                  });
            })
            ->findOrFail($id);
        
        try {
            // Adicionar notas do médico
            $examResult->doctor_notes = $request->doctor_notes;
            $examResult->analysis_date = Carbon::now();
            $examResult->doctor_id = $doctor->id;
            $examResult->save();
            
            // Registrar na trilha de auditoria
            \App\Models\AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'analyze',
                'model_type' => 'ExamResult',
                'model_id' => $examResult->id,
                'description' => 'Notas de análise adicionadas ao resultado de exame'
            ]);
            
            return redirect()->route('doctor.exam_requests.show_result', $examResult->id)
                ->with('success', 'Notas de análise adicionadas com sucesso!');
                
        } catch (\Exception $e) {
            return back()->with('error', 'Ocorreu um erro ao adicionar as notas: ' . $e->getMessage());
        }
    }
    
    /**
     * Download a file from an exam result.
     *
     * @param  int  $resultId
     * @param  int  $fileId
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function downloadResultFile($resultId, $fileId)
    {
        $doctor = Auth::user()->doctor;
        
        // Buscar o resultado do exame
        $examResult = ExamResult::whereHas('examItem.examRequest', function($q) use ($doctor) {
                // De exames solicitados por este médico
                $q->where('doctor_id', $doctor->id)
                  // Ou de pacientes que este médico atendeu
                  ->orWhereHas('patient', function($q) use ($doctor) {
                      $q->whereHas('appointments', function($q) use ($doctor) {
                          $q->where('doctor_id', $doctor->id)
                            ->where('status', 'completed');
                      });
                  });
            })
            ->findOrFail($resultId);
        
        // Buscar o arquivo
        $file = $examResult->files()->findOrFail($fileId);
        
        // Verificar se o arquivo existe
        if (!Storage::disk('public')->exists($file->file_path)) {
            return back()->with('error', 'Arquivo não encontrado no servidor.');
        }
        
        // Registrar no log de auditoria
        \App\Models\AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'download',
            'model_type' => 'ExamResultFile',
            'model_id' => $file->id,
            'description' => 'Download de arquivo de resultado de exame'
        ]);
        
        return response()->download(
            storage_path('app/public/' . $file->file_path),
            $file->file_name
        );
    }
    
    /**
     * Get common exam types for dropdown lists.
     *
     * @return array
     */
    private function getCommonExamTypes()
    {
        return [
            'blood' => 'Sangue',
            'urine' => 'Urina',
            'stool' => 'Fezes',
            'imaging' => 'Imagem',
            'ecg' => 'Eletrocardiograma',
            'endoscopy' => 'Endoscopia',
            'biopsy' => 'Biópsia',
            'covid' => 'COVID-19',
            'allergy' => 'Alergia',
            'hormone' => 'Hormonais',
            'genetic' => 'Genéticos',
            'other' => 'Outro'
        ];
    }
}