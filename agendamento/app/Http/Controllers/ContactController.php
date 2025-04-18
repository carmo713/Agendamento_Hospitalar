<?php

namespace App\Http\Controllers;

use App\Models\Clinic;
use Illuminate\Http\Request;
use App\Mail\ContactFormMail;
use Illuminate\Support\Facades\Mail;
use App\Http\Requests\ContactFormRequest;

class ContactController extends Controller
{
    /**
     * Mostrar formulário de contato
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // Buscar informações da clínica para exibir na página
        $clinics = Clinic::all();
        
        return view('contact.index', compact('clinics'));
    }
    
    /**
     * Processar envio do formulário de contato
     *
     * @param \App\Http\Requests\ContactFormRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function send(ContactFormRequest $request)
    {
        // Dados validados do formulário
        $data = $request->validated();
        
        try {
            // Enviar e-mail com os dados do formulário
            Mail::to(config('mail.contact.address'))->send(new ContactFormMail($data));
            
            // Retornar para a página com mensagem de sucesso
            return redirect()->route('contact.index')
                ->with('success', 'Mensagem enviada com sucesso! Entraremos em contato em breve.');
        } catch (\Exception $e) {
            // Log do erro
            \Log::error('Erro ao enviar e-mail de contato: ' . $e->getMessage());
            
            // Retornar para a página com mensagem de erro
            return redirect()->route('contact.index')
                ->with('error', 'Ocorreu um erro ao enviar sua mensagem. Por favor, tente novamente mais tarde.')
                ->withInput();
        }
    }
    
    /**
     * Mostrar página com mapa e localização
     *
     * @return \Illuminate\View\View
     */
    public function location()
    {
        // Buscar todas as clínicas com endereços para mostrar no mapa
        $clinics = Clinic::all();
        
        return view('contact.location', compact('clinics'));
    }
}