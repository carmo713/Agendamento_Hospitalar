<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\MedicalRecord;
use App\Models\Certificate;
use App\Models\Prescription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MedicalRecordController extends Controller
{
    /**
     * Display a listing of the patient's medical records.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $patient = Auth::user()->patient;
        
        $query = MedicalRecord::where('patient_id', $patient->id)
            ->with(['doctor.user', 'appointment'])
            ->orderBy('date', 'desc');
        
        // Filtros opcionais
        if ($request->has('doctor')) {
            $query->where('doctor_id', $request->doctor);
        }
        
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        }
        
        $medicalRecords = $query->paginate(10)->withQueryString();
        
        // Buscar médicos para o filtro
        $doctors = MedicalRecord::where('patient_id', $patient->id)
            ->with('doctor.user')
            ->select('doctor_id')
            ->distinct()
            ->get()
            ->pluck('doctor.user.name', 'doctor.id');
        
        return view('patient.medical-records.index', [
            'medicalRecords' => $medicalRecords,
            'doctors' => $doctors
        ]);
    }
    
    /**
     * Display the specified medical record.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function show($id)
    {
        $medicalRecord = MedicalRecord::with([
                'doctor.user', 
                'appointment',
                'prescriptions.items',
                'certificates'
            ])
            ->where('patient_id', Auth::user()->patient->id)
            ->findOrFail($id);
        
        return view('patient.medical-records.show', [
            'medicalRecord' => $medicalRecord
        ]);
    }
    
    /**
     * Download a medical document (prescription or certificate).
     *
     * @param  string  $type
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function downloadDocument($type, $id)
    {
        $patient = Auth::user()->patient;
        
        if ($type === 'prescription') {
            $document = Prescription::with(['medicalRecord'])
                ->whereHas('medicalRecord', function ($query) use ($patient) {
                    $query->where('patient_id', $patient->id);
                })
                ->findOrFail($id);
                
            // Lógica para gerar PDF da receita
            $pdf = $this->generatePrescriptionPdf($document);
            return $pdf->download('receita_' . $document->code . '.pdf');
            
        } elseif ($type === 'certificate') {
            $document = Certificate::with(['medicalRecord'])
                ->whereHas('medicalRecord', function ($query) use ($patient) {
                    $query->where('patient_id', $patient->id);
                })
                ->findOrFail($id);
                
            // Lógica para gerar PDF do atestado
            $pdf = $this->generateCertificatePdf($document);
            return $pdf->download('atestado_' . $document->id . '.pdf');
            
        } else {
            abort(404);
        }
    }
    
    /**
     * Generate a PDF for a prescription.
     *
     * @param  \App\Models\Prescription  $prescription
     * @return \Barryvdh\DomPDF\PDF
     */
    private function generatePrescriptionPdf($prescription)
    {
        // Esta é uma implementação básica. Em um sistema real, 
        // você usaria uma biblioteca como DomPDF ou TCPDF
        
        // Exemplo com DomPDF:
        $doctor = $prescription->medicalRecord->doctor;
        $patient = $prescription->medicalRecord->patient;
        
        $pdf = \PDF::loadView('pdf.prescription', [
            'prescription' => $prescription,
            'doctor' => $doctor,
            'patient' => $patient
        ]);
        
        return $pdf;
    }
    
    /**
     * Generate a PDF for a medical certificate.
     *
     * @param  \App\Models\Certificate  $certificate
     * @return \Barryvdh\DomPDF\PDF
     */
    private function generateCertificatePdf($certificate)
    {
        // Implementação similar à do método anterior
        $doctor = $certificate->medicalRecord->doctor;
        $patient = $certificate->medicalRecord->patient;
        
        $pdf = \PDF::loadView('pdf.certificate', [
            'certificate' => $certificate,
            'doctor' => $doctor,
            'patient' => $patient
        ]);
        
        return $pdf;
    }
}