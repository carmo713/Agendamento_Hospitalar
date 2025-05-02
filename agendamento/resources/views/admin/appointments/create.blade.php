<!-- filepath: /home/carmo/Documentos/trabalhofinal_agendamentohospitalar/agendamento/resources/views/admin/appointments/create.blade.php -->
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Novo Agendamento') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    @if (session('error'))
                        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">
                            <p>{{ session('error') }}</p>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('admin.appointments.store') }}">
                        @csrf

                        <!-- Seleção de Paciente -->
                        <div class="mb-6">
                            <h3 class="text-lg font-medium mb-4 pb-2 border-b border-gray-200 dark:border-gray-700">Informações do Paciente</h3>
                            
                            <div>
                                <x-input-label for="patient_id" :value="__('Selecione o Paciente')" />
                                <select id="patient_id" name="patient_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    <option value="">Selecione um paciente</option>
                                    @foreach ($patients as $patient)
                                        <option value="{{ $patient->id }}" {{ $selectedPatientId == $patient->id ? 'selected' : '' }}>
                                            {{ $patient->user->name }} - {{ $patient->user->email }}
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('patient_id')" class="mt-2" />
                            </div>
                        </div>

                        <!-- Seleção de Médico e Especialidade -->
                        <div class="mb-6">
                            <h3 class="text-lg font-medium mb-4 pb-2 border-b border-gray-200 dark:border-gray-700">Informações do Médico</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <x-input-label for="doctor_id" :value="__('Selecione o Médico')" />
                                    <select id="doctor_id" name="doctor_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white" onchange="updateSpecialties()">
                                        <option value="">Selecione um médico</option>
                                        @foreach ($doctors as $doctor)
                                            <option value="{{ $doctor->id }}" 
                                                {{ $selectedDoctorId == $doctor->id ? 'selected' : '' }}
                                                data-specialties="{{ $doctor->specialties->pluck('id') }}">
                                                {{ $doctor->user->name }} - CRM: {{ $doctor->crm }}/{{ $doctor->crm_state }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('doctor_id')" class="mt-2" />
                                </div>
                                
                                <div>
                                    <x-input-label for="specialty_id" :value="__('Especialidade')" />
                                    <select id="specialty_id" name="specialty_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                        <option value="">Selecione uma especialidade</option>
                                        @foreach ($specialties as $specialty)
                                            <option value="{{ $specialty->id }}" {{ $selectedSpecialtyId == $specialty->id ? 'selected' : '' }}>
                                                {{ $specialty->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('specialty_id')" class="mt-2" />
                                </div>
                            </div>
                        </div>

                        <!-- Detalhes do Agendamento -->
                        <div class="mb-6">
                            <h3 class="text-lg font-medium mb-4 pb-2 border-b border-gray-200 dark:border-gray-700">Detalhes do Agendamento</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-4">
                                <div>
                                    <x-input-label for="start_date" :value="__('Data')" />
                                    <x-text-input id="start_date" name="start_date" type="date" class="mt-1 block w-full" :value="old('start_date', date('Y-m-d'))" required />
                                    <x-input-error :messages="$errors->get('start_date')" class="mt-2" />
                                </div>
                                
                                <div>
                                    <x-input-label for="start_time" :value="__('Hora')" />
                                    <x-text-input id="start_time" name="start_time" type="time" class="mt-1 block w-full" :value="old('start_time', '08:00')" required step="300" />
                                    <x-input-error :messages="$errors->get('start_time')" class="mt-2" />
                                </div>
                                
                                <div>
                                    <x-input-label for="duration" :value="__('Duração (minutos)')" />
                                    <x-text-input id="duration" name="duration" type="number" class="mt-1 block w-full" :value="old('duration', 30)" required min="5" step="5" />
                                    <x-input-error :messages="$errors->get('duration')" class="mt-2" />
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
                                <div>
                                    <x-input-label for="reason" :value="__('Motivo da Consulta')" />
                                    <x-text-input id="reason" name="reason" type="text" class="mt-1 block w-full" :value="old('reason')" required />
                                    <x-input-error :messages="$errors->get('reason')" class="mt-2" />
                                </div>
                                
                                <div>
                                    <x-input-label for="status" :value="__('Status')" />
                                    <select id="status" name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                        <option value="scheduled" {{ old('status') === 'scheduled' ? 'selected' : '' }}>Agendado</option>
                                        <option value="confirmed" {{ old('status') === 'confirmed' ? 'selected' : '' }}>Confirmado</option>
                                    </select>
                                    <x-input-error :messages="$errors->get('status')" class="mt-2" />
                                </div>
                            </div>
                            
                            <div>
                                <x-input-label for="notes" :value="__('Observações')" />
                                <textarea id="notes" name="notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white">{{ old('notes') }}</textarea>
                                <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                            </div>
                        </div>

                        <div class="flex items-center justify-end mt-6">
                            <a href="{{ route('admin.appointments.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-200 dark:bg-gray-700 border border-transparent rounded-md font-semibold text-xs text-gray-800 dark:text-gray-200 uppercase tracking-widest hover:bg-gray-300 dark:hover:bg-gray-600 focus:bg-gray-300 dark:focus:bg-gray-600 active:bg-gray-400 dark:active:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150 mr-3">
                                Cancelar
                            </a>
                            
                            <x-primary-button>
                                {{ __('Agendar Consulta') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function updateSpecialties() {
            const doctorSelect = document.getElementById('doctor_id');
            const specialtySelect = document.getElementById('specialty_id');
            const selectedOption = doctorSelect.options[doctorSelect.selectedIndex];
            
            if (selectedOption && selectedOption.value) {
                const doctorSpecialties = JSON.parse(selectedOption.getAttribute('data-specialties'));
                
                // Habilitar apenas as especialidades do médico selecionado
                Array.from(specialtySelect.options).forEach(option => {
                    if (option.value) {
                        if (doctorSpecialties.includes(parseInt(option.value))) {
                            option.disabled = false;
                        } else {
                            option.disabled = true;
                        }
                    }
                });
                
                // Se a especialidade atual não estiver na lista do médico, selecionar a primeira disponível
                if (doctorSpecialties.length > 0 && !doctorSpecialties.includes(parseInt(specialtySelect.value))) {
                    for (let i = 0; i < specialtySelect.options.length; i++) {
                        if (!specialtySelect.options[i].disabled && specialtySelect.options[i].value) {
                            specialtySelect.selectedIndex = i;
                            break;
                        }
                    }
                }
            } else {
                // Reativar todas as especialidades se nenhum médico estiver selecionado
                Array.from(specialtySelect.options).forEach(option => {
                    option.disabled = false;
                });
            }
        }
        
        // Executar ao carregar a página
        document.addEventListener('DOMContentLoaded', function() {
            updateSpecialties();
        });
    </script>
</x-app-layout>