<!-- filepath: /home/carmo/Documentos/trabalhofinal_agendamentohospitalar/agendamento/resources/views/admin/patients/show.blade.php -->
<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Detalhes do Paciente') }}
            </h2>
            <div class="flex space-x-2">
                <a href="{{ route('admin.patients.edit', $patient) }}" class="inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white focus:bg-gray-700 dark:focus:bg-white active:bg-gray-900 dark:active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                    Editar
                </a>
                <a href="{{ route('admin.patients.index') }}" class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                    Voltar
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-white">Informações do Paciente</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <div class="mb-4">
                                <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">Nome</h4>
                                <p class="mt-1 text-gray-900 dark:text-white">{{ $patient->user->name }}</p>
                            </div>
                            
                            <div class="mb-4">
                                <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">E-mail</h4>
                                <p class="mt-1 text-gray-900 dark:text-white">{{ $patient->user->email }}</p>
                            </div>

                            <div class="mb-4">
                                <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">Data de Cadastro</h4>
                                <p class="mt-1 text-gray-900 dark:text-white">{{ $patient->created_at->format('d/m/Y H:i') }}</p>
                            </div>
                        </div>
                        
                        <div>
                            <div class="mb-4">
                                <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">Plano de Saúde</h4>
                                <p class="mt-1 text-gray-900 dark:text-white">{{ $patient->health_insurance ?? 'Não informado' }}</p>
                                @if($patient->health_insurance_number)
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Nº {{ $patient->health_insurance_number }}</p>
                                @endif
                            </div>
                            
                            <div class="mb-4">
                                <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">Contato de Emergência</h4>
                                <p class="mt-1 text-gray-900 dark:text-white">{{ $patient->emergency_contact_name ?? 'Não informado' }}</p>
                                @if($patient->emergency_contact_phone)
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $patient->emergency_contact_phone }}</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Histórico de Consultas -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Histórico de Consultas</h3>
                        
                        <a href="{{ route('admin.appointments.create', ['patient_id' => $patient->id]) }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                            Agendar Nova Consulta
                        </a>
                    </div>
                    
                    @if(isset($patient->appointments) && $patient->appointments->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Data e Hora
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Médico
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Especialidade
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Status
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Ações
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-600">
                                    @foreach ($patient->appointments as $appointment)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                                {{ $appointment->start_time->format('d/m/Y H:i') }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                                {{ $appointment->doctor->user->name }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                                {{ $appointment->specialty->name ?? 'N/A' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                    @if($appointment->status == 'scheduled') bg-yellow-100 text-yellow-800 
                                                    @elseif($appointment->status == 'confirmed') bg-green-100 text-green-800
                                                    @elseif($appointment->status == 'completed') bg-blue-100 text-blue-800
                                                    @elseif($appointment->status == 'canceled') bg-red-100 text-red-800
                                                    @else bg-gray-100 text-gray-800 @endif">
                                                    {{ ucfirst($appointment->status) }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <a href="{{ route('admin.appointments.show', $appointment) }}" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300">
                                                    Ver Detalhes
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-md p-6 text-center">
                            <p class="text-gray-500 dark:text-gray-400">Nenhuma consulta encontrada para este paciente.</p>
                        </div>
                    @endif
                </div>
            </div>
            
            <!-- Ações -->
            <div class="mt-6 flex justify-between">
                <div>
                    <form action="{{ route('admin.patients.destroy', $patient) }}" method="POST" onsubmit="return confirm('Tem certeza que deseja excluir este paciente? Isto também excluirá a conta de usuário associada.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:bg-red-700 active:bg-red-800 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                            Excluir Paciente
                        </button>
                    </form>
                </div>
                
                <div>
                    <a href="{{ route('admin.appointments.create', ['patient_id' => $patient->id]) }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                        Agendar Nova Consulta
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>