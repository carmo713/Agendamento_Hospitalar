<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\Specialty;
use App\Models\Clinic;
use App\Models\Feedback;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Mostrar a página inicial do sistema
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // Buscar especialidades em destaque
        $specialties = Specialty::orderBy('name')->take(8)->get();
        
        // Buscar médicos em destaque (com melhores avaliações)
        $doctors = Doctor::with(['user', 'specialties'])
            ->withAvg('feedbacks', 'rating')
            ->orderByDesc('feedbacks_avg_rating')
            ->take(6)
            ->get();
        
        // Buscar avaliações recentes positivas
        $testimonials = Feedback::with(['patient.user', 'doctor.user'])
            ->where('rating', '>=', 4)
            ->where('anonymous', false)
            ->latest()
            ->take(5)
            ->get();
        
        // Buscar informações da clínica principal
        $clinic = Clinic::first();
        
        return view('home', compact('specialties', 'doctors', 'testimonials', 'clinic'));
    }
    
    /**
     * Página de perguntas frequentes
     *
     * @return \Illuminate\View\View
     */
    public function faq()
    {
        return view('faq');
    }
    
    /**
     * Página de termos de uso
     *
     * @return \Illuminate\View\View
     */
    public function terms()
    {
        return view('terms');
    }
    
    /**
     * Página de política de privacidade
     *
     * @return \Illuminate\View\View
     */
    public function privacy()
    {
        return view('privacy');
    }
}