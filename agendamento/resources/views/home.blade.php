@extends('layouts.app')

@section('title', 'Sistema de Agendamento Médico - Sua saúde em boas mãos')

@section('content')
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

    <!-- Depoimentos -->
    @if($testimonials->count() > 0)
    <section class="py-12 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h2 class="text-3xl font-extrabold text-gray-900 sm:text-4xl">
                    O que nossos pacientes dizem
                </h2>
                <p class="mt-3 max-w-2xl mx-auto text-xl text-gray-500 sm:mt-4">
                    A opinião de quem já utilizou nossos serviços
                </p>
            </div>

            <div class="mt-10">
                <div class="max-w-4xl mx-auto">
                    <div class="swiper-container">
                        <div class="swiper-wrapper">
                            @foreach($testimonials as $testimonial)
                                <div class="swiper-slide px-4">
                                    <div class="bg-white rounded-lg shadow-lg p-6">
                                        <div class="flex items-center mb-4">
                                            <div class="flex-shrink-0 h-12 w-12">
                                                @if($testimonial->patient->user->photo)
                                                    <img class="h-12 w-12 rounded-full object-cover" src="{{ asset('storage/' . $testimonial->patient->user->photo) }}" alt="{{ $testimonial->patient->user->name }}">
                                                @else
                                                    <div class="h-12 w-12 rounded-full bg-indigo-100 flex items-center justify-center">
                                                        <span class="text-sm font-medium text-indigo-800">
                                                            {{ substr($testimonial->patient->user->name, 0, 2) }}
                                                        </span>
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="ml-4">
                                                <h4 class="text-lg font-semibold">{{ $testimonial->patient->user->name }}</h4>
                                                <div class="flex items-center">
                                                    @for($i = 1; $i <= 5; $i++)
                                                        @if($i <= $testimonial->rating)
                                                            <i class="fas fa-star text-yellow-400"></i>
                                                        @else
                                                            <i class="far fa-star text-yellow-400"></i>
                                                        @endif
                                                    @endfor
                                                </div>
                                            </div>
                                        </div>
                                        <p class="text-gray-600">
                                            "{{ $testimonial->comments }}"
                                        </p>
                                        <div class="mt-4 text-sm text-gray-500">
                                            Atendimento com Dr. {{ $testimonial->doctor->user->name }} - {{ $testimonial->created_at->format('d/m/Y') }}
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="swiper-pagination mt-6"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    @endif

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

    @if($clinic)
    <!-- Informações da Clínica -->
    <section class="py-12 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h2 class="text-3xl font-extrabold text-gray-900 sm:text-4xl">
                    Onde estamos
                </h2>
                <p class="mt-3 max-w-2xl mx-auto text-xl text-gray-500 sm:mt-4">
                    Visite nossa clínica
                </p>
            </div>

            <div class="mt-10 bg-gray-50 rounded-lg shadow overflow-hidden sm:grid sm:grid-cols-2">
                <div class="px-4 py-5 sm:px-6 flex flex-col justify-center">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">
                        {{ $clinic->name }}
                    </h3>
                    <div class="mt-4 space-y-3">
                        <p class="text-gray-500 flex items-center">
                            <i class="fas fa-map-marker-alt w-5 mr-2 text-indigo-500"></i>
                            {{ $clinic->address }}, {{ $clinic->city }} - {{ $clinic->state }}
                        </p>
                        <p class="text-gray-500 flex items-center">
                            <i class="fas fa-phone w-5 mr-2 text-indigo-500"></i>
                            {{ $clinic->phone }}
                        </p>
                        <p class="text-gray-500 flex items-center">
                            <i class="fas fa-envelope w-5 mr-2 text-indigo-500"></i>
                            {{ $clinic->email }}
                        </p>
                    </div>
                    <div class="mt-6">
                        <a href="{{ route('contact.location') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700">
                            Ver no mapa
                        </a>
                    </div>
                </div>
                <div class="px-4 py-5 sm:p-6">
                    <div class="h-full min-h-[250px] bg-gray-300">
                        <!-- Aqui entraria um mapa ou imagem da clínica -->
                        <div class="w-full h-full flex items-center justify-center">
                            <span class="text-gray-500">Mapa da localização</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    @endif
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        new Swiper('.swiper-container', {
            slidesPerView: 1,
            spaceBetween: 30,
            pagination: {
                el: '.swiper-pagination',
                clickable: true,
            },
            autoplay: {
                delay: 5000,
            },
            breakpoints: {
                640: {
                    slidesPerView: 1,
                },
                768: {
                    slidesPerView: 1,
                },
            }
        });
    });
</script>
@endpush