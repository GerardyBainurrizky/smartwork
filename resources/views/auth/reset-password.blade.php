<x-guest-layout>
    <div class="hidden lg:block mb-10">
        <h2 class="text-2xl font-bold text-gray-900">Atur Ulang Password</h2>
        <p class="text-sm text-gray-500 mt-1">Masukkan password baru untuk akun Anda.</p>
    </div>

    {{-- Error Alert --}}
    @if ($errors->any())
    <div class="mb-6 rounded-2xl bg-red-50 border border-red-200 p-4" x-data="{show:true}" x-init="setTimeout(() => show = false, 5000)" x-show="show" x-transition>
        <div class="flex items-start gap-3">
            <svg class="w-5 h-5 text-red-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div class="flex-1">
                <p class="text-sm font-medium text-red-800">{{ $errors->first('email') ?? 'Terjadi kesalahan. Silakan coba lagi.' }}</p>
            </div>
            <button type="button" @click="show=false" class="text-red-400 hover:text-red-600">&times;</button>
        </div>
    </div>
    @endif

    <form method="POST" action="{{ route('password.store') }}" x-data="{ showPassword: false, showConfirm: false, loading: false }" @submit="loading = true">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        {{-- Email --}}
        <div class="mb-5">
            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
                <input
                    id="email"
                    class="block w-full pl-12 pr-4 py-3.5 border border-gray-200 rounded-2xl text-sm text-gray-900 placeholder:text-gray-400 bg-gray-50/50 focus:bg-white focus:border-[#0DA4CE] focus:ring-4 focus:ring-[#0DA4CE]/10 transition-all duration-200 outline-none"
                    type="email"
                    name="email"
                    value="{{ old('email', $request->email ?? '') }}"
                    placeholder="Masukkan email yang terdaftar"
                    required
                    autofocus
                    autocomplete="username"
                >
            </div>
            @error('email')<p class="mt-2 text-xs text-red-500">{{ $message }}</p>@enderror
        </div>

        {{-- New Password --}}
        <div class="mb-5">
            <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Password Baru</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                    </svg>
                </div>
                <input
                    id="password"
                    class="block w-full pl-12 pr-12 py-3.5 border border-gray-200 rounded-2xl text-sm text-gray-900 placeholder:text-gray-400 bg-gray-50/50 focus:bg-white focus:border-[#0DA4CE] focus:ring-4 focus:ring-[#0DA4CE]/10 transition-all duration-200 outline-none"
                    x-bind:type="showPassword ? 'text' : 'password'"
                    name="password"
                    placeholder="Masukkan password baru"
                    required
                    autocomplete="new-password"
                >
                <button
                    type="button"
                    @click="showPassword = !showPassword"
                    class="absolute inset-y-0 right-0 flex items-center pr-4 text-gray-400 hover:text-gray-600 transition"
                    tabindex="-1"
                >
                    <svg x-show="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    <svg x-show="showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                    </svg>
                </button>
            </div>
            @error('password')<p class="mt-2 text-xs text-red-500">{{ $message }}</p>@enderror
        </div>

        {{-- Confirm Password --}}
        <div class="mb-6">
            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">Konfirmasi Password</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                    </svg>
                </div>
                <input
                    id="password_confirmation"
                    class="block w-full pl-12 pr-12 py-3.5 border border-gray-200 rounded-2xl text-sm text-gray-900 placeholder:text-gray-400 bg-gray-50/50 focus:bg-white focus:border-[#0DA4CE] focus:ring-4 focus:ring-[#0DA4CE]/10 transition-all duration-200 outline-none"
                    x-bind:type="showConfirm ? 'text' : 'password'"
                    name="password_confirmation"
                    placeholder="Ulangi password baru"
                    required
                    autocomplete="new-password"
                >
                <button
                    type="button"
                    @click="showConfirm = !showConfirm"
                    class="absolute inset-y-0 right-0 flex items-center pr-4 text-gray-400 hover:text-gray-600 transition"
                    tabindex="-1"
                >
                    <svg x-show="!showConfirm" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    <svg x-show="showConfirm" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                    </svg>
                </button>
            </div>
            @error('password_confirmation')<p class="mt-2 text-xs text-red-500">{{ $message }}</p>@enderror
        </div>

        <button
            type="submit"
            :disabled="loading"
            class="relative w-full inline-flex items-center justify-center px-6 py-3.5 bg-gradient-to-r from-[#097A99] to-[#0DA4CE] text-white rounded-2xl font-semibold text-sm shadow-lg shadow-[#0DA4CE]/25 hover:shadow-xl hover:shadow-[#0DA4CE]/30 focus:outline-none focus:ring-4 focus:ring-[#0DA4CE]/20 disabled:opacity-70 disabled:cursor-not-allowed transition-all duration-200"
        >
            <span x-show="!loading" class="flex items-center">
                Simpan Password Baru
                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </span>
            <span x-show="loading" class="flex items-center">
                <svg class="w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                Memproses...
            </span>
        </button>

        <div class="mt-6 text-center">
            <a href="{{ route('login') }}" class="text-sm font-medium text-[#0DA4CE] hover:text-[#097A99] transition">
                &larr; Kembali ke halaman masuk
            </a>
        </div>
    </form>
</x-guest-layout>