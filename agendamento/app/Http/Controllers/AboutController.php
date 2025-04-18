<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\Specialty;
use App\Models\Clinic;
use Illuminate\Http\Request;

class AboutController extends Controller
{
    /**
     * Mostrar a página sobre a clínica
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // Buscar informações da clínica
        $clinic = Clinic::first();
        
        // Contar total de médicos e especialidades
        $doctorsCount = Doctor::where('status', 'active')->count();
        $specialtiesCount = Specialty::count();
        
        return view('about.index', compact('clinic', 'doctorsCount', 'specialtiesCount'));
    }
    
    /**
     * Mostrar a página com a equipe médica
     *
     * @return \Illuminate\View\View
     */
    public function team()
    {
        // Buscar médicos ativos organizados por especialidade
        $doctors = Doctor::with(['user', 'specialties'])
            ->whereHas('user', function($query) {
                $query->where('status', 'active');
            })
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy(function($doctor) {
                // Agrupar pela primeira especialidade do médico
                return $doctor->specialties->first()->name ?? 'Geral';
            });
        
        return view('about.team', compact('doctors'));
    }
    
    /**
     * Mostrar a página de especialidades
     *
     * @return \Illuminate\View\View
     */
    public function specialties()
    {
        // Buscar todas as especialidades com contagem de médicos
        $specialties = Specialty::withCount('doctors')->orderBy('name')->get();
        
        return view('about.specialties', compact('specialties'));
    }
    
    /**
     * Mostrar detalhes de uma especialidade específica
     *
     * @param string $slug
     * @return \Illuminate\View\View
     */
    public function specialty($slug)
    {
        // Buscar especialidade pelo slug
        $specialty = Specialty::where('slug', $slug)
            ->with(['doctors' => function($query) {
                $query->with('user');
            }])
            ->firstOrFail();
        
        return view('about.specialty', compact('specialty'));
    }
}