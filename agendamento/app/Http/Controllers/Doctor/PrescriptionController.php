<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\MedicalRecord;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use PDF;

class PrescriptionController extends Controller
{
    /**
     * Display a listing of the prescriptions.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $doctor = Auth::user()->doctor;
        
        // Base query
        $query = Prescription::whereHas('medicalRecord', function($q) use ($doctor) {
            $q->where('doctor_id', $doctor->id);
        })->with(['medicalRecord.patient.user']);
        
        // Filtro por paciente
        if ($request->has('patient_id') && $request->patient_id) {
            $query->whereHas('medicalRecord', function($q) use ($request) {
                $q->where('patient_id', $request->patient_id);
            });
        }
        
        // Filtro por data
        if ($request->has('start_date') && $request->start_date) {
            $query->where('issue_date', '>=', $request->start_date);
        }
        
        if ($request->has('end_date') && $request->end_date) {
            $query->where('issue_date', '<=', Carbon::parse($request->end_date)->endOfDay());
        }
        
        // Filtro por status (expirada/ativa)
        if ($request->has('status') && in_array($request->status, ['active', 'expired'])) {
            if ($request->status === 'active') {
                $query->where('expiration_date', '>=', Carbon::now());
            } else {
                $query->where('expiration_date', '<', Carbon::now());
            }
        }
        
        // Filtro por medicamento
        if ($request->has('medication') && $request->medication) {
            $query->whereHas('prescriptionItems', function($q) use ($request) {
                $q->where('medication', 'like', '%' . $request->medication . '%');
            });
        }
        
        // Ordenação
        $sortBy = $request->sort_by ?? 'issue_date';
        $sortDirection = $request->sort_direction ?? 'desc';
        
        $prescriptions = $query->orderBy($sortBy, $sortDirection)
            ->paginate(15)
            ->withQueryString();
        
        // Buscar pacientes do médico para filtro
        $patients = Patient::whereHas('medicalRecords', function($q) use ($doctor) {
            $q->where('doctor_id', $doctor->id);
        })
        ->with('user')
        ->orderBy('user_id')
        ->get();
            
        // Buscar os medicamentos mais comuns prescritos por este médico
        $commonMedications = PrescriptionItem::whereHas('prescription.medicalRecord', function($q) use ($doctor) {
                $q->where('doctor_id', $doctor->id);
            })
            ->select('medication')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('medication')
            ->orderByRaw('COUNT(*) DESC')
            ->limit(10)
            ->get();
        
        return view('doctor.prescriptions.index', [
            'prescriptions' => $prescriptions,
            'patients' => $patients,
            'commonMedications' => $commonMedications,
            'filters' => $request->only(['patient_id', 'start_date', 'end_date', 'status', 'medication', 'sort_by', 'sort_direction'])
        ]);
    }
    
    /**
     * Show the form for creating a new prescription.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function create(Request $request)
    {
        $doctor = Auth::user()->doctor;
        $medicalRecordId = $request->query('medical_record_id');
        $patientId = $request->query('patient_id');
        
        // Se um ID de prontuário médico for fornecido, buscar o prontuário
        if ($medicalRecordId) {
            $medicalRecord = MedicalRecord::where(function($query) use ($doctor) {
                    $query->where('doctor_id', $doctor->id)
                        ->orWhereHas('patient', function($q) use ($doctor) {
                            $q->whereHas('appointments', function($q) use ($doctor) {
                                $q->where('doctor_id', $doctor->id)
                                  ->where('status', 'completed');
                            });
                        });
                })
                ->with('patient.user')
                ->findOrFail($medicalRecordId);
                
            $patient = $medicalRecord->patient;
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
                    ->with('error', 'Você só pode prescrever medicamentos para pacientes que já atendeu.');
            }
            
            // Buscar o prontuário mais recente deste paciente feito por este médico
            $medicalRecord = MedicalRecord::where('doctor_id', $doctor->id)
                ->where('patient_id', $patientId)
                ->orderBy('created_at', 'desc')
                ->first();
        } else {
            // Se nem paciente nem prontuário forem fornecidos, redirecionar para selecionar
            return redirect()->route('doctor.prescriptions.select_patient');
        }
        
        // Buscar medicamentos frequentemente prescritos por este médico
        $frequentMedications = PrescriptionItem::whereHas('prescription.medicalRecord', function($q) use ($doctor) {
                $q->where('doctor_id', $doctor->id);
            })
            ->select('medication')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('medication')
            ->orderByRaw('COUNT(*) DESC')
            ->limit(15)
            ->pluck('medication');
            
        // Buscar histórico de prescrições do paciente
        $patientPrescriptions = Prescription::whereHas('medicalRecord', function($q) use ($patient) {
                $q->where('patient_id', $patient->id);
            })
            ->with(['medicalRecord.doctor.user', 'prescriptionItems'])
            ->orderBy('issue_date', 'desc')
            ->limit(5)
            ->get();
        
        return view('doctor.prescriptions.create', [
            'patient' => $patient,
            'medicalRecord' => $medicalRecord ?? null,
            'frequentMedications' => $frequentMedications,
            'patientPrescriptions' => $patientPrescriptions
        ]);
    }
    
    /**
     * Display form to select a patient for a new prescription.
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
        
        return view('doctor.prescriptions.select_patient', [
            'recentPatients' => $recentPatients
        ]);
    }
    
    /**
     * Store a newly created prescription.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'medical_record_id' => 'required|exists:medical_records,id',
            'issue_date' => 'required|date|before_or_equal:today',
            'expiration_date' => 'required|date|after_or_equal:issue_date',
            'instructions' => 'nullable|string|max:2000',
            'use_type' => 'required|in:continuous,prescribed_period,as_needed',
            'medications' => 'required|array|min:1',
            'medications.*.medication' => 'required|string|max:255',
            'medications.*.dosage' => 'required|string|max:255',
            'medications.*.frequency' => 'required|string|max:255',
            'medications.*.duration' => 'nullable|string|max:255',
            'medications.*.instructions' => 'nullable|string|max:500',
            'notes' => 'nullable|string|max:1000',
        ]);
        
        $doctor = Auth::user()->doctor;
        
        // Verificar se o médico tem acesso ao prontuário
        $medicalRecord = MedicalRecord::where(function($query) use ($doctor) {
                $query->where('doctor_id', $doctor->id)
                    ->orWhereHas('patient', function($q) use ($doctor) {
                        $q->whereHas('appointments', function($q) use ($doctor) {
                            $q->where('doctor_id', $doctor->id)
                              ->where('status', 'completed');
                        });
                    });
            })
            ->findOrFail($request->medical_record_id);
        
        // Criar a prescrição
        $prescription = new Prescription();
        $prescription->medical_record_id = $medicalRecord->id;
        $prescription->issue_date = $request->issue_date;
        $prescription->expiration_date = $request->expiration_date;
        $prescription->instructions = $request->instructions;
        $prescription->use_type = $request->use_type;
        $prescription->notes = $request->notes;
        $prescription->save();
        
        // Adicionar medicamentos à prescrição
        foreach ($request->medications as $med) {
            $prescriptionItem = new PrescriptionItem();
            $prescriptionItem->prescription_id = $prescription->id;
            $prescriptionItem->medication = $med['medication'];
            $prescriptionItem->dosage = $med['dosage'];
            $prescriptionItem->frequency = $med['frequency'];
            $prescriptionItem->duration = $med['duration'] ?? null;
            $prescriptionItem->instructions = $med['instructions'] ?? null;
            $prescriptionItem->save();
        }
        
        // Registrar na trilha de auditoria
        \App\Models\AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'create',
            'model_type' => 'Prescription',
            'model_id' => $prescription->id,
            'description' => 'Prescrição médica criada'
        ]);
        
        return redirect()->route('doctor.prescriptions.show', $prescription->id)
            ->with('success', 'Receita criada com sucesso!');
    }
    
    /**
     * Display the specified prescription.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function show($id)
    {
        $doctor = Auth::user()->doctor;
        
        // Buscar a prescrição
        $prescription = Prescription::whereHas('medicalRecord', function($q) use ($doctor) {
                // O médico pode ver prescrições feitas por ele
                $q->where('doctor_id', $doctor->id)
                  // Ou prescrições de pacientes que ele já atendeu
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
                'prescriptionItems'
            ])
            ->findOrFail($id);
            
        // Verificar se a prescrição está ativa
        $isActive = Carbon::parse($prescription->expiration_date)->isFuture();
        
        // Tipos de uso para exibição
        $useTypes = [
            'continuous' => 'Uso contínuo',
            'prescribed_period' => 'Período determinado',
            'as_needed' => 'Conforme necessário'
        ];
        
        return view('doctor.prescriptions.show', [
            'prescription' => $prescription,
            'isActive' => $isActive,
            'useType' => $useTypes[$prescription->use_type] ?? 'Desconhecido',
            'isOwner' => $prescription->medicalRecord->doctor_id === $doctor->id
        ]);
    }
    
    /**
     * Show the form for editing the specified prescription.
     *
     * @param  int  $id
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function edit($id)
    {
        $doctor = Auth::user()->doctor;
        
        // Apenas o médico que criou a prescrição pode editá-la
        $prescription = Prescription::whereHas('medicalRecord', function($q) use ($doctor) {
                $q->where('doctor_id', $doctor->id);
            })
            ->with([
                'medicalRecord.patient.user', 
                'prescriptionItems'
            ])
            ->findOrFail($id);
            
        // Verificar se a prescrição foi criada há pouco tempo (menos de 24 horas)
        $editDeadline = Carbon::now()->subHours(24);
        if ($prescription->created_at->lt($editDeadline)) {
            return redirect()->route('doctor.prescriptions.show', $prescription->id)
                ->with('error', 'Não é possível editar uma prescrição após 24 horas da sua criação por questões de segurança.');
        }
        
        // Buscar medicamentos frequentemente prescritos por este médico
        $frequentMedications = PrescriptionItem::whereHas('prescription.medicalRecord', function($q) use ($doctor) {
                $q->where('doctor_id', $doctor->id);
            })
            ->select('medication')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('medication')
            ->orderByRaw('COUNT(*) DESC')
            ->limit(15)
            ->pluck('medication');
            
        // Tipos de uso
        $useTypes = [
            'continuous' => 'Uso contínuo',
            'prescribed_period' => 'Período determinado',
            'as_needed' => 'Conforme necessário'
        ];
        
        return view('doctor.prescriptions.edit', [
            'prescription' => $prescription,
            'frequentMedications' => $frequentMedications,
            'useTypes' => $useTypes
        ]);
    }
    
    /**
     * Update the specified prescription.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'issue_date' => 'required|date|before_or_equal:today',
            'expiration_date' => 'required|date|after_or_equal:issue_date',
            'instructions' => 'nullable|string|max:2000',
            'use_type' => 'required|in:continuous,prescribed_period,as_needed',
            'medications' => 'required|array|min:1',
            'medications.*.id' => 'nullable|exists:prescription_items,id',
            'medications.*.medication' => 'required|string|max:255',
            'medications.*.dosage' => 'required|string|max:255',
            'medications.*.frequency' => 'required|string|max:255',
            'medications.*.duration' => 'nullable|string|max:255',
            'medications.*.instructions' => 'nullable|string|max:500',
            'notes' => 'nullable|string|max:1000',
        ]);
        
        $doctor = Auth::user()->doctor;
        
        // Apenas o médico que criou a prescrição pode editá-la
        $prescription = Prescription::whereHas('medicalRecord', function($q) use ($doctor) {
                $q->where('doctor_id', $doctor->id);
            })
            ->findOrFail($id);
            
        // Verificar se a prescrição foi criada há pouco tempo (menos de 24 horas)
        $editDeadline = Carbon::now()->subHours(24);
        if ($prescription->created_at->lt($editDeadline)) {
            return redirect()->route('doctor.prescriptions.show', $prescription->id)
                ->with('error', 'Não é possível editar uma prescrição após 24 horas da sua criação por questões de segurança.');
        }
        
        try {
            // Atualizar a prescrição
            $prescription->issue_date = $request->issue_date;
            $prescription->expiration_date = $request->expiration_date;
            $prescription->instructions = $request->instructions;
            $prescription->use_type = $request->use_type;
            $prescription->notes = $request->notes;
            $prescription->save();
            
            // Obter IDs atuais dos medicamentos
            $currentItemIds = $prescription->prescriptionItems->pluck('id')->toArray();
            $submittedItemIds = [];
            
            // Processar medicamentos
            foreach ($request->medications as $med) {
                if (!empty($med['id'])) {
                    // Atualizar item existente
                    $item = PrescriptionItem::where('prescription_id', $prescription->id)
                        ->find($med['id']);
                        
                    if ($item) {
                        $item->medication = $med['medication'];
                        $item->dosage = $med['dosage'];
                        $item->frequency = $med['frequency'];
                        $item->duration = $med['duration'] ?? null;
                        $item->instructions = $med['instructions'] ?? null;
                        $item->save();
                        
                        $submittedItemIds[] = $item->id;
                    }
                } else {
                    // Criar novo item
                    $item = new PrescriptionItem();
                    $item->prescription_id = $prescription->id;
                    $item->medication = $med['medication'];
                    $item->dosage = $med['dosage'];
                    $item->frequency = $med['frequency'];
                    $item->duration = $med['duration'] ?? null;
                    $item->instructions = $med['instructions'] ?? null;
                    $item->save();
                    
                    $submittedItemIds[] = $item->id;
                }
            }
            
            // Remover itens que não foram incluídos no envio
            $itemsToDelete = array_diff($currentItemIds, $submittedItemIds);
            if (count($itemsToDelete) > 0) {
                PrescriptionItem::whereIn('id', $itemsToDelete)->delete();
            }
            
            // Registrar na trilha de auditoria
            \App\Models\AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'update',
                'model_type' => 'Prescription',
                'model_id' => $prescription->id,
                'description' => 'Prescrição médica atualizada'
            ]);
            
            return redirect()->route('doctor.prescriptions.show', $prescription->id)
                ->with('success', 'Receita atualizada com sucesso!');
                
        } catch (\Exception $e) {
            return back()->with('error', 'Ocorreu um erro ao atualizar a prescrição: ' . $e->getMessage())
                ->withInput();
        }
    }
    
    /**
     * Renew an existing prescription.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function renew($id)
    {
        $doctor = Auth::user()->doctor;
        
        $prescription = Prescription::whereHas('medicalRecord', function($q) use ($doctor) {
                // O médico pode renovar prescrições feitas por ele
                $q->where('doctor_id', $doctor->id)
                  // Ou prescrições de pacientes que ele já atendeu
                  ->orWhereHas('patient', function($q) use ($doctor) {
                      $q->whereHas('appointments', function($q) use ($doctor) {
                          $q->where('doctor_id', $doctor->id)
                            ->where('status', 'completed');
                      });
                  });
            })
            ->with([
                'medicalRecord.patient.user', 
                'prescriptionItems'
            ])
            ->findOrFail($id);
            
        // Verificar se o médico atual foi quem criou a prescrição original
        $isOriginalDoctor = $prescription->medicalRecord->doctor_id === $doctor->id;
        
        // Buscar o prontuário mais recente deste paciente feito por este médico
        $medicalRecord = MedicalRecord::where('doctor_id', $doctor->id)
            ->where('patient_id', $prescription->medicalRecord->patient_id)
            ->orderBy('created_at', 'desc')
            ->first();
            
        if (!$medicalRecord) {
            // Se não houver um prontuário recente, criar um novo
            $medicalRecord = new MedicalRecord();
            $medicalRecord->doctor_id = $doctor->id;
            $medicalRecord->patient_id = $prescription->medicalRecord->patient_id;
            $medicalRecord->record_type = 'follow_up';
            $medicalRecord->reason = 'Renovação de receita';
            $medicalRecord->notes = 'Prontuário criado automaticamente para renovação de receita.';
            $medicalRecord->save();
        }
        
        // Tipos de uso
        $useTypes = [
            'continuous' => 'Uso contínuo',
            'prescribed_period' => 'Período determinado',
            'as_needed' => 'Conforme necessário'
        ];
        
        // Buscar medicamentos frequentemente prescritos por este médico
        $frequentMedications = PrescriptionItem::whereHas('prescription.medicalRecord', function($q) use ($doctor) {
                $q->where('doctor_id', $doctor->id);
            })
            ->select('medication')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('medication')
            ->orderByRaw('COUNT(*) DESC')
            ->limit(15)
            ->pluck('medication');
            
        return view('doctor.prescriptions.renew', [
            'prescription' => $prescription,
            'medicalRecord' => $medicalRecord,
            'isOriginalDoctor' => $isOriginalDoctor,
            'useTypes' => $useTypes,
            'frequentMedications' => $frequentMedications
        ]);
    }
    
    /**
     * Store a renewed prescription.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeRenewed(Request $request, $id)
    {
        $request->validate([
            'medical_record_id' => 'required|exists:medical_records,id',
            'issue_date' => 'required|date|before_or_equal:today',
            'expiration_date' => 'required|date|after_or_equal:issue_date',
            'instructions' => 'nullable|string|max:2000',
            'use_type' => 'required|in:continuous,prescribed_period,as_needed',
            'medications' => 'required|array|min:1',
            'medications.*.medication' => 'required|string|max:255',
            'medications.*.dosage' => 'required|string|max:255',
            'medications.*.frequency' => 'required|string|max:255',
            'medications.*.duration' => 'nullable|string|max:255',
            'medications.*.instructions' => 'nullable|string|max:500',
            'notes' => 'nullable|string|max:1000',
            'renewal_reason' => 'required|string|max:500'
        ]);
        
        $doctor = Auth::user()->doctor;
        
        // Verificar se o médico tem acesso ao prontuário
        $medicalRecord = MedicalRecord::where('doctor_id', $doctor->id)
            ->findOrFail($request->medical_record_id);
            
        // Buscar a receita original
        $originalPrescription = Prescription::findOrFail($id);
        
        try {
            // Criar a nova prescrição
            $prescription = new Prescription();
            $prescription->medical_record_id = $medicalRecord->id;
            $prescription->issue_date = $request->issue_date;
            $prescription->expiration_date = $request->expiration_date;
            $prescription->instructions = $request->instructions;
            $prescription->use_type = $request->use_type;
            $prescription->notes = $request->notes;
            $prescription->renewal_of_id = $originalPrescription->id;
            $prescription->renewal_reason = $request->renewal_reason;
            $prescription->save();
            
            // Adicionar medicamentos à prescrição
            foreach ($request->medications as $med) {
                $prescriptionItem = new PrescriptionItem();
                $prescriptionItem->prescription_id = $prescription->id;
                $prescriptionItem->medication = $med['medication'];
                $prescriptionItem->dosage = $med['dosage'];
                $prescriptionItem->frequency = $med['frequency'];
                $prescriptionItem->duration = $med['duration'] ?? null;
                $prescriptionItem->instructions = $med['instructions'] ?? null;
                $prescriptionItem->save();
            }
            
            // Registrar na trilha de auditoria
            \App\Models\AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'renew',
                'model_type' => 'Prescription',
                'model_id' => $prescription->id,
                'description' => 'Prescrição médica renovada (Original: #' . $originalPrescription->id . ')'
            ]);
            
            // Se existir uma solicitação de renovação relacionada, atualizá-la
            $renewalRequest = \App\Models\Notification::where('type', 'prescription_renewal_request')
                ->where('status', 'pending')
                ->whereJsonContains('data->prescription_id', $originalPrescription->id)
                ->first();
                
            if ($renewalRequest) {
                $renewalRequest->status = 'completed';
                $renewalRequest->save();
                
                // Notificar o paciente
                $patient = $medicalRecord->patient;
                \App\Models\Notification::create([
                    'user_id' => $patient->user_id,
                    'type' => 'prescription_renewed',
                    'title' => 'Receita renovada',
                    'message' => 'Sua solicitação de renovação de receita foi atendida pelo Dr. ' . $doctor->user->name,
                    'data' => json_encode([
                        'prescription_id' => $prescription->id
                    ]),
                    'status' => 'unread'
                ]);
                
                // Enviar email (opcional)
                if ($patient->user->notification_settings['email_prescription_renewed'] ?? true) {
                    \Mail::to($patient->user->email)->send(new \App\Mail\PrescriptionRenewed(
                        $patient->user,
                        $doctor->user,
                        $prescription
                    ));
                }
            }
            
            return redirect()->route('doctor.prescriptions.show', $prescription->id)
                ->with('success', 'Receita renovada com sucesso!');
                
        } catch (\Exception $e) {
            return back()->with('error', 'Ocorreu um erro ao renovar a prescrição: ' . $e->getMessage())
                ->withInput();
        }
    }
    
    /**
     * Generate PDF of the prescription.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function generatePdf($id)
    {
        $doctor = Auth::user()->doctor;
        
        // Médico pode gerar PDF de uma prescrição se ele criou ou se atendeu o paciente
        $prescription = Prescription::whereHas('medicalRecord', function($q) use ($doctor) {
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
                'medicalRecord.doctor.specialties',
                'prescriptionItems'
            ])
            ->findOrFail($id);
            
        // Tipos de uso para exibição
        $useTypes = [
            'continuous' => 'Uso contínuo',
            'prescribed_period' => 'Período determinado',
            'as_needed' => 'Conforme necessário'
        ];
        
        $pdf = PDF::loadView('pdfs.prescription', [
            'prescription' => $prescription,
            'patient' => $prescription->medicalRecord->patient,
            'doctor' => $prescription->medicalRecord->doctor,
            'useType' => $useTypes[$prescription->use_type] ?? 'Desconhecido',
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
            'model_type' => 'Prescription',
            'model_id' => $prescription->id,
            'description' => 'PDF de receita gerado'
        ]);
        
        return $pdf->download('receita_' . $id . '_' . $prescription->medicalRecord->patient->user->name . '.pdf');
    }
    
    /**
     * Cancel a prescription.
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
        
        // Apenas o médico que criou a prescrição pode cancelá-la
        $prescription = Prescription::whereHas('medicalRecord', function($q) use ($doctor) {
                $q->where('doctor_id', $doctor->id);
            })
            ->findOrFail($id);
            
        try {
            // Forçar expiração da prescrição
            $prescription->expiration_date = Carbon::now()->subDay();
            $prescription->is_cancelled = true;
            $prescription->cancellation_reason = $request->cancellation_reason;
            $prescription->cancelled_at = Carbon::now();
            $prescription->save();
            
            // Registrar na trilha de auditoria
            \App\Models\AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'cancel',
                'model_type' => 'Prescription',
                'model_id' => $prescription->id,
                'description' => 'Prescrição médica cancelada: ' . $request->cancellation_reason
            ]);
            
            // Notificar o paciente
            $patient = $prescription->medicalRecord->patient;
            \App\Models\Notification::create([
                'user_id' => $patient->user_id,
                'type' => 'prescription_cancelled',
                'title' => 'Receita cancelada',
                'message' => 'Uma receita emitida pelo Dr. ' . $doctor->user->name . ' foi cancelada.',
                'data' => json_encode([
                    'prescription_id' => $prescription->id,
                    'reason' => $request->cancellation_reason
                ]),
                'status' => 'unread'
            ]);
            
            // Enviar email (opcional)
            if ($patient->user->notification_settings['email_prescription_cancelled'] ?? true) {
                \Mail::to($patient->user->email)->send(new \App\Mail\PrescriptionCancelled(
                    $patient->user,
                    $doctor->user,
                    $prescription,
                    $request->cancellation_reason
                ));
            }
            
            return redirect()->route('doctor.prescriptions.show', $prescription->id)
                ->with('success', 'Receita cancelada com sucesso.');
                
        } catch (\Exception $e) {
            return back()->with('error', 'Ocorreu um erro ao cancelar a prescrição: ' . $e->getMessage());
        }
    }
    
    /**
     * Display a form for handling renewal requests.
     *
     * @param  int  $notificationId
     * @return \Illuminate\View\View
     */
    public function handleRenewalRequest($notificationId)
    {
        $doctor = Auth::user()->doctor;
        
        // Buscar a notificação
        $notification = \App\Models\Notification::where('id', $notificationId)
            ->where('type', 'prescription_renewal_request')
            ->where(function($q) use ($doctor) {
                $q->where('user_id', $doctor->user_id)
                  ->orWhere('recipient_role', 'doctor');
            })
            ->first();
            
        if (!$notification) {
            return redirect()->route('doctor.dashboard')
                ->with('error', 'Solicitação de renovação não encontrada ou não autorizada.');
        }
        
        // Marcar como lida
        if ($notification->status == 'unread') {
            $notification->status = 'read';
            $notification->save();
        }
        
        // Extrair dados
        $data = json_decode($notification->data, true);
        $prescriptionId = $data['prescription_id'] ?? null;
        $patientId = $data['patient_id'] ?? null;
        $reason = $data['reason'] ?? 'Não especificado';
        
        if (!$prescriptionId || !$patientId) {
            return redirect()->route('doctor.dashboard')
                ->with('error', 'Dados incompletos na solicitação de renovação.');
        }
        
        // Buscar a receita original
        $prescription = Prescription::with([
                'medicalRecord.patient.user', 
                'prescriptionItems'
            ])
            ->findOrFail($prescriptionId);
            
        // Verificar se o médico já atendeu este paciente
        $hasConsulted = MedicalRecord::where('doctor_id', $doctor->id)
            ->where('patient_id', $patientId)
            ->exists();
            
        if (!$hasConsulted) {
            return redirect()->route('doctor.dashboard')
                ->with('error', 'Você não pode renovar receitas para pacientes que nunca atendeu.');
        }
        
        // Buscar o prontuário mais recente deste paciente feito por este médico
        $medicalRecord = MedicalRecord::where('doctor_id', $doctor->id)
            ->where('patient_id', $patientId)
            ->orderBy('created_at', 'desc')
            ->first();
            
        // Tipos de uso
        $useTypes = [
            'continuous' => 'Uso contínuo',
            'prescribed_period' => 'Período determinado',
            'as_needed' => 'Conforme necessário'
        ];
        
        return view('doctor.prescriptions.handle_renewal', [
            'prescription' => $prescription,
            'notification' => $notification,
            'patient' => $prescription->medicalRecord->patient,
            'medicalRecord' => $medicalRecord,
            'renewalReason' => $reason,
            'useTypes' => $useTypes
        ]);
    }
}