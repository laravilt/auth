<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('Two-Factor Authentication') }} - {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="h-full bg-gradient-to-br from-slate-50 to-slate-100 dark:from-slate-900 dark:to-slate-800">
    <div class="min-h-full flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <!-- Logo/Brand -->
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-blue-100 dark:bg-blue-900/30">
                    <svg class="h-10 w-10 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
                <h2 class="mt-6 text-3xl font-bold text-slate-900 dark:text-white">
                    {{ __('Two-Factor Authentication') }}
                </h2>
                <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">
                    {{ __('Please enter your authentication code to continue') }}
                </p>
            </div>

            <!-- Error Messages -->
            @if (session('error'))
                <div class="rounded-lg bg-red-50 dark:bg-red-900/20 p-4 border border-red-200 dark:border-red-800">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-red-800 dark:text-red-200">{{ session('error') }}</p>
                        </div>
                    </div>
                </div>
            @endif

            @error('code')
                <div class="rounded-lg bg-red-50 dark:bg-red-900/20 p-4 border border-red-200 dark:border-red-800">
                    <p class="text-sm text-red-800 dark:text-red-200">{{ $message }}</p>
                </div>
            @enderror

            @error('recovery_code')
                <div class="rounded-lg bg-red-50 dark:bg-red-900/20 p-4 border border-red-200 dark:border-red-800">
                    <p class="text-sm text-red-800 dark:text-red-200">{{ $message }}</p>
                </div>
            @enderror

            <!-- 2FA Card -->
            <div class="bg-white dark:bg-slate-800 shadow-xl rounded-2xl overflow-hidden" x-data="{
                useRecoveryCode: false,
                loading: false,
                code: '',
                handleInput(index) {
                    const inputs = Array.from(document.querySelectorAll('.code-input'));
                    const input = inputs[index];

                    if (input.value.length === 1 && index < inputs.length - 1) {
                        inputs[index + 1].focus();
                    }

                    // Build the complete code
                    this.code = inputs.map(i => i.value).join('');
                },
                handleKeydown(index, event) {
                    const inputs = Array.from(document.querySelectorAll('.code-input'));

                    if (event.key === 'Backspace' && !inputs[index].value && index > 0) {
                        inputs[index - 1].focus();
                    }
                },
                handlePaste(event) {
                    event.preventDefault();
                    const paste = (event.clipboardData || window.clipboardData).getData('text');
                    const inputs = Array.from(document.querySelectorAll('.code-input'));

                    for (let i = 0; i < Math.min(paste.length, inputs.length); i++) {
                        inputs[i].value = paste[i];
                    }

                    this.code = inputs.map(i => i.value).join('');

                    if (paste.length >= inputs.length) {
                        inputs[inputs.length - 1].focus();
                    } else {
                        inputs[paste.length].focus();
                    }
                }
            }">
                <!-- Method Toggle -->
                <div class="border-b border-slate-200 dark:border-slate-700 p-4 bg-slate-50 dark:bg-slate-700/50">
                    <button
                        type="button"
                        @click="useRecoveryCode = !useRecoveryCode"
                        class="text-sm text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300 transition-colors flex items-center"
                    >
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                        </svg>
                        <span x-show="!useRecoveryCode">{{ __('Use a recovery code instead') }}</span>
                        <span x-show="useRecoveryCode" x-cloak>{{ __('Use authentication code instead') }}</span>
                    </button>
                </div>

                <div class="p-8">
                    <!-- Authentication Code Form -->
                    <form method="POST" action="{{ route('laravilt.auth.two-factor.verify') }}" x-show="!useRecoveryCode" @submit="loading = true">
                        @csrf
                        <input type="hidden" name="code" x-model="code">

                        <div class="space-y-6">
                            <!-- Info Message -->
                            <div class="text-center">
                                <p class="text-sm text-slate-600 dark:text-slate-400">
                                    {{ __('Open your two-factor authenticator app to view your authentication code.') }}
                                </p>
                            </div>

                            <!-- Code Input Grid -->
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 text-center mb-4">
                                    {{ __('Authentication Code') }}
                                </label>
                                <div class="flex gap-2 justify-center">
                                    @for($i = 0; $i < 6; $i++)
                                        <input
                                            type="text"
                                            inputmode="numeric"
                                            maxlength="1"
                                            class="code-input w-12 h-14 text-center text-2xl font-bold border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-slate-700 dark:text-white transition-colors"
                                            @input="handleInput({{ $i }})"
                                            @keydown="handleKeydown({{ $i }}, $event)"
                                            @paste="handlePaste($event)"
                                            {{ $i === 0 ? 'autofocus' : '' }}
                                        >
                                    @endfor
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="mt-8">
                                <button
                                    type="submit"
                                    :disabled="loading || code.length < 6"
                                    class="w-full flex justify-center items-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors dark:focus:ring-offset-slate-800"
                                >
                                    <span x-show="!loading">
                                        <svg class="w-5 h-5 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        {{ __('Verify') }}
                                    </span>
                                    <span x-show="loading" class="flex items-center" x-cloak>
                                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        {{ __('Verifying...') }}
                                    </span>
                                </button>
                            </div>
                        </div>
                    </form>

                    <!-- Recovery Code Form -->
                    <form method="POST" action="{{ route('laravilt.auth.two-factor.verify') }}" x-show="useRecoveryCode" x-cloak @submit="loading = true">
                        @csrf

                        <div class="space-y-6">
                            <!-- Info Message -->
                            <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm text-yellow-800 dark:text-yellow-200">
                                            {{ __('Recovery codes can only be used once. Make sure to generate new codes after using one.') }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Recovery Code Input -->
                            <div>
                                <label for="recovery_code" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                                    {{ __('Recovery Code') }}
                                </label>
                                <div class="mt-1 relative">
                                    <input
                                        id="recovery_code"
                                        name="recovery_code"
                                        type="text"
                                        autocomplete="off"
                                        value="{{ old('recovery_code') }}"
                                        required
                                        autofocus
                                        class="appearance-none block w-full px-3 py-2.5 border border-slate-300 dark:border-slate-600 rounded-lg shadow-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-slate-700 dark:text-white transition-colors font-mono"
                                        placeholder="{{ __('xxxx-xxxx-xxxx') }}"
                                    >
                                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                        <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                                        </svg>
                                    </div>
                                </div>
                                <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                                    {{ __('Enter one of your emergency recovery codes') }}
                                </p>
                            </div>

                            <!-- Submit Button -->
                            <div class="mt-8">
                                <button
                                    type="submit"
                                    :disabled="loading"
                                    class="w-full flex justify-center items-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors dark:focus:ring-offset-slate-800"
                                >
                                    <span x-show="!loading">
                                        <svg class="w-5 h-5 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        {{ __('Verify Recovery Code') }}
                                    </span>
                                    <span x-show="loading" class="flex items-center" x-cloak>
                                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        {{ __('Verifying...') }}
                                    </span>
                                </button>
                            </div>
                        </div>
                    </form>

                    <!-- Help Section -->
                    <div class="mt-6 pt-6 border-t border-slate-200 dark:border-slate-700">
                        <div class="flex items-start">
                            <svg class="h-5 w-5 text-slate-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div class="ml-3">
                                <p class="text-sm text-slate-600 dark:text-slate-400">
                                    {{ __('Having trouble? Contact your administrator or use a recovery code to regain access to your account.') }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Logout Button -->
            <div>
                <form method="POST" action="{{ route('laravilt.auth.logout') }}">
                    @csrf
                    <button
                        type="submit"
                        class="w-full flex justify-center items-center py-2.5 px-4 text-sm font-medium text-slate-600 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200 transition-colors"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        {{ __('Cancel and logout') }}
                    </button>
                </form>
            </div>

            <!-- Security Notice -->
            <div class="text-center">
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    {{ __('Your account is protected by two-factor authentication') }}
                </p>
            </div>
        </div>
    </div>

    <style>
        [x-cloak] { display: none !important; }

        /* Remove number input arrows */
        input[type="text"].code-input::-webkit-outer-spin-button,
        input[type="text"].code-input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        input[type="text"].code-input {
            -moz-appearance: textfield;
        }
    </style>
</body>
</html>
