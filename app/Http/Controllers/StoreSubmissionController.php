<?php

namespace App\Http\Controllers;

use App\Models\StoreSubmission;
use App\Services\ActivityNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class StoreSubmissionController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();

        $submissions = StoreSubmission::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('stores.submissions.index', compact('submissions'));
    }

    public function create(): View
    {
        return view('stores.submissions.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'owner' => ['nullable', 'string', 'max:200'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['required', 'string', 'max:500'],
            'city' => ['required', 'string', 'max:100'],
            'kecamatan' => ['nullable', 'string', 'max:100'],
            'province' => ['required', 'string', 'max:100'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'maps_url' => ['nullable', 'url', 'max:500'],
            'notes' => ['nullable', 'string', 'max:500'],
            'photo' => ['nullable', 'image', 'max:4096'],
        ], [
            'name.required' => 'Nama toko wajib diisi.',
            'name.max' => 'Nama toko maksimal 200 karakter.',
            'address.required' => 'Alamat toko wajib diisi.',
            'address.max' => 'Alamat toko maksimal 500 karakter.',
            'city.required' => 'Kota wajib diisi.',
            'province.required' => 'Provinsi wajib diisi.',
            'maps_url.url' => 'Link Google Maps tidak valid.',
            'photo.image' => 'Foto harus berupa file gambar.',
            'photo.max' => 'Ukuran foto maksimal 4MB.',
        ]);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('store-submissions/' . auth()->id(), 'public');
        }

        $submission = StoreSubmission::create([
            'user_id' => auth()->id(),
            'name' => $validated['name'],
            'owner' => $validated['owner'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'],
            'city' => $validated['city'],
            'kecamatan' => $validated['kecamatan'] ?? null,
            'province' => $validated['province'],
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'maps_url' => $validated['maps_url'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'photo' => $photoPath,
            'status' => 'pending',
        ]);

        $user = auth()->user();
        $userRoleLabel = $user->role_label;
        ActivityNotificationService::notifyAdmins([
            'activity_type' => 'store_submission_created',
            'title' => "{$user->name} ({$userRoleLabel}) mengajukan Toko Baru: {$submission->name}.",
            'message' => "{$user->name} ({$userRoleLabel}) mengajukan Toko Baru {$submission->name} untuk ditinjau oleh Admin.",
            'actor_id' => (string) $user->id,
            'actor_name' => $user->name,
            'actor_role' => $userRoleLabel,
            'store_name' => $submission->name,
            'info' => "Pengajuan Toko • " . now()->format('H.i'),
            'url' => route('admin.stores.submissions.show', $submission->id),
            'related_id' => (string) $submission->id,
            'related_type' => 'StoreSubmission',
            'activity_time' => now()->toIso8601String(),
        ]);

        return redirect()->route('stores.submissions.index')
            ->with('success', 'Pengajuan informasi toko baru berhasil dikirim dan menunggu verifikasi Admin.');
    }
}
