<!-- filepath: /home/carmo/Documentos/trabalhofinal_agendamentohospitalar/agendamento/resources/views/admin/appointments/index.blade.php -->
<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Agendamentos') }}
            </h2>
            <a href="{{ route('admin.appointments.create') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white focus:bg-gray-700 dark:focus:bg-white active:bg-gray-900 dark:active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                Novo Agendamento
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

                    <!-- Filtros -->
                    <div class="mb-6">
                        <form method="GET" action="{{ route('admin.appointments.index') }}" class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <x-input-label for="doctor_id" :value="__('Médico')" />
                                    <select id="doctor_id" name="doctor_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                        <option value="">Todos os Médicos</option>
                                        @foreach ($doctors as $doctor)
                                            <option value="{{ $doctor->id }}" {{ $doctorId == $doctor->id ? 'selected' : '' }}>
                                                {{ $doctor->user->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                
                                <div>
                                    <x-input-label for="patient_id" :value="__('Paciente')" />
                                    <select id="patient_id" name="patient_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                        <option value="">Todos os Pacientes</option>
                                        @foreach ($patients as $patient)
                                            <option value="{{ $patient->id }}" {{ $patientId == $patient->id ? 'selected' : '' }}>
                                                {{ $patient->user->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                
                                <div>
                                    <x-input-label for="specialty_id" :value="__('Especialidade')" />
                                    <select id="specialty_id" name="specialty_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                        <option value="">Todas as Especialidades</option>
                                        @foreach ($specialties as $specialty)
                                            <option value="{{ $specialty->id }}" {{ $specialtyId == $specialty->id ? 'selected' : '' }}>
                                                {{ $specialty->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <x-input-label for="status" :value="__('Status')" />
                                    <select id="status" name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                        <option value="">Todos os Status</option>
                                        <option value="scheduled" {{ $status === 'scheduled' ? 'selected' : '' }}>Agendado</option>
                                        <option value="confirmed" {{ $status === 'confirmed' ? 'selected' : '' }}>Confirmado</option>
                                        <option value="completed" {{ $status === 'completed' ? 'selected' : '' }}>Concluído</option>
                                        <option value="canceled" {{ $status === 'canceled' ? 'selected' : '' }}>Cancelado</option>
                                        <option value="no_show" {{ $status === 'no_show' ? 'selected' : '' }}>Não Compareceu</option>
                                    </select>
                                </div>
                                
                                <div>
                                    <x-input-label for="date_start" :value="__('Data Inicial')" />
                                    <x-text-input id="date_start" name="date_start" type="date" class="mt-1 block w-full" :value="$dateStart" />
                                </div>
                                
                                <div>
                                    <x-input-label for="date_end" :value="__('Data Final')" />
                                    <x-text-input id="date_end" name="date_end" type="date" class="mt-1 block w-full" :value="$dateEnd" />
                                </div>
                            </div>
                            
                            <div class="flex space-x-2">
                                <x-primary-button>
                                    {{ __('Filtrar') }}
                                </x-primary-button>
                                
                                @if ($doctorId || $patientId || $specialtyId || $status || $dateStart || $dateEnd)
                                    <a href="{{ route('admin.appointments.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-200 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                                        Limpar Filtros
                                    </a>
                                @endif
                            </div>
                        </form>
                    </div>

                    <!-- Tabela de Agendamentos -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Data/Hora
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Paciente
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
                                @forelse ($appointments as $appointment)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ $appointment->start_time->format('d/m/Y') }}
                                            </div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ $appointment->start_time->format('H:i') }} - {{ $appointment->end_time->format('H:i') }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ $appointment->patient->user->name }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ $appointment->doctor->user->name }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ $appointment->specialty->name }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                @if($appointment->status == 'scheduled') bg-yellow-100 text-yellow-800 
                                                @elseif($appointment->status == 'confirmed') bg-green-100 text-green-800
                                                @elseif($appointment->status == 'completed') bg-blue-100 text-blue-800
                                                @elseif($appointment->status == 'canceled') bg-red-100 text-red-800
                                                @elseif($appointment->status == 'no_show') bg-gray-100 text-gray-800 
                                                @endif">
                                                @if($appointment->status == 'scheduled') Agendado
                                                @elseif($appointment->status == 'confirmed') Confirmado
                                                @elseif($appointment->status == 'completed') Concluído
                                                @elseif($appointment->status == 'canceled') Cancelado
                                                @elseif($appointment->status == 'no_show') Não Compareceu
                                                @endif
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex space-x-2">
                                                <a href="{{ route('admin.appointments.show', $appointment) }}" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300">
                                                    Detalhes
                                                </a>
                                                <a href="{{ route('admin.appointments.edit', $appointment) }}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">
                                                    Editar
                                                </a>
                                                <form action="{{ route('admin.appointments.destroy', $appointment) }}" method="POST" class="inline-block">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300" onclick="return confirm('Tem certeza que deseja excluir este agendamento?')">
                                                        Excluir
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                            Nenhum agendamento encontrado
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginação -->
                    <div class="mt-4">
                        {{ $appointments->appends(request()->query())->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>