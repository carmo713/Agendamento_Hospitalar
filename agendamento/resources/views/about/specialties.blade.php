@extends('layouts.app')

@section('title', 'Especialidades - Sistema de Agendamento Médico')

@section('content')
    <!-- Hero Section -->
    <div class="bg-indigo-800 text-white py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h1 class="text-4xl font-extrabold tracking-tight sm:text-5xl">
                    Nossas Especialidades
                </h1>
                <p class="mt-6 text-xl max-w-3xl mx-auto">
                    Conheça todas as áreas médicas disponíveis para agendamento em nossa clínica.
                </p>
            </div>
        </div>
    </div>

    <!-- Lista de especialidades -->
    <section class="py-12 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @if($specialties->isEmpty())
                <div class="text-center py-12">
                    <p class="text-xl text-gray-500">No momento, não há especialidades cadastradas em nossa plataforma.</p>
                </div>
            @else
                <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($specialties as $specialty)
                        <div class="bg-white overflow-hidden shadow rounded-lg border group hover:shadow-lg transition-shadow duration-300">
                            <div class="px-4 py-5 sm:p-6">
                                <div class="flex flex-col items-center text-center">
                                    <div class="flex-shrink-0 mb-4">
                                        <span class="inline-flex items-center justify-center h-20 w-20 rounded-md bg-indigo-500 text-white group-hover:bg-indigo-600 transition-colors duration-300">
                                            <i class="fas fa-{{ $specialty->icon ?? 'stethoscope' }} text-3xl"></i>
                                        </span>
                                    </div>
                                    
                                    <h3 class="text-xl font-medium text-gray-900 group-hover:text-indigo-600 transition-colors duration-300">
                                        {{ $specialty->name }}
                                    </h3>
                                    
                                    @if($specialty->doctors_count > 0)
                                        <div class="mt-1 text-sm text-gray-500">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                {{ $specialty->doctors_count }} {{ Str::plural('médico', $specialty->doctors_count) }}
                                            </span>
                                        </div>
                                    @endif
                                    
                                    <p class="mt-3 text-sm text-gray-500">
                                        {{ Str::limit($specialty->description, 150) }}
                                    </p>
                                    
                                    <div class="mt-5 w-full">
                                        <a href="{{ route('about.specialty', $specialty->slug) }}" class="block w-full text-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                                            Ver detalhes
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
    
    <!-- Benefícios -->
    <section class="py-12 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h2 class="text-3xl font-extrabold text-gray-900">
                    Por que escolher nossa clínica?
                </h2>
                <p class="mt-3 max-w-2xl mx-auto text-xl text-gray-500">
                    Oferecemos uma experiência completa para cuidar da sua saúde
                </p>
            </div>
            
            <div class="mt-10 grid grid-cols-1 gap-8 md:grid-cols-3">
                <div class="bg-white p-6 rounded-lg shadow">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <span class="inline-flex items-center justify-center h-12 w-12 rounded-md bg-indigo-100 text-indigo-600">
                                <i class="fas fa-user-md text-xl"></i>
                            </span>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-medium text-gray-900">Especialistas qualificados</h3>
                        </div>
                    </div>
                    <div class="mt-4">
                        <p class="text-gray-500">
                            Nossa equipe é formada por profissionais com vasta experiência e constante atualização.
                        </p>
                    </div>
                </div>
                
                <div class="bg-white p-6 rounded-lg shadow">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <span class="inline-flex items-center justify-center h-12 w-12 rounded-md bg-indigo-100 text-indigo-600">
                                <i class="fas fa-calendar-check text-xl"></i>
                            </span>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-medium text-gray-900">Agendamento flexível</h3>
                        </div>
                    </div>
                    <div class="mt-4">
                        <p class="text-gray-500">
                            Marque consultas conforme sua disponibilidade, com horários amplos e convenientes.
                        </p>
                    </div>
                </div>
                
                <div class="bg-white p-6 rounded-lg shadow">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <span class="inline-flex items-center justify-center h-12 w-12 rounded-md bg-indigo-100 text-indigo-600">
                                <i class="fas fa-laptop-medical text-xl"></i>
                            </span>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-medium text-gray-900">Tecnologia avançada</h3>
                        </div>
                    </div>
                    <div class="mt-4">
                        <p class="text-gray-500">
                            Utilizamos equipamentos modernos e sistemas digitais para garantir diagnósticos precisos.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- CTA -->
    <section class="bg-indigo-700">
        <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:py-16 lg:px-8 lg:flex lg:items-center lg:justify-between">
            <h2 class="text-3xl font-extrabold tracking-tight text-white sm:text-4xl">
                <span class="block">Pronto para agendar sua consulta?</span>
                <span class="block text-indigo-200">Escolha a especialidade e o profissional.</span>
            </h2>
            <div class="mt-8 flex lg:mt-0 lg:flex-shrink-0">
                <div class="inline-flex rounded-md shadow">
                    <a href="{{ route('patient.appointments.search') }}" class="inline-flex items-center justify-center px-5 py-3 border border-transparent text-base font-medium rounded-md text-indigo-600 bg-white hover:bg-indigo-50">
                        Agendar agora
                    </a>
                </div>
            </div>
        </div>
    </section>
@endsection