<x-app-layout>
    <x-slot name="header">
        <x-page-header icon="user" title="Profil Saya" subtitle="Kelola informasi akun dan password Anda"></x-page-header>
    </x-slot>

    <div class="max-w-2xl mx-auto space-y-6">

        {{-- Status Messages --}}
        @if (session('status') === 'profile-updated')
        <div class="rounded-2xl bg-green-50 dark:bg-green-950 border border-green-200 dark:border-green-800 p-4 text-sm text-green-800 dark:text-green-200" x-data="{show:true}" x-show="show" x-transition>
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span class="flex-1 font-medium">Profile berhasil diperbarui.</span>
                <button @click="show=false" class="text-green-500 hover:text-green-700">&times;</button>
            </div>
        </div>
        @endif

        @if (session('status') === 'password-updated')
        <div class="rounded-2xl bg-green-50 dark:bg-green-950 border border-green-200 dark:border-green-800 p-4 text-sm text-green-800 dark:text-green-200" x-data="{show:true}" x-show="show" x-transition>
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span class="flex-1 font-medium">Password berhasil diubah.</span>
                <button @click="show=false" class="text-green-500 hover:text-green-700">&times;</button>
            </div>
        </div>
        @endif

        {{-- Profile Info --}}
        <div class="sw-card overflow-hidden">
            <div class="p-6 sm:p-7 border-b border-gray-100 dark:border-gray-800 bg-gradient-to-br from-[#0DA4CE]/[0.06] via-transparent to-transparent">
                <div class="flex items-center gap-4 sm:gap-5">
                    <div class="shrink-0">
                        @if($user->avatar)
                        <img src="{{ asset('storage/' . $user->avatar) }}" alt="{{ $user->name }}"
                            class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl object-cover ring-4 ring-[#0DA4CE]/10 shadow-lg shadow-[#0DA4CE]/10">
                        @else
                        <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl bg-gradient-to-br from-[#0DA4CE] to-[#097A99] flex items-center justify-center text-white text-2xl font-bold shadow-lg shadow-[#0DA4CE]/25 ring-4 ring-[#0DA4CE]/10">
                            {{ strtoupper(substr($user->name ?? 'U', 0, 2)) }}
                        </div>
                        @endif
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-lg sm:text-xl font-bold text-gray-900 dark:text-gray-100 truncate">{{ $user->name }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 truncate">{{ $user->email }}</p>
                        <div class="flex flex-wrap items-center gap-x-2 gap-y-1 mt-1.5">
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-[#0DA4CE]/10 dark:bg-[#0DA4CE]/20 text-[#0DA4CE] dark:text-[#5BD8F7]">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                {{ $user->getRoleNames()->first() ?? 'User' }}
                            </span>
                            <span class="text-xs text-gray-400 dark:text-gray-500">{{ $user->username ?? 'Tanpa username' }}</span>
                            @if($user->created_at)
                            <span class="text-xs text-gray-400 dark:text-gray-500">&middot; Bergabung {{ $user->created_at->format('d M Y') }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <form method="post" action="{{ route('profile.update') }}" class="p-6 sm:p-7 space-y-5" x-data="{ submitting: false }" @submit="submitting = true">
                @csrf @method('patch')

                {{-- Nama --}}
                <div>
                    <label for="name" class="sw-label">Nama Lengkap</label>
                    <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required autofocus autocomplete="name"
                        placeholder="Masukkan nama sesuai data karyawan"
                        class="sw-input {{ $errors->has('name') ? 'sw-input-error' : '' }}">
                    @error('name')
                    <p class="sw-input-helper sw-helper-error">
                        <svg class="sw-helper-icon w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        {{ $message }}
                    </p>
                    @else
                    <p class="sw-input-helper">
                        <svg class="sw-helper-icon w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Masukkan nama sesuai data karyawan.
                    </p>
                    @enderror
                </div>

                {{-- Username --}}
                <div>
                    <label for="username" class="sw-label">Username</label>
                    <input id="username" name="username" type="text" value="{{ old('username', $user->username) }}" required
                        placeholder="Masukkan username"
                        class="sw-input {{ $errors->has('username') ? 'sw-input-error' : '' }}">
                    @error('username')
                    <p class="sw-input-helper sw-helper-error">
                        <svg class="sw-helper-icon w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        {{ $message }}
                    </p>
                    @else
                    <p class="sw-input-helper">
                        <svg class="sw-helper-icon w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Gunakan username yang unik dan mudah diingat.
                    </p>
                    @enderror
                </div>

                {{-- Email --}}
                <div>
                    <label for="email" class="sw-label">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required autocomplete="email"
                        pattern="^[a-zA-Z0-9]+([._%+-][a-zA-Z0-9]+)*@((gmail\.com|yahoo\.com|outlook\.com)|([a-zA-Z0-9]{2,}(\.[a-zA-Z0-9]{2,})*\.(co\.id|ac\.id|or\.id|go\.id|sch\.id|web\.id|mil\.id|biz\.id|my\.id|net\.id|id)))$"
                        title="Format email: nama@gmail.com, nama@yahoo.com, nama@outlook.com, atau nama@perusahaan.co.id / .id / .ac.id"
                        placeholder="nama@perusahaan.co.id"
                        class="sw-input {{ $errors->has('email') ? 'sw-input-error' : '' }}">
                    @error('email')
                    <p class="sw-input-helper sw-helper-error">
                        <svg class="sw-helper-icon w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        {{ $message }}
                    </p>
                    @else
                    <p class="sw-input-helper">
                        <svg class="sw-helper-icon w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        Gunakan email aktif (contoh: nama@gmail.com atau nama@perusahaan.co.id).
                    </p>
                    @enderror
                </div>

                {{-- Telepon --}}
                <div>
                    <label for="phone" class="sw-label">Nomor Telepon <span class="font-normal text-gray-400 dark:text-gray-500">(opsional)</span></label>
                    <input id="phone" name="phone" type="tel" inputmode="numeric" value="{{ old('phone', $user->phone) }}" autocomplete="tel"
                        maxlength="13"
                        oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                        pattern="^08[0-9]{10,11}$"
                        title="Nomor telepon harus terdiri dari 12–13 digit angka dengan awalan 08"
                        placeholder="08xxxxxxxxxx"
                        class="sw-input {{ $errors->has('phone') ? 'sw-input-error' : '' }}">
                    @error('phone')
                    <p class="sw-input-helper sw-helper-error">
                        <svg class="sw-helper-icon w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        {{ $message }}
                    </p>
                    @else
                    <p class="sw-input-helper">
                        <svg class="sw-helper-icon w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        Gunakan nomor WhatsApp aktif (12–13 digit, contoh: 081234567890).
                    </p>
                    @enderror
                </div>

                <div class="flex flex-wrap items-center gap-3 pt-2">
                    <button type="submit" :disabled="submitting"
                        class="sw-btn sw-btn-primary min-h-11 px-6 shadow-lg shadow-[#0DA4CE]/20 disabled:opacity-60 disabled:cursor-not-allowed disabled:shadow-none">
                        <svg x-show="submitting" x-cloak class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <svg x-show="!submitting" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span x-text="submitting ? 'Menyimpan...' : 'Simpan Perubahan'"></span>
                    </button>
                    @if (session('status') === 'profile-updated')
                    <p class="text-sm text-green-600 dark:text-green-400 font-medium" x-data="{show:true}" x-show="show" x-transition>
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Perubahan tersimpan.
                    </p>
                    @endif
                </div>
            </form>
        </div>

        {{-- Change Password --}}
        <div class="sw-card overflow-hidden" x-data="{ showCurrent: false, showNew: false, showConfirm: false }">
            <div class="px-6 sm:px-7 py-5 border-b border-gray-100 dark:border-gray-800 flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-[#0DA4CE]/10 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-[#0DA4CE] dark:text-[#5BD8F7]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                </div>
                <div>
                    <h3 class="text-base font-bold text-gray-900 dark:text-gray-100">Ubah Password</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Minimal 8 karakter dengan kombinasi huruf dan angka.</p>
                </div>
            </div>

            <form method="post" action="{{ route('profile.password.update') }}" class="p-6 sm:p-7 space-y-5" x-data="{ submitting: false }" @submit="submitting = true">
                @csrf @method('put')

                {{-- Current Password --}}
                <div>
                    <label for="current_password" class="sw-label">Password Saat Ini</label>
                    <div class="relative">
                        <input id="current_password" name="current_password" :type="showCurrent ? 'text' : 'password'" required autocomplete="current-password"
                            placeholder="Masukkan password saat ini"
                            style="padding-right: 2.75rem"
                            class="sw-input {{ $errors->getBag('updatePassword')->has('current_password') ? 'sw-input-error' : '' }}">
                        <button type="button" @click="showCurrent = !showCurrent" tabindex="-1"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 dark:text-gray-500 hover:text-[#0DA4CE] transition p-0.5">
                            <svg x-show="!showCurrent" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            <svg x-show="showCurrent" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a10.05 10.05 0 012.633-3.626M6.015 6.015A10.05 10.05 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.05 10.05 0 01-2.633 3.626M3 3l18 18"/></svg>
                        </button>
                    </div>
                    @error('current_password', 'updatePassword')
                    <p class="sw-input-helper sw-helper-error">
                        <svg class="sw-helper-icon w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        {{ $message }}
                    </p>
                    @else
                    <p class="sw-input-helper">
                        <svg class="sw-helper-icon w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Masukkan password aktif Anda saat ini.
                    </p>
                    @enderror
                </div>

                {{-- New Password --}}
                <div>
                    <label for="password" class="sw-label">Password Baru</label>
                    <div class="relative">
                        <input id="password" name="password" :type="showNew ? 'text' : 'password'" required autocomplete="new-password"
                            placeholder="Buat password baru"
                            style="padding-right: 2.75rem"
                            class="sw-input {{ $errors->getBag('updatePassword')->has('password') ? 'sw-input-error' : '' }}">
                        <button type="button" @click="showNew = !showNew" tabindex="-1"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 dark:text-gray-500 hover:text-[#0DA4CE] transition p-0.5">
                            <svg x-show="!showNew" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            <svg x-show="showNew" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a10.05 10.05 0 012.633-3.626M6.015 6.015A10.05 10.05 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.05 10.05 0 01-2.633 3.626M3 3l18 18"/></svg>
                        </button>
                    </div>
                    @error('password', 'updatePassword')
                    <p class="sw-input-helper sw-helper-error">
                        <svg class="sw-helper-icon w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        {{ $message }}
                    </p>
                    @else
                    <p class="sw-input-helper">
                        <svg class="sw-helper-icon w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Gunakan minimal 8 karakter dengan kombinasi huruf dan angka.
                    </p>
                    @enderror
                </div>

                {{-- Confirm New Password --}}
                <div>
                    <label for="password_confirmation" class="sw-label">Konfirmasi Password Baru</label>
                    <div class="relative">
                        <input id="password_confirmation" name="password_confirmation" :type="showConfirm ? 'text' : 'password'" required autocomplete="new-password"
                            placeholder="Ulangi password baru"
                            style="padding-right: 2.75rem"
                            class="sw-input {{ $errors->getBag('updatePassword')->has('password') ? 'sw-input-error' : '' }}">
                        <button type="button" @click="showConfirm = !showConfirm" tabindex="-1"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 dark:text-gray-500 hover:text-[#0DA4CE] transition p-0.5">
                            <svg x-show="!showConfirm" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            <svg x-show="showConfirm" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a10.05 10.05 0 012.633-3.626M6.015 6.015A10.05 10.05 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.05 10.05 0 01-2.633 3.626M3 3l18 18"/></svg>
                        </button>
                    </div>
                    @error('password_confirmation', 'updatePassword')
                    <p class="sw-input-helper sw-helper-error">
                        <svg class="sw-helper-icon w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        {{ $message }}
                    </p>
                    @else
                    <p class="sw-input-helper">
                        <svg class="sw-helper-icon w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Ketik ulang password baru untuk konfirmasi.
                    </p>
                    @enderror
                </div>

                <div class="flex flex-wrap items-center gap-3 pt-2">
                    <button type="submit" :disabled="submitting"
                        class="sw-btn sw-btn-primary min-h-11 px-6 shadow-lg shadow-[#0DA4CE]/20 disabled:opacity-60 disabled:cursor-not-allowed disabled:shadow-none">
                        <svg x-show="submitting" x-cloak class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <svg x-show="!submitting" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                        <span x-text="submitting ? 'Menyimpan...' : 'Ubah Password'"></span>
                    </button>
                    @if (session('status') === 'password-updated')
                    <p class="text-sm text-green-600 dark:text-green-400 font-medium" x-data="{show:true}" x-show="show" x-transition>
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Password diperbarui.
                    </p>
                    @endif
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
