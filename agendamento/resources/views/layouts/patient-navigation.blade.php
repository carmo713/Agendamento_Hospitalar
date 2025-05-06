<!-- filepath: /home/carmo/Documentos/trabalhofinal_agendamentohospitalar/agendamento/resources/views/layouts/patient-navigation.blade.php -->
<nav x-data="{ open: false }" class="bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('patient.dashboard') }}">
                        <x-application-logo class="block h-9 w-auto fill-current text-gray-800 dark:text-gray-200" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ml-10 sm:flex">
                    <!-- Dashboard -->
                    <x-nav-link :href="route('patient.dashboard')" :active="request()->routeIs('patient.dashboard')">
                        {{ __('Dashboard') }}
                    </x-nav-link>
                    
                    <!-- Agendamento -->
                    <x-nav-link href="#" :active="false">
                        {{ __('Agendar Consulta') }}
                    </x-nav-link>
                    
                    <!-- Minhas Consultas -->
                    <x-nav-link href="#" :active="false">
                        {{ __('Minhas Consultas') }}
                    </x-nav-link>
                    
                    <!-- Histórico Médico - Dropdown -->
                    <div class="hidden sm:flex sm:items-center">
                        <x-dropdown align="right" width="48">
                            <x-slot name="trigger">
                                <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none transition ease-in-out duration-150">
                                    <div>{{ __('Meu Histórico') }}</div>

                                    <div class="ml-1">
                                        <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                </button>
                            </x-slot>

                            <x-slot name="content">
                                <x-dropdown-link href="#" :active="false">
                                    {{ __('Prontuários') }}
                                </x-dropdown-link>
                                
                                <x-dropdown-link href="#" :active="false">
                                    {{ __('Receitas') }}
                                </x-dropdown-link>
                                
                                <x-dropdown-link href="#" :active="false">
                                    {{ __('Medicamentos') }}
                                </x-dropdown-link>
                                
                                <x-dropdown-link href="#" :active="false">
                                    {{ __('Atestados') }}
                                </x-dropdown-link>
                            </x-slot>
                        </x-dropdown>
                    </div>
                    
                    <!-- Documentos -->
                    <x-nav-link href="#" :active="false">
                        {{ __('Documentos') }}
                    </x-nav-link>
                    
                    <!-- Mensagens -->
                    <x-nav-link href="#" :active="false">
                        {{ __('Mensagens') }}
                        <span class="ml-1 bg-red-500 text-white text-xs font-bold px-1.5 py-0.5 rounded-full">2</span>
                    </x-nav-link>
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ml-6">
                <!-- Notificações -->
                <button class="mr-3 relative p-1 rounded-full text-gray-500 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <span class="sr-only">Ver notificações</span>
                    <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    <!-- Indicador de notificação -->
                    <span class="absolute top-0 right-0 block h-2 w-2 rounded-full bg-red-500 ring-2 ring-white"></span>
                </button>
                
                <!-- Menu do Usuário -->
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none transition ease-in-out duration-150">
                            <div>{{ Auth::user()->name ?? 'Usuário' }}</div>

                            <div class="ml-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <!-- Perfil de Saúde -->
                        <x-dropdown-link href="#">
                            {{ __('Perfil de Saúde') }}
                        </x-dropdown-link>
                        
                        <!-- Pagamentos -->
                        <x-dropdown-link href="#">
                            {{ __('Pagamentos') }}
                        </x-dropdown-link>
                        
                        <!-- Avaliações -->
                        <x-dropdown-link href="#">
                            {{ __('Minhas Avaliações') }}
                        </x-dropdown-link>
                        
                        <!-- Configurações -->
                        <x-dropdown-link href="#">
                            {{ __('Dados Pessoais') }}
                        </x-dropdown-link>
                        
                        <x-dropdown-link href="#">
                            {{ __('Senha e Segurança') }}
                        </x-dropdown-link>
                        
                        <x-dropdown-link href="#">
                            {{ __('Preferências de Notificação') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Sair') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-mr-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-900 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-900 focus:text-gray-500 dark:focus:text-gray-400 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <!-- Dashboard -->
            <x-responsive-nav-link :href="route('patient.dashboard')" :active="request()->routeIs('patient.dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>
            
            <!-- Agendamento -->
            <x-responsive-nav-link href="#" :active="false">
                {{ __('Agendar Consulta') }}
            </x-responsive-nav-link>
            
            <!-- Minhas Consultas -->
            <x-responsive-nav-link href="#" :active="false">
                {{ __('Minhas Consultas') }}
            </x-responsive-nav-link>
            
            <!-- Histórico Médico -->
            <div class="pl-3 pr-4 py-2 border-l-4 border-transparent text-base font-medium text-gray-600 dark:text-gray-400">
                Meu Histórico
            </div>
            
            <x-responsive-nav-link href="#" :active="false" class="pl-8">
                {{ __('Prontuários') }}
            </x-responsive-nav-link>
            
            <x-responsive-nav-link href="#" :active="false" class="pl-8">
                {{ __('Receitas') }}
            </x-responsive-nav-link>
            
            <x-responsive-nav-link href="#" :active="false" class="pl-8">
                {{ __('Medicamentos') }}
            </x-responsive-nav-link>
            
            <x-responsive-nav-link href="#" :active="false" class="pl-8">
                {{ __('Atestados') }}
            </x-responsive-nav-link>
            
            <!-- Documentos -->
            <x-responsive-nav-link href="#" :active="false">
                {{ __('Documentos') }}
            </x-responsive-nav-link>
            
            <!-- Mensagens -->
            <x-responsive-nav-link href="#" :active="false">
                <div class="flex">
                    <span>{{ __('Mensagens') }}</span>
                    <span class="ml-1 bg-red-500 text-white text-xs font-bold px-1.5 py-0.5 rounded-full">2</span>
                </div>
            </x-responsive-nav-link>
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200 dark:border-gray-600">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800 dark:text-gray-200">{{ Auth::user()->name ?? 'Usuário' }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email ?? 'usuario@example.com' }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <!-- Perfil de Saúde -->
                <x-responsive-nav-link href="#">
                    {{ __('Perfil de Saúde') }}
                </x-responsive-nav-link>
                
                <!-- Pagamentos -->
                <x-responsive-nav-link href="#">
                    {{ __('Pagamentos') }}
                </x-responsive-nav-link>
                
                <!-- Avaliações -->
                <x-responsive-nav-link href="#">
                    {{ __('Minhas Avaliações') }}
                </x-responsive-nav-link>
                
                <!-- Configurações -->
                <x-responsive-nav-link href="#">
                    {{ __('Dados Pessoais') }}
                </x-responsive-nav-link>
                
                <x-responsive-nav-link href="#">
                    {{ __('Senha e Segurança') }}
                </x-responsive-nav-link>
                
                <x-responsive-nav-link href="#">
                    {{ __('Preferências de Notificação') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Sair') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>