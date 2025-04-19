<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\Doctor;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MessageController extends Controller
{
    /**
     * Display a listing of the patient's conversations.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $userId = Auth::id();
        $patient = Auth::user()->patient;
        
        // Buscar todas as conversas do paciente com médicos
        // Agrupadas por médico, com a mensagem mais recente
        $conversations = Message::where(function ($query) use ($userId) {
                $query->where('sender_id', $userId)
                    ->orWhere('receiver_id', $userId);
            })
            ->select(
                DB::raw('CASE 
                    WHEN sender_id = ' . $userId . ' THEN receiver_id 
                    WHEN receiver_id = ' . $userId . ' THEN sender_id 
                    END as conversation_with_id'),
                DB::raw('MAX(created_at) as latest_message_time')
            )
            ->groupBy('conversation_with_id')
            ->orderBy('latest_message_time', 'desc')
            ->get()
            ->pluck('conversation_with_id');
        
        // Buscar informações dos médicos
        $doctors = collect();
        
        if ($conversations->count() > 0) {
            $doctors = Doctor::whereHas('user', function ($query) use ($conversations) {
                    $query->whereIn('id', $conversations);
                })
                ->with(['user'])
                ->get();
            
            // Adicionar a última mensagem e quantas não lidas para cada médico
            foreach ($doctors as $doctor) {
                $lastMessage = Message::where(function ($query) use ($userId, $doctor) {
                        $query->where(function ($q) use ($userId, $doctor) {
                            $q->where('sender_id', $userId)
                                ->where('receiver_id', $doctor->user->id);
                        })->orWhere(function ($q) use ($userId, $doctor) {
                            $q->where('sender_id', $doctor->user->id)
                                ->where('receiver_id', $userId);
                        });
                    })
                    ->orderBy('created_at', 'desc')
                    ->first();
                
                $unreadCount = Message::where('sender_id', $doctor->user->id)
                    ->where('receiver_id', $userId)
                    ->whereNull('read_at')
                    ->count();
                
                $doctor->last_message = $lastMessage;
                $doctor->unread_count = $unreadCount;
            }
        }
        
        // Buscar médicos com quem o paciente teve consultas, mas ainda não tem conversas
        $appointmentDoctors = Appointment::where('patient_id', $patient->id)
            ->where('status', 'completed')
            ->whereNotIn('doctor_id', $doctors->pluck('id')->toArray())
            ->with('doctor.user')
            ->groupBy('doctor_id')
            ->get()
            ->pluck('doctor');
        
        return view('patient.messages.index', [
            'doctors' => $doctors,
            'appointmentDoctors' => $appointmentDoctors
        ]);
    }
    
    /**
     * Display the conversation with a specific doctor.
     *
     * @param  int  $doctorId
     * @return \Illuminate\View\View
     */
    public function show($doctorId)
    {
        $patient = Auth::user()->patient;
        $userId = Auth::id();
        
        $doctor = Doctor::with('user')->findOrFail($doctorId);
        $doctorUserId = $doctor->user->id;
        
        // Verificar se o paciente tem uma consulta com este médico
        $hasAppointment = Appointment::where('patient_id', $patient->id)
            ->where('doctor_id', $doctorId)
            ->whereIn('status', ['completed', 'scheduled', 'confirmed'])
            ->exists();
        
        if (!$hasAppointment) {
            return redirect()->route('patient.messages.index')
                ->with('error', 'Você não pode enviar mensagens para este médico.');
        }
        
        // Consultas com este médico para referência
        $appointments = Appointment::where('patient_id', $patient->id)
            ->where('doctor_id', $doctorId)
            ->whereIn('status', ['scheduled', 'confirmed', 'completed'])
            ->orderBy('start_time', 'desc')
            ->get();
        
        // Buscar mensagens da conversa
        $messages = Message::where(function ($query) use ($userId, $doctorUserId) {
                $query->where(function ($q) use ($userId, $doctorUserId) {
                    $q->where('sender_id', $userId)
                        ->where('receiver_id', $doctorUserId);
                })->orWhere(function ($q) use ($userId, $doctorUserId) {
                    $q->where('sender_id', $doctorUserId)
                        ->where('receiver_id', $userId);
                });
            })
            ->orderBy('created_at', 'asc')
            ->get();
        
        // Marcar mensagens recebidas como lidas
        Message::where('sender_id', $doctorUserId)
            ->where('receiver_id', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => Carbon::now()]);
        
        return view('patient.messages.show', [
            'doctor' => $doctor,
            'messages' => $messages,
            'appointments' => $appointments
        ]);
    }
    
    /**
     * Send a new message to a doctor.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $doctorId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function send(Request $request, $doctorId)
    {
        $request->validate([
            'message' => 'required|string|max:1000',
            'appointment_id' => 'nullable|exists:appointments,id'
        ]);
        
        $patient = Auth::user()->patient;
        $userId = Auth::id();
        
        $doctor = Doctor::with('user')->findOrFail($doctorId);
        $doctorUserId = $doctor->user->id;
        
        // Verificar se o paciente tem uma consulta com este médico
        $hasAppointment = Appointment::where('patient_id', $patient->id)
            ->where('doctor_id', $doctorId)
            ->whereIn('status', ['completed', 'scheduled', 'confirmed'])
            ->exists();
        
        if (!$hasAppointment) {
            return redirect()->route('patient.messages.index')
                ->with('error', 'Você não pode enviar mensagens para este médico.');
        }
        
        // Verificar se o appointment_id pertence ao paciente com este médico
        if ($request->appointment_id) {
            $appointmentExists = Appointment::where('id', $request->appointment_id)
                ->where('patient_id', $patient->id)
                ->where('doctor_id', $doctorId)
                ->exists();
                
            if (!$appointmentExists) {
                return back()->with('error', 'Consulta inválida selecionada.');
            }
        }
        
        try {
            // Criar a mensagem
            $message = new Message();
            $message->sender_id = $userId;
            $message->receiver_id = $doctorUserId;
            $message->appointment_id = $request->appointment_id;
            $message->message = $request->message;
            $message->save();
            
            return redirect()->route('patient.messages.show', $doctorId)
                ->with('success', 'Mensagem enviada com sucesso!');
                
        } catch (\Exception $e) {
            return back()->with('error', 'Ocorreu um erro ao enviar a mensagem. Por favor, tente novamente.');
        }
    }
    
    /**
     * Start a new conversation with a doctor.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function startConversation(Request $request)
    {
        $request->validate([
            'doctor_id' => 'required|exists:doctors,id',
            'message' => 'required|string|max:1000',
        ]);
        
        $patient = Auth::user()->patient;
        $userId = Auth::id();
        
        $doctor = Doctor::with('user')->findOrFail($request->doctor_id);
        $doctorUserId = $doctor->user->id;
        
        // Verificar se o paciente tem uma consulta com este médico
        $hasAppointment = Appointment::where('patient_id', $patient->id)
            ->where('doctor_id', $doctor->id)
            ->whereIn('status', ['completed', 'scheduled', 'confirmed'])
            ->exists();
        
        if (!$hasAppointment) {
            return redirect()->route('patient.messages.index')
                ->with('error', 'Você não pode enviar mensagens para este médico.');
        }
        
        try {
            // Criar a mensagem
            $message = new Message();
            $message->sender_id = $userId;
            $message->receiver_id = $doctorUserId;
            $message->message = $request->message;
            $message->save();
            
            return redirect()->route('patient.messages.show', $doctor->id)
                ->with('success', 'Conversa iniciada com sucesso!');
                
        } catch (\Exception $e) {
            return back()->with('error', 'Ocorreu um erro ao iniciar a conversa. Por favor, tente novamente.');
        }
    }
}