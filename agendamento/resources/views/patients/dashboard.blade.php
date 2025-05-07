<!-- filepath: /home/carmo/Documentos/trabalhofinal_agendamentohospitalar/agendamento/resources/views/patient/dashboard.blade.php -->
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Dashboard do Paciente') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
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

            <!-- Estatísticas -->
            <div class="mb-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-500 bg-opacity-10 text-blue-500">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total de Consultas</p>
                            <p class="text-2xl font-semibold text-gray-800 dark:text-white">{{ $stats['total_appointments'] }}</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-500 bg-opacity-10 text-green-500">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Consultas Concluídas</p>
                            <p class="text-2xl font-semibold text-gray-800 dark:text-white">{{ $stats['completed_appointments'] }}</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-purple-500 bg-opacity-10 text-purple-500">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Próximas Consultas</p>
                            <p class="text-2xl font-semibold text-gray-800 dark:text-white">{{ $stats['upcoming_appointments'] }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Próximas Consultas -->
                <div class="lg:col-span-2">
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                    Próximas Consultas
                                </h3>
                                <a href="{{ route('patient.appointments.index', ['type' => 'upcoming']) }}" class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                    Ver todas →
                                </a>
                            </div>
                            
                            <div class="overflow-x-auto">
                                @if($upcomingAppointments->count() > 0)
                                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                        <thead class="bg-gray-50 dark:bg-gray-700">
                                            <tr>
                                                <th scope="col" class="px-3 py-3.5 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                    Data/Hora
                                                </th>
                                                <th scope="col" class="px-3 py-3.5 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                    Médico
                                                </th>
                                                <th scope="col" class="px-3 py-3.5 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                    Especialidade
                                                </th>
                                                <th scope="col" class="px-3 py-3.5 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                    Ação
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                            @foreach($upcomingAppointments as $appointment)
                                                <tr>
                                                    <td class="px-3 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                                        <div class="font-medium">{{ $appointment->start_time->format('d/m/Y') }}</div>
                                                        <div class="text-gray-500 dark:text-gray-400">{{ $appointment->start_time->format('H:i') }}</div>
                                                    </td>
                                                    <td class="px-3 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                                        <div class="font-medium">Dr. {{ $appointment->doctor->user->name }}</div>
                                                        <div class="text-gray-500 dark:text-gray-400">CRM: {{ $appointment->doctor->crm }}</div>
                                                    </td>
                                                    <td class="px-3 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                                        {{ $appointment->specialty->name }}
                                                    </td>
                                                    <td class="px-3 py-4 whitespace-nowrap text-right text-sm">
                                                        <a href="{{ route('patient.appointments.show', $appointment) }}" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300">
                                                            Detalhes
                                                        </a>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                @else
                                    <div class="py-8 text-center text-gray-500 dark:text-gray-400">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        <p class="mt-2">Você não possui consultas agendadas.</p>
                                        <div class="mt-4">
                                            <a href="{{ route('patient.appointments.search') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-900 focus:outline-none focus:border-blue-900 focus:ring ring-blue-300 disabled:opacity-25 transition ease-in-out duration-150">
                                                Agendar Consulta
                                            </a>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Consultas Recentes -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mt-6">
                        <div class="p-6">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                    Consultas Recentes
                                </h3>
                                <a href="{{ route('patient.appointments.index', ['type' => 'past']) }}" class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                    Ver histórico →
                                </a>
                            </div>
                            
                            @if($recentAppointments->count() > 0)
                                <div class="space-y-4">
                                    @foreach($recentAppointments as $appointment)
                                        <div class="flex flex-col sm:flex-row sm:items-center p-4 border rounded-lg border-gray-200 dark:border-gray-700">
                                            <div class="flex-1">
                                                <div class="flex items-center">
                                                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                                        {{ $appointment->start_time->format('d/m/Y') }}
                                                    </div>
                                                    <span class="mx-2 text-gray-500 dark:text-gray-400">•</span>
                                                    <div class="text-sm font-medium">
                                                        Dr. {{ $appointment->doctor->user->name }}
                                                    </div>
                                                </div>
                                                <div class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                                    {{ $appointment->specialty->name }}
                                                </div>
                                            </div>
                                            <div class="mt-3 sm:mt-0 flex space-x-2">
                                                <a href="{{ route('patient.appointments.show', $appointment) }}" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                                    Detalhes
                                                </a>
                                                @unless($appointment->feedback)
                                                    <a href="{{ route('patient.feedbacks.create', ['appointment_id' => $appointment->id]) }}" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                                        Avaliar
                                                    </a>
                                                @endunless
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="py-8 text-center text-gray-500 dark:text-gray-400">
                                    <p>Nenhuma consulta realizada recentemente.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                
                <!-- Notificações e Ações Rápidas -->
                <div>
                    <!-- Notificações -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                    Notificações
                                </h3>
                                <a href="#" class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                    Ver todas →
                                </a>
                            </div>
                            
                            @if($notifications->count() > 0)
                                <div class="space-y-3">
                                    @foreach($notifications as $notification)
                                        <div class="flex p-3 border-l-4 {{ $notification->type === 'alert' ? 'border-red-500 bg-red-50 dark:bg-red-900 dark:bg-opacity-10' : 'border-blue-500 bg-blue-50 dark:bg-blue-900 dark:bg-opacity-10' }}">
                                            <div class="flex-shrink-0 mr-3">
                                                @if($notification->type === 'alert')
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                    </svg>
                                                @elseif($notification->type === 'info')
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                @else
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                                    </svg>
                                                @endif
                                            </div>
                                            <div>
                                                <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                    {{ $notification->title }}
                                                </div>
                                                <div class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                                    {{ $notification->content }}
                                                </div>
                                                <div class="mt-2 text-xs text-gray-500 dark:text-gray-500">
                                                    {{ $notification->created_at->diffForHumans() }}
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="py-6 text-center text-gray-500 dark:text-gray-400">
                                    <p>Você não possui novas notificações.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                    
                    <!-- Ações Rápidas -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mt-6">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                                Ações Rápidas
                            </h3>
                            
                            <div class="space-y-2">
                                <a href="{{ route('patient.appointments.search') }}" class="flex items-center p-3 bg-blue-50 dark:bg-blue-900 dark:bg-opacity-20 rounded-lg hover:bg-blue-100 dark:hover:bg-opacity-30 transition-colors">
                                    <div class="flex-shrink-0 p-2 bg-blue-500 bg-opacity-20 rounded-full">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <div class="font-medium">Agendar Consulta</div>
                                    </div>
                                </a>
                                
                                <a href="{{ route('patient.medical-records.index') }}" class="flex items-center p-3 bg-green-50 dark:bg-green-900 dark:bg-opacity-20 rounded-lg hover:bg-green-100 dark:hover:bg-opacity-30 transition-colors">
                                    <div class="flex-shrink-0 p-2 bg-green-500 bg-opacity-20 rounded-full">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <div class="font-medium">Prontuários Médicos</div>
                                    </div>
                                </a>
                                
                                <a href="{{ route('patient.prescriptions.index') }}" class="flex items-center p-3 bg-purple-50 dark:bg-purple-900 dark:bg-opacity-20 rounded-lg hover:bg-purple-100 dark:hover:bg-opacity-30 transition-colors">
                                    <div class="flex-shrink-0 p-2 bg-purple-500 bg-opacity-20 rounded-full">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <div class="font-medium">Receitas Médicas</div>
                                    </div>
                                </a>
                                
                                <a href="{{ route('patient.health-profile.show') }}" class="flex items-center p-3 bg-amber-50 dark:bg-amber-900 dark:bg-opacity-20 rounded-lg hover:bg-amber-100 dark:hover:bg-opacity-30 transition-colors">
                                    <div class="flex-shrink-0 p-2 bg-amber-500 bg-opacity-20 rounded-full">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <div class="font-medium">Meu Perfil de Saúde</div>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>