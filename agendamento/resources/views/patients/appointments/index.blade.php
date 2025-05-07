<!-- filepath: /home/carmo/Documentos/trabalhofinal_agendamentohospitalar/agendamento/resources/views/patient/appointments/index.blade.php -->
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Minhas Consultas') }}
        </h2>
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

                    <!-- Filtros -->
                    <div class="mb-6 flex flex-wrap items-center justify-between">
                        <div class="flex space-x-2 mb-3 sm:mb-0">
                            <a href="{{ route('patient.appointments.index', ['type' => 'upcoming']) }}" 
                               class="px-4 py-2 rounded-md {{ $type === 'upcoming' ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300' }}">
                                Próximas
                            </a>
                            <a href="{{ route('patient.appointments.index', ['type' => 'past']) }}" 
                               class="px-4 py-2 rounded-md {{ $type === 'past' ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300' }}">
                                Anteriores
                            </a>
                            <a href="{{ route('patient.appointments.index', ['type' => 'all']) }}" 
                               class="px-4 py-2 rounded-md {{ $type === 'all' ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300' }}">
                                Todas
                            </a>
                        </div>
                        
                        <div>
                            <form method="GET" action="{{ route('patient.appointments.index') }}" class="flex">
                                <input type="hidden" name="type" value="{{ $type }}">
                                
                                <select name="status" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    <option value="">Todos os status</option>
                                    <option value="scheduled" {{ $status === 'scheduled' ? 'selected' : '' }}>Agendada</option>
                                    <option value="confirmed" {{ $status === 'confirmed' ? 'selected' : '' }}>Confirmada</option>
                                    <option value="completed" {{ $status === 'completed' ? 'selected' : '' }}>Concluída</option>
                                    <option value="canceled" {{ $status === 'canceled' ? 'selected' : '' }}>Cancelada</option>
                                    <option value="no_show" {{ $status === 'no_show' ? 'selected' : '' }}>Não Compareceu</option>
                                </select>
                                
                                <button type="submit" class="ml-2 px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    Filtrar
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Lista de Consultas -->
                    @if($appointments->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full bg-white dark:bg-gray-800">
                                <thead class="bg-gray-100 dark:bg-gray-700">
                                    <tr>
                                        <th class="py-3 px-4 text-left">Data/Hora</th>
                                        <th class="py-3 px-4 text-left">Médico</th>
                                        <th class="py-3 px-4 text-left">Especialidade</th>
                                        <th class="py-3 px-4 text-left">Status</th>
                                        <th class="py-3 px-4 text-center">Ações</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($appointments as $appointment)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                            <td class="py-4 px-4">
                                                <div class="font-medium">{{ $appointment->start_time->format('d/m/Y') }}</div>
                                                <div class="text-sm text-gray-500 dark:text-gray-400">{{ $appointment->start_time->format('H:i') }} - {{ $appointment->end_time->format('H:i') }}</div>
                                            </td>
                                            <td class="py-4 px-4">
                                                <div class="font-medium">{{ $appointment->doctor->user->name }}</div>
                                                <div class="text-sm text-gray-500 dark:text-gray-400">CRM: {{ $appointment->doctor->crm }}</div>
                                            </td>
                                            <td class="py-4 px-4">
                                                {{ $appointment->specialty->name }}
                                            </td>
                                            <td class="py-4 px-4">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
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
                                            </td>
                                            <td class="py-4 px-4 text-center">
                                                <div class="flex justify-center space-x-2">
                                                    <a href="{{ route('patient.appointments.show', $appointment) }}" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-200">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                            <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                                                            <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                                                        </svg>
                                                    </a>
                                                    
                                                    @if($appointment->isCancellable())
                                                        <a href="{{ route('patient.appointments.reschedule', $appointment) }}" class="text-yellow-600 hover:text-yellow-900 dark:text-yellow-400 dark:hover:text-yellow-200">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                                <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd" />
                                                            </svg>
                                                        </a>
                                                        
                                                        <a href="{{ route('patient.appointments.cancel', $appointment) }}" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-200">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                                <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                                            </svg>
                                                        </a>
                                                    @endif

                                                    @if($appointment->status === 'completed' && !$appointment->feedback)
                                                        <a href="{{ route('patient.feedbacks.create', ['appointment_id' => $appointment->id]) }}" class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-200">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                                            </svg>
                                                        </a>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Paginação -->
                        <div class="mt-4">
                            {{ $appointments->appends(['type' => $type, 'status' => $status])->links() }}
                        </div>
                    @else
                        <div class="text-center py-10">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <h3 class="mt-3 text-lg font-medium text-gray-900 dark:text-gray-100">Nenhuma consulta encontrada</h3>
                            <p class="mt-1 text-gray-500 dark:text-gray-400">
                                @if($type === 'upcoming')
                                    Você não possui consultas agendadas.
                                @elseif($type === 'past')
                                    Você ainda não realizou nenhuma consulta.
                                @else
                                    Não há consultas para exibir com os filtros selecionados.
                                @endif
                            </p>
                            <div class="mt-6">
                                <a href="{{ route('patient.appointments.search') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-900 focus:outline-none focus:border-blue-900 focus:ring ring-blue-300 disabled:opacity-25 transition ease-in-out duration-150">
                                    Agendar Consulta
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>