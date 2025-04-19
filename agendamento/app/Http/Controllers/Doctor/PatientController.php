<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\MedicalRecord;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\Certificate;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class PatientController extends Controller
{
    /**
     * Display a listing of patients consulted by the doctor.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $doctor = Auth::user()->doctor;
        
        // Base query - pacientes que o médico já consultou
        $query = Patient::whereHas('appointments', function($q) use ($doctor) {
            $q->where('doctor_id', $doctor->id)
              ->where('status', 'completed');
        })->with('user');
        
        // Filtro por nome
        if ($request->has('name') && $request->name) {
            $name = $request->name;
            $query->whereHas('user', function($q) use ($name) {
                $q->where('name', 'like', "%{$name}%");
            });
        }
        
        // Filtro por data da última consulta
        if ($request->has('last_appointment')) {
            if ($request->last_appointment == 'month') {
                $date = Carbon::now()->subMonth();
            } elseif ($request->last_appointment == 'quarter') {
                $date = Carbon::now()->subMonths(3);
            } elseif ($request->last_appointment == 'year') {
                $date = Carbon::now()->subYear();
            }
            
            if (isset($date)) {
                $query->whereHas('appointments', function($q) use ($doctor, $date) {
                    $q->where('doctor_id', $doctor->id)
                      ->where('status', 'completed')
                      ->where('start_time', '>=', $date);
                });
            }
        }
        
        // Ordenação
        if ($request->has('sort_by')) {
            if ($request->sort_by == 'name') {
                $query->join('users', 'patients.user_id', '=', 'users.id')
                      ->orderBy('users.name', $request->sort_direction ?? 'asc')
                      ->select('patients.*');
            } elseif ($request->sort_by == 'last_appointment') {
                // Ordenar pela data da última consulta com este médico
                $query->addSelect(['last_appointment' => Appointment::select('start_time')
                    ->whereColumn('patient_id', 'patients.id')
                    ->where('doctor_id', $doctor->id)
                    ->where('status', 'completed')
                    ->latest('start_time')
                    ->limit(1)
                ])
                ->orderBy('last_appointment', $request->sort_direction ?? 'desc');
            }
        } else {
            // Ordenação padrão - pela data da última consulta
            $query->addSelect(['last_appointment' => Appointment::select('start_time')
                ->whereColumn('patient_id', 'patients.id')
                ->where('doctor_id', $doctor->id)
                ->where('status', 'completed')
                ->latest('start_time')
                ->limit(1)
            ])
            ->orderBy('last_appointment', 'desc');
        }
        
        $patients = $query->paginate(15)->withQueryString();
        
        // Para cada paciente, buscar a última consulta com este médico
        foreach ($patients as $patient) {
            $lastAppointment = Appointment::where('patient_id', $patient->id)
                ->where('doctor_id', $doctor->id)
                ->where('status', 'completed')
                ->orderBy('start_time', 'desc')
                ->first();
                
            $patient->last_appointment_date = $lastAppointment ? $lastAppointment->start_time : null;
            
            // Contar total de consultas com este médico
            $patient->appointment_count = Appointment::where('patient_id', $patient->id)
                ->where('doctor_id', $doctor->id)
                ->where('status', 'completed')
                ->count();
        }
        
        return view('doctor.patients.index', [
            'patients' => $patients,
            'filters' => $request->only(['name', 'last_appointment', 'sort_by', 'sort_direction'])
        ]);
    }
    
    /**
     * Display the specified patient's profile and medical history.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function show($id)
    {
        $doctor = Auth::user()->doctor;
        $patient = Patient::with('user', 'healthProfile')->findOrFail($id);
        
        // Verificar se o médico já atendeu este paciente
        $hasConsulted = Appointment::where('doctor_id', $doctor->id)
            ->where('patient_id', $patient->id)
            ->where('status', 'completed')
            ->exists();
        
        if (!$hasConsulted) {
            return redirect()->route('doctor.patients.index')
                ->with('error', 'Você não tem autorização para acessar o perfil deste paciente.');
        }
        
        // Buscar consultas deste paciente com este médico
        $appointments = Appointment::where('doctor_id', $doctor->id)
            ->where('patient_id', $patient->id)
            ->orderBy('start_time', 'desc')
            ->get();
            
        // Buscar prontuários deste paciente criados por este médico
        $medicalRecords = MedicalRecord::where('doctor_id', $doctor->id)
            ->where('patient_id', $patient->id)
            ->orderBy('created_at', 'desc')
            ->get();
            
        // Buscar receitas prescritas para este paciente por este médico
        $prescriptions = Prescription::whereHas('medicalRecord', function($q) use ($doctor, $patient) {
            $q->where('doctor_id', $doctor->id)
              ->where('patient_id', $patient->id);
        })
        ->orderBy('created_at', 'desc')
        ->get();
        
        // Buscar atestados emitidos para este paciente por este médico
        $certificates = Certificate::whereHas('medicalRecord', function($q) use ($doctor, $patient) {
            $q->where('doctor_id', $doctor->id)
              ->where('patient_id', $patient->id);
        })
        ->orderBy('created_at', 'desc')
        ->get();
        
        // Calcular estatísticas
        $statistics = [
            'total_appointments' => $appointments->count(),
            'first_appointment' => $appointments->last()->start_time ?? null,
            'last_appointment' => $appointments->first()->start_time ?? null,
            'total_prescriptions' => $prescriptions->count(),
            'total_certificates' => $certificates->count()
        ];
        
        return view('doctor.patients.show', [
            'patient' => $patient,
            'appointments' => $appointments->take(5),
            'medicalRecords' => $medicalRecords->take(5),
            'prescriptions' => $prescriptions->take(5),
            'certificates' => $certificates->take(5),
            'statistics' => $statistics
        ]);
    }
    
    /**
     * Display patient's health profile.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function healthProfile($id)
    {
        $doctor = Auth::user()->doctor;
        $patient = Patient::with('user', 'healthProfile')->findOrFail($id);
        
        // Verificar se o médico já atendeu este paciente
        $hasConsulted = Appointment::where('doctor_id', $doctor->id)
            ->where('patient_id', $patient->id)
            ->where('status', 'completed')
            ->exists();
        
        if (!$hasConsulted) {
            return redirect()->route('doctor.patients.index')
                ->with('error', 'Você não tem autorização para acessar o perfil deste paciente.');
        }
        
        // Buscar alergias e condições de saúde do paciente
        $allergiesAndConditions = $patient->healthProfile ? $patient->healthProfile->data : [];
        
        // Histórico de peso e altura
        $weightHistory = [];
        $heightHistory = [];
        
        if ($patient->healthProfile) {
            $weightHistory = isset($patient->healthProfile->metrics['weight']) ? $patient->healthProfile->metrics['weight'] : [];
            $heightHistory = isset($patient->healthProfile->metrics['height']) ? $patient->healthProfile->metrics['height'] : [];
        }
        
        // Histórico de medicamentos
        $medications = Prescription::whereHas('medicalRecord', function($q) use ($patient) {
            $q->where('patient_id', $patient->id);
        })
        ->with('prescriptionItems')
        ->orderBy('issue_date', 'desc')
        ->get()
        ->pluck('prescriptionItems')
        ->flatten()
        ->unique('medication')
        ->values();
        
        return view('doctor.patients.health_profile', [
            'patient' => $patient,
            'allergiesAndConditions' => $allergiesAndConditions,
            'weightHistory' => $weightHistory,
            'heightHistory' => $heightHistory,
            'medications' => $medications
        ]);
    }
    
    /**
     * Display patient's medical records.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function medicalRecords($id)
    {
        $doctor = Auth::user()->doctor;
        $patient = Patient::with('user')->findOrFail($id);
        
        // Verificar se o médico já atendeu este paciente
        $hasConsulted = Appointment::where('doctor_id', $doctor->id)
            ->where('patient_id', $patient->id)
            ->where('status', 'completed')
            ->exists();
        
        if (!$hasConsulted) {
            return redirect()->route('doctor.patients.index')
                ->with('error', 'Você não tem autorização para acessar os prontuários deste paciente.');
        }
        
        // Buscar prontuários deste paciente criados por qualquer médico
        $medicalRecords = MedicalRecord::where('patient_id', $patient->id)
            ->with('doctor.user')
            ->orderBy('created_at', 'desc')
            ->paginate(10);
            
        return view('doctor.patients.medical_records', [
            'patient' => $patient,
            'medicalRecords' => $medicalRecords
        ]);
    }
    
    /**
     * Display patient's prescriptions.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function prescriptions($id)
    {
        $doctor = Auth::user()->doctor;
        $patient = Patient::with('user')->findOrFail($id);
        
        // Verificar se o médico já atendeu este paciente
        $hasConsulted = Appointment::where('doctor_id', $doctor->id)
            ->where('patient_id', $patient->id)
            ->where('status', 'completed')
            ->exists();
        
        if (!$hasConsulted) {
            return redirect()->route('doctor.patients.index')
                ->with('error', 'Você não tem autorização para acessar as receitas deste paciente.');
        }
        
        // Buscar receitas deste paciente
        $prescriptions = Prescription::whereHas('medicalRecord', function($q) use ($patient) {
            $q->where('patient_id', $patient->id);
        })
        ->with(['medicalRecord.doctor.user', 'prescriptionItems'])
        ->orderBy('issue_date', 'desc')
        ->paginate(10);
            
        return view('doctor.patients.prescriptions', [
            'patient' => $patient,
            'prescriptions' => $prescriptions
        ]);
    }
    
    /**
     * Display patient's certificates.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function certificates($id)
    {
        $doctor = Auth::user()->doctor;
        $patient = Patient::with('user')->findOrFail($id);
        
        // Verificar se o médico já atendeu este paciente
        $hasConsulted = Appointment::where('doctor_id', $doctor->id)
            ->where('patient_id', $patient->id)
            ->where('status', 'completed')
            ->exists();
        
        if (!$hasConsulted) {
            return redirect()->route('doctor.patients.index')
                ->with('error', 'Você não tem autorização para acessar os atestados deste paciente.');
        }
        
        // Buscar atestados deste paciente
        $certificates = Certificate::whereHas('medicalRecord', function($q) use ($patient) {
            $q->where('patient_id', $patient->id);
        })
        ->with('medicalRecord.doctor.user')
        ->orderBy('created_at', 'desc')
        ->paginate(10);
            
        $certificateTypes = [
            'sick_leave' => 'Atestado Médico',
            'medical_certificate' => 'Certificado Médico',
            'other' => 'Outro'
        ];
            
        return view('doctor.patients.certificates', [
            'patient' => $patient,
            'certificates' => $certificates,
            'certificateTypes' => $certificateTypes
        ]);
    }
    
    /**
     * Display patient's documents.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function documents($id)
    {
        $doctor = Auth::user()->doctor;
        $patient = Patient::with('user')->findOrFail($id);
        
        // Verificar se o médico já atendeu este paciente
        $hasConsulted = Appointment::where('doctor_id', $doctor->id)
            ->where('patient_id', $patient->id)
            ->where('status', 'completed')
            ->exists();
        
        if (!$hasConsulted) {
            return redirect()->route('doctor.patients.index')
                ->with('error', 'Você não tem autorização para acessar os documentos deste paciente.');
        }
        
        // Buscar documentos compartilhados pelo paciente
        $query = Document::where('patient_id', $patient->id)
            ->where('is_shared_with_doctors', true);
            
        // Filtros opcionais (tipo)
        if (request()->has('type') && request()->type != 'all') {
            $query->where('type', request()->type);
        }
        
        $documents = $query->orderBy('created_at', 'desc')
            ->paginate(12)
            ->withQueryString();
        
        $documentTypes = [
            'exam' => 'Exames',
            'report' => 'Relatórios',
            'image' => 'Imagens médicas',
            'other' => 'Outros'
        ];
            
        return view('doctor.patients.documents', [
            'patient' => $patient,
            'documents' => $documents,
            'documentTypes' => $documentTypes,
            'selectedType' => request()->type ?? 'all'
        ]);
    }
    
    /**
     * Display patient's appointment history with the doctor.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function appointments($id)
    {
        $doctor = Auth::user()->doctor;
        $patient = Patient::with('user')->findOrFail($id);
        
        // Verificar se o médico já atendeu este paciente
        $hasConsulted = Appointment::where('doctor_id', $doctor->id)
            ->where('patient_id', $patient->id)
            ->where('status', 'completed')
            ->exists();
        
        if (!$hasConsulted) {
            return redirect()->route('doctor.patients.index')
                ->with('error', 'Você não tem autorização para acessar o histórico de consultas deste paciente.');
        }
        
        // Buscar consultas deste paciente com este médico
        $query = Appointment::where('doctor_id', $doctor->id)
            ->where('patient_id', $patient->id);
            
        // Filtro por status
        if (request()->has('status') && request()->status != 'all') {
            $query->where('status', request()->status);
        }
        
        // Filtro por data
        if (request()->has('start_date') && request()->start_date) {
            $query->where('start_time', '>=', request()->start_date);
        }
        
        if (request()->has('end_date') && request()->end_date) {
            $query->where('start_time', '<=', Carbon::parse(request()->end_date)->endOfDay());
        }
        
        $appointments = $query->orderBy('start_time', 'desc')
            ->paginate(15)
            ->withQueryString();
            
        $statuses = [
            'scheduled' => 'Agendada',
            'confirmed' => 'Confirmada',
            'completed' => 'Realizada',
            'canceled' => 'Cancelada',
            'missed' => 'Não compareceu'
        ];
            
        return view('doctor.patients.appointments', [
            'patient' => $patient,
            'appointments' => $appointments,
            'statuses' => $statuses,
            'filters' => request()->only(['status', 'start_date', 'end_date'])
        ]);
    }
    
    /**
     * Search patients for autocomplete.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        $doctor = Auth::user()->doctor;
        $query = $request->get('query');
        $limit = $request->get('limit', 10);
        
        // Buscar pacientes do médico que correspondem à consulta
        $patients = Patient::whereHas('appointments', function($q) use ($doctor) {
            $q->where('doctor_id', $doctor->id);
        })
        ->whereHas('user', function($q) use ($query) {
            $q->where('name', 'like', "%{$query}%")
              ->orWhere('email', 'like', "%{$query}%");
        })
        ->with('user')
        ->limit($limit)
        ->get()
        ->map(function($patient) {
            return [
                'id' => $patient->id,
                'name' => $patient->user->name,
                'email' => $patient->user->email,
                'avatar' => $patient->user->photo ? asset('storage/' . $patient->user->photo) : asset('images/default-avatar.png'),
                'url' => route('doctor.patients.show', $patient->id)
            ];
        });
        
        return response()->json([
            'results' => $patients
        ]);
    }
    
    /**
     * Add notes to patient's profile.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function addNote(Request $request, $id)
    {
        $request->validate([
            'note' => 'required|string|max:1000'
        ]);
        
        $doctor = Auth::user()->doctor;
        $patient = Patient::findOrFail($id);
        
        // Verificar se o médico já atendeu este paciente
        $hasConsulted = Appointment::where('doctor_id', $doctor->id)
            ->where('patient_id', $patient->id)
            ->where('status', 'completed')
            ->exists();
        
        if (!$hasConsulted) {
            return redirect()->route('doctor.patients.index')
                ->with('error', 'Você não tem autorização para adicionar notas a este paciente.');
        }
        
        // Criar ou atualizar as notas do médico para este paciente
        $doctorNotes = $patient->doctorNotes()->updateOrCreate(
            ['doctor_id' => $doctor->id],
            ['notes' => $request->note]
        );
        
        return back()->with('success', 'Anotação salva com sucesso.');
    }
}