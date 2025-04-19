<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\MedicalRecord;
use App\Models\Patient;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use PDF;

class CertificateController extends Controller
{
    /**
     * Display a listing of certificates.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $doctor = Auth::user()->doctor;
        
        // Base query
        $query = Certificate::whereHas('medicalRecord', function($q) use ($doctor) {
            $q->where('doctor_id', $doctor->id);
        })->with(['medicalRecord.patient.user']);
        
        // Filtro por paciente
        if ($request->has('patient_id') && $request->patient_id) {
            $query->whereHas('medicalRecord', function($q) use ($request) {
                $q->where('patient_id', $request->patient_id);
            });
        }
        
        // Filtro por tipo
        if ($request->has('type') && $request->type != 'all') {
            $query->where('type', $request->type);
        }
        
        // Filtro por período de validade
        if ($request->has('start_date') && $request->start_date) {
            $query->where('start_date', '>=', $request->start_date);
        }
        
        if ($request->has('end_date') && $request->end_date) {
            $query->where('end_date', '<=', Carbon::parse($request->end_date)->endOfDay());
        }
        
        // Filtro por CID
        if ($request->has('cid') && $request->cid) {
            $query->where('cid', 'like', '%' . $request->cid . '%');
        }
        
        // Filtro por texto
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('text', 'like', '%' . $search . '%')
                  ->orWhere('cid', 'like', '%' . $search . '%')
                  ->orWhereHas('medicalRecord.patient.user', function($q) use ($search) {
                      $q->where('name', 'like', '%' . $search . '%');
                  });
            });
        }
        
        // Ordenação
        $sortBy = $request->sort_by ?? 'created_at';
        $sortDirection = $request->sort_direction ?? 'desc';
        
        $certificates = $query->orderBy($sortBy, $sortDirection)
            ->paginate(15)
            ->withQueryString();
        
        // Buscar pacientes do médico para filtro
        $patients = Patient::whereHas('medicalRecords', function($q) use ($doctor) {
            $q->where('doctor_id', $doctor->id);
        })
        ->with('user')
        ->orderBy('user_id')
        ->get();
        
        // Tipos de atestado para exibição
        $certificateTypes = [
            'sick_leave' => 'Atestado Médico',
            'medical_certificate' => 'Certificado Médico',
            'other' => 'Outro'
        ];
        
        return view('doctor.certificates.index', [
            'certificates' => $certificates,
            'patients' => $patients,
            'certificateTypes' => $certificateTypes,
            'filters' => $request->only(['patient_id', 'type', 'start_date', 'end_date', 'cid', 'search', 'sort_by', 'sort_direction'])
        ]);
    }
    
    /**
     * Show the form for creating a new certificate.
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
                    'notes' => 'Prontuário criado automaticamente para emissão de atestado'
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
                    ->with('error', 'Você só pode emitir atestados para pacientes que já atendeu.');
            }
            
            // Buscar o prontuário mais recente deste paciente feito por este médico
            $medicalRecord = MedicalRecord::where('doctor_id', $doctor->id)
                ->where('patient_id', $patientId)
                ->orderBy('created_at', 'desc')
                ->first();
        } else {
            // Se nem paciente nem prontuário forem fornecidos, redirecionar para selecionar
            return redirect()->route('doctor.certificates.select_patient');
        }
        
        // Tipos de atestado
        $certificateTypes = [
            'sick_leave' => 'Atestado Médico',
            'medical_certificate' => 'Certificado Médico',
            'other' => 'Outro'
        ];
        
        // Buscar CIDs mais usados por este médico
        $commonCids = Certificate::whereHas('medicalRecord', function($q) use ($doctor) {
                $q->where('doctor_id', $doctor->id);
            })
            ->select('cid')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('cid')
            ->orderByRaw('COUNT(*) DESC')
            ->limit(10)
            ->get();
        
        // Modelos de texto para diferentes tipos de atestado
        $templates = [
            'sick_leave' => 'Atesto para os devidos fins que o(a) paciente [NOME] esteve sob meus cuidados médicos no dia [DATA], necessitando afastar-se de suas atividades por [DIAS] dias a partir desta data.',
            'medical_certificate' => 'Certifico para os devidos fins que o(a) paciente [NOME] compareceu a esta unidade de saúde na presente data para consulta médica.',
            'other' => 'Atesto para os devidos fins que o(a) paciente [NOME]...'
        ];
        
        return view('doctor.certificates.create', [
            'patient' => $patient,
            'medicalRecord' => $medicalRecord,
            'certificateTypes' => $certificateTypes,
            'commonCids' => $commonCids,
            'templates' => $templates
        ]);
    }
    
    /**
     * Display form to select a patient for a new certificate.
     *
     * @return \Illuminate\View\View
     */
    public function selectPatient()
    {
        $doctor = Auth::user()->doctor;
        
        // Buscar pacientes recentemente atendidos pelo médico
        $recentPatients = Patient::whereHas('medicalRecords', function($q) use ($doctor) {
            $q->where('doctor_id', $doctor->id)
              ->orderBy('created_at', 'desc');
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
        
        return view('doctor.certificates.select_patient', [
            'recentPatients' => $recentPatients,
            'todaysAppointments' => $todaysAppointments
        ]);
    }
    
    /**
     * Store a newly created certificate.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'medical_record_id' => 'required|exists:medical_records,id',
            'type' => 'required|in:sick_leave,medical_certificate,other',
            'text' => 'required|string|min:10|max:2000',
            'start_date' => 'required|date|before_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'cid' => 'nullable|string|max:10',
            'observations' => 'nullable|string|max:1000',
            'days_off' => 'required|integer|min:0|max:365'
        ]);
        
        $doctor = Auth::user()->doctor;
        
        // Verificar se o médico tem acesso ao prontuário
        $medicalRecord = MedicalRecord::where('doctor_id', $doctor->id)
            ->findOrFail($request->medical_record_id);
        
        try {
            // Criar o atestado
            $certificate = new Certificate();
            $certificate->medical_record_id = $medicalRecord->id;
            $certificate->type = $request->type;
            $certificate->text = $request->text;
            $certificate->start_date = $request->start_date;
            $certificate->end_date = $request->end_date;
            $certificate->cid = $request->cid;
            $certificate->observations = $request->observations;
            $certificate->days_off = $request->days_off;
            $certificate->save();
            
            // Registrar na trilha de auditoria
            \App\Models\AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'create',
                'model_type' => 'Certificate',
                'model_id' => $certificate->id,
                'description' => 'Atestado médico criado'
            ]);
            
            // Se existir uma solicitação de atestado relacionada, atualizá-la
            $certificateRequest = \App\Models\Notification::where('type', 'certificate_request')
                ->where('status', 'pending')
                ->whereJsonContains('data->patient_id', $medicalRecord->patient_id)
                ->first();
                
            if ($certificateRequest) {
                $certificateRequest->status = 'completed';
                $certificateRequest->save();
                
                // Notificar o paciente
                $patient = $medicalRecord->patient;
                \App\Models\Notification::create([
                    'user_id' => $patient->user_id,
                    'type' => 'certificate_issued',
                    'title' => 'Atestado médico emitido',
                    'message' => 'O Dr. ' . $doctor->user->name . ' emitiu um atestado para você.',
                    'data' => json_encode([
                        'certificate_id' => $certificate->id
                    ]),
                    'status' => 'unread'
                ]);
                
                // Enviar email (opcional)
                if ($patient->user->notification_settings['email_certificate_issued'] ?? true) {
                    \Mail::to($patient->user->email)->send(new \App\Mail\CertificateIssued(
                        $patient->user,
                        $doctor->user,
                        $certificate
                    ));
                }
            }
            
            return redirect()->route('doctor.certificates.show', $certificate->id)
                ->with('success', 'Atestado criado com sucesso!');
                
        } catch (\Exception $e) {
            return back()->with('error', 'Ocorreu um erro ao criar o atestado: ' . $e->getMessage())
                ->withInput();
        }
    }
    
    /**
     * Display the specified certificate.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function show($id)
    {
        $doctor = Auth::user()->doctor;
        
        // Buscar o atestado
        $certificate = Certificate::whereHas('medicalRecord', function($q) use ($doctor) {
                // O médico pode ver atestados feitos por ele
                $q->where('doctor_id', $doctor->id)
                  // Ou atestados de pacientes que ele já atendeu
                  ->orWhereHas('patient', function($q) use ($doctor) {
                      $q->whereHas('appointments', function($q) use ($doctor) {
                          $q->where('doctor_id', $doctor->id)
                            ->where('status', 'completed');
                      });
                  });
            })
            ->with([
                'medicalRecord.patient.user', 
                'medicalRecord.doctor.user',
                'medicalRecord.doctor.specialties'
            ])
            ->findOrFail($id);
            
        // Verificar se o atestado está ativo
        $isActive = Carbon::parse($certificate->end_date)->isFuture() || Carbon::parse($certificate->end_date)->isToday();
        
        // Tipos de atestado para exibição
        $certificateTypes = [
            'sick_leave' => 'Atestado Médico',
            'medical_certificate' => 'Certificado Médico',
            'other' => 'Outro'
        ];
        
        return view('doctor.certificates.show', [
            'certificate' => $certificate,
            'isActive' => $isActive,
            'certificateType' => $certificateTypes[$certificate->type] ?? 'Desconhecido',
            'isOwner' => $certificate->medicalRecord->doctor_id === $doctor->id
        ]);
    }
    
    /**
     * Show the form for editing the specified certificate.
     *
     * @param  int  $id
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function edit($id)
    {
        $doctor = Auth::user()->doctor;
        
        // Apenas o médico que criou o atestado pode editá-lo
        $certificate = Certificate::whereHas('medicalRecord', function($q) use ($doctor) {
                $q->where('doctor_id', $doctor->id);
            })
            ->with(['medicalRecord.patient.user'])
            ->findOrFail($id);
            
        // Verificar se o atestado foi criado há pouco tempo (menos de 24 horas)
        $editDeadline = Carbon::now()->subHours(24);
        if ($certificate->created_at->lt($editDeadline)) {
            return redirect()->route('doctor.certificates.show', $certificate->id)
                ->with('error', 'Não é possível editar um atestado após 24 horas da sua criação por questões de segurança.');
        }
        
        // Tipos de atestado
        $certificateTypes = [
            'sick_leave' => 'Atestado Médico',
            'medical_certificate' => 'Certificado Médico',
            'other' => 'Outro'
        ];
        
        // Buscar CIDs mais usados por este médico
        $commonCids = Certificate::whereHas('medicalRecord', function($q) use ($doctor) {
                $q->where('doctor_id', $doctor->id);
            })
            ->select('cid')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('cid')
            ->orderByRaw('COUNT(*) DESC')
            ->limit(10)
            ->get();
        
        return view('doctor.certificates.edit', [
            'certificate' => $certificate,
            'certificateTypes' => $certificateTypes,
            'commonCids' => $commonCids
        ]);
    }
    
    /**
     * Update the specified certificate.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'type' => 'required|in:sick_leave,medical_certificate,other',
            'text' => 'required|string|min:10|max:2000',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'cid' => 'nullable|string|max:10',
            'observations' => 'nullable|string|max:1000',
            'days_off' => 'required|integer|min:0|max:365'
        ]);
        
        $doctor = Auth::user()->doctor;
        
        // Apenas o médico que criou o atestado pode editá-lo
        $certificate = Certificate::whereHas('medicalRecord', function($q) use ($doctor) {
                $q->where('doctor_id', $doctor->id);
            })
            ->findOrFail($id);
            
        // Verificar se o atestado foi criado há pouco tempo (menos de 24 horas)
        $editDeadline = Carbon::now()->subHours(24);
        if ($certificate->created_at->lt($editDeadline)) {
            return redirect()->route('doctor.certificates.show', $certificate->id)
                ->with('error', 'Não é possível editar um atestado após 24 horas da sua criação por questões de segurança.');
        }
        
        try {
            // Atualizar o atestado
            $certificate->type = $request->type;
            $certificate->text = $request->text;
            $certificate->start_date = $request->start_date;
            $certificate->end_date = $request->end_date;
            $certificate->cid = $request->cid;
            $certificate->observations = $request->observations;
            $certificate->days_off = $request->days_off;
            $certificate->save();
            
            // Registrar na trilha de auditoria
            \App\Models\AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'update',
                'model_type' => 'Certificate',
                'model_id' => $certificate->id,
                'description' => 'Atestado médico atualizado'
            ]);
            
            return redirect()->route('doctor.certificates.show', $certificate->id)
                ->with('success', 'Atestado atualizado com sucesso!');
                
        } catch (\Exception $e) {
            return back()->with('error', 'Ocorreu um erro ao atualizar o atestado: ' . $e->getMessage())
                ->withInput();
        }
    }
    
    /**
     * Generate PDF of the certificate.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function generatePdf($id)
    {
        $doctor = Auth::user()->doctor;
        
        // Médico pode gerar PDF de um atestado se ele criou ou se atendeu o paciente
        $certificate = Certificate::whereHas('medicalRecord', function($q) use ($doctor) {
                $q->where('doctor_id', $doctor->id)
                  ->orWhereHas('patient', function($q) use ($doctor) {
                      $q->whereHas('appointments', function($q) use ($doctor) {
                          $q->where('doctor_id', $doctor->id)
                            ->where('status', 'completed');
                      });
                  });
            })
            ->with([
                'medicalRecord.patient.user', 
                'medicalRecord.doctor.user',
                'medicalRecord.doctor.specialties'
            ])
            ->findOrFail($id);
            
        // Tipos de atestado para exibição
        $certificateTypes = [
            'sick_leave' => 'Atestado Médico',
            'medical_certificate' => 'Certificado Médico',
            'other' => 'Outro'
        ];
        
        $pdf = PDF::loadView('pdfs.certificate', [
            'certificate' => $certificate,
            'patient' => $certificate->medicalRecord->patient,
            'doctor' => $certificate->medicalRecord->doctor,
            'certificateType' => $certificateTypes[$certificate->type] ?? 'Documento Médico',
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
            'model_type' => 'Certificate',
            'model_id' => $certificate->id,
            'description' => 'PDF de atestado gerado'
        ]);
        
        return $pdf->download('atestado_' . $id . '_' . $certificate->medicalRecord->patient->user->name . '.pdf');
    }
    
    /**
     * Extend an existing certificate.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function extend($id)
    {
        $doctor = Auth::user()->doctor;
        
        $certificate = Certificate::whereHas('medicalRecord', function($q) use ($doctor) {
                // O médico pode estender atestados feitos por ele
                $q->where('doctor_id', $doctor->id)
                  // Ou atestados de pacientes que ele já atendeu
                  ->orWhereHas('patient', function($q) use ($doctor) {
                      $q->whereHas('appointments', function($q) use ($doctor) {
                          $q->where('doctor_id', $doctor->id)
                            ->where('status', 'completed');
                      });
                  });
            })
            ->with(['medicalRecord.patient.user'])
            ->findOrFail($id);
            
        // Buscar o prontuário mais recente deste paciente feito por este médico
        $medicalRecord = MedicalRecord::where('doctor_id', $doctor->id)
            ->where('patient_id', $certificate->medicalRecord->patient_id)
            ->orderBy('created_at', 'desc')
            ->first();
            
        if (!$medicalRecord || $certificate->medicalRecord->doctor_id !== $doctor->id) {
            // Se não houver um prontuário recente ou se o médico não é o autor do atestado original, criar um novo
            $medicalRecord = new MedicalRecord();
            $medicalRecord->doctor_id = $doctor->id;
            $medicalRecord->patient_id = $certificate->medicalRecord->patient_id;
            $medicalRecord->record_type = 'follow_up';
            $medicalRecord->reason = 'Extensão de atestado médico';
            $medicalRecord->notes = 'Prontuário criado automaticamente para extensão de atestado.';
            $medicalRecord->save();
        }
        
        // Verificar se o médico atual foi quem criou o atestado original
        $isOriginalDoctor = $certificate->medicalRecord->doctor_id === $doctor->id;
            
        // Tipos de atestado
        $certificateTypes = [
            'sick_leave' => 'Atestado Médico',
            'medical_certificate' => 'Certificado Médico',
            'other' => 'Outro'
        ];
        
        // Buscar CIDs mais usados por este médico
        $commonCids = Certificate::whereHas('medicalRecord', function($q) use ($doctor) {
                $q->where('doctor_id', $doctor->id);
            })
            ->select('cid')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('cid')
            ->orderByRaw('COUNT(*) DESC')
            ->limit(10)
            ->get();
        
        return view('doctor.certificates.extend', [
            'certificate' => $certificate,
            'medicalRecord' => $medicalRecord,
            'isOriginalDoctor' => $isOriginalDoctor,
            'certificateTypes' => $certificateTypes,
            'commonCids' => $commonCids
        ]);
    }
    
    /**
     * Store an extended certificate.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeExtended(Request $request, $id)
    {
        $request->validate([
            'medical_record_id' => 'required|exists:medical_records,id',
            'type' => 'required|in:sick_leave,medical_certificate,other',
            'text' => 'required|string|min:10|max:2000',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'cid' => 'nullable|string|max:10',
            'observations' => 'nullable|string|max:1000',
            'days_off' => 'required|integer|min:1|max:365',
            'extension_reason' => 'required|string|min:10|max:500'
        ]);
        
        $doctor = Auth::user()->doctor;
        
        // Verificar se o médico tem acesso ao prontuário
        $medicalRecord = MedicalRecord::where('doctor_id', $doctor->id)
            ->findOrFail($request->medical_record_id);
            
        // Buscar o atestado original
        $originalCertificate = Certificate::findOrFail($id);
        
        try {
            // Criar o novo atestado
            $certificate = new Certificate();
            $certificate->medical_record_id = $medicalRecord->id;
            $certificate->type = $request->type;
            $certificate->text = $request->text;
            $certificate->start_date = $request->start_date;
            $certificate->end_date = $request->end_date;
            $certificate->cid = $request->cid;
            $certificate->observations = $request->observations;
            $certificate->days_off = $request->days_off;
            $certificate->extension_of_id = $originalCertificate->id;
            $certificate->extension_reason = $request->extension_reason;
            $certificate->save();
            
            // Registrar na trilha de auditoria
            \App\Models\AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'extend',
                'model_type' => 'Certificate',
                'model_id' => $certificate->id,
                'description' => 'Atestado médico estendido (Original: #' . $originalCertificate->id . ')'
            ]);
            
            // Se existir uma solicitação de extensão relacionada, atualizá-la
            $extensionRequest = \App\Models\Notification::where('type', 'certificate_extension_request')
                ->where('status', 'pending')
                ->whereJsonContains('data->certificate_id', $originalCertificate->id)
                ->first();
                
            if ($extensionRequest) {
                $extensionRequest->status = 'completed';
                $extensionRequest->save();
                
                // Notificar o paciente
                $patient = $medicalRecord->patient;
                \App\Models\Notification::create([
                    'user_id' => $patient->user_id,
                    'type' => 'certificate_extended',
                    'title' => 'Atestado médico estendido',
                    'message' => 'Sua solicitação de extensão de atestado foi atendida pelo Dr. ' . $doctor->user->name,
                    'data' => json_encode([
                        'certificate_id' => $certificate->id
                    ]),
                    'status' => 'unread'
                ]);
                
                // Enviar email (opcional)
                if ($patient->user->notification_settings['email_certificate_extended'] ?? true) {
                    \Mail::to($patient->user->email)->send(new \App\Mail\CertificateExtended(
                        $patient->user,
                        $doctor->user,
                        $certificate
                    ));
                }
            }
            
            return redirect()->route('doctor.certificates.show', $certificate->id)
                ->with('success', 'Atestado estendido com sucesso!');
                
        } catch (\Exception $e) {
            return back()->with('error', 'Ocorreu um erro ao estender o atestado: ' . $e->getMessage())
                ->withInput();
        }
    }
    
    /**
     * Cancel a certificate.
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
        
        // Apenas o médico que criou o atestado pode cancelá-lo
        $certificate = Certificate::whereHas('medicalRecord', function($q) use ($doctor) {
                $q->where('doctor_id', $doctor->id);
            })
            ->findOrFail($id);
            
        try {
            // Forçar expiração do atestado
            $certificate->end_date = Carbon::now()->subDay();
            $certificate->is_cancelled = true;
            $certificate->cancellation_reason = $request->cancellation_reason;
            $certificate->cancelled_at = Carbon::now();
            $certificate->save();
            
            // Registrar na trilha de auditoria
            \App\Models\AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'cancel',
                'model_type' => 'Certificate',
                'model_id' => $certificate->id,
                'description' => 'Atestado médico cancelado: ' . $request->cancellation_reason
            ]);
            
            // Notificar o paciente
            $patient = $certificate->medicalRecord->patient;
            \App\Models\Notification::create([
                'user_id' => $patient->user_id,
                'type' => 'certificate_cancelled',
                'title' => 'Atestado cancelado',
                'message' => 'Um atestado emitido pelo Dr. ' . $doctor->user->name . ' foi cancelado.',
                'data' => json_encode([
                    'certificate_id' => $certificate->id,
                    'reason' => $request->cancellation_reason
                ]),
                'status' => 'unread'
            ]);
            
            // Enviar email (opcional)
            if ($patient->user->notification_settings['email_certificate_cancelled'] ?? true) {
                \Mail::to($patient->user->email)->send(new \App\Mail\CertificateCancelled(
                    $patient->user,
                    $doctor->user,
                    $certificate,
                    $request->cancellation_reason
                ));
            }
            
            return redirect()->route('doctor.certificates.show', $certificate->id)
                ->with('success', 'Atestado cancelado com sucesso.');
                
        } catch (\Exception $e) {
            return back()->with('error', 'Ocorreu um erro ao cancelar o atestado: ' . $e->getMessage());
        }
    }
    
    /**
     * Display a form for handling certificate requests.
     *
     * @param  int  $notificationId
     * @return \Illuminate\View\View
     */
    public function handleCertificateRequest($notificationId)
    {
        $doctor = Auth::user()->doctor;
        
        // Buscar a notificação
        $notification = \App\Models\Notification::where('id', $notificationId)
            ->where('type', 'certificate_request')
            ->where(function($q) use ($doctor) {
                $q->where('user_id', $doctor->user_id)
                  ->orWhere('recipient_role', 'doctor');
            })
            ->first();
            
        if (!$notification) {
            return redirect()->route('doctor.dashboard')
                ->with('error', 'Solicitação de atestado não encontrada ou não autorizada.');
        }
        
        // Marcar como lida
        if ($notification->status == 'unread') {
            $notification->status = 'read';
            $notification->save();
        }
        
        // Extrair dados
        $data = json_decode($notification->data, true);
        $patientId = $data['patient_id'] ?? null;
        $reason = $data['reason'] ?? 'Não especificado';
        $startDate = $data['start_date'] ?? date('Y-m-d');
        $daysNeeded = $data['days_needed'] ?? 1;
        
        if (!$patientId) {
            return redirect()->route('doctor.dashboard')
                ->with('error', 'Dados incompletos na solicitação de atestado.');
        }
        
        // Buscar o paciente
        $patient = Patient::with('user')->findOrFail($patientId);
        
        // Verificar se o médico já atendeu este paciente
        $hasConsulted = MedicalRecord::where('doctor_id', $doctor->id)
            ->where('patient_id', $patientId)
            ->exists();
            
        if (!$hasConsulted) {
            return redirect()->route('doctor.dashboard')
                ->with('error', 'Você não pode emitir atestados para pacientes que nunca atendeu.');
        }
        
        // Buscar o prontuário mais recente deste paciente feito por este médico
        $medicalRecord = MedicalRecord::where('doctor_id', $doctor->id)
            ->where('patient_id', $patientId)
            ->orderBy('created_at', 'desc')
            ->first();
            
        // Tipos de atestado
        $certificateTypes = [
            'sick_leave' => 'Atestado Médico',
            'medical_certificate' => 'Certificado Médico',
            'other' => 'Outro'
        ];
        
        // Buscar CIDs mais usados por este médico
        $commonCids = Certificate::whereHas('medicalRecord', function($q) use ($doctor) {
                $q->where('doctor_id', $doctor->id);
            })
            ->select('cid')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('cid')
            ->orderByRaw('COUNT(*) DESC')
            ->limit(10)
            ->get();
            
        // Modelo de texto para o atestado
        $suggestionText = 'Atesto para os devidos fins que o(a) paciente ' . $patient->user->name . 
                          ' necessita de afastamento de suas atividades por ' . $daysNeeded . 
                          ' dias a partir de ' . Carbon::parse($startDate)->format('d/m/Y') . 
                          ' pelo seguinte motivo: ' . $reason;
        
        $endDate = Carbon::parse($startDate)->addDays($daysNeeded)->format('Y-m-d');
        
        return view('doctor.certificates.handle_request', [
            'notification' => $notification,
            'patient' => $patient,
            'medicalRecord' => $medicalRecord,
            'certificateTypes' => $certificateTypes,
            'commonCids' => $commonCids,
            'requestReason' => $reason,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'daysNeeded' => $daysNeeded,
            'suggestionText' => $suggestionText
        ]);
    }
}