
@extends('layouts.app')

@section('title', 'Sobre - Sistema de Agendamento Médico')

@section('content')
    <!-- Hero Section -->
    <div class="bg-indigo-800 text-white py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h1 class="text-4xl font-extrabold tracking-tight sm:text-5xl lg:text-6xl">
                    Sobre Nós
                </h1>
                <p class="mt-6 text-xl max-w-3xl mx-auto">
                    Conheça nossa história e missão de proporcionar saúde e bem-estar
                    através de atendimento médico de qualidade.
                </p>
            </div>
        </div>
    </div>

    <!-- Sobre a Clínica -->
    <section class="py-12 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="lg:grid lg:grid-cols-2 lg:gap-8 items-center">
                <div>
                    <h2 class="text-3xl font-extrabold text-gray-900 sm:text-4xl">
                        Nossa História
                    </h2>
                    <p class="mt-4 text-lg text-gray-500">
                        {{ $clinic->description ?? 'Fundada com o compromisso de oferecer atendimento médico de excelência, nossa clínica vem crescendo e se desenvolvendo para atender às necessidades dos nossos pacientes com o mais alto padrão de qualidade.' }}
                    </p>
                    
                    <div class="mt-8">
                        <div class="flex items-center">
                            <h3 class="text-lg font-medium text-gray-900">Missão</h3>
                            <div class="ml-4 flex-shrink-0">
                                <span class="inline-flex items-center px-3 py-0.5 rounded-full text-sm font-medium bg-indigo-100 text-indigo-800">
                                    <i class="fas fa-heart mr-1"></i> Cuidar
                                </span>
                            </div>
                        </div>
                        <p class="mt-2 text-base text-gray-500">
                            Proporcionar serviços de saúde humanizados, com excelência técnica e atendimento acolhedor, valorizando o bem-estar dos pacientes.
                        </p>
                    </div>

                    <div class="mt-8">
                        <div class="flex items-center">
                            <h3 class="text-lg font-medium text-gray-900">Visão</h3>
                            <div class="ml-4 flex-shrink-0">
                                <span class="inline-flex items-center px-3 py-0.5 rounded-full text-sm font-medium bg-indigo-100 text-indigo-800">
                                    <i class="fas fa-eye mr-1"></i> Futuro
                                </span>
                            </div>
                        </div>
                        <p class="mt-2 text-base text-gray-500">
                            Ser reconhecida como referência em atendimento médico, combinando tecnologia avançada e cuidado humanizado.
                        </p>
                    </div>

                    <div class="mt-8">
                        <div class="flex items-center">
                            <h3 class="text-lg font-medium text-gray-900">Valores</h3>
                            <div class="ml-4 flex-shrink-0">
                                <span class="inline-flex items-center px-3 py-0.5 rounded-full text-sm font-medium bg-indigo-100 text-indigo-800">
                                    <i class="fas fa-star mr-1"></i> Princípios
                                </span>
                            </div>
                        </div>
                        <ul class="mt-2 text-base text-gray-500 space-y-2">
                            <li class="flex items-start">
                                <i class="fas fa-check-circle text-indigo-500 mt-1 mr-2"></i>
                                <span>Ética e compromisso com a saúde do paciente</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle text-indigo-500 mt-1 mr-2"></i>
                                <span>Acolhimento e humanização no atendimento</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle text-indigo-500 mt-1 mr-2"></i>
                                <span>Excelência técnica e atualização constante</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle text-indigo-500 mt-1 mr-2"></i>
                                <span>Respeito à diversidade e inclusão</span>
                            </li>
                        </ul>
                    </div>
                </div>
                
                <div class="mt-12 lg:mt-0">
                    <div class="bg-white rounded-lg shadow-xl overflow-hidden">
                        <img src="/images/clinic-photo.jpg" alt="Nossa clínica" class="w-full object-cover h-64">
                        <div class="p-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-lg font-bold text-gray-900">Números</h3>
                                    <p class="text-sm text-gray-500">Nosso compromisso com a saúde</p>
                                </div>
                                <span class="inline-flex items-center px-3 py-0.5 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                    <i class="fas fa-hospital mr-1"></i> Desde {{ $clinic->founded_at ?? '2010' }}
                                </span>
                            </div>
                            
                            <div class="mt-6 grid grid-cols-2 gap-6">
                                <div class="border-r border-gray-200 pr-4">
                                    <div class="text-4xl font-extrabold text-indigo-600">{{ $doctorsCount }}</div>
                                    <div class="mt-1 text-base font-medium text-gray-500">Médicos</div>
                                </div>
                                <div>
                                    <div class="text-4xl font-extrabold text-indigo-600">{{ $specialtiesCount }}</div>
                                    <div class="mt-1 text-base font-medium text-gray-500">Especialidades</div>
                                </div>
                                <div class="border-r border-gray-200 pr-4">
                                    <div class="text-4xl font-extrabold text-indigo-600">+10k</div>
                                    <div class="mt-1 text-base font-medium text-gray-500">Pacientes atendidos</div>
                                </div>
                                <div>
                                    <div class="text-4xl font-extrabold text-indigo-600">97%</div>
                                    <div class="mt-1 text-base font-medium text-gray-500">Satisfação</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Equipe -->
    <section class="py-12 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h2 class="text-3xl font-extrabold text-gray-900 sm:text-4xl">
                    Nossa Equipe
                </h2>
                <p class="mt-3 max-w-2xl mx-auto text-xl text-gray-500 sm:mt-4">
                    Profissionais qualificados e comprometidos com sua saúde
                </p>
            </div>
            
            <div class="mt-10 flex justify-center">
                <a href="{{ route('about.team') }}" class="inline-flex items-center justify-center px-5 py-3 border border-transparent text-base font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                    Conheça nossa equipe completa
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
                    <a href="{{ route('contact.index') }}" class="inline-flex items-center justify-center px-5 py-3 border border-transparent text-base font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                        Contato
                    </a>
                </div>
            </div>
        </div>
    </section>
@endsection