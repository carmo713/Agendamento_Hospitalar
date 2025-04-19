<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\HealthProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HealthProfileController extends Controller
{
    /**
     * Display the patient's health profile.
     *
     * @return \Illuminate\View\View
     */
    public function show()
    {
        $patient = Auth::user()->patient;
        $healthProfile = $patient->healthProfile ?? null;
        
        $bloodTypes = [
            'A+' => 'A positivo (A+)',
            'A-' => 'A negativo (A-)',
            'B+' => 'B positivo (B+)',
            'B-' => 'B negativo (B-)',
            'AB+' => 'AB positivo (AB+)',
            'AB-' => 'AB negativo (AB-)',
            'O+' => 'O positivo (O+)',
            'O-' => 'O negativo (O-)',
        ];
        
        return view('patient.health-profile.show', [
            'patient' => $patient,
            'healthProfile' => $healthProfile,
            'bloodTypes' => $bloodTypes
        ]);
    }
    
    /**
     * Show the form for editing the patient's health profile.
     *
     * @return \Illuminate\View\View
     */
    public function edit()
    {
        $patient = Auth::user()->patient;
        $healthProfile = $patient->healthProfile ?? new HealthProfile();
        
        $bloodTypes = [
            'A+' => 'A positivo (A+)',
            'A-' => 'A negativo (A-)',
            'B+' => 'B positivo (B+)',
            'B-' => 'B negativo (B-)',
            'AB+' => 'AB positivo (AB+)',
            'AB-' => 'AB negativo (AB-)',
            'O+' => 'O positivo (O+)',
            'O-' => 'O negativo (O-)',
        ];
        
        return view('patient.health-profile.edit', [
            'patient' => $patient,
            'healthProfile' => $healthProfile,
            'bloodTypes' => $bloodTypes
        ]);
    }
    
    /**
     * Update the patient's health profile.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request)
    {
        $request->validate([
            'blood_type' => 'nullable|string|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
            'height' => 'nullable|numeric|min:1|max:300',
            'weight' => 'nullable|numeric|min:0.1|max:500',
            'allergies' => 'nullable|string|max:1000',
            'chronic_diseases' => 'nullable|string|max:1000',
            'current_medications' => 'nullable|string|max:1000',
            'family_history' => 'nullable|string|max:1000',
        ]);
        
        $patient = Auth::user()->patient;
        $healthProfile = $patient->healthProfile ?? new HealthProfile(['patient_id' => $patient->id]);
        
        try {
            $healthProfile->blood_type = $request->blood_type;
            $healthProfile->height = $request->height;
            $healthProfile->weight = $request->weight;
            $healthProfile->allergies = $request->allergies;
            $healthProfile->chronic_diseases = $request->chronic_diseases;
            $healthProfile->current_medications = $request->current_medications;
            $healthProfile->family_history = $request->family_history;
            
            // Se é um novo perfil
            if (!$healthProfile->exists) {
                $healthProfile->patient_id = $patient->id;
            }
            
            $healthProfile->save();
            
            return redirect()->route('patient.health-profile.show')
                ->with('success', 'Perfil de saúde atualizado com sucesso!');
                
        } catch (\Exception $e) {
            return back()->with('error', 'Ocorreu um erro ao atualizar o perfil de saúde. Por favor, tente novamente.')
                ->withInput();
        }
    }
    
    /**
     * Generate a PDF with the patient's health profile information.
     *
     * @return \Illuminate\Http\Response
     */
    public function exportPdf()
    {
        $patient = Auth::user()->patient;
        $healthProfile = $patient->healthProfile;
        
        if (!$healthProfile) {
            return redirect()->route('patient.health-profile.show')
                ->with('error', 'Você precisa completar seu perfil de saúde antes de exportá-lo.');
        }
        
        $bloodTypes = [
            'A+' => 'A positivo (A+)',
            'A-' => 'A negativo (A-)',
            'B+' => 'B positivo (B+)',
            'B-' => 'B negativo (B-)',
            'AB+' => 'AB positivo (AB+)',
            'AB-' => 'AB negativo (AB-)',
            'O+' => 'O positivo (O+)',
            'O-' => 'O negativo (O-)',
        ];
        
        $pdf = \PDF::loadView('pdf.health-profile', [
            'patient' => $patient,
            'healthProfile' => $healthProfile,
            'bloodType' => $bloodTypes[$healthProfile->blood_type] ?? null
        ]);
        
        return $pdf->download('perfil_saude_' . $patient->user->name . '.pdf');
    }
}