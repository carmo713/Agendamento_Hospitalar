<!-- filepath: /home/carmo/Documentos/trabalhofinal_agendamentohospitalar/agendamento/resources/views/admin/patients/edit.blade.php -->
<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Editar Paciente') }}
            </h2>
            <a href="{{ route('admin.patients.show', $patient) }}" class="inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white focus:bg-gray-700 dark:focus:bg-white active:bg-gray-900 dark:active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
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

                    <form method="POST" action="{{ route('admin.patients.update', $patient) }}">
                        @csrf
                        @method('PUT')

                        <!-- Informações de Usuário -->
                        <div class="mb-8">
                            <h3 class="text-lg font-medium mb-4 pb-2 border-b border-gray-200 dark:border-gray-700">Informações de Usuário</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <x-input-label for="name" :value="__('Nome Completo')" />
                                    <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name', $patient->user->name)" required autofocus />
                                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="email" :value="__('E-mail')" />
                                    <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email', $patient->user->email)" required />
                                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <x-input-label for="password" :value="__('Nova Senha (opcional)')" />
                                    <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" />
                                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                                    <p class="text-xs text-gray-500 mt-1">Deixe em branco para manter a senha atual.</p>
                                </div>
                                
                                <div>
                                    <x-input-label for="password_confirmation" :value="__('Confirmar Nova Senha')" />
                                    <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" />
                                </div>
                            </div>
                        </div>

                        <!-- Informações de Saúde -->
                        <div class="mb-8">
                            <h3 class="text-lg font-medium mb-4 pb-2 border-b border-gray-200 dark:border-gray-700">Informações de Saúde e Emergência</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <x-input-label for="health_insurance" :value="__('Plano de Saúde')" />
                                    <x-text-input id="health_insurance" class="block mt-1 w-full" type="text" name="health_insurance" :value="old('health_insurance', $patient->health_insurance)" />
                                    <x-input-error :messages="$errors->get('health_insurance')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="health_insurance_number" :value="__('Número da Carteirinha')" />
                                    <x-text-input id="health_insurance_number" class="block mt-1 w-full" type="text" name="health_insurance_number" :value="old('health_insurance_number', $patient->health_insurance_number)" />
                                    <x-input-error :messages="$errors->get('health_insurance_number')" class="mt-2" />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <x-input-label for="emergency_contact_name" :value="__('Nome do Contato de Emergência')" />
                                    <x-text-input id="emergency_contact_name" class="block mt-1 w-full" type="text" name="emergency_contact_name" :value="old('emergency_contact_name', $patient->emergency_contact_name)" />
                                    <x-input-error :messages="$errors->get('emergency_contact_name')" class="mt-2" />
                                </div>
                                
                                <div>
                                    <x-input-label for="emergency_contact_phone" :value="__('Telefone de Emergência')" />
                                    <x-text-input id="emergency_contact_phone" class="block mt-1 w-full" type="text" name="emergency_contact_phone" :value="old('emergency_contact_phone', $patient->emergency_contact_phone)" />
                                    <x-input-error :messages="$errors->get('emergency_contact_phone')" class="mt-2" />
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end mt-6">
                            <a href="{{ route('admin.patients.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-200 dark:bg-gray-700 border border-transparent rounded-md font-semibold text-xs text-gray-800 dark:text-gray-200 uppercase tracking-widest hover:bg-gray-300 dark:hover:bg-gray-600 focus:bg-gray-300 dark:focus:bg-gray-600 active:bg-gray-400 dark:active:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150 mr-3">
                                Cancelar
                            </a>
                            
                            <x-primary-button>
                                {{ __('Atualizar Paciente') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>