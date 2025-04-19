<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>MedAgenda - Sistema de Agendamento Médico</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
        
        <!-- Swiper JS -->
        <link rel="stylesheet" href="https://unpkg.com/swiper/swiper-bundle.min.css" />
        
        <!-- Styles -->
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @else
            <script src="https://cdn.tailwindcss.com"></script>
            <style>
                /* Base styles */
                body {
                    font-family: 'Figtree', sans-serif;
                    color: #1a202c;
                    line-height: 1.5;
                }
                
                .antialiased {
                    -webkit-font-smoothing: antialiased;
                    -moz-osx-font-smoothing: grayscale;
                }
                
                /* Custom styles */
                .bg-dots-darker {
                    background-image: url("data:image/svg+xml,%3Csvg width='30' height='30' viewBox='0 0 30 30' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M1.22676 0C1.91374 0 2.45351 0.539773 2.45351 1.22676C2.45351 1.91374 1.91374 2.45351 1.22676 2.45351C0.539773 2.45351 0 1.91374 0 1.22676C0 0.539773 0.539773 0 1.22676 0Z' fill='rgba(0,0,0,0.07)'/%3E%3C/svg%3E");
                }
            </style>
        @endif
    </head>
    <body class="antialiased">
        <!-- Cabeçalho de autenticação -->
        <header class="w-full bg-white shadow dark:bg-gray-800">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16 items-center">
                    <div class="flex-shrink-0">
                        <a href="{{ url('/') }}" class="flex items-center">
                            <span class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">MedAgenda</span>
                        </a>
                    </div>
                    
                    <div class="hidden md:flex md:items-center md:space-x-6">
                        <nav class="flex space-x-8">
                            <a href="{{ route('about.index') }}" class="text-gray-500 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white px-3 py-2 text-sm font-medium">Sobre Nós</a>
                            <a href="{{ route('about.specialties') }}" class="text-gray-500 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white px-3 py-2 text-sm font-medium">Especialidades</a>
                            <a href="{{ route('patient.appointments.doctors') }}" class="text-gray-500 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white px-3 py-2 text-sm font-medium">Médicos</a>
                            <a href="{{ route('contact.location') }}" class="text-gray-500 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white px-3 py-2 text-sm font-medium">Contato</a>
                        </nav>
                        
                        @if (Route::has('login'))
                            <div class="flex items-center space-x-4">
                                @auth
                                    <a href="{{ url('/dashboard') }}" class="font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-500">Painel</a>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="font-medium text-gray-500 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white">
                                            Sair
                                        </button>
                                    </form>
                                @else
                                    <a href="{{ route('login') }}" class="font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-500">Entrar</a>
                                    @if (Route::has('register'))
                                        <a href="{{ route('register') }}" class="font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-500 ml-4">Cadastrar</a>
                                    @endif
                                @endauth
                            </div>
                        @endif
                    </div>
                    
                    <!-- Menu mobile -->
                    <div class="md:hidden">
                        <button type="button" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none" aria-expanded="false">
                            <span class="sr-only">Abrir menu</span>
                            <svg class="block h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main>
            <!-- Hero Section -->
            <div class="relative bg-gradient-to-r from-blue-500 to-indigo-600 px-4 py-16 sm:px-6 lg:px-8 lg:py-24 text-white">
                <div class="max-w-7xl mx-auto">
                    <div class="flex flex-col md:flex-row md:items-center">
                        <div class="md:w-1/2 mb-10 md:mb-0">
                            <h1 class="text-4xl font-extrabold tracking-tight sm:text-5xl lg:text-6xl">
                                Agende sua consulta online
                            </h1>
                            <p class="mt-6 text-xl max-w-3xl">
                                Cuidar da sua saúde nunca foi tão fácil. Escolha o médico, 
                                a data e o horário mais conveniente para você.
                            </p>
                            <div class="mt-10 max-w-sm sm:flex">
                                <div class="space-y-4 sm:space-y-0 sm:mx-auto">
                                    <a href="{{ route('patient.appointments.search') }}" class="w-full flex items-center justify-center px-8 py-3 border border-transparent text-base font-medium rounded-md text-indigo-700 bg-white hover:bg-gray-50 md:py-4 md:text-lg md:px-10">
                                        Agendar Consulta
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="md:w-1/2">
                            <img src="/images/hero-doctor.svg" alt="Médico com paciente" class="w-full h-auto">
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Especialidades -->
            <section class="py-12 bg-white">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="text-center">
                        <h2 class="text-3xl font-extrabold text-gray-900 sm:text-4xl">
                            Nossas Especialidades
                        </h2>
                        <p class="mt-3 max-w-2xl mx-auto text-xl text-gray-500 sm:mt-4">
                            Contamos com profissionais especializados em diversas áreas da medicina.
                        </p>
                    </div>
                    <div class="mt-10 grid grid-cols-1 gap-10 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach($specialties as $specialty)
                            <div class="group relative bg-white border border-gray-200 rounded-lg flex flex-col overflow-hidden hover:shadow-lg transition-shadow duration-300">
                                <div class="flex-1 p-6 flex flex-col justify-between">
                                    <div class="flex-1">
                                        <div class="flex justify-center">
                                            <span class="inline-flex items-center justify-center h-14 w-14 rounded-md bg-indigo-500 text-white mb-4">
                                                <i class="fas fa-{{ $specialty->icon ?? 'stethoscope' }} text-2xl"></i>
                                            </span>
                                        </div>
                                        <div class="text-center">
                                            <h3 class="text-xl font-semibold text-gray-900">
                                                {{ $specialty->name }}
                                            </h3>
                                            <p class="mt-3 text-base text-gray-500">
                                                {{ Str::limit($specialty->description, 100) }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="mt-6 text-center">
                                        <a href="{{ route('about.specialty', $specialty->slug) }}" class="text-indigo-600 hover:text-indigo-800">
                                            Saiba mais
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-10 text-center">
                        <a href="{{ route('about.specialties') }}" class="inline-flex items-center justify-center px-5 py-3 border border-transparent text-base font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                            Ver todas as especialidades
                        </a>
                    </div>
                </div>
            </section>
            
            <!-- Como funciona -->
            <section class="py-12 bg-gray-50">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="text-center">
                        <h2 class="text-3xl font-extrabold text-gray-900 sm:text-4xl">
                            Como funciona
                        </h2>
                        <p class="mt-3 max-w-2xl mx-auto text-xl text-gray-500 sm:mt-4">
                            Agendar sua consulta é simples e rápido
                        </p>
                    </div>
                    <div class="mt-10">
                        <div class="grid grid-cols-1 gap-8 md:grid-cols-3">
                            <div class="bg-white p-6 rounded-lg shadow text-center">
                                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-indigo-100 text-indigo-600 text-2xl font-bold">
                                    1
                                </div>
                                <h3 class="mt-6 text-xl font-semibold text-gray-900">Escolha a especialidade</h3>
                                <p class="mt-2 text-gray-500">Selecione a especialidade médica de acordo com sua necessidade</p>
                            </div>
                            <div class="bg-white p-6 rounded-lg shadow text-center">
                                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-indigo-100 text-indigo-600 text-2xl font-bold">
                                    2
                                </div>
                                <h3 class="mt-6 text-xl font-semibold text-gray-900">Encontre o médico</h3>
                                <p class="mt-2 text-gray-500">Veja as avaliações e escolha o profissional que melhor te atende</p>
                            </div>
                            <div class="bg-white p-6 rounded-lg shadow text-center">
                                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-indigo-100 text-indigo-600 text-2xl font-bold">
                                    3
                                </div>
                                <h3 class="mt-6 text-xl font-semibold text-gray-900">Agende sua consulta</h3>
                                <p class="mt-2 text-gray-500">Selecione data e horário conforme sua disponibilidade</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            
            <!-- Médicos em destaque -->
            <section class="py-12 bg-white">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="text-center">
                        <h2 class="text-3xl font-extrabold text-gray-900 sm:text-4xl">
                            Médicos em destaque
                        </h2>
                        <p class="mt-3 max-w-2xl mx-auto text-xl text-gray-500 sm:mt-4">
                            Conheça alguns dos nossos especialistas mais bem avaliados
                        </p>
                    </div>
                    <div class="mt-10 grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach($doctors as $doctor)
                            <div class="bg-white overflow-hidden shadow rounded-lg border">
                                <div class="px-4 py-5 sm:p-6">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-20 w-20">
                                            @if($doctor->user->photo)
                                                <img class="h-20 w-20 rounded-full object-cover" src="{{ asset('storage/' . $doctor->user->photo) }}" alt="{{ $doctor->user->name }}">
                                            @else
                                                <div class="h-20 w-20 rounded-full bg-indigo-100 flex items-center justify-center">
                                                    <span class="text-xl font-medium text-indigo-800">
                                                        {{ substr($doctor->user->name, 0, 2) }}
                                                    </span>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="ml-5">
                                            <h3 class="text-lg font-medium text-gray-900">Dr. {{ $doctor->user->name }}</h3>
                                            <div class="text-sm text-gray-500">
                                                {{ $doctor->specialties->pluck('name')->join(', ') }}
                                            </div>
                                            <div class="mt-1 flex items-center">
                                                @for($i = 1; $i <= 5; $i++)
                                                    @if($i <= round($doctor->feedbacks_avg_rating))
                                                        <i class="fas fa-star text-yellow-400"></i>
                                                    @else
                                                        <i class="far fa-star text-yellow-400"></i>
                                                    @endif
                                                @endfor
                                                <span class="ml-2 text-sm text-gray-500">
                                                    {{ number_format($doctor->feedbacks_avg_rating, 1) }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-5">
                                        <a href="{{ route('patient.appointments.doctor-profile', $doctor->id) }}" class="block w-full text-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                                            Ver perfil e agendar
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-10 text-center">
                        <a href="{{ route('patient.appointments.doctors') }}" class="inline-flex items-center justify-center px-5 py-3 border border-transparent text-base font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                            Ver todos os médicos
                        </a>
                    </div>
                </div>
            </section>
            
            <!-- CTA -->
            <section class="bg-indigo-700">
                <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:py-16 lg:px-8 lg:flex lg:items-center lg:justify-between">
                    <h2 class="text-3xl font-extrabold tracking-tight text-white sm:text-4xl">
                        <span class="block">Pronto para cuidar da sua saúde?</span>
                        <span class="block text-indigo-200">Agende sua consulta hoje mesmo.</span>
                    </h2>
                    <div class="mt-8 flex lg:mt-0 lg:flex-shrink-0">
                        <div class="inline-flex rounded-md shadow">
                            <a href="{{ route('patient.appointments.search') }}" class="inline-flex items-center justify-center px-5 py-3 border border-transparent text-base font-medium rounded-md text-indigo-600 bg-white hover:bg-indigo-50">
                                Agendar consulta
                            </a>
                        </div>
                        <div class="ml-3 inline-flex rounded-md shadow">
                            <a href="{{ route('about.index') }}" class="inline-flex items-center justify-center px-5 py-3 border border-transparent text-base font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                                Saiba mais
                            </a>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <!-- Footer -->
        <footer class="bg-gray-800 text-white">
            <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                    <div>
                        <h3 class="text-xl font-bold mb-4">MedAgenda</h3>
                        <p class="text-gray-300">
                            Sistema de agendamento médico online.
                            Facilitando o acesso à saúde de qualidade.
                        </p>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold mb-4">Links Rápidos</h3>
                        <ul class="space-y-2">
                            <li><a href="{{ route('about.index') }}" class="text-gray-300 hover:text-white">Sobre Nós</a></li>
                            <li><a href="{{ route('about.specialties') }}" class="text-gray-300 hover:text-white">Especialidades</a></li>
                            <li><a href="{{ route('patient.appointments.doctors') }}" class="text-gray-300 hover:text-white">Médicos</a></li>
                            <li><a href="{{ route('contact.index') }}" class="text-gray-300 hover:text-white">Contato</a></li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold mb-4">Pacientes</h3>
                        <ul class="space-y-2">
                            <li><a href="{{ route('patient.appointments.search') }}" class="text-gray-300 hover:text-white">Agendar Consulta</a></li>
                            <li><a href="{{ route('login') }}" class="text-gray-300 hover:text-white">Acessar Conta</a></li>
                            <li><a href="{{ route('register') }}" class="text-gray-300 hover:text-white">Criar Conta</a></li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold mb-4">Contato</h3>
                        @if(isset($clinic))
                            <ul class="space-y-2 text-gray-300">
                                <li class="flex items-center">
                                    <i class="fas fa-map-marker-alt w-5 mr-2"></i>
                                    {{ $clinic->address }}, {{ $clinic->city }}
                                </li>
                                <li class="flex items-center">
                                    <i class="fas fa-phone w-5 mr-2"></i>
                                    {{ $clinic->phone }}
                                </li>
                                <li class="flex items-center">
                                    <i class="fas fa-envelope w-5 mr-2"></i>
                                    {{ $clinic->email }}
                                </li>
                            </ul>
                        @endif
                        <div class="mt-4 flex space-x-4">
                            <a href="#" class="text-gray-300 hover:text-white">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                            <a href="#" class="text-gray-300 hover:text-white">
                                <i class="fab fa-instagram"></i>
                            </a>
                            <a href="#" class="text-gray-300 hover:text-white">
                                <i class="fab fa-twitter"></i>
                            </a>
                            <a href="#" class="text-gray-300 hover:text-white">
                                <i class="fab fa-linkedin-in"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="mt-8 pt-8 border-t border-gray-700">
                    <p class="text-center text-gray-300">
                        &copy; {{ date('Y') }} MedAgenda. Todos os direitos reservados.
                    </p>
                </div>
            </div>
        </footer>

        <!-- Swiper JS -->
        <script src="https://unpkg.com/swiper/swiper-bundle.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                new Swiper('.swiper-container', {
                    slidesPerView: 1,
                    spaceBetween: 30,
                    pagination: {
                        el: '.swiper-pagination',
                        clickable: true,
                    },
                    breakpoints: {
                        640: {
                            slidesPerView: 1,
                        },
                        768: {
                            slidesPerView: 2,
                            spaceBetween: 20,
                        },
                        1024: {
                            slidesPerView: 2,
                            spaceBetween: 30,
                        },
                    }
                });
            });
        </script>
    </body>
</html>