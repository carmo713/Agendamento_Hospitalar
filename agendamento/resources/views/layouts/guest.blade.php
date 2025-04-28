<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'MedAgenda') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- Additional Styles -->
        <style>
            body {
                font-family: 'Plus Jakarta Sans', sans-serif;
            }
        </style>
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen flex flex-col">
            <!-- Background with gradient -->
            <div class="fixed inset-0 bg-gradient-to-br from-blue-600 to-indigo-700 pointer-events-none"></div>

            <!-- Content -->
            <main class="flex-grow flex items-center justify-center relative z-10 p-6">
                <!-- Decorative Elements -->
                <div class="absolute inset-0 overflow-hidden pointer-events-none">
                    <div class="absolute w-96 h-96 bg-blue-400 rounded-full -top-10 -left-16 opacity-10 blur-3xl"></div>
                    <div class="absolute w-96 h-96 bg-indigo-400 rounded-full -bottom-10 -right-16 opacity-10 blur-3xl"></div>
                </div>

                <!-- Logo and Content Container -->
                <div class="w-full max-w-md space-y-8">
                    <!-- Logo Section -->
                    <div class="text-center">
                        <a href="/" class="inline-block">
                        </a>
                    </div>

                    <!-- Main Content -->
                    {{ $slot }}
                </div>
            </main>

            <!-- Footer -->
            <footer class="relative z-10 text-center p-6">
                <p class="text-white/70 text-sm">
                    &copy; {{ date('Y') }} MedAgenda. Todos os direitos reservados.
                </p>
            </footer>
        </div>
    </body>
</html>