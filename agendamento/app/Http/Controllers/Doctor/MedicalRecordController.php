<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\MedicalRecord;
use App\Models\Patient;
use App\Models\Appointment;
use App\Models\Prescription;
use App\Models\Certificate;
use App\Models\PrescriptionItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use PDF;

class MedicalRecordController extends Controller
{
    /**
     * Display a listing of the medical records.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $doctor = Auth::user()->doctor;
        
        $query = MedicalRecord::where('doctor_id', $doctor->id)
            ->with(['patient.user']);
        
        // Filtro por paciente
        if ($request->has('patient_id') && $request->patient_id) {
            $query->where('patient_id', $request->patient_id);
        }
        
        // Filtro por data
        if ($request->has('start_date') && $request->start_date) {
            $query->where('created_at', '>=', $request->start_date);
        }
        
        if ($request->has('end_date') && $request->end_date) {
            $query->where('created_at', '<=', Carbon::parse($request->end_date)->endOfDay());
        }
        
        // Filtro por tipo
        if ($request->has('record_type') && $request->record_type != 'all') {
            $query->where('record_type', $request->record_type);
        }
        
        // Filtro por termo de busca
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('diagnosis', 'like', "%$search%")
                  ->orWhere('notes', 'like', "%$search%")
                  ->orWhere('symptoms', 'like', "%$search%")
                  ->orWhere('treatment_plan', 'like', "%$search%")
                  ->orWhere('reason', 'like', "%$search%")
                  ->orWhereHas('patient.user', function($q) use ($search) {
                      $q->where('name', 'like', "%$search%");
                  });
            });
        }
        
        // Ordenação
        $sortBy = $request->sort_by ?? 'created_at';
        $sortDirection = $request->sort_direction ?? 'desc';
        
        $medicalRecords = $query->orderBy($sortBy, $sortDirection)
            ->paginate(15)
            ->withQueryString();
        
        // Tipos de prontuário
        $recordTypes = [
            'consultation' => 'Consulta Regular',
            'follow_up' => 'Acompanhamento',
            'emergency' => 'Emergência',
            'exam_review' => 'Análise de Exames',
            'other' => 'Outro'
        ];
        
        // Buscar pacientes do médico para filtro
        $patients = Patient::whereHas('appointments', function($q) use ($doctor) {
            $q->where('doctor_id', $doctor->id)
              ->where('status', 'completed');
        })
        ->with('user')
        ->get();
        
        return view('doctor.medical_records.index', [
            'medicalRecords' => $medicalRecords,
            'patients' => $patients,
            'recordTypes' => $recordTypes,
            'filters' => $request->only(['patient_id', 'start_date', 'end_date', 'record_type', 'search', 'sort_by', 'sort_direction'])
        ]);
    }
    
    /**
     * Show the form for creating a new medical record.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function create(Request $request)
    {
        $doctor = Auth::user()->doctor;
        $appointmentId = $request->query('appointment_id');
        $patientId = $request->query('patient_id');
        
        $appointment = null;
        $patient = null;
        
        // Se um ID de consulta for fornecido, buscar a consulta
        if ($appointmentId) {
            $appointment = Appointment::where('doctor_id', $doctor->id)
                ->where('id', $appointmentId)
                ->with('patient.user')
                ->first();
                
            if ($appointment) {
                $patient = $appointment->patient;
            }
        }
        // Se apenas um ID de paciente for fornecido
        elseif ($patientId) {
            $patient = Patient::with('user')->find($patientId);
            
            // Verificar se o médico já atendeu este paciente
            $hasConsulted = Appointment::where('doctor_id', $doctor->id)
                ->where('patient_id', $patientId)
                ->where('status', 'completed')
                ->exists();
                
            if (!$hasConsulted) {
                return redirect()->route('doctor.patients.index')
                    ->with('error', 'Você só pode criar prontuários para pacientes que já consultou.');
            }
        }
        
        // Se nem paciente nem consulta forem encontrados, redirecionar para selecionar
        if (!$patient) {
            return redirect()->route('doctor.medical_records.select_patient');
        }
        
        // Tipos de prontuário
        $recordTypes = [
            'consultation' => 'Consulta Regular',
            'follow_up' => 'Acompanhamento',
            'emergency' => 'Emergência',
            'exam_review' => 'Análise de Exames',
            'other' => 'Outro'
        ];
        
        // Buscar últimos prontuários deste paciente para referência
        $previousRecords = MedicalRecord::where('patient_id', $patient->id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
        
        return view('doctor.medical_records.create', [
            'doctor' => $doctor,
            'patient' => $patient,
            'appointment' => $appointment,
            'recordTypes' => $recordTypes,
            'previousRecords' => $previousRecords
        ]);
    }
    
    /**
     * Display form to select a patient for a new medical record.
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
        
        return view('doctor.medical_records.select_patient', [
            'recentPatients' => $recentPatients
        ]);
    }
    
    /**
     * Store a newly created medical record.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'record_type' => 'required|string|max:50',
            'reason' => 'required|string|max:500',
            'symptoms' => 'nullable|string|max:1000',
            'diagnosis' => 'nullable|string|max:1000',
            'treatment_plan' => 'nullable|string|max:1000',
            'notes' => 'nullable|string|max:2000',
            'appointment_id' => 'nullable|exists:appointments,id',
            'files.*' => 'nullable|file|max:10240', // Máximo 10MB por arquivo
            'private_notes' => 'nullable|string|max:1000'
        ]);
        
        $doctor = Auth::user()->doctor;
        
        // Verificar se o médico já atendeu este paciente
        $hasConsulted = Appointment::where('doctor_id', $doctor->id)
            ->where('patient_id', $request->patient_id)
            ->where('status', 'completed')
            ->exists();
            
        if (!$hasConsulted) {
            return back()->with('error', 'Você só pode criar prontuários para pacientes que já consultou.')
                ->withInput();
        }
        
        try {
            $medicalRecord = new MedicalRecord();
            $medicalRecord->doctor_id = $doctor->id;
            $medicalRecord->patient_id = $request->patient_id;
            $medicalRecord->record_type = $request->record_type;
            $medicalRecord->reason = $request->reason;
            $medicalRecord->symptoms = $request->symptoms;
            $medicalRecord->diagnosis = $request->diagnosis;
            $medicalRecord->treatment_plan = $request->treatment_plan;
            $medicalRecord->notes = $request->notes;
            $medicalRecord->private_notes = $request->private_notes;
            
            // Se o prontuário está associado a uma consulta
            if ($request->appointment_id) {
                $appointment = Appointment::where('doctor_id', $doctor->id)
                    ->where('id', $request->appointment_id)
                    ->first();
                    
                if ($appointment) {
                    $medicalRecord->appointment_id = $appointment->id;
                    
                    // Marcar a consulta como concluída se ainda não estiver
                    if ($appointment->status != 'completed') {
                        $appointment->status = 'completed';
                        $appointment->save();
                    }
                }
            }
            
            $medicalRecord->save();
            
            // Processar arquivos anexados
            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $file) {
                    $path = $file->store('medical_records/' . $medicalRecord->id, 'public');
                    
                    $medicalRecord->attachments()->create([
                        'file_path' => $path,
                        'file_name' => $file->getClientOriginalName(),
                        'file_type' => $file->getMimeType(),
                        'file_size' => $file->getSize()
                    ]);
                }
            }
            
            return redirect()->route('doctor.medical_records.show', $medicalRecord->id)
                ->with('success', 'Prontuário criado com sucesso!');
                
        } catch (\Exception $e) {
            return back()->with('error', 'Ocorreu um erro ao criar o prontuário: ' . $e->getMessage())
                ->withInput();
        }
    }
    
    /**
     * Display the specified medical record.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function show($id)
    {
        $doctor = Auth::user()->doctor;
        
        // Buscar o prontuário
        $medicalRecord = MedicalRecord::where(function($query) use ($doctor) {
                // O médico pode ver prontuários que ele mesmo criou
                $query->where('doctor_id', $doctor->id)
                    // Ou prontuários de pacientes que ele já atendeu
                    ->orWhereHas('patient', function($q) use ($doctor) {
                        $q->whereHas('appointments', function($q) use ($doctor) {
                            $q->where('doctor_id', $doctor->id)
                              ->where('status', 'completed');
                        });
                    });
            })
            ->with(['patient.user', 'doctor.user', 'attachments'])
            ->findOrFail($id);
        
        // Verificar se este prontuário tem prescrições associadas
        $prescriptions = Prescription::where('medical_record_id', $medicalRecord->id)
            ->with('prescriptionItems')
            ->get();
            
        // Verificar se este prontuário tem atestados associados
        $certificates = Certificate::where('medical_record_id', $medicalRecord->id)
            ->get();
        
        // Tipos de prontuário para exibição
        $recordTypes = [
            'consultation' => 'Consulta Regular',
            'follow_up' => 'Acompanhamento',
            'emergency' => 'Emergência',
            'exam_review' => 'Análise de Exames',
            'other' => 'Outro'
        ];
        
        return view('doctor.medical_records.show', [
            'medicalRecord' => $medicalRecord,
            'prescriptions' => $prescriptions,
            'certificates' => $certificates,
            'recordType' => $recordTypes[$medicalRecord->record_type] ?? 'Desconhecido',
            'isOwner' => $medicalRecord->doctor_id === $doctor->id
        ]);
    }
    
    /**
     * Show the form for editing the specified medical record.
     *
     * @param  int  $id
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function edit($id)
    {
        $doctor = Auth::user()->doctor;
        
        // Apenas o médico que criou o prontuário pode editá-lo
        $medicalRecord = MedicalRecord::where('doctor_id', $doctor->id)
            ->with(['patient.user', 'attachments'])
            ->findOrFail($id);
            
        // Verificar se não é um prontuário muito antigo (mais de 7 dias)
        $editDeadline = Carbon::now()->subDays(7);
        if ($medicalRecord->created_at->lt($editDeadline)) {
            return redirect()->route('doctor.medical_records.show', $medicalRecord->id)
                ->with('error', 'Não é possível editar um prontuário criado há mais de 7 dias por questões de segurança.');
        }
        
        // Tipos de prontuário
        $recordTypes = [
            'consultation' => 'Consulta Regular',
            'follow_up' => 'Acompanhamento',
            'emergency' => 'Emergência',
            'exam_review' => 'Análise de Exames',
            'other' => 'Outro'
        ];
        
        return view('doctor.medical_records.edit', [
            'medicalRecord' => $medicalRecord,
            'recordTypes' => $recordTypes
        ]);
    }
    
    /**
     * Update the specified medical record.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'record_type' => 'required|string|max:50',
            'reason' => 'required|string|max:500',
            'symptoms' => 'nullable|string|max:1000',
            'diagnosis' => 'nullable|string|max:1000',
            'treatment_plan' => 'nullable|string|max:1000',
            'notes' => 'nullable|string|max:2000',
            'files.*' => 'nullable|file|max:10240', // Máximo 10MB por arquivo
            'delete_attachments' => 'nullable|array',
            'delete_attachments.*' => 'exists:medical_record_attachments,id',
            'private_notes' => 'nullable|string|max:1000'
        ]);
        
        $doctor = Auth::user()->doctor;
        
        // Apenas o médico que criou o prontuário pode editá-lo
        $medicalRecord = MedicalRecord::where('doctor_id', $doctor->id)
            ->findOrFail($id);
            
        // Verificar se não é um prontuário muito antigo (mais de 7 dias)
        $editDeadline = Carbon::now()->subDays(7);
        if ($medicalRecord->created_at->lt($editDeadline)) {
            return redirect()->route('doctor.medical_records.show', $medicalRecord->id)
                ->with('error', 'Não é possível editar um prontuário criado há mais de 7 dias por questões de segurança.');
        }
        
        try {
            $medicalRecord->record_type = $request->record_type;
            $medicalRecord->reason = $request->reason;
            $medicalRecord->symptoms = $request->symptoms;
            $medicalRecord->diagnosis = $request->diagnosis;
            $medicalRecord->treatment_plan = $request->treatment_plan;
            $medicalRecord->notes = $request->notes;
            $medicalRecord->private_notes = $request->private_notes;
            $medicalRecord->updated_at = Carbon::now();
            $medicalRecord->save();
            
            // Excluir anexos marcados para remoção
            if ($request->has('delete_attachments')) {
                foreach ($request->delete_attachments as $attachmentId) {
                    $attachment = $medicalRecord->attachments()->find($attachmentId);
                    
                    if ($attachment) {
                        // Deletar o arquivo do armazenamento
                        if (Storage::disk('public')->exists($attachment->file_path)) {
                            Storage::disk('public')->delete($attachment->file_path);
                        }
                        
                        // Deletar o registro do anexo
                        $attachment->delete();
                    }
                }
            }
            
            // Adicionar novos anexos
            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $file) {
                    $path = $file->store('medical_records/' . $medicalRecord->id, 'public');
                    
                    $medicalRecord->attachments()->create([
                        'file_path' => $path,
                        'file_name' => $file->getClientOriginalName(),
                        'file_type' => $file->getMimeType(),
                        'file_size' => $file->getSize()
                    ]);
                }
            }
            
            // Registrar a edição no log de auditoria
            \App\Models\AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'update',
                'model_type' => 'MedicalRecord',
                'model_id' => $medicalRecord->id,
                'description' => 'Prontuário atualizado'
            ]);
            
            return redirect()->route('doctor.medical_records.show', $medicalRecord->id)
                ->with('success', 'Prontuário atualizado com sucesso!');
                
        } catch (\Exception $e) {
            return back()->with('error', 'Ocorreu um erro ao atualizar o prontuário: ' . $e->getMessage())
                ->withInput();
        }
    }
    
    /**
     * Generate PDF of the medical record.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function generatePdf($id)
    {
        $doctor = Auth::user()->doctor;
        
        // Médico pode gerar PDF de um prontuário se ele criou ou se atendeu o paciente
        $medicalRecord = MedicalRecord::where(function($query) use ($doctor) {
                // O médico pode ver prontuários que ele mesmo criou
                $query->where('doctor_id', $doctor->id)
                    // Ou prontuários de pacientes que ele já atendeu
                    ->orWhereHas('patient', function($q) use ($doctor) {
                        $q->whereHas('appointments', function($q) use ($doctor) {
                            $q->where('doctor_id', $doctor->id)
                              ->where('status', 'completed');
                        });
                    });
            })
            ->with(['patient.user', 'doctor.user', 'doctor.specialties'])
            ->findOrFail($id);
        
        // Tipos de prontuário para exibição
        $recordTypes = [
            'consultation' => 'Consulta Regular',
            'follow_up' => 'Acompanhamento',
            'emergency' => 'Emergência',
            'exam_review' => 'Análise de Exames',
            'other' => 'Outro'
        ];
        
        $pdf = PDF::loadView('pdfs.medical_record', [
            'medicalRecord' => $medicalRecord,
            'recordType' => $recordTypes[$medicalRecord->record_type] ?? 'Desconhecido',
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
            'model_type' => 'MedicalRecord',
            'model_id' => $medicalRecord->id,
            'description' => 'PDF de prontuário gerado'
        ]);
        
        return $pdf->download('prontuario_' . $medicalRecord->id . '_' . $medicalRecord->patient->user->name . '.pdf');
    }
    
    /**
     * Create a prescription based on a medical record.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function createPrescription($id)
    {
        $doctor = Auth::user()->doctor;
        
        // Apenas o médico que criou o prontuário pode criar uma prescrição a partir dele
        $medicalRecord = MedicalRecord::where('doctor_id', $doctor->id)
            ->findOrFail($id);
            
        // Redirecionar para a criação de prescrição com o id do prontuário
        return redirect()->route('doctor.prescriptions.create', ['medical_record_id' => $medicalRecord->id]);
    }
    
    /**
     * Create a certificate based on a medical record.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function createCertificate($id)
    {
        $doctor = Auth::user()->doctor;
        
        // Apenas o médico que criou o prontuário pode criar um atestado a partir dele
        $medicalRecord = MedicalRecord::where('doctor_id', $doctor->id)
            ->findOrFail($id);
            
        // Redirecionar para a criação de atestado com o id do prontuário
        return redirect()->route('doctor.certificates.create', ['medical_record_id' => $medicalRecord->id]);
    }
    
    /**
     * Download an attachment from a medical record.
     *
     * @param  int  $id
     * @param  int  $attachmentId
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function downloadAttachment($id, $attachmentId)
    {
        $doctor = Auth::user()->doctor;
        
        // Médico pode baixar anexos se criou o prontuário ou se atendeu o paciente
        $medicalRecord = MedicalRecord::where(function($query) use ($doctor) {
                // O médico pode ver prontuários que ele mesmo criou
                $query->where('doctor_id', $doctor->id)
                    // Ou prontuários de pacientes que ele já atendeu
                    ->orWhereHas('patient', function($q) use ($doctor) {
                        $q->whereHas('appointments', function($q) use ($doctor) {
                            $q->where('doctor_id', $doctor->id)
                              ->where('status', 'completed');
                        });
                    });
            })
            ->findOrFail($id);
        
        // Buscar o anexo
        $attachment = $medicalRecord->attachments()->findOrFail($attachmentId);
        
        // Verificar se o arquivo existe
        if (!Storage::disk('public')->exists($attachment->file_path)) {
            return back()->with('error', 'Arquivo não encontrado no servidor.');
        }
        
        // Registrar no log de auditoria
        \App\Models\AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'download',
            'model_type' => 'MedicalRecordAttachment',
            'model_id' => $attachment->id,
            'description' => 'Download de anexo de prontuário'
        ]);
        
        return response()->download(
            storage_path('app/public/' . $attachment->file_path),
            $attachment->file_name
        );
    }
}