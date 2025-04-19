<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\MedicalRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use PDF;

class CertificateController extends Controller
{
    /**
     * Display a listing of the patient's certificates.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $patient = Auth::user()->patient;
        
        $query = Certificate::whereHas('medicalRecord', function($query) use ($patient) {
            $query->where('patient_id', $patient->id);
        })->with(['medicalRecord.doctor.user']);
        
        // Filtro por tipo
        if ($request->has('type') && $request->type != 'all') {
            $query->where('type', $request->type);
        }
        
        // Filtro por data
        if ($request->has('start_date') && $request->start_date) {
            $query->where('start_date', '>=', $request->start_date);
        }
        
        if ($request->has('end_date') && $request->end_date) {
            $query->where('end_date', '<=', $request->end_date);
        }
        
        // Filtro por médico
        if ($request->has('doctor_id') && $request->doctor_id) {
            $query->whereHas('medicalRecord', function($query) use ($request) {
                $query->where('doctor_id', $request->doctor_id);
            });
        }
        
        // Filtro por busca
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($query) use ($search) {
                $query->where('text', 'like', "%$search%")
                      ->orWhere('cid', 'like', "%$search%")
                      ->orWhereHas('medicalRecord.doctor.user', function($query) use ($search) {
                          $query->where('name', 'like', "%$search%");
                      });
            });
        }
        
        // Ordenação
        $sortBy = $request->sort_by ?? 'start_date';
        $sortDirection = $request->sort_direction ?? 'desc';
        $query->orderBy($sortBy, $sortDirection);
        
        $certificates = $query->paginate(10)->withQueryString();
        
        // Buscar médicos para filtro
        $doctors = MedicalRecord::where('patient_id', $patient->id)
            ->distinct('doctor_id')
            ->with('doctor.user')
            ->get()
            ->pluck('doctor')
            ->unique('id');
            
        // Tipos de atestado para filtro
        $certificateTypes = [
            'sick_leave' => 'Atestado Médico',
            'medical_certificate' => 'Certificado Médico',
            'other' => 'Outro'
        ];
        
        return view('patient.certificates.index', [
            'certificates' => $certificates,
            'doctors' => $doctors,
            'certificateTypes' => $certificateTypes,
            'filters' => $request->only(['type', 'start_date', 'end_date', 'doctor_id', 'search', 'sort_by', 'sort_direction'])
        ]);
    }
    
    /**
     * Display the specified certificate.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function show($id)
    {
        $patient = Auth::user()->patient;
        
        $certificate = Certificate::whereHas('medicalRecord', function($query) use ($patient) {
            $query->where('patient_id', $patient->id);
        })
        ->with([
            'medicalRecord.doctor.user',
            'medicalRecord.doctor.specialties',
            'medicalRecord.patient.user'
        ])
        ->findOrFail($id);
        
        $certificateTypes = [
            'sick_leave' => 'Atestado Médico',
            'medical_certificate' => 'Certificado Médico',
            'other' => 'Outro'
        ];
        
        // Verificar se está dentro do período de validade
        $isValid = Carbon::parse($certificate->end_date)->isFuture() || Carbon::parse($certificate->end_date)->isToday();
        
        // Calcular dias restantes
        $daysRemaining = Carbon::now()->diffInDays(Carbon::parse($certificate->end_date), false);
        
        return view('patient.certificates.show', [
            'certificate' => $certificate,
            'certificateType' => $certificateTypes[$certificate->type] ?? 'Desconhecido',
            'isValid' => $isValid,
            'daysRemaining' => $daysRemaining
        ]);
    }
    
    /**
     * Download the certificate as PDF.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function downloadPdf($id)
    {
        $patient = Auth::user()->patient;
        
        $certificate = Certificate::whereHas('medicalRecord', function($query) use ($patient) {
            $query->where('patient_id', $patient->id);
        })
        ->with([
            'medicalRecord.doctor.user',
            'medicalRecord.doctor.specialties',
            'medicalRecord.patient.user'
        ])
        ->findOrFail($id);
        
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
        
        $filename = 'atestado_' . $id . '_' . date('Ymd') . '.pdf';
        
        return $pdf->download($filename);
    }
    
    /**
     * Print the certificate.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function print($id)
    {
        $patient = Auth::user()->patient;
        
        $certificate = Certificate::whereHas('medicalRecord', function($query) use ($patient) {
            $query->where('patient_id', $patient->id);
        })
        ->with([
            'medicalRecord.doctor.user',
            'medicalRecord.doctor.specialties',
            'medicalRecord.patient.user'
        ])
        ->findOrFail($id);
        
        $certificateTypes = [
            'sick_leave' => 'Atestado Médico',
            'medical_certificate' => 'Certificado Médico',
            'other' => 'Outro'
        ];
        
        return view('patient.certificates.print', [
            'certificate' => $certificate,
            'patient' => $certificate->medicalRecord->patient,
            'doctor' => $certificate->medicalRecord->doctor,
            'certificateType' => $certificateTypes[$certificate->type] ?? 'Documento Médico'
        ]);
    }
    
    /**
     * Share the certificate by email.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function shareByEmail(Request $request, $id)
    {
        $request->validate([
            'email' => 'required|email|max:255',
            'message' => 'nullable|string|max:500'
        ]);
        
        $patient = Auth::user()->patient;
        
        $certificate = Certificate::whereHas('medicalRecord', function($query) use ($patient) {
            $query->where('patient_id', $patient->id);
        })->findOrFail($id);
        
        try {
            $certificateTypes = [
                'sick_leave' => 'Atestado Médico',
                'medical_certificate' => 'Certificado Médico',
                'other' => 'Outro'
            ];
            
            // Gerar PDF
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
            
            $filename = 'atestado_' . $id . '_' . date('Ymd') . '.pdf';
            $pdfPath = storage_path('app/temp/' . $filename);
            $pdf->save($pdfPath);
            
            // Enviar email
            \Mail::send('emails.certificate.share', [
                'patient' => $patient->user->name,
                'message' => $request->message,
                'certificateType' => $certificateTypes[$certificate->type] ?? 'Documento Médico'
            ], function($message) use ($request, $pdfPath, $filename, $certificateTypes, $certificate) {
                $certificateType = $certificateTypes[$certificate->type] ?? 'Documento Médico';
                $message->to($request->email)
                    ->subject($certificateType . ' Compartilhado')
                    ->attach($pdfPath, [
                        'as' => $filename,
                        'mime' => 'application/pdf',
                    ]);
            });
            
            // Remover arquivo temporário
            if (file_exists($pdfPath)) {
                unlink($pdfPath);
            }
            
            return back()->with('success', 'Atestado enviado com sucesso para ' . $request->email);
            
        } catch (\Exception $e) {
            return back()->with('error', 'Ocorreu um erro ao compartilhar o atestado: ' . $e->getMessage());
        }
    }
    
    /**
     * Request a new certificate or extension.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function requestNew(Request $request)
    {
        $request->validate([
            'reason' => 'required|string|min:20|max:1000',
            'start_date' => 'required|date|after_or_equal:today',
            'days_needed' => 'required|integer|min:1|max:60',
            'doctor_id' => 'required|exists:doctors,id'
        ]);
        
        $patient = Auth::user()->patient;
        
        try {
            // Criar notificação para o médico
            $notification = new \App\Models\Notification();
            $notification->user_id = \App\Models\Doctor::find($request->doctor_id)->user_id;
            $notification->type = 'certificate_request';
            $notification->title = 'Solicitação de Atestado';
            $notification->message = 'O paciente ' . $patient->user->name . ' solicitou um atestado médico.';
            $notification->data = json_encode([
                'patient_id' => $patient->id,
                'reason' => $request->reason,
                'start_date' => $request->start_date,
                'days_needed' => $request->days_needed
            ]);
            $notification->save();
            
            // Se o médico tiver configurado para receber emails de solicitação
            $doctor = \App\Models\Doctor::with('user')->find($request->doctor_id);
            if ($doctor->user->notification_settings['email_certificate_requests'] ?? true) {
                \Mail::to($doctor->user->email)->send(new \App\Mail\CertificateRequest(
                    $doctor->user,
                    $patient->user,
                    $request->reason,
                    $request->start_date,
                    $request->days_needed
                ));
            }
            
            return redirect()->route('patient.certificates.index')
                ->with('success', 'Solicitação de atestado enviada com sucesso. O médico analisará seu pedido em breve.');
                
        } catch (\Exception $e) {
            return back()->with('error', 'Ocorreu um erro ao enviar a solicitação: ' . $e->getMessage())
                ->withInput();
        }
    }
    
    /**
     * Display the form to request a new certificate.
     *
     * @return \Illuminate\View\View
     */
    public function requestForm()
    {
        $patient = Auth::user()->patient;
        
        // Buscar médicos com quem o paciente já se consultou
        $doctors = MedicalRecord::where('patient_id', $patient->id)
            ->distinct('doctor_id')
            ->with('doctor.user', 'doctor.specialties')
            ->get()
            ->pluck('doctor')
            ->unique('id');
            
        if ($doctors->isEmpty()) {
            return redirect()->route('patient.certificates.index')
                ->with('error', 'Você precisa ter se consultado com pelo menos um médico para solicitar um atestado.');
        }
        
        return view('patient.certificates.request', [
            'doctors' => $doctors
        ]);
    }
    
    /**
     * Get current valid certificates.
     *
     * @return \Illuminate\View\View
     */
    public function currentCertificates()
    {
        $patient = Auth::user()->patient;
        $today = Carbon::today();
        
        $activeCertificates = Certificate::whereHas('medicalRecord', function($query) use ($patient) {
            $query->where('patient_id', $patient->id);
        })
        ->where('end_date', '>=', $today)
        ->with(['medicalRecord.doctor.user'])
        ->orderBy('end_date')
        ->get();
        
        $certificateTypes = [
            'sick_leave' => 'Atestado Médico',
            'medical_certificate' => 'Certificado Médico',
            'other' => 'Outro'
        ];
        
        return view('patient.certificates.active', [
            'certificates' => $activeCertificates,
            'certificateTypes' => $certificateTypes
        ]);
    }
}