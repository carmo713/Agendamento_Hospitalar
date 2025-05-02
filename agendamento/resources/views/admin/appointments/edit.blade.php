<!-- filepath: /home/carmo/Documentos/trabalhofinal_agendamentohospitalar/agendamento/resources/views/admin/appointments/edit.blade.php -->
<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Editar Agendamento') }}
            </h2>
            <a href="{{ route('admin.appointments.show', $appointment) }}" class="inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white focus:bg-gray-700 dark:focus:bg-white active:bg-gray-900 dark:active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                Ver Detalhes
            </a>
        </div>
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

                    <form method="POST" action="{{ route('admin.appointments.update', $appointment) }}">
                        @csrf
                        @method('PUT')

                        <!-- Seleção de Paciente -->
                        <div class="mb-6">
                            <h3 class="text-lg font-medium mb-4 pb-2 border-b border-gray-200 dark:border-gray-700">Informações do Paciente</h3>
                            
                            <div>
                                <x-input-label for="patient_id" :value="__('Selecione o Paciente')" />
                                <select id="patient_id" name="patient_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    <option value="">Selecione um paciente</option>
                                    @foreach ($patients as $patient)
                                        <option value="{{ $patient->id }}" {{ old('patient_id', $appointment->patient_id) == $patient->id ? 'selected' : '' }}>
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
                                                {{ old('doctor_id', $appointment->doctor_id) == $doctor->id ? 'selected' : '' }}
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
                                            <option value="{{ $specialty->id }}" {{ old('specialty_id', $appointment->specialty_id) == $specialty->id ? 'selected' : '' }}>
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
                                    <x-text-input id="start_date" name="start_date" type="date" class="mt-1 block w-full" :value="old('start_date', $startDate)" required />
                                    <x-input-error :messages="$errors->get('start_date')" class="mt-2" />
                                </div>
                                
                                <div>
                                    <x-input-label for="start_time" :value="__('Hora')" />
                                    <x-text-input id="start_time" name="start_time" type="time" class="mt-1 block w-full" :value="old('start_time', $startTime)" required step="300" />
                                    <x-input-error :messages="$errors->get('start_time')" class="mt-2" />
                                </div>
                                
                                <div>
                                    <x-input-label for="duration" :value="__('Duração (minutos)')" />
                                    <x-text-input id="duration" name="duration" type="number" class="mt-1 block w-full" :value="old('duration', $duration)" required min="5" step="5" />
                                    <x-input-error :messages="$errors->get('duration')" class="mt-2" />
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
                                <div>
                                    <x-input-label for="reason" :value="__('Motivo da Consulta')" />
                                    <x-text-input id="reason" name="reason" type="text" class="mt-1 block w-full" :value="old('reason', $appointment->reason)" required />
                                    <x-input-error :messages="$errors->get('reason')" class="mt-2" />
                                </div>
                                
                                <div>
                                    <x-input-label for="status" :value="__('Status')" />
                                    <select id="status" name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white" onchange="toggleCancellationReason()">
                                        <option value="scheduled" {{ old('status', $appointment->status) === 'scheduled' ? 'selected' : '' }}>Agendado</option>
                                        <option value="confirmed" {{ old('status', $appointment->status) === 'confirmed' ? 'selected' : '' }}>Confirmado</option>
                                        <option value="completed" {{ old('status', $appointment->status) === 'completed' ? 'selected' : '' }}>Concluído</option>
                                        <option value="canceled" {{ old('status', $appointment->status) === 'canceled' ? 'selected' : '' }}>Cancelado</option>
                                        <option value="no_show" {{ old('status', $appointment->status) === 'no_show' ? 'selected' : '' }}>Não Compareceu</option>
                                    </select>
                                    <x-input-error :messages="$errors->get('status')" class="mt-2" />
                                </div>
                            </div>
                            
                            <div id="cancellation_reason_group" class="mb-4 {{ old('status', $appointment->status) === 'canceled' ? '' : 'hidden' }}">
                                <x-input-label for="cancellation_reason" :value="__('Motivo do Cancelamento')" />
                                <textarea id="cancellation_reason" name="cancellation_reason" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white">{{ old('cancellation_reason', $appointment->cancellation_reason) }}</textarea>
                                <x-input-error :messages="$errors->get('cancellation_reason')" class="mt-2" />
                            </div>
                            
                            <div>
                                <x-input-label for="notes" :value="__('Observações')" />
                                <textarea id="notes" name="notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white">{{ old('notes', $appointment->notes) }}</textarea>
                                <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                            </div>
                        </div>

                        <div class="flex items-center justify-end mt-6">
                            <a href="{{ route('admin.appointments.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-200 dark:bg-gray-700 border border-transparent rounded-md font-semibold text-xs text-gray-800 dark:text-gray-200 uppercase tracking-widest hover:bg-gray-300 dark:hover:bg-gray-600 focus:bg-gray-300 dark:focus:bg-gray-600 active:bg-gray-400 dark:active:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150 mr-3">
                                Cancelar
                            </a>
                            
                            <x-primary-button>
                                {{ __('Atualizar Agendamento') }}
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
        
        function toggleCancellationReason() {
            const statusSelect = document.getElementById('status');
            const cancellationReasonGroup = document.getElementById('cancellation_reason_group');
            
            if (statusSelect.value === 'canceled') {
                cancellationReasonGroup.classList.remove('hidden');
                document.getElementById('cancellation_reason').setAttribute('required', 'required');
            } else {
                cancellationReasonGroup.classList.add('hidden');
                document.getElementById('cancellation_reason').removeAttribute('required');
            }
        }
        
        // Executar ao carregar a página
        document.addEventListener('DOMContentLoaded', function() {
            updateSpecialties();
            toggleCancellationReason();
        });
    </script>
</x-app-layout>