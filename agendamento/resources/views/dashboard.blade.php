<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            @auth
            @if(auth()->user()->hasRole('admin'))
                <a href="/admin">Admin Dashboard</a>
            @endif
            
            @if(auth()->user()->hasRole('doctor'))
                <a href="/doctor">Doctor Dashboard</a>
            @endif
        
            @if(auth()->user()->hasRole('patient'))
                <a href="/patient">Patient Dashboard</a>
            @endif
        @endauth
        </h2>
    </x-slot>

   
</x-app-layout>
