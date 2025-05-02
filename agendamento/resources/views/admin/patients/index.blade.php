<!-- filepath: /home/carmo/Documentos/trabalhofinal_agendamentohospitalar/agendamento/resources/views/admin/patients/index.blade.php -->
<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Pacientes') }}
            </h2>
            <a href="{{ route('admin.patients.create') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white focus:bg-gray-700 dark:focus:bg-white active:bg-gray-900 dark:active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                Novo Paciente
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

                    <!-- Filtros e Busca -->
                    <div class="mb-6">
                        <form method="GET" action="{{ route('admin.patients.index') }}" class="flex flex-col md:flex-row gap-4 md:items-end">
                            <div class="flex-grow">
                                <x-input-label for="search" :value="__('Buscar')" />
                                <x-text-input id="search" name="search" type="text" class="mt-1 block w-full" :value="$search" placeholder="Nome ou email do paciente" />
                            </div>
                            
                            <div class="md:w-1/4">
                                <x-input-label for="health_insurance" :value="__('Plano de Saúde')" />
                                <select id="health_insurance" name="health_insurance" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    <option value="">Todos os Planos</option>
                                    @foreach ($healthInsurances as $insurance)
                                        <option value="{{ $insurance }}" {{ $healthInsurance == $insurance ? 'selected' : '' }}>
                                            {{ $insurance }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div class="flex space-x-2">
                                <x-primary-button>
                                    {{ __('Filtrar') }}
                                </x-primary-button>
                                
                                @if ($search || $healthInsurance)
                                    <a href="{{ route('admin.patients.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-200 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                                        Limpar
                                    </a>
                                @endif
                            </div>
                        </form>
                    </div>

                    <!-- Tabela de Pacientes -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Nome
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        E-mail
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Plano de Saúde
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Contato de Emergência
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Ações
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-600">
                                @forelse ($patients as $patient)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ $patient->user->name }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ $patient->user->email }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                                @if($patient->health_insurance)
                                                    <span>{{ $patient->health_insurance }}</span>
                                                    @if($patient->health_insurance_number)
                                                        <span class="text-xs text-gray-400 dark:text-gray-500 block">
                                                            Nº {{ $patient->health_insurance_number }}
                                                        </span>
                                                    @endif
                                                @else
                                                    <span class="text-gray-400 dark:text-gray-500">Não informado</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                                @if($patient->emergency_contact_name)
                                                    <span>{{ $patient->emergency_contact_name }}</span>
                                                    @if($patient->emergency_contact_phone)
                                                        <span class="text-xs text-gray-400 dark:text-gray-500 block">
                                                            {{ $patient->emergency_contact_phone }}
                                                        </span>
                                                    @endif
                                                @else
                                                    <span class="text-gray-400 dark:text-gray-500">Não informado</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex space-x-2">
                                                <a href="{{ route('admin.patients.show', $patient) }}" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300">
                                                    Detalhes
                                                </a>
                                                <a href="{{ route('admin.patients.edit', $patient) }}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">
                                                    Editar
                                                </a>
                                                <form action="{{ route('admin.patients.destroy', $patient) }}" method="POST" class="inline-block">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300" onclick="return confirm('Tem certeza que deseja excluir este paciente? Esta ação também excluirá a conta de usuário associada.')">
                                                        Excluir
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                            Nenhum paciente encontrado
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginação -->
                    <div class="mt-4">
                        {{ $patients->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>