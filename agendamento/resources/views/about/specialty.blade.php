@extends('layouts.app')

@section('title', $specialty->name . ' - Especialidades')

@section('content')
    <!-- Hero Section -->
    <div class="bg-indigo-800 text-white py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <div class="flex justify-center mb-6">
                    <span class="inline-flex items-center justify-center h-24 w-24 rounded-full bg-indigo-100 text-indigo-600">
                        <i class="fas fa-{{ $specialty->icon ?? 'stethoscope' }} text-4xl"></i>
                    </span>
                </div>
                <h1 class="text-4xl font-extrabold tracking-tight sm:text-5xl">
                    {{ $specialty->name }}
                </h1>
                <p class="mt-6 text-xl max-w-3xl mx-auto">
                    Conheça mais sobre esta especialidade e nossos profissionais.
                </p>
            </div>
        </div>
    </div>

    <!-- Sobre a especialidade -->
    <section class="py-12 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="prose prose-indigo prose-lg mx-auto">
                <h2>O que é {{ $specialty->name }}?</h2>
                
                <p>{{ $specialty->description }}</p>
                
                <h3>Quando procurar esta especialidade?</h3>
                
                @if($specialty->when_to_seek)
                    <p>{{ $specialty->when_to_seek }}</p>
                @else
                    <p>É recomendado buscar atendimento nesta especialidade quando você apresentar sintomas específicos ou precisar de diagnóstico, tratamento ou acompanhamento relacionado à área de atuação destes profissionais. Consulte um de nossos especialistas para mais informações.</p>
                @endif
                
                <h3>Principais tratamentos</h3>
                
                @if($specialty->treatments)
                    <p>{{ $specialty->treatments }}</p>
                @else
                    <ul>
                        <li>Diagnóstico e avaliação especializada</li>
                        <li>Tratamentos específicos para cada condição</li>
                        <li>Acompanhamento continuado</li>
                        <li>Prevenção de complicações</li>
                    </ul>
                @endif
            </div>
        </div>
    </section>
    
    <!-- Médicos desta especialidade -->
    <section class="py-12 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h2 class="text-3xl font-extrabold text-gray-900">
                    Nossos Especialistas em {{ $specialty->name }}
                </h2>
                <p class="mt-3 max-w-2xl mx-auto text-xl text-gray-500">
                    Conheça os profissionais qualificados disponíveis para atendimento
                </p>
            </div>
            
            <div class="mt-10">
                @if($specialty->doctors->isEmpty())
                    <div class="text-center py-8">
                        <p class="text-lg text-gray-500">No momento, não há médicos desta especialidade cadastrados em nossa plataforma.</p>
                    </div>
                @else
                    <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach($specialty->doctors as $doctor)
                            <div class="bg-white overflow-hidden shadow rounded-lg border">
                                <div class="px-4 py-5 sm:p-6">
                                    <div class="flex flex-col items-center text-center">
                                        <div class="flex-shrink-0 h-32 w-32 mb-4">
                                            @if($doctor->user->photo)
                                                <img class="h-32 w-32 rounded-full object-cover" src="{{ asset('storage/' . $doctor->user->photo) }}" alt="{{ $doctor->user->name }}">
                                            @else
                                                <div class="h-32 w-32 rounded-full bg-indigo-100 flex items-center justify-center">
                                                    <span class="text-3xl font-medium text-indigo-800">
                                                        {{ substr($doctor->user->name, 0, 2) }}
                                                    </span>
                                                </div>
                                            @endif
                                        </div>
                                        
                                        <h3 class="text-xl font-medium text-gray-900">Dr. {{ $doctor->user->name }}</h3>
                                        
                                        @if($doctor->bio)
                                            <p class="mt-3 text-sm text-gray-500 line-clamp-3">
                                                {{ Str::limit($doctor->bio, 120) }}
                                            </p>
                                        @endif
                                        
                                        @if($doctor->feedbacks_count > 0)
                                            <div class="mt-3 flex items-center">
                                                @for($i = 1; $i <= 5; $i++)
                                                    @if($i <= round($doctor->feedbacks_avg_rating))
                                                        <i class="fas fa-star text-yellow-400"></i>
                                                    @else
                                                        <i class="far fa-star text-yellow-400"></i>
                                                    @endif
                                                @endfor
                                                <span class="ml-2 text-sm text-gray-500">
                                                    {{ number_format($doctor->feedbacks_avg_rating, 1) }} ({{ $doctor->feedbacks_count }})
                                                </span>
                                            </div>
                                        @endif
                                        
                                        <div class="mt-5 w-full">
                                            <a href="{{ route('patient.appointments.doctor-profile', $doctor->id) }}" class="block w-full text-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                                                Ver perfil e agendar
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </section>
    
    <!-- CTA -->
    <section class="bg-indigo-700">
        <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:py-16 lg:px-8 lg:flex lg:items-center lg:justify-between">
            <h2 class="text-3xl font-extrabold tracking-tight text-white sm:text-4xl">
                <span class="block">Precisa de atendimento em {{ $specialty->name }}?</span>
                <span class="block text-indigo-200">Agende sua consulta agora mesmo.</span>
            </h2>
            <div class="mt-8 flex lg:mt-0 lg:flex-shrink-0">
                <div class="inline-flex rounded-md shadow">
                    <a href="{{ route('patient.appointments.search', ['specialty' => $specialty->id]) }}" class="inline-flex items-center justify-center px-5 py-3 border border-transparent text-base font-medium rounded-md text-indigo-600 bg-white hover:bg-indigo-50">
                        Agendar consulta
                    </a>
                </div>
            </div>
        </div>
    </section>
@endsection