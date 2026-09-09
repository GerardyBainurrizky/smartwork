<x-guest-layout>
    <div class="hidden lg:block mb-10">
        <h2 class="text-2xl font-bold text-gray-900">Lupa Password?</h2>
        <p class="text-sm text-gray-500 mt-1">Tenang, kami akan bantu Anda mengatur ulang password.</p>
    </div>

    <div class="mb-6 rounded-2xl bg-blue-50 border border-blue-200 p-4">
        <div class="flex items-start gap-3">
            <svg class="w-5 h-5 text-blue-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="text-sm text-blue-800">Masukkan nama pengguna Anda. Jika akun tersebut terdaftar, tautan untuk mengatur ulang password akan dikirim ke email yang terdaftar.</p>
        </div>
    </div>

    {{-- Session Status --}}
    @if (session('status'))
    <div class="mb-6 rounded-2xl bg-green-50 border border-green-200 p-4" x-data="{show:true}" x-init="setTimeout(() => show = false, 5000)" x-show="show" x-transition>
        <div class="flex items-start gap-3">
            <svg class="w-5 h-5 text-green-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div class="flex-1">
                <p class="text-sm font-medium text-green-800">{{ session('status') }}</p>
            </div>
            <button type="button" @click="show=false" class="text-green-400 hover:text-green-600">&times;</button>
        </div>
    </div>
    @endif

    {{-- Error Alert --}}
    @if ($errors->any())
    <div class="mb-6 rounded-2xl bg-red-50 border border-red-200 p-4" x-data="{show:true}" x-init="setTimeout(() => show = false, 5000)" x-show="show" x-transition>
        <div class="flex items-start gap-3">
            <svg class="w-5 h-5 text-red-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div class="flex-1">
                <p class="text-sm font-medium text-red-800">{{ $errors->first('username') ?? 'Terjadi kesalahan. Silakan coba lagi.' }}</p>
            </div>
            <button type="button" @click="show=false" class="text-red-400 hover:text-red-600">&times;</button>
        </div>
    </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" x-data="{ loading: false }" @submit="loading = true">
        @csrf

        <div class="mb-5">
            <label for="username" class="block text-sm font-medium text-gray-700 mb-2">Nama Pengguna</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </div>
                <input id="username" name="username" type="text" value="{{ old('username') }}" required autofocus autocomplete="username"
                    placeholder="Masukkan nama pengguna"
                    class="block w-full pl-12 pr-4 py-3.5 border border-gray-200 rounded-2xl text-sm text-gray-900 placeholder:text-gray-400 bg-gray-50/50 focus:bg-white focus:border-[#0DA4CE] focus:ring-4 focus:ring-[#0DA4CE]/10 transition-all duration-200 outline-none">
            </div>
            @error('username')<p class="mt-2 text-xs text-red-500">{{ $message }}</p>@enderror
        </div>

        <button type="submit" :disabled="loading"
            class="relative w-full inline-flex items-center justify-center px-6 py-3.5 bg-gradient-to-r from-[#097A99] to-[#0DA4CE] text-white rounded-2xl font-semibold text-sm shadow-lg shadow-[#0DA4CE]/25 hover:shadow-xl hover:shadow-[#0DA4CE]/30 transition-all duration-200 disabled:opacity-70">
            <span x-show="!loading" class="flex items-center">
                Kirim Tautan Reset Password
                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
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