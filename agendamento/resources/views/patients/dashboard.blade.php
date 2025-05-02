<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Minha Área
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Informações do Paciente -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <div class="flex flex-col md:flex-row md:items-center">
                        <div class="flex-shrink-0 h-20 w-20 rounded-full overflow-hidden">
                            <div class="h-full w-full bg-gray-300 flex items-center justify-center text-gray-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                        </div>
                        <div class="md:ml-6 mt-4 md:mt-0">
                            <h3 class="text-2xl font-semibold text-gray-800 dark:text-white">Olá, [Nome do Paciente]</h3>
                            <p class="text-gray-600 dark:text-gray-300">Bem-vindo à sua área do paciente</p>
                        </div>
                        <div class="ml-auto mt-4 md:mt-0 text-right">
                            <div class="text-sm text-gray-600 dark:text-gray-300">
                                <span class="font-semibold">Data:</span> [Data Atual]
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-300">
                                <span class="font-semibold">Hora:</span> [Hora Atual]
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status de Saúde & Ações Rápidas -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <!-- Status de Saúde -->
                <div class="md:col-span-2">
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg h-full">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-white">Status de Saúde</h3>
                            
                            <!-- Alertas e Lembretes -->
                            <div class="mb-6 bg-blue-50 dark:bg-blue-900 p-4 rounded-lg">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-blue-600 dark:text-blue-300" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2h-1V9z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">Lembrete de Medicação</h3>
                                        <div class="mt-1 text-sm text-blue-700 dark:text-blue-300">
                                            <p>Não se esqueça de tomar seu medicamento hoje às [Hora do Medicamento].</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Medicamentos Atuais -->
                            <div class="mb-4">
                                <h4 class="text-md font-medium mb-2 text-gray-700 dark:text-gray-300">Medicamentos Atuais</h4>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
                                        <thead>
                                            <tr>
                                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Medicamento</th>
                                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Dosagem</th>
                                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Frequência</th>
                                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Validade</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-600">
                                            <tr>
                                                <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200">[Medicamento 1]</td>
                                                <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200">[Dosagem]</td>
                                                <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200">[Frequência]</td>
                                                <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200">[Validade]</td>
                                            </tr>
                                            <tr>
                                                <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200">[Medicamento 2]</td>
                                                <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200">[Dosagem]</td>
                                                <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200">[Frequência]</td>
                                                <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200">[Validade]</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Ações Rápidas -->
                <div class="md:col-span-1">
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg h-full">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-white">Ações Rápidas</h3>
                            <div class="space-y-3">
                                <a href="#" class="block w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded text-center transition">
                                    Agendar Consulta
                                </a>
                                <a href="#" class="block w-full bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 font-semibold py-2 px-4 rounded text-center transition">
                                    Minhas Consultas
                                </a>
                                <a href="#" class="block w-full bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 font-semibold py-2 px-4 rounded text-center transition">
                                    Mensagens
                                </a>
                                <a href="#" class="block w-full bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 font-semibold py-2 px-4 rounded text-center transition">
                                    Receitas
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Próximas Consultas & Notificações -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Próximas Consultas -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Próximas Consultas</h3>
                            <a href="#" class="text-sm text-blue-500 hover:text-blue-700">Ver todas</a>
                        </div>
                        
                        <div class="space-y-4">
                            <!-- Consulta 1 -->
                            <div class="border-l-4 border-green-500 pl-4 py-2">
                                <div class="flex justify-between">
                                    <div>
                                        <p class="font-semibold text-gray-800 dark:text-gray-200">[Médico 1]</p>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">[Local]</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-semibold text-gray-800 dark:text-gray-200">[Data]</p>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">[Hora]</p>
                                    </div>
                                </div>
                                <div class="mt-2 flex space-x-2">
                                    <a href="#" class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded hover:bg-blue-200">Detalhes</a>
                                    <a href="#" class="text-xs bg-yellow-100 text-yellow-800 px-2 py-1 rounded hover:bg-yellow-200">Reagendar</a>
                                    <a href="#" class="text-xs bg-red-100 text-red-800 px-2 py-1 rounded hover:bg-red-200">Cancelar</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notificações -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Notificações</h3>
                            <button class="text-sm text-blue-500 hover:text-blue-700">Marcar todas como lidas</button>
                        </div>
                        
                        <div class="space-y-4">
                            <!-- Notificação 1 -->
                            <div class="bg-yellow-50 dark:bg-yellow-900/30 p-3 rounded-lg">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-yellow-600 dark:text-yellow-300" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm0-2a6 6 0 100-12 6 6 0 000 12zm0-9a1 1 0 011 1v4a1 1 0 11-2 0V8a1 1 0 011-1z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">[Título Notificação]</h3>
                                        <div class="mt-1 text-sm text-yellow-700 dark:text-yellow-300">
                                            <p>[Descrição Notificação]</p>
                                        </div>
                                        <div class="mt-2 text-xs text-yellow-600 dark:text-yellow-400">
                                            [Tempo atrás]
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
