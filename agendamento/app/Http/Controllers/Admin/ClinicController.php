<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Clinic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ClinicController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $clinics = Clinic::paginate(10);
        return view('admin.clinics.index', compact('clinics'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.clinics.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:50',
            'zip_code' => 'required|string|max:20',
            'phone' => 'required|string|max:20',
            'email' => 'required|string|email|max:255|unique:clinics',
        ]);

        if ($validator->fails()) {
            return redirect()->route('admin.clinics.create')
                ->withErrors($validator)
                ->withInput();
        }

        Clinic::create($request->all());

        return redirect()->route('admin.clinics.index')
            ->with('success', 'Clínica criada com sucesso.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Clinic $clinic)
    {
        return view('admin.clinics.show', compact('clinic'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Clinic $clinic)
    {
        return view('admin.clinics.edit', compact('clinic'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Clinic $clinic)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:50',
            'zip_code' => 'required|string|max:20',
            'phone' => 'required|string|max:20',
            'email' => 'required|string|email|max:255|unique:clinics,email,' . $clinic->id,
        ]);

        if ($validator->fails()) {
            return redirect()->route('admin.clinics.edit', $clinic->id)
                ->withErrors($validator)
                ->withInput();
        }

        $clinic->update($request->all());

        return redirect()->route('admin.clinics.index')
            ->with('success', 'Clínica atualizada com sucesso.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Clinic $clinic)
    {
        try {
            $clinic->delete();
            return redirect()->route('admin.clinics.index')
                ->with('success', 'Clínica excluída com sucesso.');
        } catch (\Exception $e) {
            return redirect()->route('admin.clinics.index')
                ->with('error', 'Erro ao excluir clínica. Verifique se não há dependências.');
        }
    }
    
}