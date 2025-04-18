<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Role;
use App\Models\Specialty;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('roles')->paginate(15);
        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        $roles = Role::all();
        $specialties = Specialty::all();
        return view('admin.users.create', compact('roles', 'specialties'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
            'cpf' => 'nullable|string|max:14|unique:users',
            'birth_date' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,id',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'cpf' => $request->cpf,
            'birth_date' => $request->birth_date,
            'gender' => $request->gender,
        ]);

        // Atribuir papéis
        $user->roles()->attach($request->roles);

        // Se for médico, criar perfil médico
        if (in_array(2, $request->roles)) { // Assumindo que o ID do papel médico é 2
            $request->validate([
                'crm' => 'required|string|max:20',
                'crm_state' => 'required|string|max:2',
                'specialties' => 'required|array',
                'specialties.*' => 'exists:specialties,id',
                'consultation_duration' => 'nullable|integer|min:5',
            ]);

            $doctor = Doctor::create([
                'user_id' => $user->id,
                'crm' => $request->crm,
                'crm_state' => $request->crm_state,
                'bio' => $request->bio,
                'consultation_duration' => $request->consultation_duration ?? 30,
            ]);

            $doctor->specialties()->attach($request->specialties);
        }

        // Se for paciente, criar perfil paciente
        if (in_array(3, $request->roles)) { // Assumindo que o ID do papel paciente é 3
            $request->validate([
                'health_insurance' => 'nullable|string|max:255',
                'health_insurance_number' => 'nullable|string|max:50',
                'emergency_contact_name' => 'nullable|string|max:255',
                'emergency_contact_phone' => 'nullable|string|max:20',
            ]);

            Patient::create([
                'user_id' => $user->id,
                'health_insurance' => $request->health_insurance,
                'health_insurance_number' => $request->health_insurance_number,
                'emergency_contact_name' => $request->emergency_contact_name,
                'emergency_contact_phone' => $request->emergency_contact_phone,
            ]);
        }

        return redirect()->route('admin.users.index')
            ->with('success', 'Usuário criado com sucesso.');
    }

    // Implementar outros métodos (show, edit, update, destroy)...
}