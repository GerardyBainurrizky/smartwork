<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\GpsLocation;
use App\Models\Route;
use App\Models\RouteStop;
use App\Models\Store;
use App\Models\User;
use App\Services\ActivityNotificationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RouteController extends Controller
{
    public function index(): View|RedirectResponse
    {
        $user = auth()->user();

        if ($user->isAdmin() || $user->isSuperAdmin()) {
            return redirect()->route('admin.routes.index');
        }

        $today = now()->toDateString();

        $todayPlan = Route::todayPlanFor($user->id, $today);
        $todayRoutes = $todayPlan['routes'];
        $todayRoute = $todayRoutes->first();
        $todayStops = $todayPlan['stops'];

        $upcomingRoutes = Route::with(['stops.store', 'stops.visit'])
            ->where('user_id', $user->id)
            ->whereDate('date', '>=', $today)
            ->orderBy('date')
            ->orderBy('created_at')
            ->get();

        $recentRoutes = Route::with(['stops.store', 'stops.visit'])
            ->where('user_id', $user->id)
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('route.index', compact('todayRoutes', 'todayRoute', 'todayStops', 'upcomingRoutes', 'recentRoutes'));
    }

    public function create(): View|RedirectResponse
    {
        $user = auth()->user();
        if ($user->isAdmin() || $user->isSuperAdmin()) {
            return redirect()->route('admin.routes.create');
        }

        $query = Store::where('status', 'active');

        if ($user->hasRole('driver')) {
            $query->where('is_delivery_destination', true);
        } else {
            $query->where('sales_penanggung_jawab_id', $user->id);
        }

        $stores = $query->orderBy('name')->get();

        return view('route.create', compact('stores'));
    }

    public function store(Request $request): JsonResponse
    {
        if (auth()->user()->isAdmin() || auth()->user()->isSuperAdmin()) {
            abort(403, 'Admin tidak dapat membuat rute Sales dari halaman ini.');
        }

        $user = auth()->user();
        $isDriver = $user?->hasRole('driver');

        $storeRules = [
            'name' => ['required', 'string', 'max:200'],
            'date' => ['required', 'date', 'after_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:500'],
            'stops' => ['required', 'array', 'min:1'],
            'stops.*.store_id' => ['required', 'string', 'exists:stores,id'],
            'stops.*.sequence' => ['required', 'integer', 'min:1'],
            'stops.*.notes' => ['nullable', 'string', 'max:255'],
        ];
        $storeMessages = [
            'stops.required' => 'Minimal satu toko harus dipilih.',
            'stops.min' => 'Minimal satu toko harus dipilih.',
            'stops.*.store_id.required' => 'Toko wajib dipilih.',
            'stops.*.sequence.required' => $isDriver ? 'Urutan pengiriman wajib diisi.' : 'Urutan kunjungan wajib diisi.',
            'stops.*.sequence.integer' => $isDriver ? 'Urutan pengiriman harus berupa angka.' : 'Urutan kunjungan harus berupa angka.',
            'stops.*.sequence.min' => $isDriver ? 'Urutan pengiriman minimal 1.' : 'Urutan kunjungan minimal 1.',
        ];

        if ($isDriver) {
            $storeRules['stops.*.estimated_duration_minutes'] = ['nullable', 'integer', 'min:1', 'max:480'];
        } else {
            $storeRules['stops.*.estimated_duration_minutes'] = ['required', 'integer', 'min:1', 'max:480'];
            $storeMessages['stops.*.estimated_duration_minutes.required'] = 'Estimasi durasi wajib diisi.';
            $storeMessages['stops.*.estimated_duration_minutes.integer'] = 'Estimasi durasi harus berupa angka dalam menit.';
            $storeMessages['stops.*.estimated_duration_minutes.min'] = 'Estimasi durasi minimal 1 menit.';
            $storeMessages['stops.*.estimated_duration_minutes.max'] = 'Estimasi durasi maksimal 480 menit.';
        }

        $validated = $request->validate($storeRules, $storeMessages);

        // Server-side Authorization & Validation of selected stores (BR-03, BR-04, BR-05, BR-06)
        $storeIds = collect($validated['stops'])->pluck('store_id')->unique();
        if ($isDriver) {
            $invalidCount = Store::whereIn('id', $storeIds)
                ->where(function ($q) {
                    $q->where('status', '!=', 'active')
                        ->orWhere('is_delivery_destination', false);
                })
                ->count();

            if ($invalidCount > 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Toko yang dipilih tidak aktif atau bukan tujuan pengiriman yang valid.',
                ], 422);
            }
        } else {
            $invalidCount = Store::whereIn('id', $storeIds)
                ->where(function ($q) use ($user) {
                    $q->where('status', '!=', 'active')
                        ->orWhere('sales_penanggung_jawab_id', '!=', $user->id)
                        ->orWhereNull('sales_penanggung_jawab_id');
                })
                ->count();

            if ($invalidCount > 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Toko yang dipilih tidak aktif atau berada di luar jangkauan Sales Anda.',
                ], 422);
            }
        }

        $stops = $this->normalizeStopsForStorage($validated['stops'], $isDriver);

        if (! $this->hasUniqueSequences($stops)) {
            $n = count($stops);
            $msg = $isDriver
                ? "Urutan pengiriman harus berurutan dari 1 sampai {$n}."
                : "Urutan kunjungan harus berurutan dari 1 sampai {$n}.";
            return response()->json([
                'status' => 'error',
                'message' => $msg,
                'errors' => ['stops.*.sequence' => [$msg]],
            ], 422);
        }

        $existing = $this->findEditableSameDateRoute(auth()->id(), $validated['date']);

        if ($existing) {
            $added = $this->mergeStopsIntoRoute($existing, $stops);

            return response()->json([
                'status' => 'success',
                'message' => $added > 0
                    ? $added.' toko ditambahkan ke rute tanggal yang sama.'
                    : 'Toko sudah terdaftar pada rute tanggal yang sama.',
                'data' => ['id' => $existing->id],
            ]);
        }

        $route = Route::create([
            'user_id' => auth()->id(),
            'created_by' => auth()->id(),
            'name' => $validated['name'],
            'date' => $validated['date'],
            'notes' => $validated['notes'] ?? null,
            'status' => 'draft',
        ]);

        foreach ($stops as $stopData) {
            RouteStop::create([
                'route_id' => $route->id,
                'store_id' => $stopData['store_id'],
                'sequence' => $stopData['sequence'],
                'estimated_duration_minutes' => $stopData['estimated_duration_minutes'],
                'notes' => $stopData['notes'] ?? null,
                'status' => 'pending',
            ]);
        }

        $creator = auth()->user();
        $userRoleLabel = $creator->role_label;
        $stopCount = count($stops);
        ActivityNotificationService::notifyAdmins([
            'activity_type' => $isDriver ? 'delivery_route_created' : 'route_created',
            'title' => $isDriver
                ? "{$creator->name} ({$userRoleLabel}) membuat Rencana Pengiriman."
                : "{$creator->name} ({$userRoleLabel}) membuat Rencana Kunjungan.",
            'message' => $isDriver
                ? "{$creator->name} ({$userRoleLabel}) membuat Rencana Pengiriman {$route->name} dengan {$stopCount} tujuan."
                : "{$creator->name} ({$userRoleLabel}) membuat Rencana Kunjungan {$route->name} dengan {$stopCount} toko.",
            'actor_id' => (string) $creator->id,
            'actor_name' => $creator->name,
            'actor_role' => $userRoleLabel,
            'route_name' => $route->name,
            'info' => $isDriver
                ? "Rute {$route->name} • {$stopCount} tujuan"
                : "Rute {$route->name} • {$stopCount} toko",
            'url' => route('route.show', $route->id),
            'related_id' => (string) $route->id,
            'related_type' => 'Route',
            'activity_time' => now()->toIso8601String(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Rute berhasil dibuat.',
            'data' => ['id' => $route->id],
        ]);
    }

    public function show(string $id): View
    {
        $route = Route::with(['stops.store', 'user', 'stops.visit'])->findOrFail($id);

        $user = auth()->user();
        if (! $user->isAdmin() && ! $user->isSuperAdmin() && (string) $route->user_id !== (string) $user->id) {
            abort(403, 'Anda tidak memiliki akses ke rute ini.');
        }

        $query = Store::where('status', 'active');
        if ($route->user->hasRole('driver')) {
            $query->where('is_delivery_destination', true);
        } else {
            $query->where('sales_penanggung_jawab_id', $route->user_id);
        }
        $stores = $query->orderBy('name')->get();

        return view('route.show', compact('route', 'stores'));
    }

    public function map(string $id): View
    {
        $route = Route::with(['stops.store', 'stops.visit', 'user', 'gpsLocations'])->findOrFail($id);

        $user = auth()->user();
        if (! $user->isAdmin() && ! $user->isSuperAdmin() && (string) $route->user_id !== (string) $user->id) {
            abort(403, 'Anda tidak memiliki akses ke rute ini.');
        }

        $routeDate = $route->date ? $route->date->toDateString() : null;

        $attendance = $routeDate ? Attendance::where('user_id', $route->user_id)
            ->whereDate('date', $routeDate)
            ->where('status', '!=', 'canceled')
            ->first() : null;

        return view('route.map', compact('route', 'attendance'));
    }

    public function start(string $id): JsonResponse
    {
        $user = auth()->user();
        $route = Route::where('user_id', $user->id)->findOrFail($id);

        if ($route->status !== 'draft') {
            return response()->json(['status' => 'error', 'message' => 'Rute sudah dimulai.'], 422);
        }

        // Validasi Presensi Hari Ini sebelum Mulai Rute (Sales & Driver)
        if ($user->hasRole('sales')) {
            $today = now()->toDateString();
            $attendance = \App\Models\Attendance::where('user_id', $user->id)
                ->whereDate('date', $today)
                ->first();

            if (! $attendance || ! $attendance->clock_in) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda belum melakukan Check In Presensi hari ini. Silakan lakukan presensi terlebih dahulu sebelum memulai rute.',
                ], 422);
            }

            if ($attendance->status === \App\Models\Attendance::STATUS_IZIN) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Presensi hari ini tercatat sebagai Izin sehingga rute tidak dapat dimulai.',
                ], 422);
            }

            if ($attendance->status === \App\Models\Attendance::STATUS_SAKIT) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Presensi hari ini tercatat sebagai Sakit sehingga rute tidak dapat dimulai.',
                ], 422);
            }
        } elseif ($user->hasRole('driver')) {
            $today = now()->toDateString();
            $attendance = \App\Models\Attendance::where('user_id', $user->id)
                ->whereDate('date', $today)
                ->first();

            if (! $attendance || ! $attendance->clock_in) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda belum melakukan Check In presensi hari ini. Silakan lakukan presensi terlebih dahulu sebelum memulai rute pengiriman.',
                ], 422);
            }

            if ($attendance->status === \App\Models\Attendance::STATUS_IZIN) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Presensi hari ini tercatat sebagai Izin sehingga rute pengiriman tidak dapat dimulai.',
                ], 422);
            }

            if ($attendance->status === \App\Models\Attendance::STATUS_SAKIT) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Presensi hari ini tercatat sebagai Sakit sehingga rute pengiriman tidak dapat dimulai.',
                ], 422);
            }
        }

        $now = now();
        $route->update([
            'status' => 'active',
            'started_at' => $now,
        ]);

        $userRoleLabel = $user->role_label;
        $isDriver = $user->hasRole('driver');
        $actTerm = $isDriver ? 'Rencana Pengiriman' : 'Rencana Kunjungan';
        ActivityNotificationService::notifyAdmins([
            'activity_type' => 'route_started',
            'title' => "{$user->name} ({$userRoleLabel}) memulai {$actTerm}.",
            'message' => "{$user->name} ({$userRoleLabel}) memulai {$actTerm} {$route->name} pada {$now->format('H:i')}.",
            'actor_id' => (string) $user->id,
            'actor_name' => $user->name,
            'actor_role' => $userRoleLabel,
            'route_name' => $route->name,
            'info' => "{$route->name} • " . $now->format('H.i'),
            'url' => route('route.show', $route->id),
            'related_id' => (string) $route->id,
            'related_type' => 'Route',
            'activity_time' => $now->toIso8601String(),
        ]);

        return response()->json(['status' => 'success', 'message' => 'Rute dimulai.']);
    }

    public function complete(string $id): JsonResponse
    {
        $route = Route::where('user_id', auth()->id())->findOrFail($id);

        if ($route->status !== 'active') {
            return response()->json(['status' => 'error', 'message' => 'Rute tidak dalam status aktif.'], 422);
        }

        $route->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        return response()->json(['status' => 'success', 'message' => 'Rute selesai.']);
    }

    public function addStop(Request $request, string $id): JsonResponse
    {
        $user = auth()->user();

        $query = Route::query();
        if (! $user->isAdmin() && ! $user->isSuperAdmin()) {
            $query->where('user_id', $user->id);
        }

        $route = $query->findOrFail($id);

        if ($route->status === 'completed') {
            return response()->json([
                'status' => 'error',
                'message' => 'Rute yang sudah selesai tidak dapat ditambahkan kunjungan baru.',
            ], 422);
        }

        if ($route->status === 'cancelled') {
            return response()->json([
                'status' => 'error',
                'message' => 'Rute yang telah dibatalkan tidak dapat ditambahkan kunjungan.',
            ], 422);
        }

        $isDriverOwner = $route->user->hasRole('driver');
        $validated = $request->validate([
            'store_id' => ['required', 'string', 'exists:stores,id'],
            'estimated_duration_minutes' => $isDriverOwner ? ['nullable', 'integer', 'min:1', 'max:480'] : ['required', 'integer', 'min:1', 'max:480'],
            'notes' => ['nullable', 'string', 'max:255'],
        ], $isDriverOwner ? [
            'store_id.required' => 'Toko wajib dipilih.',
            'store_id.exists' => 'Toko yang dipilih tidak valid.',
        ] : [
            'store_id.required' => 'Toko wajib dipilih.',
            'store_id.exists' => 'Toko yang dipilih tidak valid.',
            'estimated_duration_minutes.required' => 'Estimasi durasi wajib diisi.',
            'estimated_duration_minutes.integer' => 'Estimasi durasi harus berupa angka dalam menit.',
            'estimated_duration_minutes.min' => 'Estimasi durasi minimal 1 menit.',
            'estimated_duration_minutes.max' => 'Estimasi durasi maksimal 480 menit.',
        ]);

        $store = Store::where('status', 'active')->where('id', $validated['store_id'])->first();
        if (! $store) {
            return response()->json([
                'status' => 'error',
                'message' => 'Toko yang dipilih tidak aktif atau tidak ditemukan.',
            ], 422);
        }

        // Server-side validation based on target role (BR-03, BR-06)
        if ($isDriverOwner) {
            if (! $store->is_delivery_destination) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Toko ini bukan tujuan pengiriman yang valid.',
                ], 422);
            }
        } else {
            if ((string) $store->sales_penanggung_jawab_id !== (string) $route->user_id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Toko ini berada di luar jangkauan Sales penanggung jawab rute.',
                ], 422);
            }
        }

        if ($route->stops()->where('store_id', $validated['store_id'])->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Toko ini sudah ada dalam rute.',
            ], 422);
        }

        $nextSequence = ($route->stops()->max('sequence') ?? 0) + 1;

        $stop = RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $validated['store_id'],
            'sequence' => $nextSequence,
            'estimated_duration_minutes' => $isDriverOwner ? null : ($validated['estimated_duration_minutes'] ?? null),
            'notes' => $validated['notes'] ?? null,
            'status' => 'pending',
        ]);

        $route->load('stops.visit');
        $route->syncStatus();

        if ($user->hasAnyRole(['sales', 'driver'])) {
            $userRoleLabel = $user->role_label;
            $storeName = $store->name;
            $routeName = $route->name;
            $cityName = $store->city ?? $store->kecamatan ?? '';
            $info = ($routeName ? "{$routeName}" : '') . ($cityName ? " • {$cityName}" : '') . " • " . now()->format('H.i');

            ActivityNotificationService::notifyAdmins([
                'activity_type' => 'route_stop_added',
                'title' => $isDriverOwner
                    ? "{$user->name} ({$userRoleLabel}) menambahkan tujuan pengiriman ke {$storeName}."
                    : "{$user->name} ({$userRoleLabel}) menambahkan kunjungan ke {$storeName}.",
                'message' => $isDriverOwner
                    ? "{$user->name} ({$userRoleLabel}) menambahkan tujuan pengiriman baru ke {$storeName} pada rute {$routeName}."
                    : "{$user->name} ({$userRoleLabel}) menambahkan kunjungan baru ke toko {$storeName} pada rute {$routeName}.",
                'actor_id' => (string) $user->id,
                'actor_name' => $user->name,
                'actor_role' => $userRoleLabel,
                'store_name' => $storeName,
                'route_name' => $routeName,
                'info' => $info,
                'url' => route('route.show', $route->id),
                'related_id' => (string) $stop->id,
                'related_type' => 'RouteStop',
                'activity_time' => now()->toIso8601String(),
            ]);
        } elseif ($user->hasAnyRole(['admin', 'super-admin']) && (string) $route->user_id !== (string) $user->id) {
            $actorRoleLabel = $user->hasRole('super-admin') ? 'Super Admin' : 'Admin';
            $storeName = $store->name;
            $routeName = $route->name;
            $cityName = $store->city ?? $store->kecamatan ?? '';
            $info = ($routeName ? "{$routeName}" : '') . ($cityName ? " • {$cityName}" : '') . " • " . now()->format('H.i');

            ActivityNotificationService::notifyUser($route->user_id, [
                'activity_type' => 'admin_stop_added',
                'title' => $isDriverOwner
                    ? 'Pengiriman Ditambahkan'
                    : 'Kunjungan Ditambahkan',
                'message' => $isDriverOwner
                    ? "Pengiriman baru telah ditambahkan ke route Anda."
                    : "Kunjungan baru telah ditambahkan ke route Anda.",
                'actor_id' => (string) $user->id,
                'actor_name' => $user->name,
                'actor_role' => $actorRoleLabel,
                'store_name' => $storeName,
                'route_name' => $routeName,
                'info' => $info,
                'url' => route('route.show', $route->id),
                'related_id' => (string) $stop->id,
                'related_type' => 'RouteStop',
                'activity_time' => now()->toIso8601String(),
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => $isDriverOwner ? 'Tujuan pengiriman berhasil ditambahkan ke rute.' : 'Toko kunjungan berhasil ditambahkan ke rute.',
            'data' => [
                'stop' => $stop->load('store'),
            ],
        ]);
    }

    public function updateStop(Request $request, string $routeId, string $stopId): JsonResponse
    {
        $user = auth()->user();
        $query = Route::query();
        if (! $user->isAdmin() && ! $user->isSuperAdmin()) {
            $query->where('user_id', $user->id);
        }
        $route = $query->where('id', $routeId)->firstOrFail();

        if ($route->status === 'cancelled') {
            return response()->json([
                'status' => 'error',
                'message' => 'Rute telah dibatalkan.',
            ], 422);
        }

        $stop = $route->stops()->where('id', $stopId)->firstOrFail();

        $oldStatus = $stop->status;
        $isDriver = $route->user->hasRole('driver');

        $rules = [
            'status' => ['required', 'in:pending,visited,skipped'],
            'notes' => ['nullable', 'string', 'max:500'],
            'photo' => ['nullable', 'string'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'address' => ['nullable', 'string', 'max:500'],
            'maps_url' => ['nullable', 'string', 'max:500'],
        ];

        $messages = [];

        // Validation for skipping (Sales & Driver)
        if ($request->input('status') === 'skipped') {
            if ($route->status === 'draft') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Rute belum dimulai. Silakan klik Mulai Rute terlebih dahulu sebelum melewati ' . ($isDriver ? 'pengiriman.' : 'kunjungan.'),
                ], 422);
            }

            // Sequential check: tidak boleh skip jika stop sebelumnya belum selesai/skipped
            $priorPendingStop = RouteStop::with('store')
                ->where('route_id', $route->id)
                ->where('sequence', '<', $stop->sequence)
                ->where('status', '!=', 'skipped')
                ->whereDoesntHave('visit', function ($q) {
                    $q->where('status', 'completed');
                })
                ->orderBy('sequence')
                ->first();

            if ($priorPendingStop) {
                $priorStoreName = $priorPendingStop->store?->name ?? 'Toko';
                $term = $isDriver ? 'pengiriman' : 'kunjungan';
                return response()->json([
                    'status' => 'error',
                    'message' => "Pengiriman/kunjungan belum dapat dilewati. Selesaikan atau lewati {$term} #{$priorPendingStop->sequence} — {$priorStoreName} terlebih dahulu.",
                ], 422);
            }

            $rules['notes'] = ['required', 'string', 'min:3', 'max:500'];
            $rules['photo'] = ['required', 'string'];
            $messages['notes.required'] = 'Alasan / catatan wajib diisi untuk melewati ' . ($isDriver ? 'pengiriman.' : 'kunjungan.');
            $messages['notes.min'] = 'Alasan / catatan minimal 3 karakter.';
            $messages['photo.required'] = 'Foto bukti wajib diisi untuk melewati ' . ($isDriver ? 'pengiriman.' : 'kunjungan.');
        }

        $validated = $request->validate($rules, $messages);

        if ($validated['status'] === 'skipped' && ! empty($validated['photo'])) {
            // Save photo for skipped visit/delivery
            $photoPath = $this->savePhoto($validated['photo'], 'skip-evidence', auth()->id());
            
            // Create or update visit as skipped record so proof is preserved
            $visit = \App\Models\Visit::firstOrNew([
                'route_stop_id' => $stop->id,
                'user_id' => $route->user_id,
            ]);

            $visit->route_id = $route->id;
            $visit->store_id = $stop->store_id;
            $visit->status = 'completed'; // Mark visit as finalized/skipped
            $visit->initial_notes = $validated['notes'];
            $visit->visit_result = 'Dilewati: ' . $validated['notes'];
            $visit->final_store_photo = $photoPath;

            // Geolocation and skip timestamp
            $skipNow = now();
            if ($request->filled('latitude')) {
                $visit->check_in_lat = $validated['latitude'];
                $visit->check_out_lat = $validated['latitude'];
            }
            if ($request->filled('longitude')) {
                $visit->check_in_lng = $validated['longitude'];
                $visit->check_out_lng = $validated['longitude'];
            }
            if ($request->filled('address')) {
                $visit->check_in_address = $validated['address'];
                $visit->check_out_address = $validated['address'];
            }
            if ($request->filled('maps_url')) {
                $visit->check_in_maps_url = $validated['maps_url'];
                $visit->check_out_maps_url = $validated['maps_url'];
            }

            if (! $visit->check_in_at) {
                $visit->check_in_at = $skipNow;
            }
            if (! $visit->check_out_at) {
                $visit->check_out_at = $skipNow;
            }
            $visit->save();
        }

        $stop->update([
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? $stop->notes,
        ]);

        $route->load('stops.visit');
        $route->syncStatus();

        if ($user->hasAnyRole(['admin', 'super-admin']) && (string) $route->user_id !== (string) $user->id) {
            $actorRoleLabel = $user->hasRole('super-admin') ? 'Super Admin' : 'Admin';
            $storeName = $stop->store?->name ?? 'Toko';
            $routeName = $route->name;
            $cityName = $stop->store?->city ?? $stop->store?->kecamatan ?? '';
            $info = ($routeName ? "{$routeName}" : '') . ($cityName ? " • {$cityName}" : '') . " • " . now()->format('H.i');

            ActivityNotificationService::notifyUser($route->user_id, [
                'activity_type' => 'admin_stop_updated',
                'title' => $isDriver
                    ? 'Pengiriman Diubah'
                    : 'Kunjungan Diubah',
                'message' => $isDriver
                    ? "Data pengiriman pada route Anda telah diperbarui oleh {$actorRoleLabel}."
                    : "Data kunjungan pada route Anda telah diperbarui oleh {$actorRoleLabel}.",
                'actor_id' => (string) $user->id,
                'actor_name' => $user->name,
                'actor_role' => $actorRoleLabel,
                'store_name' => $storeName,
                'route_name' => $routeName,
                'info' => $info,
                'url' => route('route.show', $route->id),
                'related_id' => (string) $stop->id,
                'related_type' => 'RouteStop',
                'activity_time' => now()->toIso8601String(),
            ]);
        } elseif ($oldStatus !== 'skipped' && $validated['status'] === 'skipped') {
            $userRoleLabel = $user->role_label;
            $storeName = $stop->store?->name ?? 'Toko';
            $routeName = $route->name;
            $cityName = $stop->store?->city ?? $stop->store?->kecamatan ?? '';
            $info = ($routeName ? "{$routeName}" : '') . ($cityName ? " • {$cityName}" : '') . " • " . now()->format('H.i');

            $notes = $validated['notes'] ?? null;
            ActivityNotificationService::notifyAdmins([
                'activity_type' => 'route_stop_skipped',
                'title' => "{$user->name} ({$userRoleLabel}) melewati toko {$storeName}.",
                'message' => "{$user->name} ({$userRoleLabel}) melewati toko {$storeName} pada rute {$routeName}." . ($notes ? " Alasan: {$notes}" : ''),
                'actor_id' => (string) $user->id,
                'actor_name' => $user->name,
                'actor_role' => $userRoleLabel,
                'store_name' => $storeName,
                'route_name' => $routeName,
                'info' => $info,
                'url' => route('route.show', $route->id),
                'related_id' => (string) $stop->id,
                'related_type' => 'RouteStop',
                'activity_time' => now()->toIso8601String(),
            ]);
        }

        return response()->json(['status' => 'success', 'message' => 'Status toko diperbarui.']);
    }

    public function updateSequence(Request $request, string $id): JsonResponse
    {
        $route = Route::where('user_id', auth()->id())->findOrFail($id);

        if ($route->status !== 'draft') {
            return response()->json(['status' => 'error', 'message' => 'Rute sudah dimulai dan tidak dapat diedit.'], 422);
        }

        $isDriverOwner = $route->user->hasRole('driver');

        $rules = [
            'stops' => ['required', 'array', 'min:1'],
            'stops.*.store_id' => ['required', 'string', 'exists:stores,id'],
            'stops.*.sequence' => ['required', 'integer', 'min:1'],
            'stops.*.notes' => ['nullable', 'string', 'max:255'],
        ];
        $messages = [
            'stops.required' => 'Minimal satu toko harus dipilih.',
            'stops.min' => 'Minimal satu toko harus dipilih.',
            'stops.*.store_id.required' => 'Toko wajib dipilih.',
            'stops.*.sequence.required' => $isDriverOwner ? 'Urutan pengiriman wajib diisi.' : 'Urutan kunjungan wajib diisi.',
            'stops.*.sequence.integer' => $isDriverOwner ? 'Urutan pengiriman harus berupa angka.' : 'Urutan kunjungan harus berupa angka.',
            'stops.*.sequence.min' => $isDriverOwner ? 'Urutan pengiriman minimal 1.' : 'Urutan kunjungan minimal 1.',
        ];

        if ($isDriverOwner) {
            $rules['stops.*.estimated_duration_minutes'] = ['nullable', 'integer', 'min:1', 'max:480'];
        } else {
            $rules['stops.*.estimated_duration_minutes'] = ['required', 'integer', 'min:1', 'max:480'];
            $messages['stops.*.estimated_duration_minutes.required'] = 'Estimasi durasi wajib diisi.';
            $messages['stops.*.estimated_duration_minutes.integer'] = 'Estimasi durasi harus berupa angka dalam menit.';
            $messages['stops.*.estimated_duration_minutes.min'] = 'Estimasi durasi minimal 1 menit.';
            $messages['stops.*.estimated_duration_minutes.max'] = 'Estimasi durasi maksimal 480 menit.';
        }

        $request->validate($rules, $messages);

        if ($route->stops()->whereHas('visit')->exists()) {
            return response()->json(['status' => 'error', 'message' => 'Rute tidak dapat diubah karena sudah ada kunjungan pada toko tertentu.'], 422);
        }

        $stops = $request->input('stops');
        $stops = $this->normalizeStopsForStorage($stops, $isDriverOwner);

        // Server-side validation for updateSequence
        $storeIds = collect($stops)->pluck('store_id')->unique();
        if ($isDriverOwner) {
            $invalidCount = Store::whereIn('id', $storeIds)
                ->where(function ($q) {
                    $q->where('status', '!=', 'active')
                        ->orWhere('is_delivery_destination', false);
                })
                ->count();

            if ($invalidCount > 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Toko yang dipilih tidak aktif atau bukan tujuan pengiriman yang valid.',
                ], 422);
            }
        } else {
            $invalidCount = Store::whereIn('id', $storeIds)
                ->where(function ($q) use ($route) {
                    $q->where('status', '!=', 'active')
                        ->orWhere('sales_penanggung_jawab_id', '!=', $route->user_id)
                        ->orWhereNull('sales_penanggung_jawab_id');
                })
                ->count();

            if ($invalidCount > 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Toko yang dipilih tidak aktif atau berada di luar jangkauan Sales Anda.',
                ], 422);
            }
        }

        if (! $this->hasUniqueSequences($stops)) {
            $n = count($stops);
            $msg = $isDriverOwner
                ? "Urutan pengiriman harus berurutan dari 1 sampai {$n}."
                : "Urutan kunjungan harus berurutan dari 1 sampai {$n}.";
            return response()->json([
                'status' => 'error',
                'message' => $msg,
                'errors' => ['stops.*.sequence' => [$msg]],
            ], 422);
        }

        DB::transaction(function () use ($route, $stops) {
            $route->stops()->delete();

            foreach ($stops as $stopData) {
                RouteStop::create([
                    'route_id' => $route->id,
                    'store_id' => $stopData['store_id'],
                    'sequence' => $stopData['sequence'],
                    'estimated_duration_minutes' => $stopData['estimated_duration_minutes'],
                    'notes' => $stopData['notes'] ?? null,
                    'status' => 'pending',
                ]);
            }
        });

        return response()->json(['status' => 'success', 'message' => 'Rute kunjungan berhasil diperbarui.']);
    }

    public function destroy(string $id): JsonResponse
    {
        $route = Route::where('user_id', auth()->id())->findOrFail($id);

        if (! in_array($route->status, ['draft', 'cancelled'])) {
            return response()->json(['status' => 'error', 'message' => 'Rute aktif tidak dapat dihapus.'], 422);
        }

        $user = auth()->user();
        $salesName = $user->name;
        $routeName = $route->name;
        $userRoleLabel = ucfirst($user->roles->first()?->name ?? 'Pengguna');

        $deleted = $route->delete();

        if ($deleted) {
            ActivityNotificationService::notifyAdmins([
                'activity_type' => 'admin_route_deleted',
                'title' => "{$salesName} menghapus rute.",
                'message' => "{$salesName} menghapus rute {$routeName}.",
                'actor_id' => (string) $user->id,
                'actor_name' => $salesName,
                'actor_role' => $userRoleLabel,
                'route_name' => $routeName,
                'info' => "Rute {$routeName} • Dihapus",
                'url' => route('route.index'),
                'related_id' => '',
                'related_type' => 'Route',
                'activity_time' => now()->toIso8601String(),
            ]);
        }

        return response()->json(['status' => 'success', 'message' => 'Rute dihapus.']);
    }

    // ---- Route History ----

    public function history(Request $request): View|RedirectResponse
    {
        $user = auth()->user();

        if ($user->isAdmin() || $user->isSuperAdmin()) {
            return redirect()->route('admin.routes.report');
        }

        $query = Route::with(['stops.store', 'stops.visit'])
            ->where('user_id', $user->id)
            ->orderBy('date', 'desc');

        if ($request->filled('from_date')) {
            $query->where('date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->where('date', '<=', $request->to_date);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $routes = $query->paginate(15)->withQueryString();

        $totalRoutes = Route::where('user_id', $user->id)->count();
        $completedRoutes = Route::with(['stops.visit'])
            ->where('user_id', $user->id)
            ->get()
            ->filter(fn ($route) => $route->computed_status === 'completed')
            ->count();
        $totalStoresVisited = RouteStop::whereHas('route', fn ($q) => $q->where('user_id', $user->id))
            ->where('status', 'visited')->count();
        $routesThisMonth = Route::where('user_id', $user->id)
            ->whereMonth('date', now()->month)
            ->whereYear('date', now()->year)
            ->count();

        return view('route.history', compact(
            'routes', 'totalRoutes', 'completedRoutes',
            'totalStoresVisited', 'routesThisMonth'
        ));
    }

    // ---- GPS Tracking ----

    public function storeGpsLocation(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'route_id' => ['required', 'string', 'exists:routes,id'],
            'route_stop_id' => ['nullable', 'string', 'exists:route_stops,id'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'altitude' => ['nullable', 'numeric'],
            'accuracy' => ['nullable', 'numeric', 'min:0'],
            'speed' => ['nullable', 'numeric', 'min:0'],
            'recorded_at' => ['nullable', 'date'],
        ]);

        $route = Route::where('user_id', auth()->id())->findOrFail($validated['route_id']);

        if ($route->status !== 'active') {
            return response()->json(['status' => 'error', 'message' => 'Rute tidak dalam status aktif.'], 422);
        }

        GpsLocation::create([
            'route_id' => $validated['route_id'],
            'route_stop_id' => $validated['route_stop_id'] ?? null,
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'altitude' => $validated['altitude'] ?? null,
            'accuracy' => $validated['accuracy'] ?? null,
            'speed' => $validated['speed'] ?? null,
            'recorded_at' => $validated['recorded_at'] ?? now(),
        ]);

        return response()->json(['status' => 'success', 'message' => 'Lokasi GPS tercatat.']);
    }

    // ---- Admin Monitoring ----

    public function adminIndex(Request $request): View
    {
        $today = now()->toDateString();
        $date = $request->filled('date') ? $request->date : $today;

        $users = $this->salesUsers();
        $salesUserIds = $users->pluck('id');

        $applyCommonFilters = function ($query) use ($request, $salesUserIds) {
            $query->whereIn('user_id', $salesUserIds);

            if ($request->filled('user_id')) {
                $query->where('user_id', $request->user_id);
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            return $query;
        };

        $routes = Route::with(['user', 'creator', 'stops.store', 'stops.visit']);
        $applyCommonFilters($routes);
        $routes->whereDate('date', $date);
        $routes = $routes->orderBy('date', 'desc')->paginate(10)->withQueryString();
        $this->attachProgress($routes->getCollection());

        $upcomingRoutes = Route::with(['user', 'creator', 'stops', 'stops.visit']);
        $applyCommonFilters($upcomingRoutes);
        $upcomingRoutes->whereDate('date', '>=', $today)
            ->whereNotIn('status', ['completed', 'cancelled']);
        $upcomingRoutes = $upcomingRoutes->orderBy('date')->take(10)->get();
        $this->attachProgress($upcomingRoutes);

        $statsQuery = Route::query()->whereIn('user_id', $salesUserIds);
        if ($request->filled('user_id')) {
            $statsQuery->where('user_id', $request->user_id);
        }
        $statsQuery->whereDate('date', $date);

        $stats = [
            'total' => (clone $statsQuery)->count(),
            'active' => (clone $statsQuery)->where('status', 'active')->count(),
            'completed' => (clone $statsQuery)->where('status', 'completed')->count(),
        ];

        return view('route.admin', compact('routes', 'users', 'stats', 'upcomingRoutes'));
    }

    public function adminDriverIndex(Request $request): View
    {
        $today = now()->toDateString();
        $date = $request->filled('date') ? $request->date : $today;

        $users = $this->driverUsers();
        $driverUserIds = $users->pluck('id');

        $applyCommonFilters = function ($query) use ($request, $driverUserIds) {
            $query->whereIn('user_id', $driverUserIds);

            if ($request->filled('user_id')) {
                $query->where('user_id', $request->user_id);
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            return $query;
        };

        $routes = Route::with(['user', 'creator', 'stops.store', 'stops.visit']);
        $applyCommonFilters($routes);
        $routes->whereDate('date', $date);
        $routes = $routes->orderBy('date', 'desc')->paginate(10)->withQueryString();
        $this->attachProgress($routes->getCollection());

        $upcomingRoutes = Route::with(['user', 'creator', 'stops', 'stops.visit']);
        $applyCommonFilters($upcomingRoutes);
        $upcomingRoutes->whereDate('date', '>=', $today)->whereNotIn('status', ['completed', 'cancelled']);
        $upcomingRoutes = $upcomingRoutes->orderBy('date')->take(10)->get();
        $this->attachProgress($upcomingRoutes);

        $statsQuery = Route::query()->whereIn('user_id', $driverUserIds);
        if ($request->filled('user_id')) {
            $statsQuery->where('user_id', $request->user_id);
        }
        $statsQuery->whereDate('date', $date);

        $stats = [
            'total' => (clone $statsQuery)->count(),
            'active' => (clone $statsQuery)->where('status', 'active')->count(),
            'completed' => (clone $statsQuery)->where('status', 'completed')->count(),
        ];

        return view('route.admin-driver', compact('routes', 'users', 'stats', 'upcomingRoutes'));
    }

    public function adminDriverCreate(): View
    {
        $stores = Store::where('status', 'active')
            ->where('is_delivery_destination', true)
            ->orderBy('name')
            ->get();
        $salesUsers = $this->driverUsers();

        $isDriverRoute = true;
        return view('route.admin-create', compact('stores', 'salesUsers', 'isDriverRoute'));
    }

    public function adminDriverStore(Request $request): JsonResponse
    {
        $request->merge(['driver_route' => true]);
        return $this->adminStore($request);
    }

    public function adminDriverEdit(string $id): View
    {
        $route = Route::with(['stops.store', 'user'])->findOrFail($id);
        abort_unless($this->isDriverUser((string) $route->user_id), 404);
        $stores = Store::where('status', 'active')
            ->where('is_delivery_destination', true)
            ->orderBy('name')
            ->get();
        $salesUsers = $this->driverUsers();
        return view('route.admin-edit', compact('route', 'stores', 'salesUsers'));
    }

    public function adminDriverUpdate(Request $request, string $id): JsonResponse
    {
        $request->merge(['driver_route' => true]);
        return $this->adminUpdate($request, $id);
    }

    public function adminDriverDestroy(string $id): JsonResponse
    {
        abort_unless($this->isDriverUser((string) Route::findOrFail($id)->user_id), 404);
        return $this->adminDestroy($id);
    }

    public function adminDriverReport(Request $request): View
    {
        return $this->adminDriverIndex($request);
    }

    private function attachProgress($routes): void
    {
        foreach ($routes as $route) {
            $stops = $route->stops ?? collect();
            $route->progress = [
                'total' => $stops->count(),
                'selesai' => $stops->filter(function ($s) {
                    return $s->status !== 'skipped' && $s->visit && $s->visit->check_in_at && $s->visit->check_out_at;
                })->count(),
                'berjalan' => $stops->filter(function ($s) {
                    return $s->status !== 'skipped' && $s->visit && $s->visit->check_in_at && ! $s->visit->check_out_at;
                })->count(),
                'dilewati' => $stops->filter(fn ($s) => $s->status === 'skipped')->count(),
                'belum' => $stops->filter(function ($s) {
                    return $s->status !== 'skipped' && (! $s->visit || ! $s->visit->check_in_at);
                })->count(),
            ];
        }
    }

    // ---- Admin Reporting ----

    public function report(Request $request): View
    {
        $period = $request->get('period', 'this_week');
        $fromDate = null;
        $toDate = null;

        switch ($period) {
            case 'today':
                $fromDate = now()->toDateString();
                $toDate = now()->toDateString();
                break;
            case 'yesterday':
                $fromDate = now()->subDay()->toDateString();
                $toDate = now()->subDay()->toDateString();
                break;
            case 'this_week':
                $fromDate = now()->startOfWeek()->toDateString();
                $toDate = now()->endOfWeek()->toDateString();
                break;
            case 'last_week':
                $fromDate = now()->subWeek()->startOfWeek()->toDateString();
                $toDate = now()->subWeek()->endOfWeek()->toDateString();
                break;
            case 'this_month':
                $fromDate = now()->startOfMonth()->toDateString();
                $toDate = now()->endOfMonth()->toDateString();
                break;
            case 'last_month':
                $fromDate = now()->subMonth()->startOfMonth()->toDateString();
                $toDate = now()->subMonth()->endOfMonth()->toDateString();
                break;
            case 'custom':
                $fromDate = $request->get('from_date');
                $toDate = $request->get('to_date');
                break;
        }

        $query = Route::with(['user', 'stops', 'stops.visit']);
        if ($fromDate && $toDate) {
            $query->whereBetween('date', [$fromDate, $toDate]);
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $routes = $query->get();

        $salesUsers = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['sales', 'field-supervisor', 'driver']))
            ->orderBy('name')->get();

        $totalRoutes = $routes->count();
        $completedRoutes = $routes->filter(fn ($r) => $r->computed_status === 'completed')->count();
        $totalStoresVisited = RouteStop::whereIn('route_id', $routes->pluck('id'))
            ->where('status', 'visited')->count();
        $activeSales = $routes->filter(fn ($r) => $r->computed_status === 'active')->pluck('user_id')->unique()->count();

        $stats = [
            'total_routes' => $totalRoutes,
            'completion_rate' => $totalRoutes > 0 ? round(($completedRoutes / $totalRoutes) * 100) : 0,
            'total_stores_visited' => $totalStoresVisited,
            'active_sales' => $activeSales,
        ];

        $perSales = [];
        foreach ($salesUsers as $user) {
            $userRoutes = $routes->where('user_id', $user->id);
            $userCompleted = $userRoutes->filter(fn ($r) => $r->computed_status === 'completed')->count();
            $userStores = RouteStop::whereIn('route_id', $userRoutes->pluck('id'))->where('status', 'visited')->count();
            $perSales[] = [
                'name' => $user->name,
                'total_routes' => $userRoutes->count(),
                'completed_routes' => $userCompleted,
                'total_stores' => $userStores,
                'avg_stores' => $userRoutes->count() > 0 ? $userStores / $userRoutes->count() : 0,
                'completion_percent' => $userRoutes->count() > 0 ? round(($userCompleted / $userRoutes->count()) * 100) : 0,
            ];
        }

        $dailyStats = [];
        $currentDate = Carbon::parse($fromDate);
        $endDate = Carbon::parse($toDate);
        while ($currentDate->lte($endDate)) {
            $dateStr = $currentDate->toDateString();
            $dayRoutes = $routes->where('date', $dateStr);
            $dailyStats[] = [
                'date' => $dateStr,
                'total' => $dayRoutes->count(),
                'completed' => $dayRoutes->filter(fn ($r) => $r->computed_status === 'completed')->count(),
                'active' => $dayRoutes->filter(fn ($r) => $r->computed_status === 'active')->count(),
            ];
            $currentDate->addDay();
        }
        $maxDailyRoutes = max(collect($dailyStats)->max('total') ?: 1, 1);

        return view('route.report', compact(
            'stats', 'perSales', 'dailyStats', 'maxDailyRoutes', 'salesUsers'
        ));
    }

    // ---- Admin Route Management (CRUD) ----

    public function adminCreate(): View
    {
        $stores = Store::where('status', 'active')->with('salesPenanggungJawab')->orderBy('name')->get();
        $salesUsers = $this->salesUsers();

        return view('route.admin-create', compact('stores', 'salesUsers'));
    }

    public function adminStore(Request $request): JsonResponse
    {
        $isDriverRoute = $request->boolean('driver_route');

        $storeRules = [
            'user_id' => ['required', 'exists:users,id'],
            'name' => ['required', 'string', 'max:200'],
            'date' => ['required', 'date', 'after_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:500'],
            'stops' => ['required', 'array', 'min:1'],
            'stops.*.store_id' => ['required', 'string', 'exists:stores,id'],
            'stops.*.sequence' => ['required', 'integer', 'min:1'],
            'stops.*.notes' => ['nullable', 'string', 'max:255'],
        ];
        $storeMessages = $this->routeValidationMessages();

        if ($isDriverRoute) {
            $storeRules['stops.*.estimated_duration_minutes'] = ['nullable', 'integer', 'min:1', 'max:480'];
        } else {
            $storeRules['stops.*.estimated_duration_minutes'] = ['required', 'integer', 'min:1', 'max:480'];
            $storeMessages['stops.*.estimated_duration_minutes.required'] = 'Estimasi durasi wajib diisi.';
        }

        $validated = $request->validate($storeRules, $storeMessages);

        $isValidUser = $isDriverRoute
            ? $this->isDriverUser($validated['user_id'])
            : $this->isSalesUser($validated['user_id']);

        if (! $isValidUser) {
            return response()->json([
                'status' => 'error',
                'message' => $isDriverRoute ? 'Driver yang dipilih tidak valid.' : 'Sales yang dipilih tidak valid.',
            ], 422);
        }

        $stops = $this->normalizeStopsForStorage($validated['stops'], $isDriverRoute);

        // Server-side validation for adminStore (BR-03, BR-06)
        $storeIds = collect($stops)->pluck('store_id')->unique();
        if ($isDriverRoute) {
            $invalidCount = Store::whereIn('id', $storeIds)
                ->where(function ($q) {
                    $q->where('status', '!=', 'active')
                        ->orWhere('is_delivery_destination', false);
                })
                ->count();

            if ($invalidCount > 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Toko yang dipilih tidak aktif atau bukan tujuan pengiriman yang valid.',
                ], 422);
            }
        } else {
            $invalidCount = Store::whereIn('id', $storeIds)
                ->where(function ($q) use ($validated) {
                    $q->where('status', '!=', 'active')
                        ->orWhere('sales_penanggung_jawab_id', '!=', $validated['user_id'])
                        ->orWhereNull('sales_penanggung_jawab_id');
                })
                ->count();

            if ($invalidCount > 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Toko yang dipilih tidak aktif atau berada di luar jangkauan Sales yang dipilih.',
                ], 422);
            }
        }

        if (! $this->hasUniqueSequences($stops)) {
            $n = count($stops);
            $msg = $isDriverRoute
                ? "Urutan pengiriman harus berurutan dari 1 sampai {$n}."
                : "Urutan kunjungan harus berurutan dari 1 sampai {$n}.";
            return response()->json([
                'status' => 'error',
                'message' => $msg,
                'errors' => ['stops.*.sequence' => [$msg]],
            ], 422);
        }

        $existing = $this->findEditableSameDateRoute($validated['user_id'], $validated['date']);

        if ($existing) {
            $added = $this->mergeStopsIntoRoute($existing, $stops);

            return response()->json([
                'status' => 'success',
                'message' => $added > 0
                    ? $added.' toko baru ditambahkan ke rute tanggal yang sama.'
                    : 'Toko sudah terdaftar pada rute tanggal yang sama.',
                'data' => ['id' => $existing->id],
            ]);
        }

        $route = Route::create([
            'user_id' => $validated['user_id'],
            'created_by' => auth()->id(),
            'name' => $validated['name'],
            'date' => $validated['date'],
            'notes' => $validated['notes'] ?? null,
            'status' => 'draft',
        ]);

        $this->createStops($route->id, $stops);

        $creator = auth()->user();
        $actorRoleLabel = $creator->hasRole('super-admin') ? 'Super Admin' : 'Admin';
        $stopCount = count($stops);

        ActivityNotificationService::notifyUser($validated['user_id'], [
            'activity_type' => 'admin_route_created',
            'title' => $isDriverRoute ? 'Route Pengiriman Baru' : 'Route Kunjungan Baru',
            'message' => $isDriverRoute
                ? "Route pengiriman baru telah dibuat untuk Anda."
                : "Route kunjungan baru telah dibuat untuk Anda.",
            'actor_id' => (string) $creator->id,
            'actor_name' => $creator->name,
            'actor_role' => $actorRoleLabel,
            'route_name' => $route->name,
            'info' => $isDriverRoute ? "Rute Pengiriman {$route->name} • {$stopCount} tujuan" : "Rute {$route->name} • {$stopCount} toko",
            'url' => route('route.show', $route->id),
            'related_id' => (string) $route->id,
            'related_type' => 'Route',
            'activity_time' => now()->toIso8601String(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Rute berhasil dibuat untuk '.$route->user->name.'.',
            'data' => ['id' => $route->id],
        ]);
    }

    public function adminEdit(string $id): View
    {
        $route = Route::with(['stops.store', 'user'])->findOrFail($id);
        $stores = Store::where('status', 'active')->with('salesPenanggungJawab')->orderBy('name')->get();
        $salesUsers = $this->salesUsers();

        return view('route.admin-edit', compact('route', 'stores', 'salesUsers'));
    }

    public function adminUpdate(Request $request, string $id): JsonResponse
    {
        $route = Route::with(['stops.visit', 'user'])->findOrFail($id);

        if ($route->status !== 'draft') {
            return response()->json(['status' => 'error', 'message' => 'Rute yang sudah berjalan atau selesai tidak dapat diubah.'], 422);
        }

        if ($route->stops()->whereHas('visit')->exists()) {
            return response()->json(['status' => 'error', 'message' => 'Rute tidak dapat diubah karena sudah ada kunjungan pada toko tertentu.'], 422);
        }

        $isDriverRoute = $request->boolean('driver_route');

        // Owner rute wajib terkunci pada user pemilik rute existing (Sales / Driver)
        $ownerUserId = (string) $route->user_id;

        $storeRules = [
            'user_id' => ['nullable'],
            'name' => ['required', 'string', 'max:200'],
            'date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
            'stops' => ['required', 'array', 'min:1'],
            'stops.*.store_id' => ['required', 'string', 'exists:stores,id'],
            'stops.*.sequence' => ['required', 'integer', 'min:1'],
            'stops.*.notes' => ['nullable', 'string', 'max:255'],
        ];
        $storeMessages = $this->routeValidationMessages();

        if ($isDriverRoute) {
            $storeRules['stops.*.estimated_duration_minutes'] = ['nullable', 'integer', 'min:1', 'max:480'];
        } else {
            $storeRules['stops.*.estimated_duration_minutes'] = ['required', 'integer', 'min:1', 'max:480'];
            $storeMessages['stops.*.estimated_duration_minutes.required'] = 'Estimasi durasi wajib diisi.';
        }

        $validated = $request->validate($storeRules, $storeMessages);

        $isValidOwner = $isDriverRoute
            ? $this->isDriverUser($ownerUserId)
            : $this->isSalesUser($ownerUserId);

        if (! $isValidOwner) {
            return response()->json(['status' => 'error', 'message' => $isDriverRoute ? 'Driver pemilik rute tidak valid.' : 'Sales penanggung jawab rute tidak valid.'], 422);
        }

        $stops = $this->normalizeStopsForStorage($validated['stops'], $isDriverRoute);

        // Server-side validation for adminUpdate (BR-03, BR-06)
        $storeIds = collect($stops)->pluck('store_id')->unique();
        if ($isDriverRoute) {
            $invalidCount = Store::whereIn('id', $storeIds)
                ->where(function ($q) {
                    $q->where('status', '!=', 'active')
                        ->orWhere('is_delivery_destination', false);
                })
                ->count();

            if ($invalidCount > 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Toko yang dipilih tidak aktif atau bukan tujuan pengiriman yang valid.',
                ], 422);
            }
        } else {
            $invalidCount = Store::whereIn('id', $storeIds)
                ->where(function ($q) use ($ownerUserId) {
                    $q->where('status', '!=', 'active')
                        ->orWhere('sales_penanggung_jawab_id', '!=', $ownerUserId)
                        ->orWhereNull('sales_penanggung_jawab_id');
                })
                ->count();

            if ($invalidCount > 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Toko yang dipilih tidak aktif atau berada di luar jangkauan Sales yang dipilih.',
                ], 422);
            }
        }

        if (! $this->hasUniqueSequences($stops)) {
            $n = count($stops);
            $msg = $isDriverRoute
                ? "Urutan pengiriman harus berurutan dari 1 sampai {$n}."
                : "Urutan kunjungan harus berurutan dari 1 sampai {$n}.";
            return response()->json([
                'status' => 'error',
                'message' => $msg,
                'errors' => ['stops.*.sequence' => [$msg]],
            ], 422);
        }

        DB::transaction(function () use ($route, $validated, $stops, $ownerUserId) {
            $route->update([
                'user_id' => $ownerUserId,
                'name' => $validated['name'],
                'date' => $validated['date'],
                'notes' => $validated['notes'] ?? null,
            ]);

            $route->stops()->delete();
            $this->createStops($route->id, $stops);
        });

        $creator = auth()->user();
        $actorRoleLabel = $creator->hasRole('super-admin') ? 'Super Admin' : 'Admin';
        $stopCount = count($stops);

        ActivityNotificationService::notifyUser($ownerUserId, [
            'activity_type' => 'admin_route_updated',
            'title' => $isDriverRoute ? 'Route Pengiriman Diubah' : 'Route Kunjungan Diubah',
            'message' => $isDriverRoute
                ? "Route pengiriman Anda telah diperbarui oleh {$actorRoleLabel}."
                : "Route kunjungan Anda telah diperbarui oleh {$actorRoleLabel}.",
            'actor_id' => (string) $creator->id,
            'actor_name' => $creator->name,
            'actor_role' => $actorRoleLabel,
            'route_name' => $route->name,
            'info' => $isDriverRoute ? "Rute Pengiriman {$route->name} • {$stopCount} tujuan" : "Rute {$route->name} • {$stopCount} toko",
            'url' => route('route.show', $route->id),
            'related_id' => (string) $route->id,
            'related_type' => 'Route',
            'activity_time' => now()->toIso8601String(),
        ]);

        return response()->json(['status' => 'success', 'message' => 'Rute berhasil diperbarui.']);
    }

    public function adminDestroy(string $id): JsonResponse
    {
        $route = Route::with(['stops.visit'])->find($id);

        if (! $route) {
            return response()->json(['status' => 'success', 'message' => 'Rencana kunjungan berhasil dihapus.']);
        }

        if (! in_array($route->status, ['draft', 'cancelled'])) {
            return response()->json(['status' => 'error', 'message' => 'Rute aktif atau selesai tidak dapat dihapus.'], 422);
        }

        if ($route->stops()->whereHas('visit')->exists()) {
            return response()->json(['status' => 'error', 'message' => 'Rute tidak dapat dihapus karena sudah memiliki data kunjungan.'], 422);
        }

        $creator = auth()->user();
        $actorRoleLabel = $creator->hasRole('super-admin') ? 'Super Admin' : 'Admin';
        $recipient = $route->user;
        $routeName = $route->name;
        $routeId = $route->id;

        DB::transaction(function () use ($route, $recipient, $creator, $actorRoleLabel, $routeName, $routeId) {
            $isDriver = $recipient && $recipient->hasRole('driver');
            if ($recipient && $recipient->hasAnyRole(['sales', 'driver']) && (string) $recipient->id !== (string) $creator->id) {
                ActivityNotificationService::notifyUser($recipient, [
                    'activity_type' => 'admin_route_deleted',
                    'title' => $isDriver ? 'Route Pengiriman Dihapus' : 'Route Kunjungan Dihapus',
                    'message' => $isDriver
                        ? "Route pengiriman {$routeName} telah dihapus oleh {$actorRoleLabel}."
                        : "Route kunjungan {$routeName} telah dihapus oleh {$actorRoleLabel}.",
                    'actor_id' => (string) $creator->id,
                    'actor_name' => $creator->name,
                    'actor_role' => $actorRoleLabel,
                    'route_name' => $routeName,
                    'info' => $isDriver ? "Rute Pengiriman {$routeName} • Dihapus" : "Rute {$routeName} • Dihapus",
                    'url' => route('route.index'),
                    'related_id' => (string) $routeId,
                    'related_type' => 'Route',
                    'activity_time' => now()->toIso8601String(),
                ]);
            }

            $route->delete();
        });

        return response()->json(['status' => 'success', 'message' => 'Rencana kunjungan berhasil dihapus.']);
    }

    private function salesUsers()
    {
        return User::whereHas('roles', fn ($q) => $q->where('name', 'sales'))
            ->where('status', 'active')
            ->orderBy('name')
            ->get();
    }

    private function driverUsers()
    {
        return User::whereHas('roles', fn ($q) => $q->where('name', 'driver'))
            ->where('status', 'active')
            ->orderBy('name')
            ->get();
    }

    private function isSalesUser(string $userId): bool
    {
        return User::where('id', $userId)
            ->whereHas('roles', fn ($q) => $q->where('name', 'sales'))
            ->exists();
    }

    private function isDriverUser(string $userId): bool
    {
        return User::where('id', $userId)
            ->whereHas('roles', fn ($q) => $q->where('name', 'driver'))
            ->exists();
    }

    private function routeValidationMessages(): array
    {
        return [
            'stops.required' => 'Minimal satu toko harus dipilih.',
            'stops.min' => 'Minimal satu toko harus dipilih.',
            'stops.*.store_id.required' => 'Toko wajib dipilih.',
            'stops.*.sequence.required' => 'Urutan kunjungan wajib diisi.',
            'stops.*.sequence.integer' => 'Urutan kunjungan harus berupa angka.',
            'stops.*.sequence.min' => 'Urutan kunjungan minimal 1.',
            'stops.*.estimated_duration_minutes.required' => 'Estimasi durasi wajib diisi.',
            'stops.*.estimated_duration_minutes.integer' => 'Estimasi durasi harus berupa angka dalam menit.',
            'stops.*.estimated_duration_minutes.min' => 'Estimasi durasi minimal 1 menit.',
            'stops.*.estimated_duration_minutes.max' => 'Estimasi durasi maksimal 480 menit.',
        ];
    }

    private function hasUniqueSequences(array $stops): bool
    {
        $sequences = array_column($stops, 'sequence');
        $n = count($stops);
        sort($sequences);

        return $sequences === range(1, $n);
    }

    private function normalizeStopsForStorage(array $stops, bool $isDriver): array
    {
        return array_map(function ($stop) use ($isDriver) {
            return [
                'store_id' => $stop['store_id'],
                'sequence' => $stop['sequence'],
                'estimated_duration_minutes' => $isDriver ? null : ($stop['estimated_duration_minutes'] ?? null),
                'notes' => $stop['notes'] ?? null,
            ];
        }, $stops);
    }

    private function createStops(string $routeId, array $stops): void
    {
        foreach ($stops as $stopData) {
            RouteStop::create([
                'route_id' => $routeId,
                'store_id' => $stopData['store_id'],
                'sequence' => $stopData['sequence'],
                'estimated_duration_minutes' => $stopData['estimated_duration_minutes'] ?? null,
                'notes' => $stopData['notes'] ?? null,
                'status' => 'pending',
            ]);
        }
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

        $filename = "visits/{$userId}/{$prefix}_".now()->format('Ymd_His').'_'.\Illuminate\Support\Str::random(6).'.jpg';
        \Illuminate\Support\Facades\Storage::disk('public')->put($filename, $decoded);

        return $filename;
    }

    /**
     * Find an existing route for the same sales + date that can still be edited
     * (draft/cancelled and has no visits yet). When found, new stores should be
     * added to it instead of creating a duplicate route for the same date.
     */
    private function findEditableSameDateRoute(int|string $userId, string $date): ?Route
    {
        return Route::with('stops')
            ->where('user_id', $userId)
            ->whereDate('date', $date)
            ->whereIn('status', ['draft', 'cancelled'])
            ->whereDoesntHave('stops.visit')
            ->orderBy('created_at')
            ->first();
    }

    /**
     * Append the given stops to an existing route, skipping stores that are
     * already part of it. Returns the number of stores actually added.
     */
    private function mergeStopsIntoRoute(Route $route, array $stops): int
    {
        $nextSequence = $route->stops->max('sequence') ?? 0;
        $existingStoreIds = $route->stops->pluck('store_id');
        $added = 0;

        foreach ($stops as $stopData) {
            if ($existingStoreIds->contains($stopData['store_id'])) {
                continue;
            }

            $nextSequence++;
            RouteStop::create([
                'route_id' => $route->id,
                'store_id' => $stopData['store_id'],
                'sequence' => $nextSequence,
                'estimated_duration_minutes' => $stopData['estimated_duration_minutes'],
                'notes' => $stopData['notes'] ?? null,
                'status' => 'pending',
            ]);
            $added++;
        }

        return $added;
    }
}
