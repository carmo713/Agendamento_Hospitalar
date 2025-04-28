<?php
// filepath: /home/carmo/Documentos/trabalhofinal_agendamentohospitalar/agendamento/app/Http/Controllers/Admin/DoctorController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\User;
use App\Models\Specialty;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class DoctorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $specialty = $request->query('specialty');
        
        $query = Doctor::with(['user', 'specialties']);
        
        if ($specialty) {
            $query->whereHas('specialties', function($q) use ($specialty) {
                $q->where('specialty_id', $specialty);
            });
        }
        
        $doctors = $query->paginate(10);
        $specialties = Specialty::all(); // Para o filtro de especialidades
        
        return view('admin.doctors.index', compact('doctors', 'specialties', 'specialty'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $specialties = Specialty::all();
        return view('admin.doctors.create', compact('specialties'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'crm' => 'required|string|max:20|unique:doctors',
            'crm_state' => 'required|string|max:2',
            'bio' => 'nullable|string',
            'consultation_duration' => 'required|integer|min:10|max:120',
            'specialties' => 'required|array|min:1',
            'specialties.*' => 'exists:specialties,id',
        ]);

        if ($validator->fails()) {
            return redirect()->route('admin.doctors.create')
                ->withErrors($validator)
                ->withInput();
        }

        DB::beginTransaction();

        try {
            // Criar o usuário
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);
            
            // Atribuir o role de médico
            $user->assignRole('doctor');
            
            // Criar o médico
            $doctor = Doctor::create([
                'user_id' => $user->id,
                'crm' => $request->crm,
                'crm_state' => $request->crm_state,
                'bio' => $request->bio,
                'consultation_duration' => $request->consultation_duration,
            ]);
            
            // Atribuir especialidades
            $doctor->specialties()->attach($request->specialties);
            
            DB::commit();
            
            return redirect()->route('admin.doctors.index')
                ->with('success', 'Médico cadastrado com sucesso.');
        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->route('admin.doctors.create')
                ->with('error', 'Erro ao cadastrar médico: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Doctor $doctor)
    {
        $doctor->load(['user', 'specialties', 'appointments' => function($query) {
            $query->orderBy('start_time', 'desc')->take(10);
        }, 'feedbacks' => function($query) {
            $query->orderBy('created_at', 'desc')->take(5);
        }]);
        
        return view('admin.doctors.show', compact('doctor'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Doctor $doctor)
    {
        $doctor->load(['user', 'specialties']);
        $specialties = Specialty::all();
        
        return view('admin.doctors.edit', compact('doctor', 'specialties'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Doctor $doctor)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => [
                'required', 'email', 'max:255',
                Rule::unique('users')->ignore($doctor->user_id),
            ],
            'password' => 'nullable|string|min:8',
            'crm' => [
                'required', 'string', 'max:20',
                Rule::unique('doctors')->ignore($doctor->id),
            ],
            'crm_state' => 'required|string|max:2',
            'bio' => 'nullable|string',
            'consultation_duration' => 'required|integer|min:10|max:120',
            'specialties' => 'required|array|min:1',
            'specialties.*' => 'exists:specialties,id',
        ]);

        if ($validator->fails()) {
            return redirect()->route('admin.doctors.edit', $doctor->id)
                ->withErrors($validator)
                ->withInput();
        }

        DB::beginTransaction();

        try {
            // Atualizar o usuário
            $user = User::find($doctor->user_id);
            $user->name = $request->name;
            $user->email = $request->email;
            
            if ($request->filled('password')) {
                $user->password = Hash::make($request->password);
            }
            
            $user->save();
            
            // Atualizar o médico
            $doctor->update([
                'crm' => $request->crm,
                'crm_state' => $request->crm_state,
                'bio' => $request->bio,
                'consultation_duration' => $request->consultation_duration,
            ]);
            
            // Atualizar especialidades
            $doctor->specialties()->sync($request->specialties);
            
            DB::commit();
            
            return redirect()->route('admin.doctors.index')
                ->with('success', 'Médico atualizado com sucesso.');
        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->route('admin.doctors.edit', $doctor->id)
                ->with('error', 'Erro ao atualizar médico: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Doctor $doctor)
    {
        try {
            $userId = $doctor->user_id;
            
            DB::beginTransaction();
            
            // Excluir o médico
            $doctor->delete();
            
            // Excluir o usuário associado
            User::destroy($userId);
            
            DB::commit();
            
            return redirect()->route('admin.doctors.index')
                ->with('success', 'Médico excluído com sucesso.');
        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->route('admin.doctors.index')
                ->with('error', 'Erro ao excluir médico. Verifique se não há dependências como consultas agendadas.');
        }
    }
}