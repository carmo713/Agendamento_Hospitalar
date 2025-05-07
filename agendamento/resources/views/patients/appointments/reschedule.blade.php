<!-- filepath: /home/carmo/Documentos/trabalhofinal_agendamentohospitalar/agendamento/resources/views/patient/appointments/reschedule.blade.php -->
<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Reagendar Consulta') }}
            </h2>
            <a href="{{ route('patient.appointments.show', $appointment) }}" class="inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white focus:bg-gray-700 dark:focus:bg-white active:bg-gray-900 dark:active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                Voltar para Detalhes
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

                    <div class="mb-8">
                        <h3 class="text-lg font-medium mb-4">Detalhes da Consulta Atual</h3>
                        
                        <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg mb-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Médico</p>
                                    <p class="mt-1 font-medium">{{ $appointment->doctor->user->name }}</p>
                                </div>
                                
                                <div>
                                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Especialidade</p>
                                    <p class="mt-1">{{ $appointment->specialty->name }}</p>
                                </div>
                                
                                <div>
                                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Data e Hora Atual</p>
                                    <p class="mt-1">{{ $appointment->start_time->format('d/m/Y') }} às {{ $appointment->start_time->format('H:i') }}</p>
                                </div>
                                
                                <div>
                                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Motivo da Consulta</p>
                                    <p class="mt-1">{{ $appointment->reason }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('patient.appointments.process-reschedule', $appointment) }}">
                        @csrf
                        
                        <h3 class="text-lg font-medium mb-4">Selecione uma Nova Data e Hora</h3>
                        
                        <div class="mb-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <x-input-label for="date" :value="__('Data')" />
                                    <x-text-input id="date" class="block mt-1 w-full" type="date" name="date" :value="old('date')" required min="{{ $startDate }}" max="{{ $endDate }}" />
                                    <x-input-error :messages="$errors->get('date')" class="mt-2" />
                                </div>
                                
                                <div>
                                    <x-input-label for="time" :value="__('Horário')" />
                                    <select id="time" name="time" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required>
                                        <option value="">Selecione um horário</option>
                                    </select>
                                    <x-input-error :messages="$errors->get('time')" class="mt-2" />
                                </div>
                            </div>
                            
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                Os horários disponíveis serão exibidos após selecionar uma data.
                            </p>
                        </div>
                        
                        <div class="flex items-center justify-end mt-8">
                            <a href="{{ route('patient.appointments.show', $appointment) }}" class="inline-flex items-center px-4 py-2 bg-gray-200 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-300 active:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150 mr-3">
                                Cancelar
                            </a>
                            
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150">
                                Confirmar Reagendamento
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript para carregar horários disponíveis -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const dateInput = document.getElementById('date');
            const timeSelect = document.getElementById('time');
            
            // Define os horários disponíveis por data
            const availableSlots = @json($availableSlots);
            
            // Função para atualizar os horários disponíveis
            function updateAvailableTimes() {
                const selectedDate = dateInput.value;
                
                // Limpar opções atuais
                timeSelect.innerHTML = '<option value="">Selecione um horário</option>';
                
                // Se a data foi selecionada e existem horários disponíveis
                if (selectedDate && availableSlots[selectedDate]) {
                    // Adicionar os horários disponíveis ao select
                    availableSlots[selectedDate].forEach(slot => {
                        const option = document.createElement('option');
                        option.value = slot;
                        option.textContent = slot;
                        timeSelect.appendChild(option);
                    });
                }
            }
            
            // Atualizar horários quando a data é alterada
            dateInput.addEventListener('change', updateAvailableTimes);
            
            // Executar na carga inicial (caso haja uma data pré-selecionada)
            updateAvailableTimes();
        });
    </script>
</x-app-layout>