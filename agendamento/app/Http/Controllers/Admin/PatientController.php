<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PatientController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->query('search');
        $healthInsurance = $request->query('health_insurance');
        
        $query = Patient::with('user');
        
        if ($search) {
            $query->whereHas('user', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }
        
        if ($healthInsurance) {
            $query->where('health_insurance', $healthInsurance);
        }
        
        $patients = $query->paginate(10);
        
        // Obter lista de planos de saúde para filtro
        $healthInsurances = Patient::distinct('health_insurance')
            ->whereNotNull('health_insurance')
            ->pluck('health_insurance')
            ->sort();
        
        return view('admin.patients.index', compact('patients', 'healthInsurances', 'search', 'healthInsurance'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.patients.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'health_insurance' => 'nullable|string|max:255',
            'health_insurance_number' => 'nullable|string|max:50',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return redirect()->route('admin.patients.create')
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
            
            // Atribuir o role de paciente
            $user->assignRole('patient');
            
            // Criar o paciente
            Patient::create([
                'user_id' => $user->id,
                'health_insurance' => $request->health_insurance,
                'health_insurance_number' => $request->health_insurance_number,
                'emergency_contact_name' => $request->emergency_contact_name,
                'emergency_contact_phone' => $request->emergency_contact_phone,
            ]);
            
            DB::commit();
            
            return redirect()->route('admin.patients.index')
                ->with('success', 'Paciente cadastrado com sucesso.');
        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->route('admin.patients.create')
                ->with('error', 'Erro ao cadastrar paciente: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Patient $patient)
    {
        $patient->load(['user', 'appointments' => function($query) {
            $query->orderBy('start_time', 'desc')->take(10);
        }]);
        
        return view('admin.patients.show', compact('patient'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Patient $patient)
    {
        $patient->load('user');
        return view('admin.patients.edit', compact('patient'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Patient $patient)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => [
                'required', 'email', 'max:255',
                Rule::unique('users')->ignore($patient->user_id),
            ],
            'password' => 'nullable|string|min:8|confirmed',
            'health_insurance' => 'nullable|string|max:255',
            'health_insurance_number' => 'nullable|string|max:50',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return redirect()->route('admin.patients.edit', $patient->id)
                ->withErrors($validator)
                ->withInput();
        }

        DB::beginTransaction();

        try {
            // Atualizar o usuário
            $user = User::find($patient->user_id);
            $user->name = $request->name;
            $user->email = $request->email;
            
            if ($request->filled('password')) {
                $user->password = Hash::make($request->password);
            }
            
            $user->save();
            
            // Atualizar o paciente
            $patient->update([
                'health_insurance' => $request->health_insurance,
                'health_insurance_number' => $request->health_insurance_number,
                'emergency_contact_name' => $request->emergency_contact_name,
                'emergency_contact_phone' => $request->emergency_contact_phone,
            ]);
            
            DB::commit();
            
            return redirect()->route('admin.patients.index')
                ->with('success', 'Paciente atualizado com sucesso.');
        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->route('admin.patients.edit', $patient->id)
                ->with('error', 'Erro ao atualizar paciente: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Patient $patient)
    {
        try {
            $userId = $patient->user_id;
            
            DB::beginTransaction();
            
            // Excluir o paciente
            $patient->delete();
            
            // Excluir o usuário associado
            User::destroy($userId);
            
            DB::commit();
            
            return redirect()->route('admin.patients.index')
                ->with('success', 'Paciente excluído com sucesso.');
        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->route('admin.patients.index')
                ->with('error', 'Erro ao excluir paciente. Verifique se não há dependências.');
        }
    }
}