
@extends('layouts.app')

@section('title', 'Localização - Sistema de Agendamento Médico')

@section('content')
    <!-- Hero Section -->
    <div class="bg-indigo-800 text-white py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h1 class="text-4xl font-extrabold tracking-tight sm:text-5xl">
                    Onde Estamos
                </h1>
                <p class="mt-6 text-xl max-w-3xl mx-auto">
                    Visite nossas instalações e conheça nossa estrutura.
                </p>
            </div>
        </div>
    </div>

    <!-- Mapa e informações -->
    <section class="py-12 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @if($clinics->isEmpty())
                <div class="text-center py-8">
                    <p class="text-lg text-gray-500">No momento, não há informações de localização disponíveis.</p>
                </div>
            @else
                @foreach($clinics as $clinic)
                    <div class="mb-16 last:mb-0">
                        <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-6">
                            <div class="px-4 py-5 sm:px-6 flex justify-between items-center">
                                <div>
                                    <h2 class="text-2xl font-bold text-gray-900">
                                        {{ $clinic->name }}
                                    </h2>
                                    <p class="mt-1 max-w-2xl text-sm text-gray-500">
                                        {{ $clinic->city }} - {{ $clinic->state }}
                                    </p>
                                </div>
                                <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($clinic->address . ', ' . $clinic->city . ' - ' . $clinic->state) }}" target="_blank" class="inline-flex items-center px-3 py-1 border border-transparent text-sm font-medium rounded-md text-indigo-700 bg-indigo-100 hover:bg-indigo-200">
                                    <i class="fas fa-external-link-alt mr-2"></i>
                                    Abrir no Google Maps
                                </a>
                            </div>
                            <div class="border-t border-gray-200">
                                <div class="h-96 w-full bg-gray-300">
                                    <!-- Aqui você inseriria o mapa usando Google Maps, Leaflet ou outra biblioteca -->
                                    <div class="w-full h-full flex items-center justify-center">
                                        <span class="text-gray-500">Mapa da localização</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                            <div class="px-4 py-5 sm:px-6">
                                <h3 class="text-lg leading-6 font-medium text-gray-900">
                                    Informações de localização
                                </h3>
                            </div>
                            <div class="border-t border-gray-200 px-4 py-5 sm:p-6">
                                <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                                    <div class="sm:col-span-1">
                                        <dt class="text-sm font-medium text-gray-500">Endereço</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ $clinic->address }}</dd>
                                    </div>
                                    <div class="sm:col-span-1">
                                        <dt class="text-sm font-medium text-gray-500">Complemento</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ $clinic->address_complement ?? 'N/A' }}</dd>
                                    </div>
                                    <div class="sm:col-span-1">
                                        <dt class="text-sm font-medium text-gray-500">Cidade/Estado</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ $clinic->city }} - {{ $clinic->state }}</dd>
                                    </div>
                                    <div class="sm:col-span-1">
                                        <dt class="text-sm font-medium text-gray-500">CEP</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ $clinic->zip_code }}</dd>
                                    </div>
                                    <div class="sm:col-span-1">
                                        <dt class="text-sm font-medium text-gray-500">Telefone</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ $clinic->phone }}</dd>
                                    </div>
                                    <div class="sm:col-span-1">
                                        <dt class="text-sm font-medium text-gray-500">Email</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ $clinic->email }}</dd>
                                    </div>
                                    @if($clinic->working_hours)
                                    <div class="sm:col-span-2">
                                        <dt class="text-sm font-medium text-gray-500">Horário de funcionamento</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ $clinic->working_hours }}</dd>
                                    </div>
                                    @endif
                                </dl>
                            </div>
                            <div class="border-t border-gray-200 px-4 py-5 sm:px-6">
                                <div class="flex space-x-3">
                                    <a href="{{ route('contact.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                        <i class="fas fa-envelope mr-2"></i>
                                        Enviar mensagem
                                    </a>
                                    <a href="tel:{{ preg_replace('/\D/', '', $clinic->phone) }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                                        <i class="fas fa-phone-alt mr-2"></i>
                                        Ligar
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </section>
    
    <!-- Como chegar -->
    @if($clinics->isNotEmpty())
    <section class="py-12 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h2 class="text-3xl font-extrabold text-gray-900">
                    Como Chegar
                </h2>
                <p class="mt-3 max-w-2xl mx-auto text-xl text-gray-500">
                    Veja as melhores formas de transporte para nos visitar
                </p>
            </div>
            
            <div class="mt-10 grid grid-cols-1 gap-8 md:grid-cols-3">
                <div class="bg-white p-6 rounded-lg shadow">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <span class="inline-flex items-center justify-center h-12 w-12 rounded-md bg-indigo-100 text-indigo-600">
                                <i class="fas fa-car text-xl"></i>
                            </span>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-medium text-gray-900">Carro</h3>
                        </div>
                    </div>
                    <div class="mt-4">
                        <p class="text-gray-500">
                            Temos estacionamento próprio com manobrista para sua comodidade. Também há estacionamentos pagos nas proximidades.
                        </p>
                    </div>
                </div>
                
                <div class="bg-white p-6 rounded-lg shadow">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <span class="inline-flex items-center justify-center h-12 w-12 rounded-md bg-indigo-100 text-indigo-600">
                                <i class="fas fa-bus text-xl"></i>
                            </span>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-medium text-gray-900">Transporte público</h3>
                        </div>@extends('layouts.app')

