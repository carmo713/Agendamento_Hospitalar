<!-- filepath: /home/carmo/Documentos/trabalhofinal_agendamentohospitalar/agendamento/resources/views/patient/appointments/show.blade.php -->
<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Detalhes da Consulta') }}
            </h2>
            <a href="{{ route('patient.appointments.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white focus:bg-gray-700 dark:focus:bg-white active:bg-gray-900 dark:active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                Voltar para Lista
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    @if (session('success'))
                        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert">
                            <p>{{ session('success') }}</p>
                        </div>
                    @endif
                    
                    @if (session('error'))
                        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">
                            <p>{{ session('error') }}</p>
                        </div>
                    @endif

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                        <!-- Principal Info -->
                        <div class="md:col-span-2">
                            <h3 class="text-lg font-bold mb-4 pb-2 border-b border-gray-200 dark:border-gray-700">
                                Informações da Consulta
                            </h3>
                            
                            <div class="mb-8">
                                <!-- Status Badge -->
                                <div class="mb-4">
                                    <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full 
                                        @if($appointment->status == 'scheduled') bg-yellow-100 text-yellow-800 
                                        @elseif($appointment->status == 'confirmed') bg-green-100 text-green-800
                                        @elseif($appointment->status == 'completed') bg-blue-100 text-blue-800
                                        @elseif($appointment->status == 'canceled') bg-red-100 text-red-800
                                        @elseif($appointment->status == 'no_show') bg-gray-100 text-gray-800 
                                        @endif">
                                        @if($appointment->status == 'scheduled') Agendada
                                        @elseif($appointment->status == 'confirmed') Confirmada
                                        @elseif($appointment->status == 'completed') Concluída
                                        @elseif($appointment->status == 'canceled') Cancelada
                                        @elseif($appointment->status == 'no_show') Não Compareceu
                                        @endif
                                    </span>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Data e Hora</p>
                                        <p class="mt-1">
                                            {{ $appointment->start_time->format('d/m/Y') }} às {{ $appointment->start_time->format('H:i') }} - {{ $appointment->end_time->format('H:i') }}
                                        </p>
                                    </div>
                                    
                                    <div>
                                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Especialidade</p>
                                        <p class="mt-1">{{ $appointment->specialty->name }}</p>
                                    </div>
                                    
                                    <div>
                                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Motivo da Consulta</p>
                                        <p class="mt-1">{{ $appointment->reason }}</p>
                                    </div>
                                    
                                    @if($appointment->room)
                                    <div>
                                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Local da Consulta</p>
                                        <p class="mt-1">Sala {{ $appointment->room->room_number }}</p>
                                    </div>
                                    @endif
                                    
                                    @if($appointment->status === 'canceled')
                                    <div class="md:col-span-2">
                                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Motivo do Cancelamento</p>
                                        <p class="mt-1">{{ $appointment->cancellation_reason }}</p>
                                    </div>
                                    @endif
                                    
                                    @if($appointment->notes)
                                    <div class="md:col-span-2">
                                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Observações</p>
                                        <p class="mt-1">{{ $appointment->notes }}</p>
                                    </div>
                                    @endif
                                </div>
                            </div>
                            
                            <!-- Médico -->
                            <h3 class="text-lg font-bold mb-4 pb-2 border-b border-gray-200 dark:border-gray-700">
                                Informações do Médico
                            </h3>
                            
                            <div class="mb-8 flex items-start">
                                <div class="flex-shrink-0 h-12 w-12 bg-gray-200 dark:bg-gray-700 rounded-full flex items-center justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-gray-600 dark:text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <h4 class="text-xl font-medium text-gray-900 dark:text-white">
                                        {{ $appointment->doctor->user->name }}
                                    </h4>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">CRM: {{ $appointment->doctor->crm }}/{{ $appointment->doctor->crm_state }}</p>
                                    <div class="mt-2">
                                        <a href="#" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                            Ver Perfil do Médico
                                        </a>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Prontuário Médico (se consulta concluída) -->
                            @if($appointment->status === 'completed' && $appointment->medicalRecord)
                                <h3 class="text-lg font-bold mb-4 pb-2 border-b border-gray-200 dark:border-gray-700">
                                    Prontuário Médico
                                </h3>
                                
                                <div class="mb-8">
                                    <div class="grid grid-cols-1 gap-4">
                                        <div>
                                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Diagnóstico</p>
                                            <p class="mt-1">{{ $appointment->medicalRecord->diagnosis }}</p>
                                        </div>
                                        
                                        <div>
                                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Observações do Médico</p>
                                            <p class="mt-1">{{ $appointment->medicalRecord->notes }}</p>
                                        </div>
                                        
                                        <div>
                                            <a href="{{ route('patient.medical-records.show', $appointment->medicalRecord) }}" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-900 focus:outline-none focus:border-blue-900 focus:ring ring-blue-300 disabled:opacity-25 transition ease-in-out duration-150">
                                                Ver Prontuário Completo
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endif
                            
                            <!-- Avaliação (se consulta concluída e ainda não avaliou) -->
                            @if($appointment->status === 'completed' && !$appointment->feedback)
                                <div class="flex justify-between items-center py-4">
                                    <p class="font-medium">Você ainda não avaliou esta consulta</p>
                                    <a href="{{ route('patient.feedbacks.create', ['appointment_id' => $appointment->id]) }}" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 active:bg-green-900 focus:outline-none focus:border-green-900 focus:ring ring-green-300 disabled:opacity-25 transition ease-in-out duration-150">
                                        Avaliar Consulta
                                    </a>
                                </div>
                            @elseif($appointment->feedback)
                                <h3 class="text-lg font-bold mb-4 pb-2 border-b border-gray-200 dark:border-gray-700">
                                    Sua Avaliação
                                </h3>
                                
                                <div class="mb-8 flex items-center">
                                    <div class="flex">
                                        @for($i = 1; $i <= 5; $i++)
                                            <svg class="h-5 w-5 {{ $i <= $appointment->feedback->rating ? 'text-yellow-400' : 'text-gray-300' }}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                            </svg>
                                        @endfor
                                    </div>
                                    <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">{{ $appointment->feedback->created_at->format('d/m/Y') }}</span>
                                </div>
                                
                                @if($appointment->feedback->comment)
                                    <p class="text-gray-700 dark:text-gray-300 italic mb-4">
                                        "{{ $appointment->feedback->comment }}"
                                    </p>
                                @endif
                            @endif
                        </div>
                        
                        <!-- Sidebar -->
                        <div>
                            <!-- Box com informações de pagamento -->
                            <div class="mb-6 bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                                <h3 class="font-bold text-lg mb-4">Informações de Pagamento</h3>
                                
                                @if($appointment->payment)
                                    <div class="mb-2">
                                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</p>
                                        <p class="mt-1">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                {{ $appointment->payment->status === 'paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                                {{ $appointment->payment->status === 'paid' ? 'Pago' : 'Pendente' }}
                                            </span>
                                        </p>
                                    </div>
                                    
                                    <div class="mb-2">
                                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Valor</p>
                                        <p class="mt-1 text-lg font-semibold">
                                            R$ {{ number_format($appointment->payment->amount, 2, ',', '.') }}
                                        </p>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Método</p>
                                        <p class="mt-1">{{ $appointment->payment->payment_method ?? 'Não informado' }}</p>
                                    </div>
                                    
                                    @if($appointment->payment->status !== 'paid')
                                        <div class="mt-4">
                                            <a href="{{ route('patient.payments.show', $appointment->payment) }}" class="w-full inline-flex justify-center items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 active:bg-green-900 focus:outline-none focus:border-green-900 focus:ring ring-green-300 disabled:opacity-25 transition ease-in-out duration-150">
                                                Efetuar Pagamento
                                            </a>
                                        </div>
                                    @endif
                                @else
                                    <p class="text-gray-500 dark:text-gray-400">Nenhuma informação de pagamento disponível.</p>
                                @endif
                            </div>
                            
                            <!-- Actions -->
                            <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                                <h3 class="font-bold text-lg mb-4">Ações</h3>
                                
                                <div class="space-y-3">
                                    @if($appointment->isCancellable())
                                        <a href="{{ route('patient.appointments.reschedule', $appointment) }}" class="w-full block text-center px-4 py-2 bg-yellow-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-yellow-700 active:bg-yellow-900 focus:outline-none focus:border-yellow-900 focus:ring ring-yellow-300 disabled:opacity-25 transition ease-in-out duration-150">
                                            Remarcar Consulta
                                        </a>
                                        
                                        <a href="{{ route('patient.appointments.cancel', $appointment) }}" class="w-full block text-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 active:bg-red-900 focus:outline-none focus:border-red-900 focus:ring ring-red-300 disabled:opacity-25 transition ease-in-out duration-150">
                                            Cancelar Consulta
                                        </a>
                                    @endif
                                    
                                    @if($appointment->status === 'scheduled' || $appointment->status === 'confirmed')
                                        <a href="#" class="w-full block text-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-900 focus:outline-none focus:border-blue-900 focus:ring ring-blue-300 disabled:opacity-25 transition ease-in-out duration-150">
                                            Enviar Mensagem
                                        </a>
                                    @endif
                                    
                                    <a href="{{ route('patient.appointments.index') }}" class="w-full block text-center px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-800 uppercase tracking-widest hover:bg-gray-400 active:bg-gray-500 focus:outline-none focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150">
                                        Voltar para Lista
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>