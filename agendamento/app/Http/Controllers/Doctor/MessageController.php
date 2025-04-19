<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\MessageThread;
use App\Models\User;
use App\Models\Patient;
use App\Models\Doctor;
use App\Models\Staff;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class MessageController extends Controller
{
    /**
     * Display a listing of message threads.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $doctor = Auth::user()->doctor;
        $user = Auth::user();
        
        // Base query
        $query = MessageThread::where(function($query) use ($user) {
                $query->where('creator_id', $user->id)
                    ->orWhereHas('participants', function($q) use ($user) {
                        $q->where('user_id', $user->id);
                    });
            })
            ->with(['lastMessage', 'creator', 'participants.user']);
        
        // Filtro por status (não lidas)
        if ($request->has('unread_only') && $request->unread_only) {
            $query->whereHas('messages', function($q) use ($user) {
                $q->where('is_read', false)
                  ->where('recipient_id', $user->id);
            });
        }
        
        // Filtro por assunto
        if ($request->has('subject') && $request->subject) {
            $query->where('subject', 'like', '%' . $request->subject . '%');
        }
        
        // Filtro por participante
        if ($request->has('participant') && $request->participant) {
            $participantName = $request->participant;
            $query->where(function($q) use ($participantName) {
                $q->whereHas('creator', function($q) use ($participantName) {
                    $q->where('name', 'like', '%' . $participantName . '%');
                })
                ->orWhereHas('participants.user', function($q) use ($participantName) {
                    $q->where('name', 'like', '%' . $participantName . '%');
                });
            });
        }
        
        // Filtro por tipo de participante
        if ($request->has('participant_type') && $request->participant_type) {
            $type = $request->participant_type;
            
            $query->where(function($q) use ($type) {
                if ($type === 'patient') {
                    $q->whereHas('participants.user', function($q) {
                        $q->whereHas('patient');
                    })
                    ->orWhereHas('creator', function($q) {
                        $q->whereHas('patient');
                    });
                } elseif ($type === 'doctor') {
                    $q->whereHas('participants.user', function($q) {
                        $q->whereHas('doctor');
                    })
                    ->orWhereHas('creator', function($q) {
                        $q->whereHas('doctor');
                    });
                } elseif ($type === 'staff') {
                    $q->whereHas('participants.user', function($q) {
                        $q->whereHas('staff');
                    })
                    ->orWhereHas('creator', function($q) {
                        $q->whereHas('staff');
                    });
                }
            });
        }
        
        // Ordenação
        $sortBy = $request->sort_by ?? 'updated_at';
        $sortDirection = $request->sort_direction ?? 'desc';
        
        $messageThreads = $query->orderBy($sortBy, $sortDirection)
            ->paginate(15)
            ->withQueryString();
            
        // Contar mensagens não lidas por thread
        $unreadCounts = [];
        foreach ($messageThreads as $thread) {
            $unreadCounts[$thread->id] = Message::where('thread_id', $thread->id)
                ->where('recipient_id', $user->id)
                ->where('is_read', false)
                ->count();
        }
        
        return view('doctor.messages.index', [
            'messageThreads' => $messageThreads,
            'unreadCounts' => $unreadCounts,
            'filters' => $request->only(['unread_only', 'subject', 'participant', 'participant_type', 'sort_by', 'sort_direction'])
        ]);
    }
    
    /**
     * Show the form for creating a new message thread.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function create(Request $request)
    {
        $doctor = Auth::user()->doctor;
        
        // Pré-selecionar destinatário se fornecido
        $preSelectedRecipient = null;
        $preSelectedSubject = null;
        
        if ($request->has('recipient_id')) {
            $preSelectedRecipient = User::find($request->recipient_id);
        }
        
        if ($request->has('subject')) {
            $preSelectedSubject = $request->subject;
        }
        
        // Buscar pacientes recentemente atendidos
        $recentPatients = Patient::whereHas('appointments', function($q) use ($doctor) {
            $q->where('doctor_id', $doctor->id)
              ->orderBy('start_time', 'desc');
        })
        ->with('user')
        ->limit(10)
        ->get()
        ->pluck('user');
        
        // Buscar outros médicos
        $otherDoctors = Doctor::where('id', '!=', $doctor->id)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get()
            ->pluck('user');
            
        // Buscar equipe de apoio
        $staffMembers = Staff::with('user')
            ->orderBy('staff_type')
            ->get()
            ->pluck('user');
        
        return view('doctor.messages.create', [
            'recentPatients' => $recentPatients,
            'otherDoctors' => $otherDoctors,
            'staffMembers' => $staffMembers,
            'preSelectedRecipient' => $preSelectedRecipient,
            'preSelectedSubject' => $preSelectedSubject
        ]);
    }
    
    /**
     * Store a newly created message thread.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'recipient_id' => 'required|exists:users,id',
            'subject' => 'required|string|max:100',
            'message' => 'required|string|max:5000',
            'attachments.*' => 'nullable|file|max:10240', // 10MB max por arquivo
            'appointment_id' => 'nullable|exists:appointments,id',
            'is_urgent' => 'nullable|boolean'
        ]);
        
        $user = Auth::user();
        $recipientId = $request->recipient_id;
        
        // Verificar se o destinatário existe
        $recipient = User::findOrFail($recipientId);
        
        try {
            // Criar nova thread
            $thread = new MessageThread();
            $thread->creator_id = $user->id;
            $thread->subject = $request->subject;
            $thread->is_urgent = $request->has('is_urgent') && $request->is_urgent ? true : false;
            
            if ($request->has('appointment_id') && $request->appointment_id) {
                $appointment = Appointment::find($request->appointment_id);
                if ($appointment && ($appointment->doctor_id == $user->doctor->id || $appointment->patient->user_id == $recipientId)) {
                    $thread->appointment_id = $request->appointment_id;
                }
            }
            
            $thread->save();
            
            // Adicionar participante
            $thread->participants()->create([
                'user_id' => $recipientId
            ]);
            
            // Criar a primeira mensagem
            $message = new Message();
            $message->thread_id = $thread->id;
            $message->sender_id = $user->id;
            $message->recipient_id = $recipientId;
            $message->content = $request->message;
            $message->is_read = false;
            $message->save();
            
            // Processar anexos
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $fileName = time() . '_' . $file->getClientOriginalName();
                    $filePath = $file->storeAs('message_attachments', $fileName, 'public');
                    
                    $message->attachments()->create([
                        'file_name' => $file->getClientOriginalName(),
                        'file_path' => $filePath,
                        'file_size' => $file->getSize(),
                        'file_type' => $file->getMimeType()
                    ]);
                }
            }
            
            // Criar notificação para o destinatário
            $senderName = $user->name;
            \App\Models\Notification::create([
                'user_id' => $recipientId,
                'type' => $thread->is_urgent ? 'urgent_message' : 'new_message',
                'title' => $thread->is_urgent ? 'Mensagem Urgente' : 'Nova Mensagem',
                'message' => $thread->is_urgent 
                    ? "Mensagem urgente de {$senderName}: {$thread->subject}"
                    : "Nova mensagem de {$senderName}: {$thread->subject}",
                'data' => json_encode([
                    'thread_id' => $thread->id,
                    'message_id' => $message->id,
                    'sender_id' => $user->id,
                    'sender_name' => $senderName
                ]),
                'status' => 'unread'
            ]);
            
            return redirect()->route('doctor.messages.show', $thread->id)
                ->with('success', 'Mensagem enviada com sucesso!');
                
        } catch (\Exception $e) {
            return back()->with('error', 'Ocorreu um erro ao enviar a mensagem: ' . $e->getMessage())
                ->withInput();
        }
    }
    
    /**
     * Display the specified message thread.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function show($id)
    {
        $user = Auth::user();
        
        // Verificar acesso à thread
        $thread = MessageThread::where(function($query) use ($user) {
                $query->where('creator_id', $user->id)
                    ->orWhereHas('participants', function($q) use ($user) {
                        $q->where('user_id', $user->id);
                    });
            })
            ->with(['creator', 'participants.user', 'appointment.patient.user', 'appointment.doctor.user'])
            ->findOrFail($id);
            
        // Buscar mensagens ordenadas por data
        $messages = Message::where('thread_id', $thread->id)
            ->with(['sender', 'recipient', 'attachments'])
            ->orderBy('created_at', 'asc')
            ->get();
        
        // Marcar mensagens não lidas como lidas
        Message::where('thread_id', $thread->id)
            ->where('recipient_id', $user->id)
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => Carbon::now()]);
            
        // Determinar o outro participante (para exibição)
        $otherParticipant = null;
        
        if ($thread->creator_id != $user->id) {
            $otherParticipant = $thread->creator;
        } else {
            $participants = $thread->participants;
            if ($participants->count() > 0) {
                $otherParticipant = $participants->first()->user;
            }
        }
        
        return view('doctor.messages.show', [
            'thread' => $thread,
            'messages' => $messages,
            'otherParticipant' => $otherParticipant
        ]);
    }
    
    /**
     * Reply to an existing message thread.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function reply(Request $request, $id)
    {
        $request->validate([
            'message' => 'required|string|max:5000',
            'attachments.*' => 'nullable|file|max:10240' // 10MB max por arquivo
        ]);
        
        $user = Auth::user();
        
        // Verificar acesso à thread
        $thread = MessageThread::where(function($query) use ($user) {
                $query->where('creator_id', $user->id)
                    ->orWhereHas('participants', function($q) use ($user) {
                        $q->where('user_id', $user->id);
                    });
            })
            ->findOrFail($id);
            
        try {
            // Determinar o destinatário
            $recipientId = null;
            
            if ($thread->creator_id == $user->id) {
                // Se o usuário atual é o criador, enviar para o primeiro participante
                $participant = $thread->participants->first();
                if ($participant) {
                    $recipientId = $participant->user_id;
                }
            } else {
                // Caso contrário, enviar para o criador
                $recipientId = $thread->creator_id;
            }
            
            if (!$recipientId) {
                return back()->with('error', 'Não foi possível determinar o destinatário.');
            }
            
            // Atualizar a thread
            $thread->touch();
            
            // Criar a mensagem de resposta
            $message = new Message();
            $message->thread_id = $thread->id;
            $message->sender_id = $user->id;
            $message->recipient_id = $recipientId;
            $message->content = $request->message;
            $message->is_read = false;
            $message->save();
            
            // Processar anexos
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $fileName = time() . '_' . $file->getClientOriginalName();
                    $filePath = $file->storeAs('message_attachments', $fileName, 'public');
                    
                    $message->attachments()->create([
                        'file_name' => $file->getClientOriginalName(),
                        'file_path' => $filePath,
                        'file_size' => $file->getSize(),
                        'file_type' => $file->getMimeType()
                    ]);
                }
            }
            
            // Criar notificação para o destinatário
            $senderName = $user->name;
            \App\Models\Notification::create([
                'user_id' => $recipientId,
                'type' => $thread->is_urgent ? 'urgent_message' : 'message_reply',
                'title' => $thread->is_urgent ? 'Resposta Urgente' : 'Nova Resposta',
                'message' => $thread->is_urgent 
                    ? "Resposta urgente de {$senderName}: {$thread->subject}"
                    : "Nova resposta de {$senderName}: {$thread->subject}",
                'data' => json_encode([
                    'thread_id' => $thread->id,
                    'message_id' => $message->id,
                    'sender_id' => $user->id,
                    'sender_name' => $senderName
                ]),
                'status' => 'unread'
            ]);
            
            return redirect()->route('doctor.messages.show', $thread->id)
                ->with('success', 'Resposta enviada com sucesso!');
                
        } catch (\Exception $e) {
            return back()->with('error', 'Ocorreu um erro ao enviar a resposta: ' . $e->getMessage())
                ->withInput();
        }
    }
    
    /**
     * Download a message attachment.
     *
     * @param  int  $messageId
     * @param  int  $attachmentId
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function downloadAttachment($messageId, $attachmentId)
    {
        $user = Auth::user();
        
        // Verificar acesso à mensagem
        $message = Message::where(function($query) use ($user) {
                $query->where('sender_id', $user->id)
                    ->orWhere('recipient_id', $user->id);
            })
            ->findOrFail($messageId);
        
        // Buscar o anexo
        $attachment = $message->attachments()->findOrFail($attachmentId);
        
        // Verificar se o arquivo existe
        if (!Storage::disk('public')->exists($attachment->file_path)) {
            return back()->with('error', 'Arquivo não encontrado no servidor.');
        }
        
        // Registrar no log de auditoria
        \App\Models\AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'download',
            'model_type' => 'MessageAttachment',
            'model_id' => $attachment->id,
            'description' => 'Download de anexo de mensagem'
        ]);
        
        return response()->download(
            storage_path('app/public/' . $attachment->file_path),
            $attachment->file_name
        );
    }
    
    /**
     * Mark a thread as urgent or non-urgent.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function toggleUrgent(Request $request, $id)
    {
        $user = Auth::user();
        
        // Apenas o criador pode alterar a prioridade da thread
        $thread = MessageThread::where('creator_id', $user->id)
            ->findOrFail($id);
            
        try {
            $thread->is_urgent = !$thread->is_urgent;
            $thread->save();
            
            $status = $thread->is_urgent ? 'marcada como urgente' : 'desmarcada como urgente';
            return back()->with('success', "Conversa {$status} com sucesso!");
            
        } catch (\Exception $e) {
            return back()->with('error', 'Ocorreu um erro ao alterar a prioridade da conversa.');
        }
    }
    
    /**
     * Delete a message thread (soft delete).
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        $user = Auth::user();
        
        // Verificar acesso à thread
        $thread = MessageThread::where(function($query) use ($user) {
                $query->where('creator_id', $user->id)
                    ->orWhereHas('participants', function($q) use ($user) {
                        $q->where('user_id', $user->id);
                    });
            })
            ->findOrFail($id);
            
        try {
            if ($thread->creator_id == $user->id) {
                $thread->creator_deleted_at = Carbon::now();
            } else {
                // Marcar como excluído pelo participante
                $participant = $thread->participants()
                    ->where('user_id', $user->id)
                    ->first();
                    
                if ($participant) {
                    $participant->deleted_at = Carbon::now();
                    $participant->save();
                }
            }
            
            $thread->save();
            
            return redirect()->route('doctor.messages.index')
                ->with('success', 'Conversa excluída com sucesso!');
                
        } catch (\Exception $e) {
            return back()->with('error', 'Ocorreu um erro ao excluir a conversa.');
        }
    }
    
    /**
     * Show the form for composing a message to a specific patient.
     *
     * @param  int  $patientId
     * @return \Illuminate\View\View
     */
    public function composeToPatient($patientId)
    {
        $doctor = Auth::user()->doctor;
        
        // Verificar se o médico pode enviar mensagem para este paciente
        $patient = Patient::with('user')
            ->whereHas('appointments', function($q) use ($doctor) {
                $q->where('doctor_id', $doctor->id);
            })
            ->findOrFail($patientId);
            
        // Buscar consultas recentes com este paciente para contexto
        $recentAppointments = Appointment::where('doctor_id', $doctor->id)
            ->where('patient_id', $patientId)
            ->whereIn('status', ['completed', 'confirmed'])
            ->orderBy('start_time', 'desc')
            ->limit(5)
            ->get();
        
        return view('doctor.messages.compose_to_patient', [
            'patient' => $patient,
            'recentAppointments' => $recentAppointments
        ]);
    }
    
    /**
     * Show the form for composing a message about a specific appointment.
     *
     * @param  int  $appointmentId
     * @return \Illuminate\View\View
     */
    public function composeAboutAppointment($appointmentId)
    {
        $doctor = Auth::user()->doctor;
        
        // Verificar se o médico tem acesso a esta consulta
        $appointment = Appointment::where('doctor_id', $doctor->id)
            ->with('patient.user')
            ->findOrFail($appointmentId);
            
        // Sugerir título baseado na consulta
        $suggestedSubject = 'Consulta de ' . $appointment->start_time->format('d/m/Y H:i');
        
        return view('doctor.messages.compose_about_appointment', [
            'appointment' => $appointment,
            'suggestedSubject' => $suggestedSubject
        ]);
    }
    
    /**
     * Search for users to send messages to.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function searchUsers(Request $request)
    {
        $doctor = Auth::user()->doctor;
        $search = $request->q;
        $type = $request->type ?? 'all'; // all, patients, doctors, staff
        
        $query = User::where('name', 'like', "%{$search}%");
        
        // Filtrar por tipo
        if ($type == 'patients') {
            $query->whereHas('patient', function($q) use ($doctor) {
                // Apenas pacientes que este médico já atendeu
                $q->whereHas('appointments', function($q) use ($doctor) {
                    $q->where('doctor_id', $doctor->id);
                });
            });
        } elseif ($type == 'doctors') {
            $query->whereHas('doctor')
                  ->where('id', '!=', Auth::id()); // Excluir o próprio médico
        } elseif ($type == 'staff') {
            $query->whereHas('staff');
        } else {
            // Para 'all', mostra pacientes atendidos, outros médicos e staff
            $query->where(function($q) use ($doctor) {
                $q->whereHas('patient', function($q) use ($doctor) {
                    $q->whereHas('appointments', function($q) use ($doctor) {
                        $q->where('doctor_id', $doctor->id);
                    });
                })
                ->orWhereHas('doctor', function($q) use ($doctor) {
                    $q->where('id', '!=', $doctor->id);
                })
                ->orWhereHas('staff');
            });
        }
        
        $users = $query->limit(10)->get();
        
        $formattedUsers = $users->map(function ($user) {
            $role = '';
            if ($user->patient) {
                $role = 'Paciente';
            } elseif ($user->doctor) {
                $role = 'Médico';
            } elseif ($user->staff) {
                $role = ucfirst($user->staff->staff_type);
            }
            
            return [
                'id' => $user->id,
                'text' => $user->name . ' (' . $role . ')'
            ];
        });
        
        return response()->json(['results' => $formattedUsers]);
    }
    
    /**
     * Mark all messages as read.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function markAllAsRead()
    {
        $user = Auth::user();
        
        try {
            Message::where('recipient_id', $user->id)
                ->where('is_read', false)
                ->update(['is_read' => true, 'read_at' => Carbon::now()]);
                
            return redirect()->route('doctor.messages.index')
                ->with('success', 'Todas as mensagens foram marcadas como lidas.');
                
        } catch (\Exception $e) {
            return back()->with('error', 'Ocorreu um erro ao marcar as mensagens como lidas.');
        }
    }
    
    /**
     * Get unread messages count.
     *
     * @return \Illuminate\Http\Response
     */
    public function getUnreadCount()
    {
        $user = Auth::user();
        
        $count = Message::where('recipient_id', $user->id)
            ->where('is_read', false)
            ->count();
            
        return response()->json(['count' => $count]);
    }
}