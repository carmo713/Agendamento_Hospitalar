<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Feedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FeedbackController extends Controller
{
    /**
     * Display a listing of the patient's feedbacks.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $patient = Auth::user()->patient;
        
        $feedbacks = Feedback::where('patient_id', $patient->id)
            ->with(['doctor.user', 'appointment'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);
        
        return view('patient.feedbacks.index', [
            'feedbacks' => $feedbacks
        ]);
    }
    
    /**
     * Show the form for creating a new feedback for an appointment.
     *
     * @param  int  $appointmentId
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function create($appointmentId)
    {
        $patient = Auth::user()->patient;
        
        $appointment = Appointment::where('patient_id', $patient->id)
            ->where('id', $appointmentId)
            ->where('status', 'completed')
            ->with(['doctor.user', 'specialty'])
            ->first();
        
        if (!$appointment) {
            return redirect()->route('patient.appointments.index')
                ->with('error', 'Consulta não encontrada ou não elegível para avaliação.');
        }
        
        // Verificar se já existe feedback para esta consulta
        $existingFeedback = Feedback::where('appointment_id', $appointmentId)
            ->where('patient_id', $patient->id)
            ->first();
        
        if ($existingFeedback) {
            return redirect()->route('patient.feedbacks.edit', $existingFeedback->id)
                ->with('info', 'Você já avaliou esta consulta. Você pode editar sua avaliação.');
        }
        
        return view('patient.feedbacks.create', [
            'appointment' => $appointment
        ]);
    }
    
    /**
     * Store a newly created feedback.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $appointmentId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request, $appointmentId)
    {
        $request->validate([
            'rating' => 'required|integer|between:1,5',
            'comments' => 'required|string|max:1000',
            'anonymous' => 'nullable|boolean',
        ]);
        
        $patient = Auth::user()->patient;
        
        $appointment = Appointment::where('patient_id', $patient->id)
            ->where('id', $appointmentId)
            ->where('status', 'completed')
            ->first();
        
        if (!$appointment) {
            return redirect()->route('patient.appointments.index')
                ->with('error', 'Consulta não encontrada ou não elegível para avaliação.');
        }
        
        // Verificar se já existe feedback para esta consulta
        $existingFeedback = Feedback::where('appointment_id', $appointmentId)
            ->where('patient_id', $patient->id)
            ->first();
        
        if ($existingFeedback) {
            return redirect()->route('patient.feedbacks.edit', $existingFeedback->id)
                ->with('info', 'Você já avaliou esta consulta. Você pode editar sua avaliação.');
        }
        
        try {
            $feedback = new Feedback();
            $feedback->appointment_id = $appointment->id;
            $feedback->patient_id = $patient->id;
            $feedback->doctor_id = $appointment->doctor_id;
            $feedback->rating = $request->rating;
            $feedback->comments = $request->comments;
            $feedback->anonymous = $request->has('anonymous');
            $feedback->save();
            
            return redirect()->route('patient.appointments.show', $appointment->id)
                ->with('success', 'Avaliação enviada com sucesso! Obrigado pelo seu feedback.');
                
        } catch (\Exception $e) {
            return back()->with('error', 'Ocorreu um erro ao enviar a avaliação. Por favor, tente novamente.')
                ->withInput();
        }
    }
    
    /**
     * Show the form for editing a feedback.
     *
     * @param  int  $id
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function edit($id)
    {
        $patient = Auth::user()->patient;
        
        $feedback = Feedback::where('patient_id', $patient->id)
            ->where('id', $id)
            ->with(['appointment.doctor.user', 'appointment.specialty'])
            ->first();
        
        if (!$feedback) {
            return redirect()->route('patient.feedbacks.index')
                ->with('error', 'Avaliação não encontrada.');
        }
        
        return view('patient.feedbacks.edit', [
            'feedback' => $feedback
        ]);
    }
    
    /**
     * Update the specified feedback.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'rating' => 'required|integer|between:1,5',
            'comments' => 'required|string|max:1000',
            'anonymous' => 'nullable|boolean',
        ]);
        
        $patient = Auth::user()->patient;
        
        $feedback = Feedback::where('patient_id', $patient->id)
            ->where('id', $id)
            ->first();
        
        if (!$feedback) {
            return redirect()->route('patient.feedbacks.index')
                ->with('error', 'Avaliação não encontrada.');
        }
        
        try {
            $feedback->rating = $request->rating;
            $feedback->comments = $request->comments;
            $feedback->anonymous = $request->has('anonymous');
            $feedback->save();
            
            return redirect()->route('patient.feedbacks.index')
                ->with('success', 'Avaliação atualizada com sucesso!');
                
        } catch (\Exception $e) {
            return back()->with('error', 'Ocorreu um erro ao atualizar a avaliação. Por favor, tente novamente.')
                ->withInput();
        }
    }
    
    /**
     * Remove the specified feedback.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        $patient = Auth::user()->patient;
        
        $feedback = Feedback::where('patient_id', $patient->id)
            ->where('id', $id)
            ->first();
        
        if (!$feedback) {
            return redirect()->route('patient.feedbacks.index')
                ->with('error', 'Avaliação não encontrada.');
        }
        
        try {
            $feedback->delete();
            
            return redirect()->route('patient.feedbacks.index')
                ->with('success', 'Avaliação excluída com sucesso!');
                
        } catch (\Exception $e) {
            return back()->with('error', 'Ocorreu um erro ao excluir a avaliação. Por favor, tente novamente.');
        }
    }
}