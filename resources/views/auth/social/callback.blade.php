<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('Processing...') }} - {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="h-full bg-gradient-to-br from-slate-50 to-slate-100 dark:from-slate-900 dark:to-slate-800">
    <div class="min-h-full flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">

            @if(session('error'))
                <!-- Error State -->
                <div class="text-center">
                    <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100 dark:bg-red-900/30">
                        <svg class="h-10 w-10 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h2 class="mt-6 text-3xl font-bold text-slate-900 dark:text-white">
                        {{ __('Authentication Failed') }}
                    </h2>
                    <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">
                        {{ __('We encountered an error during the authentication process') }}
                    </p>
                </div>

                <!-- Error Message Card -->
                <div class="bg-white dark:bg-slate-800 shadow-xl rounded-2xl overflow-hidden p-8">
                    <div class="rounded-lg bg-red-50 dark:bg-red-900/20 p-4 border border-red-200 dark:border-red-800">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-red-800 dark:text-red-200">
                                    {{ session('error') }}
                                </h3>
                                @if(session('error_description'))
                                    <div class="mt-2 text-sm text-red-700 dark:text-red-300">
                                        <p>{{ session('error_description') }}</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Suggestions -->
                    <div class="mt-6">
                        <h4 class="text-sm font-medium text-slate-900 dark:text-white mb-3">
                            {{ __('What you can do:') }}
                        </h4>
                        <ul class="space-y-2 text-sm text-slate-600 dark:text-slate-400">
                            <li class="flex items-start">
                                <svg class="h-5 w-5 text-blue-500 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                                {{ __('Try signing in again') }}
                            </li>
                            <li class="flex items-start">
                                <svg class="h-5 w-5 text-blue-500 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                                {{ __('Make sure you granted all required permissions') }}
                            </li>
                            <li class="flex items-start">
                                <svg class="h-5 w-5 text-blue-500 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                                {{ __('Use a different authentication method') }}
                            </li>
                            <li class="flex items-start">
                                <svg class="h-5 w-5 text-blue-500 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                                {{ __('Contact support if the problem persists') }}
                            </li>
                        </ul>
                    </div>

                    <!-- Action Buttons -->
                    <div class="mt-8 space-y-3">
                        <a
                            href="{{ route('laravilt.auth.login') }}"
                            class="w-full flex justify-center items-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors dark:focus:ring-offset-slate-800"
                        >
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                            </svg>
                            {{ __('Back to Login') }}
                        </a>

                        <a
                            href="{{ route('laravilt.auth.register') }}"
                            class="w-full flex justify-center items-center py-3 px-4 border border-slate-300 dark:border-slate-600 rounded-lg shadow-sm text-sm font-medium text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-700 hover:bg-slate-50 dark:hover:bg-slate-600 transition-colors"
                        >
                            {{ __('Create New Account') }}
                        </a>
                    </div>
                </div>

            @elseif(session('success'))
                <!-- Success State -->
                <div class="text-center">
                    <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 dark:bg-green-900/30">
                        <svg class="h-10 w-10 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h2 class="mt-6 text-3xl font-bold text-slate-900 dark:text-white">
                        {{ __('Authentication Successful!') }}
                    </h2>
                    <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">
                        {{ __('You will be redirected shortly...') }}
                    </p>
                </div>

                <!-- Success Message Card -->
                <div class="bg-white dark:bg-slate-800 shadow-xl rounded-2xl overflow-hidden p-8">
                    <div class="rounded-lg bg-green-50 dark:bg-green-900/20 p-4 border border-green-200 dark:border-green-800">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-green-800 dark:text-green-200">
                                    {{ session('success') }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 text-center">
                        <div class="inline-flex items-center px-4 py-2 bg-slate-100 dark:bg-slate-700 rounded-lg">
                            <svg class="animate-spin h-5 w-5 text-blue-600 dark:text-blue-400 mr-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span class="text-sm text-slate-700 dark:text-slate-300">{{ __('Redirecting...') }}</span>
                        </div>
                    </div>
                </div>

                <!-- Auto redirect script -->
                <script>
                    setTimeout(function() {
                        window.location.href = "{{ session('redirect_to', route('dashboard')) }}";
                    }, 2000);
                </script>

            @else
                <!-- Processing State (Default) -->
                <div class="text-center" x-data="{
                    dots: 3,
                    init() {
                        setInterval(() => {
                            this.dots = this.dots >= 3 ? 1 : this.dots + 1;
                        }, 500);
                    }
                }">
                    <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-blue-100 dark:bg-blue-900/30">
                        <svg class="h-10 w-10 text-blue-600 dark:text-blue-400 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    <h2 class="mt-6 text-3xl font-bold text-slate-900 dark:text-white">
                        {{ __('Processing Authentication') }}<span x-text="'.'.repeat(dots)"></span>
                    </h2>
                    <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">
                        {{ __('Please wait while we verify your credentials') }}
                    </p>
                </div>

                <!-- Processing Card -->
                <div class="bg-white dark:bg-slate-800 shadow-xl rounded-2xl overflow-hidden p-8">
                    <div class="space-y-6">
                        <!-- Provider Info -->
                        @if(isset($provider))
                            <div class="text-center">
                                <p class="text-sm text-slate-600 dark:text-slate-400">
                                    {{ __('Authenticating with') }}
                                </p>
                                <p class="mt-2 text-lg font-semibold text-slate-900 dark:text-white capitalize">
                                    {{ $provider }}
                                </p>
                            </div>
                        @endif

                        <!-- Loading Spinner -->
                        <div class="flex justify-center">
                            <svg class="animate-spin h-12 w-12 text-blue-600 dark:text-blue-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>

                        <!-- Progress Steps -->
                        <div class="space-y-3" x-data="{ step: 1 }" x-init="
                            setInterval(() => {
                                if (step < 3) step++;
                            }, 1500);
                        ">
                            <div class="flex items-center text-sm">
                                <div :class="step >= 1 ? 'bg-blue-600' : 'bg-slate-300 dark:bg-slate-600'" class="flex-shrink-0 w-6 h-6 rounded-full flex items-center justify-center transition-colors">
                                    <svg x-show="step >= 1" class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </div>
                                <span :class="step >= 1 ? 'text-slate-900 dark:text-white' : 'text-slate-500 dark:text-slate-400'" class="ml-3 transition-colors">
                                    {{ __('Connecting to authentication provider') }}
                                </span>
                            </div>
                            <div class="flex items-center text-sm">
                                <div :class="step >= 2 ? 'bg-blue-600' : 'bg-slate-300 dark:bg-slate-600'" class="flex-shrink-0 w-6 h-6 rounded-full flex items-center justify-center transition-colors">
                                    <svg x-show="step >= 2" class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </div>
                                <span :class="step >= 2 ? 'text-slate-900 dark:text-white' : 'text-slate-500 dark:text-slate-400'" class="ml-3 transition-colors">
                                    {{ __('Verifying your identity') }}
                                </span>
                            </div>
                            <div class="flex items-center text-sm">
                                <div :class="step >= 3 ? 'bg-blue-600' : 'bg-slate-300 dark:bg-slate-600'" class="flex-shrink-0 w-6 h-6 rounded-full flex items-center justify-center transition-colors">
                                    <svg x-show="step >= 3" class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-cloak>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </div>
                                <span :class="step >= 3 ? 'text-slate-900 dark:text-white' : 'text-slate-500 dark:text-slate-400'" class="ml-3 transition-colors">
                                    {{ __('Setting up your account') }}
                                </span>
                            </div>
                        </div>

                        <!-- Security Notice -->
                        <div class="pt-6 border-t border-slate-200 dark:border-slate-700">
                            <div class="flex items-start">
                                <svg class="h-5 w-5 text-slate-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                                <div class="ml-3">
                                    <p class="text-xs text-slate-600 dark:text-slate-400">
                                        {{ __('Your connection is secure and encrypted. We never store your social login credentials.') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cancel Option -->
                <div class="text-center">
                    <a href="{{ route('laravilt.auth.login') }}" class="text-sm text-slate-600 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200 transition-colors">
                        {{ __('Cancel and return to login') }}
                    </a>
                </div>
            @endif

        </div>
    </div>

    <style>
        [x-cloak] { display: none !important; }
    </style>
</body>
</html>
