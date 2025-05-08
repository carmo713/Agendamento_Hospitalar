<!-- filepath: /home/carmo/Documentos/trabalhofinal_agendamentohospitalar/agendamento/resources/views/doctor/schedule/index.blade.php -->
<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Minha Agenda') }}
            </h2>
            <div class="flex space-x-3">
                <a href="# class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                    Nova Consulta
                </a>
                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" class="bg-gray-800 text-white px-4 py-2 rounded-md hover:bg-gray-700">
                        Configurações
                        <svg class="ml-1 w-4 h-4 inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                    <div x-show="open" @click.away="open = false" class="absolute right-0 z-10 mt-2 w-48 rounded-md shadow-lg bg-white dark:bg-gray-700 ring-1 ring-black ring-opacity-5">
                        <div class="py-1">
                            <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-600">
                                Configurar Horários
                            </a>
                            <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-600">
                                Folgas e Férias
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Filtros e navegação de calendário -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex flex-wrap justify-between items-center">
                        <div class="flex items-center space-x-4 mb-4 md:mb-0">
                            <button id="prev-btn" class="p-2 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                </svg>
                            </button>
                            <button id="today-btn" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 rounded-md hover:bg-gray-200 dark:hover:bg-gray-600">
                                Hoje
                            </button>
                            <button id="next-btn" class="p-2 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </button>
                            <h3 id="calendar-title" class="text-xl font-semibold">{{ now()->format('F Y') }}</h3>
                        </div>
                        <div class="flex items-center space-x-2">
                            <button id="view-day" class="px-3 py-1 rounded-md bg-blue-600 text-white">Dia</button>
                            <button id="view-week" class="px-3 py-1 rounded-md bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600">Semana</button>
                            <button id="view-month" class="px-3 py-1 rounded-md bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600">Mês</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Visualização de Agenda -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <!-- Visualização Diária do Calendário (visível por padrão) -->
                <div id="day-view" class="p-4">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold" id="day-date">{{ now()->format('d \de F \de Y') }}</h3>
                        <span class="text-sm text-gray-500">{{ now()->format('l') }}</span>
                    </div>
                    <div class="grid grid-cols-1 gap-2">
                        @php
                            $startHour = 8; // Início às 8h
                            $endHour = 18; // Término às 18h
                            $currentHour = now()->hour;
                        @endphp

                        @for ($hour = $startHour; $hour < $endHour; $hour++)
                            <div class="flex border-t border-gray-200 dark:border-gray-700 py-2">
                                <div class="w-16 text-right pr-4 text-gray-500">
                                    {{ sprintf('%02d:00', $hour) }}
                                </div>
                                <div class="flex-1 min-h-16 relative {{ $hour == $currentHour ? 'bg-blue-50 dark:bg-blue-900/20' : '' }}">
                                    @if($hour == 9)
                                        <div class="absolute inset-0 mx-1 my-1 rounded-md bg-green-100 dark:bg-green-800/30 border-l-4 border-green-500 p-2">
                                            <p class="font-medium">Maria Silva</p>
                                            <p class="text-sm text-gray-600 dark:text-gray-300">Consulta de rotina</p>
                                            <div class="flex justify-between mt-2">
                                                <span class="text-xs text-gray-500">09:00 - 09:30</span>
                                                <a href="#" class="text-xs text-blue-600 hover:text-blue-800 dark:text-blue-400">Ver</a>
                                            </div>
                                        </div>
                                    @endif
                                    @if($hour == 10)
                                        <div class="absolute inset-0 mx-1 my-1 rounded-md bg-blue-100 dark:bg-blue-800/30 border-l-4 border-blue-500 p-2">
                                            <p class="font-medium">João Pereira</p>
                                            <p class="text-sm text-gray-600 dark:text-gray-300">Retorno de exames</p>
                                            <div class="flex justify-between mt-2">
                                                <span class="text-xs text-gray-500">10:00 - 10:45</span>
                                                <a href="#" class="text-xs text-blue-600 hover:text-blue-800 dark:text-blue-400">Ver</a>
                                            </div>
                                        </div>
                                    @endif
                                    @if($hour == 14)
                                        <div class="absolute inset-0 mx-1 my-1 rounded-md bg-purple-100 dark:bg-purple-800/30 border-l-4 border-purple-500 p-2">
                                            <p class="font-medium">Ana Carolina</p>
                                            <p class="text-sm text-gray-600 dark:text-gray-300">Primeira consulta</p>
                                            <div class="flex justify-between mt-2">
                                                <span class="text-xs text-gray-500">14:00 - 15:00</span>
                                                <a href="#" class="text-xs text-blue-600 hover:text-blue-800 dark:text-blue-400">Ver</a>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <div class="flex border-t border-gray-200 dark:border-gray-700 py-2">
                                <div class="w-16 text-right pr-4 text-gray-500">
                                    {{ sprintf('%02d:30', $hour) }}
                                </div>
                                <div class="flex-1 min-h-16 relative {{ $hour == $currentHour && now()->minute >= 30 ? 'bg-blue-50 dark:bg-blue-900/20' : '' }}">
                                    <!-- Consultas poderiam aparecer aqui em meias-horas -->
                                </div>
                            </div>
                        @endfor
                    </div>
                </div>

                <!-- Visualização Semanal (oculta por padrão) -->
                <div id="week-view" class="p-4 hidden">
                    <div class="mb-4">
                        <h3 class="text-lg font-semibold">Semana de {{ now()->startOfWeek()->format('d/m') }} até {{ now()->endOfWeek()->format('d/m') }}</h3>
                    </div>
                    <div class="grid grid-cols-7 gap-2">
                        @php
                            $weekStart = now()->startOfWeek();
                        @endphp
                        @for ($i = 0; $i < 7; $i++)
                            @php
                                $day = $weekStart->copy()->addDays($i);
                                $isToday = $day->isToday();
                            @endphp
                            <div class="border rounded-md {{ $isToday ? 'bg-blue-50 dark:bg-blue-900/20 border-blue-300 dark:border-blue-700' : '' }}">
                                <div class="p-2 text-center border-b bg-gray-50 dark:bg-gray-700">
                                    <div class="font-medium">{{ $day->format('D') }}</div>
                                    <div class="{{ $isToday ? 'text-blue-600 dark:text-blue-400 font-bold' : '' }}">{{ $day->format('d') }}</div>
                                </div>
                                <div class="p-2 min-h-[100px] text-sm">
                                    @if($i == 1) <!-- Terça-feira -->
                                        <div class="mb-2 p-1 rounded bg-green-100 dark:bg-green-800/30 border-l-2 border-green-500 text-xs">
                                            <div class="font-medium">09:00 - Maria Silva</div>
                                            <div class="text-gray-600 dark:text-gray-300 truncate">Consulta de rotina</div>
                                        </div>
                                        <div class="mb-2 p-1 rounded bg-blue-100 dark:bg-blue-800/30 border-l-2 border-blue-500 text-xs">
                                            <div class="font-medium">10:00 - João Pereira</div>
                                            <div class="text-gray-600 dark:text-gray-300 truncate">Retorno de exames</div>
                                        </div>
                                    @endif
                                    @if($i == 3) <!-- Quinta-feira -->
                                        <div class="mb-2 p-1 rounded bg-purple-100 dark:bg-purple-800/30 border-l-2 border-purple-500 text-xs">
                                            <div class="font-medium">14:00 - Ana Carolina</div>
                                            <div class="text-gray-600 dark:text-gray-300 truncate">Primeira consulta</div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endfor
                    </div>
                </div>

                <!-- Visualização Mensal (oculta por padrão) -->
                <div id="month-view" class="p-4 hidden">
                    <div class="mb-4">
                        <h3 class="text-lg font-semibold">{{ now()->format('F Y') }}</h3>
                    </div>
                    <div class="grid grid-cols-7 gap-1">
                        <div class="text-center font-medium p-2 text-sm">Seg</div>
                        <div class="text-center font-medium p-2 text-sm">Ter</div>
                        <div class="text-center font-medium p-2 text-sm">Qua</div>
                        <div class="text-center font-medium p-2 text-sm">Qui</div>
                        <div class="text-center font-medium p-2 text-sm">Sex</div>
                        <div class="text-center font-medium p-2 text-sm">Sáb</div>
                        <div class="text-center font-medium p-2 text-sm">Dom</div>

                        @php
                            $startOfMonth = now()->startOfMonth();
                            $endOfMonth = now()->endOfMonth();
                            $startOfCalendar = $startOfMonth->copy()->startOfWeek()->subDay();
                            $endOfCalendar = $endOfMonth->copy()->endOfWeek();
                            $currentDay = $startOfCalendar->copy();
                        @endphp

                        @while($currentDay->lte($endOfCalendar))
                            @php
                                $isToday = $currentDay->isToday();
                                $isCurrentMonth = $currentDay->month === now()->month;
                            @endphp
                            <div class="p-1 border {{ $isToday ? 'bg-blue-50 dark:bg-blue-900/20 border-blue-300 dark:border-blue-700' : '' }} {{ !$isCurrentMonth ? 'bg-gray-50 dark:bg-gray-900 text-gray-400 dark:text-gray-600' : '' }} min-h-[80px]">
                                <div class="text-right mb-1 {{ $isToday ? 'font-bold text-blue-600 dark:text-blue-400' : '' }}">
                                    {{ $currentDay->format('j') }}
                                </div>
                                @if($isCurrentMonth && $currentDay->format('d') == '15')
                                    <div class="text-xs p-1 mb-1 rounded bg-green-100 dark:bg-green-800/30 truncate">
                                        3 consultas
                                    </div>
                                @endif
                                @if($isCurrentMonth && $currentDay->format('d') == '22')
                                    <div class="text-xs p-1 mb-1 rounded bg-purple-100 dark:bg-purple-800/30 truncate">
                                        2 consultas
                                    </div>
                                @endif
                            </div>
                            @php
                                $currentDay->addDay();
                            @endphp
                        @endwhile
                    </div>
                </div>
            </div>

            <!-- Legenda -->
            <div class="mt-6 bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-3">Legenda</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="flex items-center">
                            <div class="w-4 h-4 rounded-full bg-green-500 mr-2"></div>
                            <span class="text-sm">Consulta de Rotina</span>
                        </div>
                        <div class="flex items-center">
                            <div class="w-4 h-4 rounded-full bg-blue-500 mr-2"></div>
                            <span class="text-sm">Retorno</span>
                        </div>
                        <div class="flex items-center">
                            <div class="w-4 h-4 rounded-full bg-purple-500 mr-2"></div>
                            <span class="text-sm">Primeira Consulta</span>
                        </div>
                        <div class="flex items-center">
                            <div class="w-4 h-4 rounded-full bg-red-500 mr-2"></div>
                            <span class="text-sm">Consulta Cancelada</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const dayView = document.getElementById('day-view');
            const weekView = document.getElementById('week-view');
            const monthView = document.getElementById('month-view');
            const dayBtn = document.getElementById('view-day');
            const weekBtn = document.getElementById('view-week');
            const monthBtn = document.getElementById('view-month');
            
            dayBtn.addEventListener('click', function() {
                dayView.classList.remove('hidden');
                weekView.classList.add('hidden');
                monthView.classList.add('hidden');
                
                dayBtn.classList.add('bg-blue-600', 'text-white');
                dayBtn.classList.remove('bg-gray-200', 'dark:bg-gray-700');
                
                weekBtn.classList.remove('bg-blue-600', 'text-white');
                weekBtn.classList.add('bg-gray-200', 'dark:bg-gray-700');
                
                monthBtn.classList.remove('bg-blue-600', 'text-white');
                monthBtn.classList.add('bg-gray-200', 'dark:bg-gray-700');
            });
            
            weekBtn.addEventListener('click', function() {
                dayView.classList.add('hidden');
                weekView.classList.remove('hidden');
                monthView.classList.add('hidden');
                
                dayBtn.classList.remove('bg-blue-600', 'text-white');
                dayBtn.classList.add('bg-gray-200', 'dark:bg-gray-700');
                
                weekBtn.classList.add('bg-blue-600', 'text-white');
                weekBtn.classList.remove('bg-gray-200', 'dark:bg-gray-700');
                
                monthBtn.classList.remove('bg-blue-600', 'text-white');
                monthBtn.classList.add('bg-gray-200', 'dark:bg-gray-700');
            });
            
            monthBtn.addEventListener('click', function() {
                dayView.classList.add('hidden');
                weekView.classList.add('hidden');
                monthView.classList.remove('hidden');
                
                dayBtn.classList.remove('bg-blue-600', 'text-white');
                dayBtn.classList.add('bg-gray-200', 'dark:bg-gray-700');
                
                weekBtn.classList.remove('bg-blue-600', 'text-white');
                weekBtn.classList.add('bg-gray-200', 'dark:bg-gray-700');
                
                monthBtn.classList.add('bg-blue-600', 'text-white');
                monthBtn.classList.remove('bg-gray-200', 'dark:bg-gray-700');
            });
            
            // Implementação do calendário completo exigiria JavaScript adicional para navegação 
            // e carregamento de dados via AJAX
        });
    </script>
</x-app-layout>