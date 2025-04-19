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
       
        
        return view('home');
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