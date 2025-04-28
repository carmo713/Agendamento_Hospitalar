<!-- filepath: /home/carmo/Documentos/trabalhofinal_agendamentohospitalar/agendamento/resources/views/admin/doctors/create.blade.php -->
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Novo Médico') }}
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

                    <form method="POST" action="{{ route('admin.doctors.store') }}">
                        @csrf

                        <!-- Informações de Usuário -->
                        <div class="mb-8">
                            <h3 class="text-lg font-medium mb-4 pb-2 border-b border-gray-200 dark:border-gray-700">Informações de Usuário</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <x-input-label for="name" :value="__('Nome')" />
                                    <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus />
                                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="email" :value="__('E-mail')" />
                                    <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required />
                                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <x-input-label for="password" :value="__('Senha')" />
                                    <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required />
                                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                                    <p class="text-xs text-gray-500 mt-1">Mínimo de 8 caracteres.</p>
                                </div>
                                
                                <div>
                                    <x-input-label for="password_confirmation" :value="__('Confirmar Senha')" />
                                    <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required />
                                </div>
                            </div>
                        </div>

                        <!-- Informações Profissionais -->
                        <div class="mb-8">
                            <h3 class="text-lg font-medium mb-4 pb-2 border-b border-gray-200 dark:border-gray-700">Informações Profissionais</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                                <div class="md:col-span-2">
                                    <x-input-label for="crm" :value="__('CRM')" />
                                    <x-text-input id="crm" class="block mt-1 w-full" type="text" name="crm" :value="old('crm')" required />
                                    <x-input-error :messages="$errors->get('crm')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="crm_state" :value="__('Estado do CRM')" />
                                    <x-text-input id="crm_state" class="block mt-1 w-full" type="text" name="crm_state" :value="old('crm_state')" required maxlength="2" />
                                    <x-input-error :messages="$errors->get('crm_state')" class="mt-2" />
                                    <p class="text-xs text-gray-500 mt-1">Ex: SP, RJ, MG</p>
                                </div>

                                <div>
                                    <x-input-label for="consultation_duration" :value="__('Duração da Consulta (min)')" />
                                    <x-text-input id="consultation_duration" class="block mt-1 w-full" type="number" name="consultation_duration" :value="old('consultation_duration', 30)" required min="10" max="120" step="5" />
                                    <x-input-error :messages="$errors->get('consultation_duration')" class="mt-2" />
                                </div>
                            </div>

                            <div class="mb-4">
                                <x-input-label for="specialties" :value="__('Especialidades')" />
                                <div class="mt-2 grid grid-cols-1 md:grid-cols-3 gap-2">
                                    @foreach($specialties as $specialty)
                                        <div class="flex items-start">
                                            <div class="flex items-center h-5">
                                                <input id="specialty_{{ $specialty->id }}" name="specialties[]" type="checkbox" value="{{ $specialty->id }}" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:focus:ring-offset-gray-800" {{ in_array($specialty->id, old('specialties', [])) ? 'checked' : '' }}>
                                            </div>
                                            <div class="ml-3 text-sm">
                                                <label for="specialty_{{ $specialty->id }}" class="font-medium text-gray-700 dark:text-gray-300">{{ $specialty->name }}</label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <x-input-error :messages="$errors->get('specialties')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="bio" :value="__('Biografia Profissional')" />
                                <textarea id="bio" name="bio" rows="4" class="block mt-1 w-full rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:border-gray-600 dark:bg-gray-700 dark:text-white">{{ old('bio') }}</textarea>
                                <x-input-error :messages="$errors->get('bio')" class="mt-2" />
                            </div>
                        </div>

                        <div class="flex items-center justify-end mt-6">
                            <a href="{{ route('admin.doctors.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-200 dark:bg-gray-700 border border-transparent rounded-md font-semibold text-xs text-gray-800 dark:text-gray-200 uppercase tracking-widest hover:bg-gray-300 dark:hover:bg-gray-600 focus:bg-gray-300 dark:focus:bg-gray-600 active:bg-gray-400 dark:active:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150 mr-3">
                                Cancelar
                            </a>
                            
                            <x-primary-button>
                                {{ __('Cadastrar Médico') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>