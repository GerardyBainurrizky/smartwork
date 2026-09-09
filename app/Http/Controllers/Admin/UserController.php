<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::withTrashed()
            ->with('roles')
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->search;
                $q->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('role'), function ($q) use ($request) {
                if ($request->role === 'super-admin') {
                    $q->whereHas('roles', fn ($r) => $r->where('name', 'super-admin'));
                } elseif ($request->role === 'admin') {
                    $q->whereHas('roles', fn ($r) => $r->whereIn('name', ['admin', 'super-admin']));
                } else {
                    $q->whereHas('roles', fn ($r) => $r->where('name', $request->role));
                }
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $base = User::withTrashed();
        $stats = [
            'total' => (clone $base)->count(),
            'admin' => (clone $base)->whereHas('roles', fn ($r) => $r->whereIn('name', ['admin', 'super-admin']))->count(),
            'admin_only' => (clone $base)->whereHas('roles', fn ($r) => $r->where('name', 'admin'))->count(),
            'super_admin' => (clone $base)->whereHas('roles', fn ($r) => $r->where('name', 'super-admin'))->count(),
            'sales' => (clone $base)->whereHas('roles', fn ($r) => $r->where('name', 'sales'))->count(),
            'staff' => (clone $base)->whereHas('roles', fn ($r) => $r->where('name', 'staff'))->count(),
            'driver' => (clone $base)->whereHas('roles', fn ($r) => $r->where('name', 'driver'))->count(),
        ];

        return view('admin.users.index', compact('users', 'stats'));
    }

    public function create(): View
    {
        $roles = Role::all();

        return view('admin.users.create', compact('roles'));
    }

    public function store(UserRequest $request): RedirectResponse
    {
        $user = User::create($request->validated());

        if ($request->role) {
            $user->assignRole($request->role);
        }

        return redirect()->route('admin.users.index')
            ->with('success', 'Pengguna berhasil ditambahkan.');
    }

    public function edit(User $user): View
    {
        $roles = Role::all();

        return view('admin.users.edit', compact('user', 'roles'));
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $user->update($data);

        if ($request->role) {
            $user->syncRoles($request->role);
        }

        return redirect()->route('admin.users.index')
            ->with('success', 'Pengguna berhasil diperbarui.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Anda tidak dapat menghapus akun sendiri.');
        }

        $user->update(['status' => 'inactive']);
        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'Pengguna berhasil dinonaktifkan.');
    }

    public function restore(string $id): RedirectResponse
    {
        $user = User::withTrashed()->findOrFail($id);
        $user->restore();
        $user->update(['status' => 'active']);

        return redirect()->route('admin.users.index')
            ->with('success', 'Pengguna berhasil diaktifkan kembali.');
    }

    public function forceDestroy(string $id): RedirectResponse
    {
        $user = User::withTrashed()->findOrFail($id);

        if ($user->id === auth()->id()) {
            return back()->with('error', 'Anda tidak dapat menghapus akun sendiri.');
        }

        $relatedCount = $user->visits()->count()
            + $user->routes()->withTrashed()->count()
            + $user->attendances()->count();

        if ($relatedCount > 0) {
            return back()->with('error', 'Pengguna tidak dapat dihapus karena masih memiliki data kunjungan, rencana kunjungan, atau presensi. Nonaktifkan akun saja.');
        }

        $user->roles()->detach();
        $user->forceDelete();

        return redirect()->route('admin.users.index')
            ->with('success', 'Pengguna berhasil dihapus secara permanen.');
    }
}
