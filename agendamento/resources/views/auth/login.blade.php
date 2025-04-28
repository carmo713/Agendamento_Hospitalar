<x-guest-layout>
    <div class="min-h-screen bg-gradient-to-br from-blue-600 to-indigo-700 flex items-center justify-center p-6 relative overflow-hidden">
        <!-- Decorative circles -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute w-96 h-96 bg-blue-400 rounded-full -top-10 -left-16 opacity-10 blur-3xl"></div>
            <div class="absolute w-96 h-96 bg-indigo-400 rounded-full -bottom-10 -right-16 opacity-10 blur-3xl"></div>
        </div>

        <div class="w-full max-w-md">
            <!-- Logo Section -->
            <div class="text-center mb-8">
                <h1 class="text-4xl font-bold text-white mb-2 flex items-center justify-center">
                    <span class="bg-white p-2 rounded-lg mr-3">
                        👨‍⚕️
                    </span>
                    MedAgenda
                </h1>
                <p class="text-blue-100 text-lg">Acesse sua conta</p>
            </div>

            <!-- Card Container -->
            <div class="bg-white/95 backdrop-blur-sm rounded-2xl shadow-xl p-8">
                <!-- Session Status -->
                <x-auth-session-status class="mb-4" :status="session('status')" />

                <form method="POST" action="{{ route('login') }}" class="space-y-6">
                    @csrf

                    <!-- Email Address -->
                    <div>
                        <x-input-label for="email" :value="__('Email')" class="text-gray-700 font-semibold" />
                        <div class="mt-2">
                            <x-text-input 
                                id="email" 
                                type="email" 
                                name="email" 
                                :value="old('email')" 
                                required 
                                autofocus 
                                autocomplete="username"
                                class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-blue-500 transition duration-200"
                                placeholder="seu@email.com"
                            />
                        </div>
                        <x-input-error :messages="$errors->get('email')" class="mt-2 text-sm text-red-600" />
                    </div>

                    <!-- Password -->
                    <div>
                        <div class="flex items-center justify-between">
                            <x-input-label for="password" :value="__('Senha')" class="text-gray-700 font-semibold" />
                            @if (Route::has('password.request'))
                                <a class="text-sm text-blue-600 hover:text-blue-800 transition duration-200" href="{{ route('password.request') }}">
                                    {{ __('Esqueceu a senha?') }}
                                </a>
                            @endif
                        </div>
                        <div class="mt-2">
                            <x-text-input 
                                id="password"
                                type="password"
                                name="password"
                                required
                                autocomplete="current-password"
                                class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-blue-500 transition duration-200"
                                placeholder="••••••••"
                            />
                        </div>
                        <x-input-error :messages="$errors->get('password')" class="mt-2 text-sm text-red-600" />
                    </div>

                    <!-- Remember Me -->
                    <div class="flex items-center">
                        <input 
                            id="remember_me" 
                            type="checkbox"
                            name="remember"
                            class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                        >
                        <label for="remember_me" class="ml-2 block text-sm text-gray-700">
                            {{ __('Lembrar de mim') }}
                        </label>
                    </div>

                    <div>
                        <button type="submit" class="w-full bg-gradient-to-r from-blue-600 to-indigo-700 text-white font-semibold py-3 px-4 rounded-lg hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transform transition duration-200 hover:-translate-y-0.5">
                            {{ __('Entrar') }}
                        </button>
                    </div>
                </form>

                <!-- Registration Link -->
                <div class="mt-6 text-center">
                    <p class="text-sm text-gray-600">
                        Não tem uma conta?
                        <a href="{{ route('register') }}" class="font-semibold text-blue-600 hover:text-blue-800 transition duration-200">
                            Cadastre-se
                        </a>
                    </p>
                </div>
            </div>

            <!-- Back to Home -->
            <div class="mt-8 text-center">
                <a href="/" class="text-blue-100 hover:text-white transition duration-200">
                    ← Voltar para a página inicial
                </a>
            </div>
        </div>
    </div>
</x-guest-layout>