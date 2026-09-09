<x-guest-layout>
    <div x-data="loginPage()" @keydown.escape.window="forgotOpen = false">

        {{-- Success toast --}}
        @if (session('status'))
            <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 6000)" x-show="show" x-cloak
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
                 class="fixed top-4 left-4 right-4 sm:left-auto sm:right-6 z-[70] max-w-sm mx-auto sm:mx-0" role="status">
                <div class="flex items-start gap-3 rounded-xl bg-white border border-green-200 shadow-lg shadow-green-900/5 p-4">
                    <svg class="w-5 h-5 text-green-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="flex-1 text-sm font-medium text-green-800">{{ session('status') }}</p>
                    <button type="button" @click="show = false" aria-label="Tutup notifikasi"
                            class="text-green-400 hover:text-green-600 transition-colors">&times;</button>
                </div>
            </div>
        @endif

        {{-- Error toast --}}
        @if ($errors->any())
            <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 6000)" x-show="show" x-cloak
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
                 class="fixed top-4 left-4 right-4 sm:left-auto sm:right-6 z-[70] max-w-sm mx-auto sm:mx-0" role="alert">
                <div class="flex items-start gap-3 rounded-xl bg-white border border-red-200 shadow-lg shadow-red-900/5 p-4">
                    <svg class="w-5 h-5 text-red-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="flex-1 text-sm font-medium text-red-800">{{ $errors->first('username') ?? 'Username atau password salah. Silakan periksa kembali.' }}</p>
                    <button type="button" @click="show = false" aria-label="Tutup notifikasi"
                            class="text-red-400 hover:text-red-600 transition-colors">&times;</button>
                </div>
            </div>
        @endif

        {{-- Login card --}}
        <div class="login-card relative bg-white rounded-[24px] border border-gray-900/5 shadow-[0_1px_2px_rgba(15,45,60,0.05),0_10px_30px_-10px_rgba(15,45,60,0.12),0_36px_80px_-20px_rgba(15,45,60,0.22)] p-6 sm:p-8 lg:p-[52px] max-w-[500px] overflow-hidden animate-fade-up" style="animation-delay:150ms">
            <div class="absolute inset-x-0 top-0 h-[3px] bg-gradient-to-r from-[#054152] via-[#0a6077] to-[#0DA4CE]"></div>
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_50%_-8%,rgba(13,164,206,0.08),transparent_55%)] pointer-events-none"></div>
            <div class="flex flex-col items-center text-center">
                <div class="relative">
                    <div class="absolute -inset-5 rounded-full bg-[#0DA4CE]/10 blur-2xl"></div>
                    <img src="{{ asset('assets/images/logo-isa-smartwork.png') }}" alt="Logo Isa SmartWork" class="relative h-20 sm:h-24 w-auto object-contain drop-shadow-sm">
                </div>
                <p class="mt-5 text-xl sm:text-2xl font-extrabold text-gray-900 tracking-tight">Isa SmartWork</p>
                <p class="mt-2 text-xs text-gray-500 tracking-wide">PT ISA TRI SELARAS GEMILANG</p>
            </div>

            <form method="POST" action="{{ route('login') }}" @submit.prevent="handleSubmit" class="login-form-compact mt-8 space-y-6">
                @csrf

                {{-- Username --}}
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-2.5">Username</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </div>
                        <input id="username" name="username" type="text" value="{{ old('username') }}" required autofocus
                               autocomplete="username" placeholder="Masukkan username"
                               class="block w-full pl-12 pr-4 h-[52px] lg:h-[56px] border border-gray-200 rounded-[14px] text-base text-gray-900 placeholder:text-gray-400 bg-gray-50/60 hover:bg-gray-50 hover:border-gray-300 focus:bg-white focus:border-[#0DA4CE] focus:ring-4 focus:ring-[#0DA4CE]/10 transition-all duration-200 outline-none @error('username') border-red-300 focus:border-red-400 focus:ring-red-400/10 @enderror">
                    </div>
                    @error('username')
                        <p class="mt-2 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Password --}}
                <div>
                    <div class="flex items-center justify-between mb-2.5">
                        <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                        <button type="button" @click="forgotOpen = true"
                                class="text-sm font-semibold text-[#0DA4CE] hover:text-[#097A99] hover:underline underline-offset-4 rounded focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-[#0DA4CE]/20 transition-colors">Lupa Password?</button>
                    </div>
                    <div class="relative" x-data="{ show: false }">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>
                        <input id="password" x-bind:type="show ? 'text' : 'password'" name="password" required
                               autocomplete="current-password" placeholder="Masukkan password"
                               class="block w-full pl-12 pr-12 h-[52px] lg:h-[56px] border border-gray-200 rounded-[14px] text-base text-gray-900 placeholder:text-gray-400 bg-gray-50/60 hover:bg-gray-50 hover:border-gray-300 focus:bg-white focus:border-[#0DA4CE] focus:ring-4 focus:ring-[#0DA4CE]/10 transition-all duration-200 outline-none @error('password') border-red-300 focus:border-red-400 focus:ring-red-400/10 @enderror">
                        <button type="button" @click="show = !show" tabindex="-1"
                                :aria-label="show ? 'Sembunyikan password' : 'Tampilkan password'"
                                :aria-pressed="show ? 'true' : 'false'"
                                class="absolute inset-y-0 right-0 flex items-center pr-4 text-gray-400 hover:text-gray-600 transition-colors">
                            <svg x-show="!show" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            <svg x-show="show" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/>
                            </svg>
                        </button>
                    </div>
                    @error('password')
                        <p class="mt-2 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Remember --}}
                <label for="remember_me" class="flex items-center gap-2.5 cursor-pointer select-none group">
                    <input id="remember_me" name="remember" type="checkbox" value="1"
                           class="peer checkbox-input sr-only">
                    <span class="checkbox-box w-5 h-5 rounded-md border border-gray-300 bg-white shadow-sm flex items-center justify-center transition-colors duration-200 group-hover:border-gray-400 peer-focus-visible:ring-4 peer-focus-visible:ring-[#0DA4CE]/20 peer-focus-visible:ring-offset-1">
                        <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                        </svg>
                    </span>
                    <span class="text-sm text-gray-600">Ingat saya</span>
                </label>

                {{-- Submit --}}
                <button type="submit" :disabled="loading"
                        class="relative w-full inline-flex items-center justify-center h-[52px] lg:h-[56px] px-6 bg-[#0DA4CE] hover:bg-[#0b8fb5] active:bg-[#097A99] text-white rounded-[14px] font-semibold text-[16px] shadow-lg shadow-[#0DA4CE]/25 hover:shadow-xl hover:shadow-[#0DA4CE]/35 hover:-translate-y-0.5 active:scale-[0.99] focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-[#0DA4CE]/30 disabled:opacity-70 disabled:cursor-not-allowed transition-all duration-200">
                    <span x-show="!loading" class="flex items-center">
                        Masuk
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                        </svg>
                    </span>
                    <span x-show="loading" class="flex items-center" x-cloak>
                        <svg class="w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        Memproses...
                    </span>
                </button>
            </form>
        </div>

        {{-- Forgot password modal --}}
        <div x-cloak x-show="forgotOpen" x-transition.opacity
             class="fixed inset-0 z-[80] flex items-end sm:items-center justify-center p-4 sm:p-6 bg-gray-900/50 backdrop-blur-sm overflow-y-auto overscroll-contain"
             role="dialog" aria-modal="true" aria-labelledby="forgot-title" aria-describedby="forgot-description">
            <div @click.away="forgotOpen = false" x-show="forgotOpen"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="w-full max-w-md bg-white rounded-2xl shadow-2xl border border-gray-100 my-auto overflow-hidden">
                <div class="p-6 sm:p-7">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <span class="w-10 h-10 rounded-xl bg-[#0DA4CE]/10 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5 text-[#0DA4CE]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                                </svg>
                            </span>
                            <h3 id="forgot-title" class="text-lg font-bold text-gray-900 tracking-tight">Atur Ulang Password</h3>
                        </div>
                        <button type="button" @click="forgotOpen = false; forgotError = ''" aria-label="Tutup"
                                class="w-9 h-9 flex items-center justify-center rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-50 transition-colors">&times;</button>
                    </div>

                    <p id="forgot-description" class="mt-3 text-sm text-gray-500 leading-relaxed">
                        Masukkan username Anda untuk menerima tautan pengaturan ulang password melalui email yang terdaftar.
                    </p>

                    <form method="POST" action="{{ route('password.email') }}" @submit.prevent="submitForgot" class="mt-6 space-y-5">
                        @csrf

                        <div>
                            <label for="forgot_username" class="block text-sm font-medium text-gray-700 mb-2">Username</label>
                            <input id="forgot_username" x-ref="forgotUsername" name="username" type="text"
                                   value="{{ old('username') }}" autocomplete="username" placeholder="Masukkan username"
                                   x-init="$watch('forgotOpen', (open) => { if (open) $nextTick(() => $refs.forgotUsername.focus()) })"
                                   class="block w-full px-4 h-[52px] border border-gray-200 rounded-[14px] text-base text-gray-900 placeholder:text-gray-400 bg-gray-50/60 hover:bg-gray-50 hover:border-gray-300 focus:bg-white focus:border-[#0DA4CE] focus:ring-4 focus:ring-[#0DA4CE]/10 transition-all duration-200 outline-none @error('username') border-red-300 focus:border-red-400 focus:ring-red-400/10 @enderror">
                            <p x-show="forgotError" x-cloak class="mt-2 text-xs text-red-500" x-text="forgotError"></p>
                        </div>

                        <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2.5">
                            <button type="button" @click="forgotOpen = false; forgotError = ''"
                                    class="w-full sm:w-auto h-[48px] px-5 inline-flex items-center justify-center rounded-[14px] border border-gray-200 bg-white text-sm font-semibold text-gray-700 hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-gray-900/5 transition-colors">Batal</button>
                            <button type="submit" :disabled="forgotLoading"
                                    class="w-full sm:w-auto h-[48px] px-5 inline-flex items-center justify-center rounded-[14px] bg-[#0DA4CE] text-sm font-semibold text-white shadow-md shadow-[#0DA4CE]/25 hover:bg-[#0b8fb5] focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-[#0DA4CE]/30 disabled:opacity-70 disabled:cursor-not-allowed transition-all duration-200">
                                <span x-show="!forgotLoading">Kirim Tautan</span>
                                <span x-show="forgotLoading" class="flex items-center" x-cloak>
                                    <svg class="w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                    Memproses...
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function loginPage() {
            return {
                loading: false,
                forgotOpen: false,
                forgotLoading: false,
                forgotError: '',
                handleSubmit(e) {
                    this.loading = true;
                    e.target.submit();
                },
                submitForgot(e) {
                    const username = (this.$refs.forgotUsername.value || '').trim();
                    if (!username) {
                        this.forgotError = 'Masukkan username terlebih dahulu.';
                        return;
                    }
                    this.forgotError = '';
                    this.forgotLoading = true;
                    e.target.submit();
                }
            };
        }
    </script>
</x-guest-layout>
