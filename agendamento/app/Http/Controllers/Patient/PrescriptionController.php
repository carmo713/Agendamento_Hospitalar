<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\MedicalRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use PDF;

class PrescriptionController extends Controller
{
    /**
     * Display a listing of the patient's prescriptions.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $patient = Auth::user()->patient;
        
        $query = Prescription::whereHas('medicalRecord', function($query) use ($patient) {
            $query->where('patient_id', $patient->id);
        })->with(['medicalRecord.doctor.user']);
        
        // Filtro por data
        if ($request->has('start_date') && $request->start_date) {
            $query->where('issue_date', '>=', $request->start_date);
        }
        
        if ($request->has('end_date') && $request->end_date) {
            $query->where('issue_date', '<=', $request->end_date);
        }
        
        // Filtro por médico
        if ($request->has('doctor_id') && $request->doctor_id) {
            $query->whereHas('medicalRecord', function($query) use ($request) {
                $query->where('doctor_id', $request->doctor_id);
            });
        }
        
        // Filtro por termo de busca
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('code', 'like', "%$search%")
                  ->orWhereHas('prescriptionItems', function($q) use ($search) {
                      $q->where('medication', 'like', "%$search%");
                  });
            });
        }
        
        $prescriptions = $query->orderBy('issue_date', 'desc')
            ->paginate(10)
            ->withQueryString();
        
        // Buscar médicos para filtro
        $doctors = MedicalRecord::where('patient_id', $patient->id)
            ->distinct()
            ->with('doctor.user')
            ->get()
            ->pluck('doctor')
            ->unique('id');
        
        return view('patient.prescriptions.index', [
            'prescriptions' => $prescriptions,
            'doctors' => $doctors,
            'filters' => $request->only(['start_date', 'end_date', 'doctor_id', 'search'])
        ]);
    }
    
    /**
     * Display the specified prescription.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function show($id)
    {
        $patient = Auth::user()->patient;
        
        $prescription = Prescription::whereHas('medicalRecord', function($query) use ($patient) {
            $query->where('patient_id', $patient->id);
        })
        ->with([
            'medicalRecord.doctor.user', 
            'medicalRecord.doctor.specialties',
            'prescriptionItems'
        ])
        ->findOrFail($id);
        
        $isExpired = Carbon::parse($prescription->expiration_date)->isPast();
        
        return view('patient.prescriptions.show', [
            'prescription' => $prescription,
            'isExpired' => $isExpired
        ]);
    }
    
    /**
     * Download the prescription as PDF.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function downloadPdf($id)
    {
        $patient = Auth::user()->patient;
        
        $prescription = Prescription::whereHas('medicalRecord', function($query) use ($patient) {
            $query->where('patient_id', $patient->id);
        })
        ->with([
            'medicalRecord.doctor.user', 
            'medicalRecord.doctor.specialties',
            'prescriptionItems',
            'medicalRecord.patient.user'
        ])
        ->findOrFail($id);
        
        $pdf = PDF::loadView('pdfs.prescription', [
            'prescription' => $prescription,
            'patient' => $patient,
            'doctor' => $prescription->medicalRecord->doctor,
            'items' => $prescription->prescriptionItems
        ]);
        
        $filename = 'receita_' . $prescription->code . '.pdf';
        
        return $pdf->download($filename);
    }
    
    /**
     * Print the prescription.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function print($id)
    {
        $patient = Auth::user()->patient;
        
        $prescription = Prescription::whereHas('medicalRecord', function($query) use ($patient) {
            $query->where('patient_id', $patient->id);
        })
        ->with([
            'medicalRecord.doctor.user', 
            'medicalRecord.doctor.specialties',
            'prescriptionItems',
            'medicalRecord.patient.user'
        ])
        ->findOrFail($id);
        
        return view('patient.prescriptions.print', [
            'prescription' => $prescription,
            'patient' => $patient,
            'doctor' => $prescription->medicalRecord->doctor,
            'items' => $prescription->prescriptionItems
        ]);
    }
    
    /**
     * Share the prescription by email.
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
        
        $prescription = Prescription::whereHas('medicalRecord', function($query) use ($patient) {
            $query->where('patient_id', $patient->id);
        })->findOrFail($id);
        
        try {
            // Gerar PDF
            $pdf = PDF::loadView('pdfs.prescription', [
                'prescription' => $prescription,
                'patient' => $patient,
                'doctor' => $prescription->medicalRecord->doctor,
                'items' => $prescription->prescriptionItems
            ]);
            
            $filename = 'receita_' . $prescription->code . '.pdf';
            $pdfPath = storage_path('app/temp/' . $filename);
            $pdf->save($pdfPath);
            
            // Enviar email
            \Mail::send('emails.prescription.share', [
                'patient' => $patient->user->name,
                'message' => $request->message,
                'prescription' => $prescription
            ], function($message) use ($request, $pdfPath, $filename) {
                $message->to($request->email)
                    ->subject('Receita Médica Compartilhada')
                    ->attach($pdfPath, [
                        'as' => $filename,
                        'mime' => 'application/pdf',
                    ]);
            });
            
            // Remover arquivo temporário
            if (file_exists($pdfPath)) {
                unlink($pdfPath);
            }
            
            return back()->with('success', 'Receita enviada com sucesso para ' . $request->email);
            
        } catch (\Exception $e) {
            return back()->with('error', 'Ocorreu um erro ao compartilhar a receita: ' . $e->getMessage());
        }
    }
    
    /**
     * Show the form for requesting a prescription renewal.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function renewalForm($id)
    {
        $patient = Auth::user()->patient;
        
        $prescription = Prescription::whereHas('medicalRecord', function($query) use ($patient) {
            $query->where('patient_id', $patient->id);
        })
        ->with([
            'medicalRecord.doctor.user', 
            'prescriptionItems'
        ])
        ->findOrFail($id);
        
        return view('patient.prescriptions.request-renewal', [
            'prescription' => $prescription
        ]);
    }
    
    /**
     * Request a prescription renewal.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function requestRenewal(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
            'urgent' => 'nullable|boolean'
        ]);
        
        $patient = Auth::user()->patient;
        
        $prescription = Prescription::whereHas('medicalRecord', function($query) use ($patient) {
            $query->where('patient_id', $patient->id);
        })
        ->with(['medicalRecord.doctor.user'])
        ->findOrFail($id);
        
        try {
            // Criar notificação para o médico
            $doctor = $prescription->medicalRecord->doctor;
            $doctorUser = $doctor->user;
            
            $notification = new \App\Models\Notification();
            $notification->user_id = $doctorUser->id;
            $notification->type = 'prescription_renewal';
            $notification->title = 'Solicitação de Renovação de Receita';
            $notification->message = 'O paciente ' . $patient->user->name . ' solicitou a renovação de uma receita.';
            $notification->data = json_encode([
                'prescription_id' => $prescription->id,
                'patient_id' => $patient->id,
                'reason' => $request->reason,
                'urgent' => $request->has('urgent')
            ]);
            $notification->save();
            
            // Enviar email para o médico
            if ($doctorUser->notification_settings['email_prescription_renewal'] ?? true) {
                \Mail::to($doctorUser->email)->send(new \App\Mail\PrescriptionRenewalRequest(
                    $doctorUser,
                    $patient->user,
                    $prescription,
                    $request->reason,
                    $request->has('urgent')
                ));
            }
            
            return redirect()->route('patient.prescriptions.show', $prescription->id)
                ->with('success', 'Solicitação de renovação enviada com sucesso. O médico analisará seu pedido em breve.');
                
        } catch (\Exception $e) {
            return back()->with('error', 'Ocorreu um erro ao solicitar a renovação. Por favor, tente novamente.')
                ->withInput();
        }
    }
    
    /**
     * Show the list of medications from the patient's prescriptions.
     *
     * @return \Illuminate\View\View
     */
    public function medications()
    {
        $patient = Auth::user()->patient;
        
        $medications = PrescriptionItem::whereHas('prescription', function($query) use ($patient) {
            $query->whereHas('medicalRecord', function($query) use ($patient) {
                $query->where('patient_id', $patient->id);
            });
        })
        ->select('medication')
        ->distinct()
        ->orderBy('medication')
        ->get()
        ->map(function($item) use ($patient) {
            // Para cada medicamento, buscar a última prescrição
            $lastPrescription = PrescriptionItem::where('medication', $item->medication)
                ->whereHas('prescription', function($query) use ($patient) {
                    $query->whereHas('medicalRecord', function($query) use ($patient) {
                        $query->where('patient_id', $patient->id);
                    });
                })
                ->with('prescription')
                ->orderBy('created_at', 'desc')
                ->first();
            
            return [
                'medication' => $item->medication,
                'last_prescription' => $lastPrescription->prescription->issue_date,
                'dosage' => $lastPrescription->dosage,
                'frequency' => $lastPrescription->frequency,
                'active' => Carbon::parse($lastPrescription->prescription->expiration_date)->isFuture()
            ];
        });
        
        return view('patient.prescriptions.medications', [
            'medications' => $medications
        ]);
    }
}