@section('title', 'Localização - Sistema de Agendamento Médico')

@section('content')
    <!-- Hero Section -->
    <div class="bg-indigo-800 text-white py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h1 class="text-4xl font-extrabold tracking-tight sm:text-5xl">
                    Onde Estamos
                </h1>
                <p class="mt-6 text-xl max-w-3xl mx-auto">
                    Visite nossas instalações e conheça nossa estrutura.
                </p>
            </div>
        </div>
    </div>

    <!-- Mapa e informações -->
    <section class="py-12 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @if($clinics->isEmpty())
                <div class="text-center py-8">
                    <p class="text-lg text-gray-500">No momento, não há informações de localização disponíveis.</p>
                </div>
            @else
                @foreach($clinics as $clinic)
                    <div class="mb-16 last:mb-0">
                        <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-6">
                            <div class="px-4 py-5 sm:px-6 flex justify-between items-center">
                                <div>
                                    <h2 class="text-2xl font-bold text-gray-900">
                                        {{ $clinic->name }}
                                    </h2>
                                    <p class="mt-1 max-w-2xl text-sm text-gray-500">
                                        {{ $clinic->city }} - {{ $clinic->state }}
                                    </p>
                                </div>
                                <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($clinic->address . ', ' . $clinic->city . ' - ' . $clinic->state) }}" target="_blank" class="inline-flex items-center px-3 py-1 border border-transparent text-sm font-medium rounded-md text-indigo-700 bg-indigo-100 hover:bg-indigo-200">
                                    <i class="fas fa-external-link-alt mr-2"></i>
                                    Abrir no Google Maps
                                </a>
                            </div>
                            <div class="border-t border-gray-200">
                                <div class="h-96 w-full bg-gray-300">
                                    <!-- Aqui você inseriria o mapa usando Google Maps, Leaflet ou outra biblioteca -->
                                    <div class="w-full h-full flex items-center justify-center">
                                        <span class="text-gray-500">Mapa da localização</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                            <div class="px-4 py-5 sm:px-6">
                                <h3 class="text-lg leading-6 font-medium text-gray-900">
                                    Informações de localização
                                </h3>
                            </div>
                            <div class="border-t border-gray-200 px-4 py-5 sm:p-6">
                                <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                                    <div class="sm:col-span-1">
                                        <dt class="text-sm font-medium text-gray-500">Endereço</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ $clinic->address }}</dd>
                                    </div>
                                    <div class="sm:col-span-1">
                                        <dt class="text-sm font-medium text-gray-500">Complemento</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ $clinic->address_complement ?? 'N/A' }}</dd>
                                    </div>
                                    <div class="sm:col-span-1">
                                        <dt class="text-sm font-medium text-gray-500">Cidade/Estado</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ $clinic->city }} - {{ $clinic->state }}</dd>
                                    </div>
                                    <div class="sm:col-span-1">
                                        <dt class="text-sm font-medium text-gray-500">CEP</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ $clinic->zip_code }}</dd>
                                    </div>
                                    <div class="sm:col-span-1">
                                        <dt class="text-sm font-medium text-gray-500">Telefone</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ $clinic->phone }}</dd>
                                    </div>
                                    <div class="sm:col-span-1">
                                        <dt class="text-sm font-medium text-gray-500">Email</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ $clinic->email }}</dd>
                                    </div>
                                    @if($clinic->working_hours)
                                    <div class="sm:col-span-2">
                                        <dt class="text-sm font-medium text-gray-500">Horário de funcionamento</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ $clinic->working_hours }}</dd>
                                    </div>
                                    @endif
                                </dl>
                            </div>
                            <div class="border-t border-gray-200 px-4 py-5 sm:px-6">
                                <div class="flex space-x-3">
                                    <a href="{{ route('contact.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                        <i class="fas fa-envelope mr-2"></i>
                                        Enviar mensagem
                                    </a>
                                    <a href="tel:{{ preg_replace('/\D/', '', $clinic->phone) }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                                        <i class="fas fa-phone-alt mr-2"></i>
                                        Ligar
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </section>
    
    <!-- Como chegar -->
    @if($clinics->isNotEmpty())
    <section class="py-12 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h2 class="text-3xl font-extrabold text-gray-900">
                    Como Chegar
                </h2>
                <p class="mt-3 max-w-2xl mx-auto text-xl text-gray-500">
                    Veja as melhores formas de transporte para nos visitar
                </p>
            </div>
            
            <div class="mt-10 grid grid-cols-1 gap-8 md:grid-cols-3">
                <div class="bg-white p-6 rounded-lg shadow">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <span class="inline-flex items-center justify-center h-12 w-12 rounded-md bg-indigo-100 text-indigo-600">
                                <i class="fas fa-car text-xl"></i>
                            </span>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-medium text-gray-900">Carro</h3>
                        </div>
                    </div>
                    <div class="mt-4">
                        <p class="text-gray-500">
                            Temos estacionamento próprio com manobrista para sua comodidade. Também há estacionamentos pagos nas proximidades.
                        </p>
                    </div>
                </div>
                
                <div class="bg-white p-6 rounded-lg shadow">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <span class="inline-flex items-center justify-center h-12 w-12 rounded-md bg-indigo-100 text-indigo-600">
                                <i class="fas fa-bus text-xl"></i>
                            </span>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-medium text-gray-900">Transporte público</h3>
                        </div>