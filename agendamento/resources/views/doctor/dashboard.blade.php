<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Dashboard Médico') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Informações do Médico -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <div class="flex flex-col md:flex-row md:items-center">
                        <div class="flex-shrink-0 h-20 w-20">
                            <!-- Foto do médico -->
                            <img src="path/to/static/photo.jpg" alt="Foto do Médico">
                        </div>
                        <div class="md:ml-6 mt-4 md:mt-0">
                            <h3 class="text-2xl font-semibold text-gray-800 dark:text-white">Dr. Nome Estático</h3>
                            <p class="text-gray-600 dark:text-gray-300">Especialidade Estática</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">CRM: 123456</p>
                        </div>
                        <div class="ml-auto mt-4 md:mt-0 text-right">
                            <div class="text-sm text-gray-600 dark:text-gray-300">
                                <span class="font-semibold">Data:</span> 01/01/2023
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-300">
                                <span class="font-semibold">Hora:</span> 12:00
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cards de Estatísticas -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-blue-500 bg-opacity-75">
                                <!-- Ícone -->
                                <i class="fas fa-calendar"></i>
                            </div>
                            <div class="ml-5">
                                <h4 class="text-2xl font-semibold">10</h4>
                                <div class="text-sm text-gray-500">Consultas Hoje</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-green-500 bg-opacity-75">
                                <!-- Ícone -->
                                <i class="fas fa-user"></i>
                            </div>
                            <div class="ml-5">
                                <h4 class="text-2xl font-semibold">50</h4>
                                <div class="text-sm text-gray-500">Pacientes Total</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-purple-500 bg-opacity-75">
                                <!-- Ícone -->
                                <i class="fas fa-file-prescription"></i>
                            </div>
                            <div class="ml-5">
                                <h4 class="text-2xl font-semibold">20</h4>
                                <div class="text-sm text-gray-500">Receitas Emitidas</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-yellow-500 bg-opacity-75">
                                <!-- Ícone -->
                                <i class="fas fa-vials"></i>
                            </div>
                            <div class="ml-5">
                                <h4 class="text-2xl font-semibold">15</h4>
                                <div class="text-sm text-gray-500">Exames Solicitados</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Consultas de Hoje e Sala de Espera -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-white">Próximas Consultas</h3>
                        <p>Lista estática de consultas</p>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-white">Sala de Espera</h3>
                        <p>Lista estática da sala de espera</p>
                    </div>
                </div>
            </div>

            <!-- Notificações e Mensagens Recentes -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-white">Mensagens Recentes</h3>
                        <p>Lista estática de mensagens</p>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-white">Prontuários Recentes</h3>
                        <p>Lista estática de prontuários</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
