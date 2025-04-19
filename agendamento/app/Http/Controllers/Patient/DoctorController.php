<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\Specialty;
use App\Models\Appointment;
use App\Models\Feedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DoctorController extends Controller
{
    /**
     * Display a listing of doctors with filters.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $specialtyId = $request->query('specialty');
        $name = $request->query('name');
        $rating = $request->query('rating');
        $gender = $request->query('gender');
        $sort = $request->query('sort', 'rating_desc');
        
        $query = Doctor::with(['user', 'specialties'])
            ->withAvg('feedbacks', 'rating')
            ->withCount('feedbacks');
        
        // Filtro por especialidade
        if ($specialtyId) {
            $query->whereHas('specialties', function($q) use ($specialtyId) {
                $q->where('specialty_id', $specialtyId);
            });
        }
        
        // Filtro por nome
        if ($name) {
            $query->whereHas('user', function($q) use ($name) {
                $q->where('name', 'like', "%{$name}%");
            });
        }
        
        // Filtro por avaliação mínima
        if ($rating) {
            $query->having('feedbacks_avg_rating', '>=', $rating);
        }
        
        // Filtro por gênero
        if ($gender && in_array($gender, ['male', 'female'])) {
            $query->whereHas('user', function($q) use ($gender) {
                $q->where('gender', $gender);
            });
        }
        
        // Ordenação
        switch ($sort) {
            case 'name_asc':
                $query->orderByRaw('(SELECT name FROM users WHERE users.id = doctors.user_id) ASC');
                break;
            case 'name_desc':
                $query->orderByRaw('(SELECT name FROM users WHERE users.id = doctors.user_id) DESC');
                break;
            case 'rating_asc':
                $query->orderBy('feedbacks_avg_rating', 'asc');
                break;
            case 'rating_desc':
                $query->orderBy('feedbacks_avg_rating', 'desc');
                break;
            case 'feedbacks_count':
                $query->orderBy('feedbacks_count', 'desc');
                break;
            default:
                $query->orderBy('feedbacks_avg_rating', 'desc');
        }
        
        $doctors = $query->paginate(12)->withQueryString();
        $specialties = Specialty::orderBy('name')->get();
        
        // Calcular os próximos horários disponíveis para cada médico (simplificado para performance)
        foreach ($doctors as $doctor) {
            $nextAvailable = $this->getNextAvailableAppointment($doctor);
            $doctor->next_available = $nextAvailable;
        }
        
        return view('patient.doctors.index', [
            'doctors' => $doctors,
            'specialties' => $specialties,
            'filters' => [
                'specialty' => $specialtyId,
                'name' => $name,
                'rating' => $rating,
                'gender' => $gender,
                'sort' => $sort
            ]
        ]);
    }
    
    /**
     * Display the specified doctor's profile.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function show($id)
    {
        $doctor = Doctor::with([
                'user', 
                'specialties', 
                'feedbacks' => function($query) {
                    $query->with('patient.user')
                          ->orderBy('created_at', 'desc')
                          ->limit(10);
                }
            ])
            ->withAvg('feedbacks', 'rating')
            ->withCount('feedbacks')
            ->findOrFail($id);
        
        // Calcular estatísticas de avaliação
        $ratingStats = [
            5 => $doctor->feedbacks()->where('rating', 5)->count(),
            4 => $doctor->feedbacks()->where('rating', 4)->count(),
            3 => $doctor->feedbacks()->where('rating', 3)->count(),
            2 => $doctor->feedbacks()->where('rating', 2)->count(),
            1 => $doctor->feedbacks()->where('rating', 1)->count(),
        ];
        
        $totalRatings = array_sum($ratingStats);
        $ratingPercentages = [];
        
        if ($totalRatings > 0) {
            foreach ($ratingStats as $rating => $count) {
                $ratingPercentages[$rating] = ($count / $totalRatings) * 100;
            }
        }
        
        // Obter horários disponíveis para a próxima semana
        $availableSlots = $this->getAvailableSlotsNextWeek($doctor);
        
        // Verificar se o paciente já consultou com este médico
        $hasAppointmentHistory = false;
        $patient = Auth::user()->patient;
        
        if ($patient) {
            $hasAppointmentHistory = Appointment::where('patient_id', $patient->id)
                ->where('doctor_id', $doctor->id)
                ->where('status', 'completed')
                ->exists();
        }
        
        return view('patient.doctors.show', [
            'doctor' => $doctor,
            'ratingStats' => $ratingStats,
            'ratingPercentages' => $ratingPercentages,
            'availableSlots' => $availableSlots,
            'hasAppointmentHistory' => $hasAppointmentHistory
        ]);
    }
    
    /**
     * Display doctors by specialty.
     *
     * @param  int  $specialtyId
     * @return \Illuminate\View\View
     */
    public function bySpecialty($specialtyId)
    {
        $specialty = Specialty::findOrFail($specialtyId);
        
        $doctors = Doctor::with(['user', 'specialties'])
            ->withAvg('feedbacks', 'rating')
            ->withCount('feedbacks')
            ->whereHas('specialties', function($q) use ($specialtyId) {
                $q->where('specialty_id', $specialtyId);
            })
            ->orderBy('feedbacks_avg_rating', 'desc')
            ->paginate(12);
        
        // Calcular os próximos horários disponíveis para cada médico
        foreach ($doctors as $doctor) {
            $nextAvailable = $this->getNextAvailableAppointment($doctor);
            $doctor->next_available = $nextAvailable;
        }
        
        return view('patient.doctors.by_specialty', [
            'doctors' => $doctors,
            'specialty' => $specialty
        ]);
    }
    
    /**
     * Display doctors that the patient has consulted with.
     *
     * @return \Illuminate\View\View
     */
    public function myDoctors()
    {
        $patient = Auth::user()->patient;
        
        $doctorIds = Appointment::where('patient_id', $patient->id)
            ->where('status', 'completed')
            ->groupBy('doctor_id')
            ->pluck('doctor_id');
        
        $doctors = Doctor::with(['user', 'specialties'])
            ->withAvg('feedbacks', 'rating')
            ->withCount([
                'appointments' => function($query) use ($patient) {
                    $query->where('patient_id', $patient->id)
                          ->where('status', 'completed');
                }
            ])
            ->whereIn('id', $doctorIds)
            ->orderBy('appointments_count', 'desc')
            ->get();
        
        // Para cada médico, buscar a última consulta com o paciente
        foreach ($doctors as $doctor) {
            $lastAppointment = Appointment::where('doctor_id', $doctor->id)
                ->where('patient_id', $patient->id)
                ->where('status', 'completed')
                ->orderBy('start_time', 'desc')
                ->first();
            
            $doctor->last_appointment = $lastAppointment;
            
            // Verificar se o paciente já deixou feedback para este médico
            $feedback = Feedback::where('patient_id', $patient->id)
                ->where('doctor_id', $doctor->id)
                ->first();
            
            $doctor->has_feedback = $feedback ? true : false;
        }
        
        return view('patient.doctors.my_doctors', [
            'doctors' => $doctors
        ]);
    }
    
    /**
     * Save a doctor as favorite.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function favorite(Request $request, $id)
    {
        $patient = Auth::user()->patient;
        $doctor = Doctor::findOrFail($id);
        
        // Verificar se o médico já é favorito
        $isFavorite = $patient->favoriteDoctors()->where('doctor_id', $id)->exists();
        
        if ($isFavorite) {
            $patient->favoriteDoctors()->detach($id);
            $message = 'Médico removido dos favoritos';
            $status = false;
        } else {
            $patient->favoriteDoctors()->attach($id);
            $message = 'Médico adicionado aos favoritos';
            $status = true;
        }
        
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'is_favorite' => $status,
                'message' => $message
            ]);
        }
        
        return back()->with('success', $message);
    }
    
    /**
     * Display favorite doctors.
     *
     * @return \Illuminate\View\View
     */
    public function favorites()
    {
        $patient = Auth::user()->patient;
        
        $doctors = $patient->favoriteDoctors()
            ->with(['user', 'specialties'])
            ->withAvg('feedbacks', 'rating')
            ->withCount('feedbacks')
            ->orderBy('pivot_created_at', 'desc')
            ->get();
        
        // Calcular os próximos horários disponíveis para cada médico
        foreach ($doctors as $doctor) {
            $nextAvailable = $this->getNextAvailableAppointment($doctor);
            $doctor->next_available = $nextAvailable;
        }
        
        return view('patient.doctors.favorites', [
            'doctors' => $doctors
        ]);
    }
    
    /**
     * Search for doctors by name, specialty, or location.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        $query = $request->get('query');
        $limit = $request->get('limit', 10);
        
        if (strlen($query) < 2) {
            return response()->json(['results' => []]);
        }
        
        $doctors = Doctor::with(['user', 'specialties'])
            ->whereHas('user', function($q) use ($query) {
                $q->where('name', 'like', "%{$query}%");
            })
            ->orWhereHas('specialties', function($q) use ($query) {
                $q->where('name', 'like', "%{$query}%");
            })
            ->limit($limit)
            ->get()
            ->map(function($doctor) {
                $specialties = $doctor->specialties->pluck('name')->join(', ');
                
                return [
                    'id' => $doctor->id,
                    'name' => $doctor->user->name,
                    'specialties' => $specialties,
                    'avatar' => $doctor->user->photo ? asset('storage/' . $doctor->user->photo) : asset('images/default-avatar.png'),
                    'url' => route('patient.doctors.show', $doctor->id)
                ];
            });
        
        return response()->json([
            'results' => $doctors
        ]);
    }
    
    /**
     * Get the next available appointment slot for a doctor.
     *
     * @param  \App\Models\Doctor  $doctor
     * @return \Carbon\Carbon|null
     */
    private function getNextAvailableAppointment(Doctor $doctor)
    {
        // Implementação simplificada para performance
        // Em uma implementação real seria mais complexo e usaria o mesmo algoritmo do AppointmentController
        
        // Verifica as próximas 7 dias
        $startDate = Carbon::now();
        $endDate = Carbon::now()->addDays(7);
        
        // Busca os horários do médico
        $schedule = $doctor->schedules()->first();
        
        if (!$schedule) {
            return null;
        }
        
        // Se o médico trabalha hoje, vamos verificar se tem horário disponível
        $dayOfWeek = Carbon::now()->dayOfWeek;
        
        if ($schedule->day_of_week == $dayOfWeek) {
            // Verifica se o horário de hoje já passou
            $scheduleStart = Carbon::parse($schedule->start_time);
            $currentTime = Carbon::now();
            
            if ($currentTime->lt($scheduleStart)) {
                // O horário de hoje ainda não começou, retorna o horário de início
                return Carbon::today()->setTimeFromTimeString($schedule->start_time);
            }
        }
        
        // Busca o próximo dia da semana que o médico trabalha
        for ($i = 1; $i <= 7; $i++) {
            $nextDay = Carbon::now()->addDays($i);
            $nextDayOfWeek = $nextDay->dayOfWeek;
            
            $hasSchedule = $doctor->schedules()->where('day_of_week', $nextDayOfWeek)->exists();
            
            if ($hasSchedule) {
                $schedule = $doctor->schedules()->where('day_of_week', $nextDayOfWeek)->first();
                return $nextDay->setTimeFromTimeString($schedule->start_time);
            }
        }
        
        return null;
    }
    
    /**
     * Get available slots for the next week for a doctor.
     *
     * @param  \App\Models\Doctor  $doctor
     * @return array
     */
    private function getAvailableSlotsNextWeek(Doctor $doctor)
    {
        // Implementação simplificada - em um sistema real, usaria o algoritmo do AppointmentController
        $slots = [];
        $startDate = Carbon::today();
        $endDate = Carbon::today()->addDays(7);
        
        // Busca os horários do médico
        $schedules = $doctor->schedules;
        
        // Para cada dia no período
        for ($date = $startDate; $date <= $endDate; $date = $date->copy()->addDay()) {
            $dayOfWeek = $date->dayOfWeek;
            
            // Verifica se o médico trabalha neste dia da semana
            $daySchedules = $schedules->filter(function ($schedule) use ($dayOfWeek) {
                return $schedule->day_of_week == $dayOfWeek;
            });
            
            if (!$daySchedules->isEmpty()) {
                foreach ($daySchedules as $schedule) {
                    $slots[$date->format('Y-m-d')] = [
                        'day' => $date->format('l'),
                        'date' => $date->format('d/m/Y'),
                        'start' => Carbon::parse($schedule->start_time)->format('H:i'),
                        'end' => Carbon::parse($schedule->end_time)->format('H:i')
                    ];
                }
            }
        }
        
        return $slots;
    }
}