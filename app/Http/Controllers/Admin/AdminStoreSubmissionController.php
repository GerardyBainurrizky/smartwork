<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\StoreSubmission;
use App\Models\User;
use App\Services\ActivityNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminStoreSubmissionController extends Controller
{
    public function index(Request $request): View
    {
        $statusFilter = $request->get('status', 'pending');

        $query = StoreSubmission::with(['user', 'reviewer', 'store'])
            ->orderBy('created_at', 'desc');

        if ($statusFilter && $statusFilter !== 'all') {
            $query->where('status', $statusFilter);
        }

        $submissions = $query->paginate(10)->withQueryString();

        $stats = [
            'total' => StoreSubmission::count(),
            'pending' => StoreSubmission::where('status', 'pending')->count(),
            'approved' => StoreSubmission::where('status', 'approved')->count(),
            'rejected' => StoreSubmission::where('status', 'rejected')->count(),
        ];

        return view('admin.stores.submissions.index', compact('submissions', 'stats', 'statusFilter'));
    }

    public function show(string $id): View
    {
        $submission = StoreSubmission::with(['user', 'reviewer', 'store'])->findOrFail($id);

        // Check for duplicate stores in Master Toko (BR-14)
        $potentialDuplicates = Store::withTrashed()
            ->where(function ($q) use ($submission) {
                $q->where('name', 'like', "%{$submission->name}%")
                    ->orWhere('address', 'like', "%{$submission->address}%");
                if ($submission->phone) {
                    $q->orWhere('phone', $submission->phone);
                }
            })
            ->get();

        $salesUsers = User::whereHas('roles', fn ($q) => $q->where('name', 'sales'))
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('admin.stores.submissions.show', compact('submission', 'potentialDuplicates', 'salesUsers'));
    }

    public function approve(Request $request, string $id): RedirectResponse
    {
        $submission = StoreSubmission::findOrFail($id);

        if ($submission->status !== 'pending') {
            return redirect()->route('admin.stores.submissions.index')
                ->with('error', 'Pengajuan ini sudah diproses sebelumnya.');
        }

        $validated = $request->validate([
            'sales_penanggung_jawab_id' => ['nullable', 'exists:users,id'],
            'is_delivery_destination' => ['nullable', 'boolean'],
            'status' => ['required', 'in:active,inactive'],
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
        ], [
            'sales_penanggung_jawab_id.exists' => 'Sales penanggung jawab tidak valid.',
            'status.required' => 'Status toko wajib dipilih.',
        ]);

        $createdStore = DB::transaction(function () use ($submission, $validated, $request) {
            $lastCode = Store::withTrashed()
                ->where('code', 'like', 'ST-%')
                ->orderByDesc('code')
                ->value('code');

            $nextNum = $lastCode ? ((int) substr($lastCode, 3)) + 1 : 1;
            $code = 'ST-'.str_pad((string) $nextNum, 3, '0', STR_PAD_LEFT);

            // Business Rule: Jika ada Sales penanggung jawab, is_delivery_destination wajib true
            $salesId = $validated['sales_penanggung_jawab_id'] ?: null;
            $isDelivery = ! empty($salesId) ? true : (bool) $request->input('is_delivery_destination', true);

            $store = Store::create([
                'code' => $code,
                'name' => $validated['name'],
                'owner' => $validated['owner'] ?? null,
                'sales_penanggung_jawab_id' => $salesId,
                'address' => $validated['address'],
                'city' => $validated['city'],
                'kecamatan' => $validated['kecamatan'] ?? null,
                'province' => $validated['province'],
                'latitude' => $validated['latitude'] ?? null,
                'longitude' => $validated['longitude'] ?? null,
                'maps_url' => $validated['maps_url'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'status' => $validated['status'],
                'is_delivery_destination' => $isDelivery,
            ]);

            $submission->update([
                'status' => 'approved',
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
                'store_id' => $store->id,
            ]);

            return $store;
        });

        $reviewer = auth()->user();
        $actorRoleLabel = $reviewer->hasRole('super-admin') ? 'Super Admin' : 'Admin';
        ActivityNotificationService::notifyUser($submission->user_id, [
            'activity_type' => 'store_submission_approved',
            'title' => 'Pengajuan Toko Disetujui',
            'message' => "Pengajuan toko {$submission->name} telah disetujui oleh {$actorRoleLabel} dan masuk Master Toko ({$createdStore->code}).",
            'actor_id' => (string) $reviewer->id,
            'actor_name' => $reviewer->name,
            'actor_role' => $actorRoleLabel,
            'store_name' => $createdStore->name,
            'info' => "Disetujui • {$createdStore->code}",
            'url' => route('stores.submissions.index'),
            'related_id' => (string) $submission->id,
            'related_type' => 'StoreSubmission',
            'activity_time' => now()->toIso8601String(),
        ]);

        return redirect()->route('admin.stores.submissions.index')
            ->with('success', "Pengajuan toko disetujui. Master Toko {$createdStore->code} berhasil dibuat.");
    }

    public function reject(Request $request, string $id): RedirectResponse
    {
        $submission = StoreSubmission::findOrFail($id);

        if ($submission->status !== 'pending') {
            return redirect()->route('admin.stores.submissions.index')
                ->with('error', 'Pengajuan ini sudah diproses sebelumnya.');
        }

        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:500'],
        ], [
            'rejection_reason.required' => 'Alasan penolakan wajib diisi.',
            'rejection_reason.max' => 'Alasan penolakan maksimal 500 karakter.',
        ]);

        $submission->update([
            'status' => 'rejected',
            'rejection_reason' => $validated['rejection_reason'],
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        $reviewer = auth()->user();
        $actorRoleLabel = $reviewer->hasRole('super-admin') ? 'Super Admin' : 'Admin';
        ActivityNotificationService::notifyUser($submission->user_id, [
            'activity_type' => 'store_submission_rejected',
            'title' => 'Pengajuan Toko Ditolak',
            'message' => "Pengajuan toko {$submission->name} ditolak. Alasan: {$validated['rejection_reason']}",
            'actor_id' => (string) $reviewer->id,
            'actor_name' => $reviewer->name,
            'actor_role' => $actorRoleLabel,
            'store_name' => $submission->name,
            'info' => 'Ditolak • ' . now()->format('H.i'),
            'url' => route('stores.submissions.index'),
            'related_id' => (string) $submission->id,
            'related_type' => 'StoreSubmission',
            'activity_time' => now()->toIso8601String(),
        ]);

        return redirect()->route('admin.stores.submissions.index')
            ->with('success', 'Pengajuan toko berhasil ditolak.');
    }
}
