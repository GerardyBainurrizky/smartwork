<?php

namespace App\Http\Controllers;

use App\Models\Route;
use App\Models\RouteStop;
use App\Models\User;
use App\Models\Visit;
use App\Services\ActivityNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class VisitController extends Controller
{
    public function index(): View|RedirectResponse
    {
        $user = auth()->user();

        if ($user->isAdmin() || $user->isSuperAdmin()) {
            return redirect()->route('admin.visits.index');
        }

        $today = now()->toDateString();
        $isDriver = $user->hasRole('driver');

        // Active visit/delivery for today page: strictly bound to today's date so past incomplete records stay in history
        $activeVisit = Visit::with(['store', 'routeStop.route'])
            ->where('user_id', $user->id)
            ->where('status', 'in_progress')
            ->whereDate('check_in_at', $today)
            ->first();

        // Source of truth: the day's route plan (all route_stops across every same-date route),
        // identical to the /route page and the Sales dashboard.
        $todayPlan = Route::todayPlanFor($user->id, $today);
        $todayRoute = $todayPlan['routes']->first();
        $todayStops = $todayPlan['stops'];
        $todayPlanStats = $todayPlan['stats'];

        $todayVisits = Visit::with(['store', 'routeStop.route'])
            ->where('user_id', $user->id)
            ->whereDate('check_in_at', $today)
            ->orderBy('check_in_at', 'desc')
            ->get();

        $todayCount = $todayVisits->count();
        $todayCompleted = $todayVisits->where('status', 'completed')->count();

        return view('visit.index', compact(
            'activeVisit', 'todayRoute', 'todayStops', 'todayPlanStats',
            'todayVisits', 'todayCount', 'todayCompleted'
        ));
    }

    public function checkInForm(string $routeStopId): \Illuminate\Contracts\View\View|RedirectResponse
    {
        $user = auth()->user();

        $routeStop = RouteStop::with(['store', 'route'])->findOrFail($routeStopId);

        if ((string) $routeStop->route->user_id !== (string) $user->id) {
            abort(403, 'Anda tidak memiliki akses ke rute ini.');
        }

        if (in_array($routeStop->route->status, ['draft', 'cancelled'])) {
            return redirect()->route('route.show', $routeStop->route_id)->with('error', 'Rute belum dimulai. Silakan klik Mulai Rute terlebih dahulu sebelum melakukan check-in kunjungan.');
        }

        if ($routeStop->status === 'visited' && $routeStop->visit) {
            return redirect()->route('visit.show', $routeStop->visit->id);
        }

        $existing = Visit::where('route_stop_id', $routeStopId)
            ->where('user_id', $user->id)
            ->first();

        if ($existing) {
            return redirect()->route('visit.show', $existing->id);
        }

        // Sequential Check-in Protection (Berlaku PER RUTE, tidak memblokir antar rute berbeda):
        if ($user->hasRole('driver')) {
            // Sequential Delivery Order Rule (Khusus Driver): Pengiriman harus dimulai secara berurutan sesuai sequence per route
            // 1. Check prior stops in the SAME route (sequence < current sequence)
            $priorPendingStop = RouteStop::with(['store', 'visit'])
                ->where('route_id', $routeStop->route_id)
                ->where('sequence', '<', $routeStop->sequence)
                ->where('status', '!=', 'skipped')
                ->whereDoesntHave('visit', function ($q) {
                    $q->where('status', 'completed');
                })
                ->orderBy('sequence')
                ->first();

            if ($priorPendingStop) {
                $priorStoreName = $priorPendingStop->store?->name ?? ('Pengiriman #' . $priorPendingStop->sequence);
                $targetStoreName = $routeStop->store?->name ?? ('Pengiriman #' . $routeStop->sequence);
                $isPriorInProgress = $priorPendingStop->visit && $priorPendingStop->visit->status === 'in_progress';
                $msg = $isPriorInProgress
                    ? "{$priorStoreName} masih dalam proses pengiriman. Silakan lakukan Check Out terlebih dahulu sebelum melanjutkan ke {$targetStoreName}."
                    : "Silakan selesaikan pengiriman {$priorStoreName} terlebih dahulu sebelum melakukan Check In ke {$targetStoreName}.";

                return redirect()->route('route.show', $routeStop->route_id)->with([
                    'error' => $msg,
                    'error_stop_id' => $routeStop->id,
                    'error_title' => 'Belum dapat Check In',
                ]);
            }

            // 2. Check active in_progress delivery in the SAME route
            $activeVisitInRoute = Visit::with('store')
                ->where('route_id', $routeStop->route_id)
                ->where('status', 'in_progress')
                ->first();

            if ($activeVisitInRoute && (string) $activeVisitInRoute->route_stop_id !== (string) $routeStopId) {
                $activeStoreName = $activeVisitInRoute->store?->name ?? 'pengiriman sebelumnya';
                $targetStoreName = $routeStop->store?->name ?? 'toko ini';

                return redirect()->route('route.show', $routeStop->route_id)->with([
                    'error' => "{$activeStoreName} masih dalam proses pengiriman. Silakan lakukan Check Out terlebih dahulu sebelum melanjutkan ke {$targetStoreName}.",
                    'error_stop_id' => $routeStop->id,
                    'error_title' => 'Belum dapat Check In',
                ]);
            }
        } else {
            // Sequential Visit Order Rule (Khusus Sales): Kunjungan harus dimulai secara berurutan sesuai sequence per route
            // 1. Check prior stops in the SAME route (sequence < current sequence)
            $priorPendingStop = RouteStop::with(['store', 'visit'])
                ->where('route_id', $routeStop->route_id)
                ->where('sequence', '<', $routeStop->sequence)
                ->where('status', '!=', 'skipped')
                ->whereDoesntHave('visit', function ($q) {
                    $q->where('status', 'completed');
                })
                ->orderBy('sequence')
                ->first();

            if ($priorPendingStop) {
                $priorStoreName = $priorPendingStop->store?->name ?? ('Kunjungan #' . $priorPendingStop->sequence);
                $targetStoreName = $routeStop->store?->name ?? ('Kunjungan #' . $routeStop->sequence);
                $isPriorInProgress = $priorPendingStop->visit && $priorPendingStop->visit->status === 'in_progress';
                $msg = $isPriorInProgress
                    ? "{$priorStoreName} masih dalam proses kunjungan. Silakan lakukan Check Out terlebih dahulu sebelum melanjutkan ke {$targetStoreName}."
                    : "Silakan selesaikan kunjungan {$priorStoreName} terlebih dahulu sebelum melakukan Check In ke {$targetStoreName}.";

                return redirect()->route('route.show', $routeStop->route_id)->with([
                    'error' => $msg,
                    'error_stop_id' => $routeStop->id,
                    'error_title' => 'Belum dapat Check In',
                ]);
            }

            // 2. Check if there is an in-progress visit in this SAME route
            $activeVisitInRoute = Visit::with('store')
                ->where('route_id', $routeStop->route_id)
                ->where('status', 'in_progress')
                ->first();

            if ($activeVisitInRoute && (string) $activeVisitInRoute->route_stop_id !== (string) $routeStopId) {
                $activeStoreName = $activeVisitInRoute->store?->name ?? 'kunjungan sebelumnya';
                $targetStoreName = $routeStop->store?->name ?? 'toko ini';

                return redirect()->route('route.show', $routeStop->route_id)->with([
                    'error' => "{$activeStoreName} masih dalam proses kunjungan. Silakan lakukan Check Out terlebih dahulu sebelum melanjutkan ke {$targetStoreName}.",
                    'error_stop_id' => $routeStop->id,
                    'error_title' => 'Belum dapat Check In',
                ]);
            }
        }

        return view('visit.check-in', compact('routeStop'));
    }

    public function checkIn(Request $request): JsonResponse
    {
        $user = auth()->user();

        $isDriver = $user->hasRole('driver');
        try {
            $rules = [
                'route_stop_id' => ['required', 'string', 'exists:route_stops,id'],
                'route_id' => ['required', 'string', 'exists:routes,id'],
                'store_id' => ['required', 'string', 'exists:stores,id'],
                'latitude' => ['required', 'numeric', 'between:-90,90'],
                'longitude' => ['required', 'numeric', 'between:-180,180'],
                'address' => ['required', 'string', 'max:500'],
                'maps_url' => ['required', 'string', 'max:500'],
                'storefront_photo' => ['nullable', 'string'],
                'selfie' => ['required', 'string'],
                'delivered_goods' => ['nullable', 'string', 'max:500'],
                'cash_received' => ['nullable', 'numeric', 'min:0'],
                'initial_notes' => ['nullable', 'string', 'max:500'],
            ];
            $validated = $request->validate($rules);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }

        // Store must come from the Route Plan — validate consistency server-side.
        $routeStop = RouteStop::with(['route', 'store'])->find($validated['route_stop_id']);

        if (! $routeStop || (string) $routeStop->route->user_id !== (string) $user->id) {
            return $this->errorResponse('Rute tidak ditemukan.', 403);
        }

        if (in_array($routeStop->route->status, ['draft', 'cancelled'])) {
            return $this->errorResponse('Rute belum dimulai. Silakan klik Mulai Rute terlebih dahulu sebelum melakukan check-in kunjungan.', 422);
        }

        if ($routeStop->route_id !== $validated['route_id']) {
            return $this->errorResponse('Data rute tidak sesuai.', 422);
        }

        if ($routeStop->store_id !== $validated['store_id']) {
            return $this->errorResponse('Toko tidak sesuai dengan rencana rute.', 422);
        }

        if ($routeStop->status === 'skipped') {
            return $this->errorResponse('Toko ini telah dilewati pada rute ini.', 422);
        }

        if (Visit::where('route_stop_id', $routeStop->id)->where('user_id', $user->id)->exists()) {
            return $this->errorResponse('Kunjungan untuk toko ini sudah tercatat.', 409);
        }

        // Sequential Order Rule: Kunjungan / Pengiriman harus dimulai secara berurutan sesuai sequence per route
        if (! $isDriver) {
            // 1. Check prior stops in the SAME route (sequence < current sequence)
            $priorPendingStop = RouteStop::with(['store', 'visit'])
                ->where('route_id', $routeStop->route_id)
                ->where('sequence', '<', $routeStop->sequence)
                ->where('status', '!=', 'skipped')
                ->whereDoesntHave('visit', function ($q) {
                    $q->where('status', 'completed');
                })
                ->orderBy('sequence')
                ->first();

            if ($priorPendingStop) {
                $priorStoreName = $priorPendingStop->store?->name ?? ('Kunjungan #' . $priorPendingStop->sequence);
                $targetStoreName = $routeStop->store?->name ?? ('Kunjungan #' . $routeStop->sequence);
                $isPriorInProgress = $priorPendingStop->visit && $priorPendingStop->visit->status === 'in_progress';
                $msg = $isPriorInProgress
                    ? "{$priorStoreName} masih dalam proses kunjungan. Silakan lakukan Check Out terlebih dahulu sebelum melanjutkan ke {$targetStoreName}."
                    : "Silakan selesaikan kunjungan {$priorStoreName} terlebih dahulu sebelum melakukan Check In ke {$targetStoreName}.";

                return response()->json([
                    'status' => 'error',
                    'message' => $msg,
                ], 422);
            }

            // 2. Check active in_progress visit in the SAME route
            $activeVisit = Visit::with('store')
                ->where('route_id', $routeStop->route_id)
                ->where('status', 'in_progress')
                ->first();

            if ($activeVisit && (string) $activeVisit->route_stop_id !== (string) $routeStop->id) {
                $activeStoreName = $activeVisit->store?->name ?? 'kunjungan sebelumnya';
                $targetStoreName = $routeStop->store?->name ?? 'toko ini';

                return response()->json([
                    'status' => 'error',
                    'message' => "{$activeStoreName} masih dalam proses kunjungan. Silakan lakukan Check Out terlebih dahulu sebelum melanjutkan ke {$targetStoreName}.",
                ], 422);
            }
        } else {
            // Sequential Delivery Order Rule (Khusus Driver): Pengiriman harus dimulai secara berurutan sesuai sequence per route
            // 1. Check prior stops in the SAME route (sequence < current sequence)
            $priorPendingStop = RouteStop::with(['store', 'visit'])
                ->where('route_id', $routeStop->route_id)
                ->where('sequence', '<', $routeStop->sequence)
                ->where('status', '!=', 'skipped')
                ->whereDoesntHave('visit', function ($q) {
                    $q->where('status', 'completed');
                })
                ->orderBy('sequence')
                ->first();

            if ($priorPendingStop) {
                $priorStoreName = $priorPendingStop->store?->name ?? ('Pengiriman #' . $priorPendingStop->sequence);
                $targetStoreName = $routeStop->store?->name ?? ('Pengiriman #' . $routeStop->sequence);
                $isPriorInProgress = $priorPendingStop->visit && $priorPendingStop->visit->status === 'in_progress';
                $msg = $isPriorInProgress
                    ? "{$priorStoreName} masih dalam proses pengiriman. Silakan lakukan Check Out terlebih dahulu sebelum melanjutkan ke {$targetStoreName}."
                    : "Silakan selesaikan pengiriman {$priorStoreName} terlebih dahulu sebelum melakukan Check In ke {$targetStoreName}.";

                return response()->json([
                    'status' => 'error',
                    'message' => $msg,
                ], 422);
            }

            // 2. Check active in_progress delivery in the SAME route
            $activeVisit = Visit::with('store')
                ->where('route_id', $routeStop->route_id)
                ->where('status', 'in_progress')
                ->first();

            if ($activeVisit && (string) $activeVisit->route_stop_id !== (string) $routeStop->id) {
                $activeStoreName = $activeVisit->store?->name ?? 'pengiriman sebelumnya';
                $targetStoreName = $routeStop->store?->name ?? 'toko ini';

                return response()->json([
                    'status' => 'error',
                    'message' => "{$activeStoreName} masih dalam proses pengiriman. Silakan lakukan Check Out terlebih dahulu sebelum melanjutkan ke {$targetStoreName}.",
                ], 422);
            }
        }

        $storefrontPath = null;
        $selfiePath = null;

        try {
            $storefrontPath = ! empty($validated['storefront_photo'])
                ? $this->savePhoto($validated['storefront_photo'], 'storefront', $user->id)
                : null;
            $selfiePath = $this->savePhoto($validated['selfie'], 'checkin-selfie', $user->id);

            $visit = DB::transaction(function () use ($user, $validated, $routeStop, $storefrontPath, $selfiePath, $isDriver) {
                // Anti Concurrency / Duplicate Check in
                if ($isDriver) {
                    $activeVisit = Visit::where('route_id', $routeStop->route_id)
                        ->where('status', 'in_progress')
                        ->lockForUpdate()
                        ->first();

                    if ($activeVisit && (string) $activeVisit->route_stop_id !== (string) $routeStop->id) {
                        $activeStoreName = $activeVisit->store?->name ?? 'pengiriman sebelumnya';
                        $targetStoreName = $routeStop->store?->name ?? 'toko ini';
                        throw new \DomainException("{$activeStoreName} masih dalam proses pengiriman. Silakan lakukan Check Out terlebih dahulu sebelum melanjutkan ke {$targetStoreName}.");
                    }
                } else {
                    $activeVisit = Visit::where('route_id', $routeStop->route_id)
                        ->where('status', 'in_progress')
                        ->lockForUpdate()
                        ->first();

                    if ($activeVisit && (string) $activeVisit->route_stop_id !== (string) $routeStop->id) {
                        $activeStoreName = $activeVisit->store?->name ?? 'kunjungan sebelumnya';
                        $targetStoreName = $routeStop->store?->name ?? 'toko ini';
                        throw new \DomainException("{$activeStoreName} masih dalam proses kunjungan. Silakan lakukan Check Out terlebih dahulu sebelum melanjutkan ke {$targetStoreName}.");
                    }
                }

                $existingVisit = Visit::where('route_stop_id', $routeStop->id)
                    ->where('user_id', $user->id)
                    ->lockForUpdate()
                    ->first();

                if ($existingVisit) {
                    throw new \DomainException('Kunjungan untuk toko ini sudah tercatat.');
                }

                $visit = Visit::create([
                    'user_id' => $user->id,
                    'route_stop_id' => $validated['route_stop_id'],
                    'route_id' => $validated['route_id'],
                    'store_id' => $validated['store_id'],
                    'status' => 'in_progress',
                    'check_in_at' => now(),
                    'check_in_lat' => $validated['latitude'],
                    'check_in_lng' => $validated['longitude'],
                    'check_in_address' => $validated['address'],
                    'check_in_maps_url' => $validated['maps_url'],
                    'storefront_photo' => $storefrontPath,
                    'check_in_selfie' => $selfiePath,
                    'delivered_goods' => $validated['delivered_goods'] ?? null,
                    'cash_received' => $validated['cash_received'] ?? null,
                    'initial_notes' => $validated['initial_notes'] ?? null,
                ]);

                if ($routeStop->status === 'pending') {
                    $routeStop->update(['status' => 'visited']);
                }

                return $visit;
            });

            $storeName = $routeStop->store?->name ?? 'Toko';
            $routeName = $routeStop->route?->name;
            $isDriver = $user->hasRole('driver');
            $userRoleLabel = $user->role_label;
            $actType = $isDriver ? 'delivery_check_in' : 'visit_check_in';
            $actLabel = $isDriver ? 'Check-In Pengiriman' : 'Check-In Kunjungan';
            $checkInTime = $visit->check_in_at;

            ActivityNotificationService::notifyAdmins([
                'activity_type' => $actType,
                'title' => "{$user->name} ({$userRoleLabel}) melakukan {$actLabel} di {$storeName}.",
                'message' => "{$user->name} ({$userRoleLabel}) melakukan {$actLabel} di {$storeName}" . ($routeName ? " ({$routeName})" : '') . " pada {$checkInTime->format('H:i')}.",
                'actor_id' => (string) $user->id,
                'actor_name' => $user->name,
                'actor_role' => $userRoleLabel,
                'store_name' => $storeName,
                'route_name' => $routeName,
                'info' => ($routeName ? "Rute {$routeName} • " : '') . $checkInTime->format('H.i'),
                'url' => route('visit.show', $visit->id),
                'related_id' => (string) $visit->id,
                'related_type' => 'Visit',
                'activity_time' => $checkInTime->toIso8601String(),
            ]);
        } catch (\DomainException $e) {
            foreach ([$storefrontPath, $selfiePath] as $path) {
                if ($path) {
                    Storage::disk('public')->delete($path);
                }
            }

            return $this->errorResponse($e->getMessage(), 422);
        } catch (\Throwable $e) {
            foreach ([$storefrontPath, $selfiePath] as $path) {
                if ($path) {
                    Storage::disk('public')->delete($path);
                }
            }

            return $this->errorResponse('Terjadi kesalahan saat menyimpan kunjungan.', 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Check-in berhasil. Kunjungan dimulai.',
            'data' => [
                'id' => $visit->id,
                'status' => 'in_progress',
            ],
        ]);
    }

    public function checkOutForm(string $visitId): \Illuminate\Contracts\View\View|RedirectResponse
    {
        $visit = Visit::with(['store', 'routeStop.route'])->findOrFail($visitId);

        if ((string) $visit->user_id !== (string) auth()->id()) {
            abort(403, 'Anda tidak memiliki akses ke kunjungan ini.');
        }

        if ($visit->status === 'completed') {
            return redirect()->route('visit.show', $visit->id);
        }

        $openTransactions = collect();
        if (! auth()->user()->hasRole('driver') && $visit->store_id) {
            $openTransactions = \App\Models\StoreTransaction::with('payments')
                ->where('store_id', $visit->store_id)
                ->whereIn('status', [\App\Models\StoreTransaction::STATUS_BELUM_LUNAS, \App\Models\StoreTransaction::STATUS_SEBAGIAN])
                ->orderBy('transaction_date')
                ->orderBy('created_at')
                ->get()
                ->map(function ($trx) {
                    return [
                        'id' => $trx->id,
                        'transaction_code' => $trx->transaction_code,
                        'transaction_date' => $trx->transaction_date->format('d M Y'),
                        'description' => $trx->description ?? 'Tagihan Piutang',
                        'transaction_amount' => (float) $trx->transaction_amount,
                        'total_paid' => (float) $trx->total_paid,
                        'remaining_amount' => (float) $trx->remaining_amount,
                    ];
                });
        }

        return view('visit.check-out', compact('visit', 'openTransactions'));
    }

    public function checkOut(Request $request, string $visitId): JsonResponse
    {
        $visit = Visit::with(['routeStop.route', 'route', 'user'])->where('user_id', (string) auth()->id())->findOrFail($visitId);

        if ($visit->status === 'completed') {
            return response()->json(['status' => 'error', 'message' => 'Kunjungan sudah selesai.'], 422);
        }

        $user = auth()->user();
        $isDriver = $user->hasRole('driver');

        try {
            $request->merge([
                'transaction_amount' => $this->normalizeTransactionAmount($request->input('transaction_amount')),
            ]);

            $rules = [
                'latitude' => ['required', 'numeric', 'between:-90,90'],
                'longitude' => ['required', 'numeric', 'between:-180,180'],
                'address' => ['required', 'string', 'max:500'],
                'maps_url' => ['required', 'string', 'max:500'],
                'visit_result' => ['required', 'string', 'max:500'],
                'delivered_goods_summary' => ['nullable', 'string', 'max:500'],
                'returned_goods' => ['nullable', 'string', 'max:500'],
                'transaction_status' => ['required', 'string', 'in:none,paid,piutang,mixed'],
                'transaction_amount' => ['nullable', 'numeric', 'min:0'],
                'payment_method' => ['nullable', 'string', 'in:tunai,transfer,qris'],
                'old_payment_amount' => ['nullable', 'numeric', 'min:0'],
                'old_payment_method' => ['nullable', 'string', 'in:tunai,transfer,qris'],
                'old_payment_allocations' => ['nullable', 'array'],
                'old_payment_allocations.*.store_transaction_id' => ['required', 'string'],
                'old_payment_allocations.*.amount' => ['required', 'numeric', 'min:0.01'],
                'new_tx_total' => ['nullable', 'numeric', 'min:0'],
                'new_tx_paid' => ['nullable', 'numeric', 'min:0'],
                'new_payment_method' => ['nullable', 'string', 'in:tunai,transfer,qris'],
                'final_notes' => ['nullable', 'string', 'max:500'],
                'final_store_photo' => $isDriver ? ['nullable', 'string'] : ['required', 'string'],
                'selfie' => $isDriver ? ['nullable', 'string'] : ['required', 'string'],
                'photos' => $isDriver ? ['nullable', 'array', 'max:6'] : ['nullable'],
                'photos.*' => ['string'],
            ];

            $customMessages = [
                'final_store_photo.required' => 'Foto Etalase wajib diambil sebelum menyelesaikan Check Out.',
                'selfie.required' => 'Foto selfie wajib diambil sebelum check out.',
                'old_payment_allocations.*.store_transaction_id.required' => 'Transaksi piutang wajib dipilih.',
                'old_payment_allocations.*.amount.required' => 'Nominal pembayaran transaksi wajib diisi.',
                'old_payment_allocations.*.amount.numeric' => 'Nominal pembayaran harus berupa angka.',
                'old_payment_allocations.*.amount.min' => 'Nominal pembayaran harus lebih besar dari Rp0.',
            ];

            // Validation payment_method requirement when payment amount > 0 and validate old receivable allocations
            if (! $isDriver) {
                $txStatus = $request->input('transaction_status');
                if (in_array($txStatus, ['piutang', 'mixed'])) {
                    // Cek jika ada old_payment_allocations dikirim
                    $allocations = $request->input('old_payment_allocations');
                    if (is_array($allocations) && count($allocations) > 0) {
                        $zeroTrxCodes = [];
                        foreach ($allocations as $alloc) {
                            $allocAmount = isset($alloc['amount']) ? (float) $alloc['amount'] : 0;
                            if ($allocAmount <= 0) {
                                $tId = $alloc['store_transaction_id'] ?? null;
                                $trxModel = $tId ? \App\Models\StoreTransaction::find($tId) : null;
                                $zeroTrxCodes[] = $trxModel?->transaction_code ?? ($tId ? (string) $tId : 'Transaksi');
                            }
                        }

                        if (count($zeroTrxCodes) > 0) {
                            $msg = count($zeroTrxCodes) === 1
                                ? "Transaksi {$zeroTrxCodes[0]} dipilih tetapi nominal pembayarannya masih Rp0. Isi nominal pembayaran atau batalkan pilihan transaksi tersebut."
                                : "Beberapa transaksi yang dipilih belum memiliki nominal pembayaran (" . implode(', ', $zeroTrxCodes) . "). Isi nominal pembayaran atau batalkan pilihan transaksi tersebut.";

                            return response()->json([
                                'status' => 'error',
                                'message' => $msg,
                                'errors' => [
                                    'old_payment_allocations' => [$msg],
                                ],
                            ], 422);
                        }
                    } elseif ($txStatus === 'piutang') {
                        // Jika mode piutang tapi tidak ada alokasi dan old_payment_amount <= 0
                        $oldAmt = (float) $request->input('old_payment_amount', 0);
                        if ($oldAmt <= 0) {
                            return response()->json([
                                'status' => 'error',
                                'message' => 'Silakan pilih minimal satu transaksi piutang dan isi nominal pembayaran lebih besar dari Rp0.',
                                'errors' => [
                                    'old_payment_amount' => ['Silakan pilih minimal satu transaksi piutang dan isi nominal pembayaran lebih besar dari Rp0.'],
                                ],
                            ], 422);
                        }
                    }

                    if ($txStatus === 'piutang' && (float) $request->input('old_payment_amount') > 0) {
                        $rules['old_payment_method'] = ['required', 'string', 'in:tunai,transfer,qris'];
                    }
                    if (($txStatus === 'paid' || $txStatus === 'mixed') && (float) $request->input('new_tx_paid') > 0) {
                        $rules['new_payment_method'] = ['required', 'string', 'in:tunai,transfer,qris'];
                    }
                } elseif ($txStatus === 'paid') {
                    if ((float) $request->input('transaction_amount') > 0 && ! $request->has('new_tx_paid')) {
                        $rules['payment_method'] = ['required', 'string', 'in:tunai,transfer,qris'];
                    }
                    if ((float) $request->input('new_tx_paid') > 0) {
                        $rules['new_payment_method'] = ['required', 'string', 'in:tunai,transfer,qris'];
                    }
                }
            }

            $validated = $request->validate($rules, $customMessages);

            if ($isDriver) {
                $hasPhotos = (! empty($validated['photos']) && count($validated['photos']) > 0) || ! empty($validated['selfie']) || ! empty($validated['final_store_photo']);
                if (! $hasPhotos) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Foto barang atau dokumen pengiriman wajib diambil minimal 1 foto.',
                    ], 422);
                }

                if (! empty($validated['photos']) && count($validated['photos']) > 6) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Foto dokumentasi pengiriman maksimal 6 foto.',
                        'errors' => ['photos' => ['Foto dokumentasi pengiriman maksimal 6 foto.']],
                    ], 422);
                }

                $txStatus = $request->input('transaction_status');
                if ($txStatus === 'paid') {
                    $amt = (float) $request->input('transaction_amount', 0);
                    if ($amt <= 0) {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Nominal transaksi pengiriman wajib diisi lebih besar dari Rp0 jika ada transaksi.',
                            'errors' => ['transaction_amount' => ['Nominal transaksi pengiriman wajib diisi lebih besar dari Rp0.']],
                        ], 422);
                    }
                    $pm = $request->input('payment_method', 'tunai') ?: 'tunai';
                    if (! in_array($pm, ['tunai', 'transfer', 'qris'], true)) {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Metode pembayaran pengiriman wajib dipilih.',
                            'errors' => ['payment_method' => ['Metode pembayaran pengiriman wajib dipilih.']],
                        ], 422);
                    }
                    $validated['payment_method'] = $pm;
                }
            }
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }

        $finalStorePath = null;
        $checkOutSelfiePath = null;
        $savedPhotoPaths = [];

        try {
            if ($isDriver && ! empty($validated['photos']) && is_array($validated['photos'])) {
                foreach ($validated['photos'] as $idx => $photoBase64) {
                    $pPath = $this->savePhoto($photoBase64, 'checkout-delivery-' . ($idx + 1), auth()->id());
                    $savedPhotoPaths[] = $pPath;
                }
                $finalStorePath = $savedPhotoPaths[0] ?? null;
            } else {
                $finalStorePath = ! empty($validated['final_store_photo'])
                    ? $this->savePhoto($validated['final_store_photo'], 'final-store', auth()->id())
                    : null;
            }

            if (! empty($validated['selfie'])) {
                $checkOutSelfiePath = $this->savePhoto($validated['selfie'], 'checkout-selfie', auth()->id());
            }

            DB::transaction(function () use ($visit, $validated, $finalStorePath, $checkOutSelfiePath, $savedPhotoPaths, $isDriver, $user) {
                $lockedVisit = Visit::where('id', $visit->id)->lockForUpdate()->first();
                if ($lockedVisit->status === 'completed') {
                    throw new \DomainException('Kunjungan sudah selesai.');
                }

                $txStatus = $validated['transaction_status'] ?? 'none';
                $txAmount = $txStatus !== 'none' ? ($validated['transaction_amount'] ?? null) : null;
                $payMethod = $txStatus !== 'none' ? ($validated['payment_method'] ?? null) : null;

                $lockedVisit->update([
                    'status' => 'completed',
                    'check_out_at' => now(),
                    'check_out_lat' => $validated['latitude'],
                    'check_out_lng' => $validated['longitude'],
                    'check_out_address' => $validated['address'],
                    'check_out_maps_url' => $validated['maps_url'],
                    'visit_result' => $validated['visit_result'] ?? null,
                    'delivered_goods_summary' => $validated['delivered_goods_summary'] ?? null,
                    'returned_goods' => $validated['returned_goods'] ?? null,
                    'transaction_amount' => $txAmount,
                    'payment_method' => $payMethod,
                    'transaction_status' => $txStatus,
                    'final_notes' => $validated['final_notes'] ?? null,
                    'final_store_photo' => $finalStorePath,
                    'check_out_selfie' => $checkOutSelfiePath,
                ]);

                // Integrasi Piutang untuk Sales:
                if (! $isDriver) {
                    $store = $lockedVisit->store;

                    // 1. Bayar Piutang Lama (Allocations per transaction atau single allocation)
                    if (in_array($txStatus, ['piutang', 'mixed'])) {
                        $oldAllocations = $validated['old_payment_allocations'] ?? [];
                        $oldMethod = $validated['old_payment_method'] ?? $payMethod ?? 'tunai';

                        if (! empty($oldAllocations) && is_array($oldAllocations)) {
                            foreach ($oldAllocations as $alloc) {
                                if ((float) $alloc['amount'] > 0) {
                                    \App\Services\StoreReceivableService::recordTransactionPayment([
                                        'store_transaction_id' => $alloc['store_transaction_id'],
                                        'amount' => $alloc['amount'],
                                        'payment_method' => $oldMethod,
                                        'source' => 'visit',
                                        'source_id' => $lockedVisit->id,
                                        'notes' => 'Pembayaran piutang melalui kunjungan Sales ' . $user->name,
                                    ], $user);
                                }
                            }
                        } else {
                            $oldAmount = (float) ($validated['old_payment_amount'] ?? $txAmount ?? 0);
                            if ($oldAmount > 0) {
                                \App\Services\StoreReceivableService::recordPayment([
                                    'store_id' => $lockedVisit->store_id,
                                    'amount' => $oldAmount,
                                    'payment_method' => $oldMethod,
                                    'reference_type' => 'visit',
                                    'reference_id' => $lockedVisit->id,
                                    'notes' => 'Pembayaran piutang melalui kunjungan Sales ' . $user->name,
                                ], $user);
                            }
                        }
                    }

                    // 2. Transaksi Baru
                    if (in_array($txStatus, ['paid', 'mixed'])) {
                        $newTotal = (float) ($validated['new_tx_total'] ?? ($txStatus === 'paid' ? $txAmount : 0));
                        $newPaid = (float) ($validated['new_tx_paid'] ?? ($txStatus === 'paid' ? ($validated['new_tx_paid'] ?? $newTotal) : 0));
                        $newMethod = $validated['new_payment_method'] ?? $payMethod ?? 'tunai';

                        if ($newTotal > 0) {
                            \App\Services\StoreReceivableService::createTransaction([
                                'store_id' => $lockedVisit->store_id,
                                'transaction_amount' => $newTotal,
                                'paid_amount' => $newPaid,
                                'payment_method' => $newMethod,
                                'reference_type' => 'visit',
                                'reference_id' => $lockedVisit->id,
                                'description' => "Transaksi kunjungan {$store->name} oleh {$user->name}",
                            ], $user);
                        }
                    }
                }

                // Simpan multiple photos ke tabel visit_photos jika ada
                foreach ($savedPhotoPaths as $pPath) {
                    \App\Models\VisitPhoto::create([
                        'visit_id' => $lockedVisit->id,
                        'type' => 'checkout_documentation',
                        'photo_path' => $pPath,
                    ]);
                }

                $route = $lockedVisit->route;

                if ($route) {
                    $route->load('stops.visit');
                    $route->syncStatus();
                }
            });

            $visit->load(['store', 'route']);
            $storeName = $visit->store?->name ?? 'Toko';
            $user = auth()->user();
            $isDriver = $user->hasRole('driver');
            $userRoleLabel = $user->role_label;
            $actType = $isDriver ? 'delivery_check_out' : 'visit_check_out';
            $actLabel = $isDriver ? 'Check-Out Pengiriman' : 'Check-Out Kunjungan';
            $checkOutTime = $visit->check_out_at ?? now();

            $durationMinutes = null;
            if ($visit->check_in_at && $visit->check_out_at) {
                $durationMinutes = (int) $visit->check_in_at->diffInMinutes($visit->check_out_at);
            }

            $infoString = $durationMinutes !== null
                ? "Durasi {$durationMinutes} menit • " . $checkOutTime->format('H.i')
                : $checkOutTime->format('H.i');

            ActivityNotificationService::notifyAdmins([
                'activity_type' => $actType,
                'title' => "{$user->name} ({$userRoleLabel}) melakukan {$actLabel} di {$storeName}.",
                'message' => "{$user->name} ({$userRoleLabel}) melakukan {$actLabel} di {$storeName} pada {$checkOutTime->format('H:i')}." . ($durationMinutes !== null ? " Durasi: {$durationMinutes} menit." : ''),
                'actor_id' => (string) $user->id,
                'actor_name' => $user->name,
                'actor_role' => $userRoleLabel,
                'store_name' => $storeName,
                'info' => $infoString,
                'url' => route('visit.show', $visit->id),
                'related_id' => (string) $visit->id,
                'related_type' => 'Visit',
                'activity_time' => $checkOutTime->toIso8601String(),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            foreach (array_merge([$finalStorePath, $checkOutSelfiePath], $savedPhotoPaths) as $path) {
                if ($path) {
                    Storage::disk('public')->delete($path);
                }
            }

            return $this->validationError($e);
        } catch (\DomainException $e) {
            foreach (array_merge([$finalStorePath, $checkOutSelfiePath], $savedPhotoPaths) as $path) {
                if ($path) {
                    Storage::disk('public')->delete($path);
                }
            }

            return $this->errorResponse($e->getMessage(), 422);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            foreach (array_merge([$finalStorePath, $checkOutSelfiePath], $savedPhotoPaths) as $path) {
                if ($path) {
                    Storage::disk('public')->delete($path);
                }
            }

            return $this->errorResponse($e->getMessage() ?: 'Akses ditolak.', $e->getStatusCode());
        } catch (\Throwable $e) {
            foreach (array_merge([$finalStorePath, $checkOutSelfiePath], $savedPhotoPaths) as $path) {
                if ($path) {
                    Storage::disk('public')->delete($path);
                }
            }

            return $this->errorResponse('Terjadi kesalahan saat menyimpan kunjungan.', 500);
        }

        $isDriver = $visit->user ? $visit->user->hasRole('driver') : auth()->user()->hasRole('driver');
        $routeId = $visit->route_id ?? $visit->routeStop?->route_id;
        $redirectUrl = $routeId
            ? route('route.show', $routeId)
            : ($isDriver ? route('route.index') : route('visit.history'));

        return response()->json([
            'status' => 'success',
            'message' => 'Check-out berhasil. ' . ($isDriver ? 'Pengiriman selesai.' : 'Kunjungan selesai.'),
            'data' => [
                'id' => $visit->id,
                'status' => 'completed',
                'route_id' => $routeId,
                'redirect_url' => $redirectUrl,
            ],
        ]);
    }

    public function show(string $visitId): View
    {
        $visit = Visit::with([
            'store',
            'routeStop.route',
            'route',
            'user',
            'payments.transaction.payments',
            'transactions.payments',
            'photos',
            'checkoutPhotos',
        ])->findOrFail($visitId);

        return view('visit.show', compact('visit'));
    }

    private function normalizeTransactionAmount($value)
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value) || is_float($value)) {
            return $value;
        }

        $cleaned = preg_replace('/[^\d]/', '', (string) $value);

        return $cleaned === '' ? null : (int) $cleaned;
    }

    public function history(Request $request): View|RedirectResponse
    {
        $user = auth()->user();

        if ($user->isAdmin() || $user->isSuperAdmin()) {
            return redirect()->route('admin.visits.index');
        }

        $query = Visit::with(['store', 'routeStop.route', 'payments.transaction', 'transactions.payments'])
            ->where('user_id', $user->id)
            ->orderBy('check_in_at', 'desc');

        if ($request->filled('from_date')) {
            $query->whereDate('check_in_at', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('check_in_at', '<=', $request->to_date);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $visits = $query->paginate(10)->withQueryString();

        $isDriver = $user->hasRole('driver');
        $totalVisits = Visit::where('user_id', $user->id)->count();
        $completedVisits = Visit::where('user_id', $user->id)->where('status', 'completed')->count();
        $visitsThisMonth = Visit::where('user_id', $user->id)
            ->whereMonth('check_in_at', now()->month)
            ->whereYear('check_in_at', now()->year)
            ->count();

        $totalTransaction = 0.0;
        $monthlyCashIn = 0.0;
        $totalReceivableBalance = 0.0;
        $monthlyDebtPayments = 0.0;

        if ($isDriver) {
            // Driver Transaction Stats: Strictly isolated to Driver deliveries
            $driverScopeVisits = Visit::where('user_id', $user->id)
                ->where('status', 'completed');

            if ($request->filled('from_date')) {
                $driverScopeVisits->whereDate('check_in_at', '>=', $request->from_date);
            }
            if ($request->filled('to_date')) {
                $driverScopeVisits->whereDate('check_in_at', '<=', $request->to_date);
            }

            $driverCompletedList = $driverScopeVisits->get();
            $totalTransaction = (float) $driverCompletedList->where('transaction_status', 'paid')->sum('transaction_amount');
            $monthlyCashIn = $totalTransaction; // Total uang diterima dari transaksi pengiriman Driver
        } else {
            $salesStores = \App\Models\Store::where('sales_penanggung_jawab_id', $user->id)
                ->where('status', 'active')
                ->pluck('id');

            $startOfMonth = now()->startOfMonth()->toDateString();
            $today = now()->toDateString();

            // 1. Total Transaksi Baru Bulan Ini: Total nilai transaksi baru yang dibuat di bulan kalender berjalan untuk toko Sales ini
            $totalTransaction = (float) \App\Models\StoreTransaction::whereIn('store_id', $salesStores)
                ->whereDate('transaction_date', '>=', $startOfMonth)
                ->whereDate('transaction_date', '<=', $today)
                ->where(function ($q) {
                    $q->whereNull('reference_type')
                      ->orWhere('reference_type', '!=', 'opening_balance');
                })
                ->sum('transaction_amount');

            $legacyMonthlyNewTx = (float) Visit::where('user_id', $user->id)
                ->whereBetween('check_in_at', [$startOfMonth . ' 00:00:00', $today . ' 23:59:59'])
                ->where('status', 'completed')
                ->doesntHave('transactions')
                ->where('transaction_amount', '>', 0)
                ->whereIn('transaction_status', ['paid', 'mixed'])
                ->sum('transaction_amount');
            $totalTransaction += $legacyMonthlyNewTx;

            // 2. Total Saldo Piutang: SELURUH SALDO PIUTANG YANG MASIH OUTSTANDING untuk seluruh toko tanggung jawab Sales ini (TIDAK reset bulanan)
            foreach ($salesStores as $storeId) {
                $totalReceivableBalance += (float) \App\Services\StoreReceivableService::balanceForStore($storeId);
            }

            // 3. Pembayaran Piutang Lama Bulan Ini: Total uang yang diterima di bulan berjalan untuk mencicil/melunasi piutang yang sudah ada sebelumnya
            $monthlyDebtPayments = (float) \App\Models\StoreTransactionPayment::whereHas('transaction', function ($q) use ($salesStores, $startOfMonth) {
                    $q->whereIn('store_id', $salesStores)
                      ->where(function ($qq) use ($startOfMonth) {
                          $qq->whereDate('transaction_date', '<', $startOfMonth)
                             ->orWhere('reference_type', 'opening_balance');
                      });
                })
                ->whereDate('payment_date', '>=', $startOfMonth)
                ->whereDate('payment_date', '<=', $today)
                ->where('source', '!=', 'initial_payment')
                ->where(function ($q) {
                    $q->where('notes', 'not like', '%Pembayaran awal%')
                      ->where('notes', 'not like', '%pembayaran awal%')
                      ->orWhereNull('notes');
                })
                ->where(function ($q) {
                    $q->where('source', '!=', 'adjustment')
                      ->orWhereNull('source');
                })
                ->sum('amount');

            $legacyMonthlyOldDebtPaid = (float) Visit::where('user_id', $user->id)
                ->whereBetween('check_in_at', [$startOfMonth . ' 00:00:00', $today . ' 23:59:59'])
                ->where('status', 'completed')
                ->doesntHave('payments')
                ->where('cash_received', '>', 0)
                ->whereIn('transaction_status', ['piutang', 'mixed'])
                ->sum('cash_received');
            $monthlyDebtPayments += $legacyMonthlyOldDebtPaid;
        }

        return view('visit.history', compact(
            'visits', 'totalVisits', 'completedVisits', 'totalTransaction', 'monthlyCashIn', 'totalReceivableBalance', 'monthlyDebtPayments', 'visitsThisMonth'
        ));
    }

    public function admin(Request $request): View
    {
        $date = $request->filled('date') ? $request->date : now()->toDateString();

        $users = $this->salesUsers();
        $salesUserIds = $users->pluck('id');

        $routeScope = function ($query) use ($request, $date, $salesUserIds) {
            $query->whereDate('date', $date)->whereIn('user_id', $salesUserIds);

            if ($request->filled('user_id')) {
                $query->where('user_id', $request->user_id);
            }

            return $query;
        };

        $stopsQuery = RouteStop::query()
            ->with(['store', 'route.user', 'visit'])
            ->whereHas('route', $routeScope);

        if ($request->filled('status')) {
            $status = $request->status;

            if ($status === 'pending') {
                $stopsQuery->where('status', 'pending')->whereDoesntHave('visit');
            } elseif ($status === 'in_progress') {
                $stopsQuery->whereHas('visit', fn ($v) => $v->where('status', 'in_progress'));
            } elseif ($status === 'completed') {
                $stopsQuery->whereHas('visit', fn ($v) => $v->where('status', 'completed'));
            } elseif ($status === 'skipped') {
                $stopsQuery->where('status', 'skipped');
            }
        }

        $stops = $stopsQuery
            ->orderByDesc(Route::select('date')->whereColumn('routes.id', 'route_stops.route_id'))
            ->orderBy('sequence')
            ->paginate(10)
            ->withQueryString();

        $stops->getCollection()->each(function (RouteStop $stop) {
            $stop->monitor_status = $this->stopMonitorStatus($stop);
        });

        $statsBase = RouteStop::query()->whereHas('route', $routeScope);

        $stats = [
            'total' => (clone $statsBase)->count(),
            'completed' => (clone $statsBase)->whereHas('visit', fn ($v) => $v->where('status', 'completed'))->count(),
            'in_progress' => (clone $statsBase)->whereHas('visit', fn ($v) => $v->where('status', 'in_progress'))->count(),
            'pending' => (clone $statsBase)->where('status', 'pending')->whereDoesntHave('visit')->count(),
            'skipped' => (clone $statsBase)->where('status', 'skipped')->count(),
        ];

        return view('visit.admin', compact('stops', 'users', 'stats', 'date'));
    }

    public function adminDriver(Request $request): View
    {
        $date = $request->filled('date') ? $request->date : now()->toDateString();

        $routeScope = function ($query) use ($request, $date) {
            $query->whereDate('date', $date);

            if ($request->filled('user_id')) {
                $query->where('user_id', $request->user_id);
            }

            return $query;
        };

        $stopsQuery = RouteStop::query()
            ->with(['store', 'route.user', 'visit'])
            ->whereHas('route', $routeScope)
            ->whereHas('route.user.roles', fn ($q) => $q->where('name', 'driver'));

        if ($request->filled('status')) {
            $status = $request->status;

            if ($status === 'pending') {
                $stopsQuery->where('status', 'pending')->whereDoesntHave('visit');
            } elseif ($status === 'in_progress') {
                $stopsQuery->whereHas('visit', fn ($v) => $v->where('status', 'in_progress'));
            } elseif ($status === 'completed') {
                $stopsQuery->whereHas('visit', fn ($v) => $v->where('status', 'completed'));
            } elseif ($status === 'skipped') {
                $stopsQuery->where('status', 'skipped');
            }
        }

        $stops = $stopsQuery
            ->orderByDesc(Route::select('date')->whereColumn('routes.id', 'route_stops.route_id'))
            ->orderBy('sequence')
            ->paginate(10)
            ->withQueryString();

        $stops->getCollection()->each(function (RouteStop $stop) {
            $stop->monitor_status = $this->stopMonitorStatus($stop);
        });

        $driverIds = $this->driverUsers()->pluck('id');
        $driverScope = function ($q) use ($routeScope, $driverIds) {
            $routeScope($q);
            $q->whereIn('user_id', $driverIds);
        };
        $statsBase = RouteStop::query()->whereHas('route', $driverScope);
        $stats = [
            'total' => (clone $statsBase)->count(),
            'completed' => (clone $statsBase)->whereHas('visit', fn ($v) => $v->where('status', 'completed'))->count(),
            'in_progress' => (clone $statsBase)->whereHas('visit', fn ($v) => $v->where('status', 'in_progress'))->count(),
            'pending' => (clone $statsBase)->where('status', 'pending')->whereDoesntHave('visit')->count(),
            'skipped' => (clone $statsBase)->where('status', 'skipped')->count(),
        ];

        // Uang Masuk Pengiriman Driver (pembayaran pengiriman selesai diterima sesuai filter)
        $driverCashInQuery = Visit::query()
            ->where('status', 'completed')
            ->where('transaction_status', 'paid')
            ->whereHas('route', $driverScope);

        if ($request->filled('status') && $request->status !== 'completed') {
            $driverCashIn = 0.0;
        } else {
            $driverCashIn = (float) $driverCashInQuery->sum('transaction_amount');
        }

        $users = $this->driverUsers();

        return view('visit.admin-driver', compact('stops', 'users', 'stats', 'date', 'driverCashIn'));
    }

    private function stopMonitorStatus(RouteStop $stop): string
    {
        if ($stop->status === 'skipped') {
            return 'skipped';
        }

        if ($stop->visit && $stop->visit->status === 'completed') {
            return 'completed';
        }

        if ($stop->visit) {
            return 'in_progress';
        }

        return 'pending';
    }

    // ---- Admin Visit Management (CRUD) ----

    private function salesUsers()
    {
        return User::whereHas('roles', fn ($q) => $q->whereIn('name', ['sales', 'field-supervisor']))
            ->orderBy('name')
            ->get();
    }

    private function driverUsers()
    {
        return User::whereHas('roles', fn ($q) => $q->where('name', 'driver'))
            ->orderBy('name')
            ->get();
    }

    private function savePhoto(string $base64Image, string $prefix, string $userId): string
    {
        $image = str_replace('data:image/jpeg;base64,', '', $base64Image);
        $image = str_replace('data:image/png;base64,', '', $image);
        $image = str_replace('data:image/webp;base64,', '', $image);
        $image = str_replace(' ', '+', $image);

        $decoded = base64_decode($image, true);

        if ($decoded === false) {
            throw new \RuntimeException('Data foto tidak valid.');
        }

        $filename = "visits/{$userId}/{$prefix}_".now()->format('Ymd_His').'_'.Str::random(6).'.jpg';
        Storage::disk('public')->put($filename, $decoded);

        return $filename;
    }

    private function validationError(ValidationException $e): JsonResponse
    {
        $firstMessage = collect($e->errors())->flatten()->first();

        return response()->json([
            'status' => 'error',
            'message' => $firstMessage ?: 'Data yang dikirim tidak valid.',
            'errors' => $e->errors(),
        ], 422);
    }

    private function errorResponse(string $message, int $status = 422): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => $message,
        ], $status);
    }
}
