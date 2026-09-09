<x-app-layout>
    <x-slot name="header">
        <x-page-header icon="user" title="Edit Pengguna" subtitle="Perbarui data akun pengguna">
            <a href="{{ route('admin.users.index') }}"
               class="inline-flex items-center px-4 py-2.5 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-xl text-sm font-semibold hover:bg-gray-200 dark:hover:bg-gray-700 transition">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Kembali
            </a>
        </x-page-header>
    </x-slot>

    @php
        $base = 'w-full border-2 rounded-xl px-3.5 py-2.5 text-sm bg-white text-gray-900 placeholder-gray-400 transition duration-150 focus:outline-none focus:ring-4 disabled:bg-gray-50 disabled:text-gray-400 disabled:cursor-not-allowed dark:bg-gray-900 dark:text-gray-200 dark:placeholder-gray-500';
        $normal = 'border-gray-300 hover:border-gray-400 focus:border-[#0DA4CE] focus:ring-[#0DA4CE]/15 dark:border-gray-700 dark:hover:border-gray-600';
        $error = 'border-red-400 focus:border-red-500 focus:ring-red-500/15 dark:border-red-500';
        $cls = function (string $field, string $extra = '') use ($errors, $base, $normal, $error) {
            return trim(($errors->has($field) ? $base . ' ' . $error : $base . ' ' . $normal) . ' ' . $extra);
        };
        $labelClass = 'block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5';
        $req = '<span class="text-red-500">*</span>';
        $currentRole = $user->getRoleNames()->first();
    @endphp

    <div class="max-w-3xl mx-auto">
        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden">
            <div class="p-5 sm:p-7">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 rounded-xl bg-[#0DA4CE]/10 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-[#0DA4CE]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    </div>
                    <div>
                        <h1 class="text-lg font-bold text-gray-900 dark:text-white">Edit Pengguna</h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Perbarui informasi akun <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $user->name }}</span>.</p>
                    </div>
                </div>

                <form action="{{ route('admin.users.update', $user->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        {{-- Username --}}
                        <div>
                            <label for="username" class="{{ $labelClass }}">Username {!! $req !!}</label>
                            <input id="username" name="username" type="text" value="{{ old('username', $user->username) }}" required autofocus
                                placeholder="cth: budi_santoso"
                                class="{{ $cls('username') }}" />
                            @error('username')
                            <p class="flex items-center gap-1.5 mt-1.5 text-sm text-red-600 dark:text-red-400">
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                <span>{{ $message }}</span>
                            </p>
                            @else
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1.5">Huruf kecil, angka, dan underscore.</p>
                            @enderror
                        </div>

                        {{-- Nama --}}
                        <div>
                            <label for="name" class="{{ $labelClass }}">Nama Lengkap {!! $req !!}</label>
                            <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required
                                placeholder="cth: Budi Santoso"
                                class="{{ $cls('name') }}" />
                            @error('name')
                            <p class="flex items-center gap-1.5 mt-1.5 text-sm text-red-600 dark:text-red-400">
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                <span>{{ $message }}</span>
                            </p>
                            @enderror
                        </div>

                        {{-- Email --}}
                        <div>
                            <label for="email" class="{{ $labelClass }}">Email {!! $req !!}</label>
                            <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required
                                placeholder="cth: budi@perusahaan.co.id"
                                class="{{ $cls('email') }}" />
                            @error('email')
                            <p class="flex items-center gap-1.5 mt-1.5 text-sm text-red-600 dark:text-red-400">
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                <span>{{ $message }}</span>
                            </p>
                            @enderror
                        </div>

                        {{-- Telepon --}}
                        <div>
                            <label for="phone" class="{{ $labelClass }}">Telepon</label>
                            <input id="phone" name="phone" type="text" value="{{ old('phone', $user->phone) }}"
                                placeholder="cth: 081234567890"
                                class="{{ $cls('phone') }}" />
                            @error('phone')
                            <p class="flex items-center gap-1.5 mt-1.5 text-sm text-red-600 dark:text-red-400">
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                <span>{{ $message }}</span>
                            </p>
                            @else
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1.5">12–13 digit angka diawali 08.</p>
                            @enderror
                        </div>

                        {{-- Password --}}
                        <div>
                            <label for="password" class="{{ $labelClass }}">Password</label>
                            <input id="password" name="password" type="password"
                                placeholder="Kosongkan jika tidak ingin mengubah"
                                class="{{ $cls('password') }}" />
                            @error('password')
                            <p class="flex items-center gap-1.5 mt-1.5 text-sm text-red-600 dark:text-red-400">
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                <span>{{ $message }}</span>
                            </p>
                            @else
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1.5">Kosongkan jika tidak ingin mengubah password.</p>
                            @enderror
                        </div>

                        {{-- Konfirmasi Password --}}
                        <div>
                            <label for="password_confirmation" class="{{ $labelClass }}">Konfirmasi Password</label>
                            <input id="password_confirmation" name="password_confirmation" type="password"
                                placeholder="Ulangi password"
                                class="{{ $cls('password_confirmation') }}" />
                            @error('password_confirmation')
                            <p class="flex items-center gap-1.5 mt-1.5 text-sm text-red-600 dark:text-red-400">
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                <span>{{ $message }}</span>
                            </p>
                            @enderror
                        </div>
                    </div>

                    {{-- Role --}}
                    <div class="mt-5">
                        <label for="role" class="{{ $labelClass }}">Role {!! $req !!}</label>
                        <select id="role" name="role" required
                            class="{{ $cls('role') }}">
                            <option value="" disabled {{ $currentRole ? '' : 'selected' }}>Pilih Role</option>
                            @foreach ($roles ?? [] as $role)
                            <option value="{{ $role->name }}" {{ (old('role', $currentRole) === $role->name) ? 'selected' : '' }}>
                                {{ ucfirst($role->name) }}
                            </option>
                            @endforeach
                        </select>
                        @error('role')
                        <p class="flex items-center gap-1.5 mt-1.5 text-sm text-red-600 dark:text-red-400">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            <span>{{ $message }}</span>
                        </p>
                        @else
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1.5">Tentukan hak akses pengguna ini.</p>
                        @enderror
                    </div>

                    {{-- Status --}}
                    <div class="mt-5">
                        <span class="{{ $labelClass }}">Status {!! $req !!}</span>
                        <div class="mt-1 grid grid-cols-1 sm:grid-cols-2 gap-3" x-data="{ status: '{{ old('status', $user->status) }}' }">
                            <label :class="status === 'active' ? 'border-[#0DA4CE] bg-[#0DA4CE]/5' : 'border-gray-300 hover:border-gray-400'"
                                class="flex items-center gap-3 p-3.5 rounded-xl border-2 cursor-pointer transition">
                                <input type="radio" name="status" value="active" x-model="status" class="w-4 h-4 text-[#0DA4CE] focus:ring-[#0DA4CE]">
                                <span class="flex-1">
                                    <span class="block text-sm font-semibold text-gray-900 dark:text-white">Aktif</span>
                                    <span class="block text-xs text-gray-500 dark:text-gray-400 mt-0.5">Akun dapat login dan digunakan</span>
                                </span>
                                <svg x-show="status === 'active'" class="w-5 h-5 text-[#0DA4CE]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </label>
                            <label :class="status === 'inactive' ? 'border-red-400 bg-red-50 dark:bg-red-900/20' : 'border-gray-300 hover:border-gray-400'"
                                class="flex items-center gap-3 p-3.5 rounded-xl border-2 cursor-pointer transition">
                                <input type="radio" name="status" value="inactive" x-model="status" class="w-4 h-4 text-[#0DA4CE] focus:ring-[#0DA4CE]">
                                <span class="flex-1">
                                    <span class="block text-sm font-semibold text-gray-900 dark:text-white">Nonaktif</span>
                                    <span class="block text-xs text-gray-500 dark:text-gray-400 mt-0.5">Akun tidak dapat login</span>
                                </span>
                                <svg x-show="status === 'inactive'" class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            </label>
                        </div>
                        @error('status')
                        <p class="flex items-center gap-1.5 mt-1.5 text-sm text-red-600 dark:text-red-400">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            <span>{{ $message }}</span>
                        </p>
                        @enderror
                    </div>

                    {{-- Aksi --}}
                    <div class="mt-7 flex flex-col-reverse sm:flex-row sm:items-center gap-3 pt-5 border-t border-gray-100 dark:border-gray-800">
                        <a href="{{ route('admin.users.index') }}"
                            class="inline-flex items-center justify-center px-5 py-2.5 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-xl text-sm font-semibold hover:bg-gray-200 dark:hover:bg-gray-700 transition">
                            Batal
                        </a>
                        <button type="submit"
                            class="inline-flex items-center justify-center px-6 py-2.5 bg-[#0DA4CE] text-white rounded-xl text-sm font-semibold hover:bg-[#097A99] transition shadow-sm shadow-[#0DA4CE]/20 sm:order-2">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
