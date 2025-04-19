<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    /**
     * Display a listing of the patient's payments.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $patient = Auth::user()->patient;
        
        $query = Payment::where('patient_id', $patient->id);
        
        // Filtro por status
        if ($request->has('status') && $request->status != 'all') {
            $query->where('status', $request->status);
        }
        
        // Filtro por data
        if ($request->has('start_date') && $request->start_date) {
            $query->where('created_at', '>=', $request->start_date);
        }
        
        if ($request->has('end_date') && $request->end_date) {
            $query->where('created_at', '<=', Carbon::parse($request->end_date)->endOfDay());
        }
        
        // Ordenação
        $sortBy = $request->sort_by ?? 'created_at';
        $sortDirection = $request->sort_direction ?? 'desc';
        $query->orderBy($sortBy, $sortDirection);
        
        $payments = $query->with('appointment.doctor.user')
            ->paginate(10)
            ->withQueryString();
            
        $totalPaid = Payment::where('patient_id', $patient->id)
            ->where('status', 'paid')
            ->sum('amount');
            
        $pendingPayments = Payment::where('patient_id', $patient->id)
            ->where('status', 'pending')
            ->sum('amount');
            
        return view('patient.payments.index', [
            'payments' => $payments,
            'totalPaid' => $totalPaid,
            'pendingPayments' => $pendingPayments,
            'filters' => $request->only(['status', 'start_date', 'end_date', 'sort_by', 'sort_direction'])
        ]);
    }
    
    /**
     * Display the specified payment.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function show($id)
    {
        $patient = Auth::user()->patient;
        
        $payment = Payment::where('patient_id', $patient->id)
            ->with(['appointment.doctor.user', 'appointment.specialty'])
            ->findOrFail($id);
            
        return view('patient.payments.show', [
            'payment' => $payment
        ]);
    }
    
    /**
     * Show the form for processing payment for an appointment.
     *
     * @param  int  $appointmentId
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function create($appointmentId)
    {
        $patient = Auth::user()->patient;
        
        $appointment = Appointment::where('patient_id', $patient->id)
            ->with(['doctor.user', 'specialty'])
            ->findOrFail($appointmentId);
            
        // Verificar se já existe um pagamento para esta consulta
        $existingPayment = Payment::where('appointment_id', $appointmentId)
            ->whereIn('status', ['paid', 'pending'])
            ->first();
            
        if ($existingPayment) {
            if ($existingPayment->status === 'paid') {
                return redirect()->route('patient.payments.show', $existingPayment->id)
                    ->with('info', 'Esta consulta já foi paga.');
            } else {
                return redirect()->route('patient.payments.process', $existingPayment->id)
                    ->with('info', 'Você já possui um pagamento pendente para esta consulta.');
            }
        }
        
        // Buscar preço da consulta com base na especialidade e médico
        $price = $appointment->specialty->price ?? 0;
        if ($appointment->doctor->consultation_price) {
            $price = $appointment->doctor->consultation_price;
        }
        
        // Criar novo pagamento
        $payment = new Payment();
        $payment->patient_id = $patient->id;
        $payment->appointment_id = $appointment->id;
        $payment->amount = $price;
        $payment->status = 'pending';
        $payment->due_date = Carbon::now()->addDays(3); // 3 dias para pagar
        $payment->save();
        
        return redirect()->route('patient.payments.process', $payment->id);
    }
    
    /**
     * Show payment processing form.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function process($id)
    {
        $patient = Auth::user()->patient;
        
        $payment = Payment::where('patient_id', $patient->id)
            ->where('status', 'pending')
            ->with(['appointment.doctor.user', 'appointment.specialty'])
            ->findOrFail($id);
            
        // Obter métodos de pagamento disponíveis
        $paymentMethods = [
            'credit_card' => 'Cartão de Crédito',
            'debit_card' => 'Cartão de Débito',
            'pix' => 'PIX',
            'bank_slip' => 'Boleto Bancário'
        ];
        
        return view('patient.payments.process', [
            'payment' => $payment,
            'paymentMethods' => $paymentMethods
        ]);
    }
    
    /**
     * Process payment with selected method.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function processPayment(Request $request, $id)
    {
        $request->validate([
            'payment_method' => 'required|in:credit_card,debit_card,pix,bank_slip',
            'card_number' => 'required_if:payment_method,credit_card,debit_card|nullable|string|min:16|max:19',
            'card_holder' => 'required_if:payment_method,credit_card,debit_card|nullable|string|max:255',
            'expiration_date' => 'required_if:payment_method,credit_card,debit_card|nullable|string|max:7',
            'cvv' => 'required_if:payment_method,credit_card,debit_card|nullable|string|max:4',
            'installments' => 'required_if:payment_method,credit_card|nullable|integer|min:1|max:12',
        ]);
        
        $patient = Auth::user()->patient;
        
        $payment = Payment::where('patient_id', $patient->id)
            ->where('status', 'pending')
            ->findOrFail($id);
            
        try {
            // Simular processamento de pagamento com gateway externo
            // Em um cenário real, aqui seria feita a integração com o gateway de pagamento
            
            $success = true; // Simulando pagamento bem-sucedido
            
            if ($success) {
                $payment->status = 'paid';
                $payment->method = $request->payment_method;
                $payment->transaction_id = 'TXN' . Str::random(10);
                $payment->paid_at = Carbon::now();
                $payment->save();
                
                // Confirmar a consulta após pagamento bem-sucedido
                if ($payment->appointment) {
                    $payment->appointment->status = 'confirmed';
                    $payment->appointment->save();
                    
                    // Enviar confirmação por email
                    \Mail::to($patient->user->email)->send(new \App\Mail\AppointmentConfirmed(
                        $patient->user,
                        $payment->appointment
                    ));
                }
                
                return redirect()->route('patient.payments.success', $payment->id)
                    ->with('success', 'Pagamento processado com sucesso!');
            } else {
                return back()->with('error', 'Não foi possível processar o pagamento. Por favor, tente novamente ou use outro método de pagamento.')
                    ->withInput();
            }
        } catch (\Exception $e) {
            return back()->with('error', 'Ocorreu um erro ao processar o pagamento: ' . $e->getMessage())
                ->withInput();
        }
    }
    
    /**
     * Display payment success page.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function success($id)
    {
        $patient = Auth::user()->patient;
        
        $payment = Payment::where('patient_id', $patient->id)
            ->where('status', 'paid')
            ->with(['appointment.doctor.user', 'appointment.specialty'])
            ->findOrFail($id);
            
        return view('patient.payments.success', [
            'payment' => $payment
        ]);
    }
    
    /**
     * Generate payment receipt PDF.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function receipt($id)
    {
        $patient = Auth::user()->patient;
        
        $payment = Payment::where('patient_id', $patient->id)
            ->where('status', 'paid')
            ->with(['appointment.doctor.user', 'appointment.specialty', 'patient.user'])
            ->findOrFail($id);
            
        $pdf = \PDF::loadView('pdfs.payment-receipt', [
            'payment' => $payment,
            'clinic' => [
                'name' => config('app.name'),
                'address' => 'Av. Exemplo, 1000 - Centro',
                'city' => 'São Paulo - SP',
                'phone' => '(11) 1234-5678',
                'cnpj' => '12.345.678/0001-90'
            ]
        ]);
        
        return $pdf->download('recibo_pagamento_' . $payment->id . '.pdf');
    }
    
    /**
     * Request a refund for a payment.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function requestRefund(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|min:10|max:500',
        ]);
        
        $patient = Auth::user()->patient;
        
        $payment = Payment::where('patient_id', $patient->id)
            ->where('status', 'paid')
            ->findOrFail($id);
            
        // Verificar se o pagamento é elegível para reembolso
        $paymentDate = Carbon::parse($payment->paid_at);
        $refundWindow = Carbon::now()->subDays(7); // Janela de 7 dias para reembolso
        
        if ($paymentDate->lt($refundWindow)) {
            return back()->with('error', 'Este pagamento não é mais elegível para reembolso. O prazo máximo é de 7 dias após o pagamento.');
        }
        
        // Verificar se a consulta já ocorreu
        if ($payment->appointment && $payment->appointment->start_time < Carbon::now()) {
            return back()->with('error', 'Não é possível solicitar reembolso para consultas já realizadas.');
        }
        
        try {
            // Registrar solicitação de reembolso
            // Em um cenário real, isso poderia ser um modelo separado para tracking
            $notification = new \App\Models\Notification();
            $notification->user_id = 1; // Administrador
            $notification->type = 'refund_request';
            $notification->title = 'Solicitação de Reembolso';
            $notification->message = 'O paciente ' . $patient->user->name . ' solicitou reembolso do pagamento #' . $payment->id;
            $notification->data = json_encode([
                'payment_id' => $payment->id,
                'reason' => $request->reason,
                'requested_at' => Carbon::now()
            ]);
            $notification->save();
            
            // Marcar pagamento como "em processo de reembolso"
            $payment->notes = 'Reembolso solicitado: ' . $request->reason;
            $payment->save();
            
            return redirect()->route('patient.payments.show', $payment->id)
                ->with('success', 'Sua solicitação de reembolso foi registrada e será analisada pela nossa equipe financeira.');
                
        } catch (\Exception $e) {
            return back()->with('error', 'Ocorreu um erro ao processar sua solicitação de reembolso. Por favor, tente novamente.')
                ->withInput();
        }
    }
    
    /**
     * Display payment history and statistics.
     *
     * @return \Illuminate\View\View
     */
    public function history()
    {
        $patient = Auth::user()->patient;
        
        // Estatísticas gerais
        $stats = [
            'total_paid' => Payment::where('patient_id', $patient->id)
                ->where('status', 'paid')
                ->sum('amount'),
            'total_appointments' => Payment::where('patient_id', $patient->id)
                ->where('status', 'paid')
                ->count(),
            'pending_payments' => Payment::where('patient_id', $patient->id)
                ->where('status', 'pending')
                ->sum('amount'),
        ];
        
        // Agrupamento por mês
        $monthlyPayments = Payment::where('patient_id', $patient->id)
            ->where('status', 'paid')
            ->orderBy('paid_at')
            ->get()
            ->groupBy(function($payment) {
                return Carbon::parse($payment->paid_at)->format('Y-m');
            })
            ->map(function($group) {
                return [
                    'count' => $group->count(),
                    'total' => $group->sum('amount'),
                    'month' => Carbon::parse($group->first()->paid_at)->format('F Y')
                ];
            })
            ->take(12);
            
        // Agrupamento por especialidade
        $specialtyPayments = Payment::where('patient_id', $patient->id)
            ->where('status', 'paid')
            ->whereNotNull('appointment_id')
            ->with('appointment.specialty')
            ->get()
            ->groupBy(function($payment) {
                return $payment->appointment->specialty->name ?? 'Sem especialidade';
            })
            ->map(function($group) {
                return [
                    'count' => $group->count(),
                    'total' => $group->sum('amount')
                ];
            });
            
        return view('patient.payments.history', [
            'stats' => $stats,
            'monthlyPayments' => $monthlyPayments,
            'specialtyPayments' => $specialtyPayments
        ]);
    }
}