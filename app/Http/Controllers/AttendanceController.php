<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\User;
use App\Services\ActivityNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class AttendanceController extends Controller
{
    public function index(): View|RedirectResponse
    {
        $user = auth()->user();

        if ($user->isSuperAdmin()) {
            return redirect()->route('admin.attendance.index');
        }

        $today = Attendance::where('user_id', $user->id)
            ->whereDate('date', now()->toDateString())
            ->first();

        $history = Attendance::where('user_id', $user->id)
            ->whereMonth('date', now()->month)
            ->whereYear('date', now()->year)
            ->orderBy('date', 'desc')
            ->take(31)
            ->get();

        $stats = [
            'total' => $history->count(),
            'present' => $history->where('status', 'present')->count(),
            'late' => $history->where('status', 'late')->count(),
        ];

        return view('attendance.index', compact('today', 'history', 'stats'));
    }

    public function checkIn(Request $request): JsonResponse
    {
        $user = auth()->user();
        $date = now()->toDateString();

        if ($user->isSuperAdmin()) {
            abort(403, 'Super Admin tidak dapat melakukan presensi.');
        }

        try {
            $request->validate([
                'latitude' => ['required', 'numeric', 'between:-90,90'],
                'longitude' => ['required', 'numeric', 'between:-180,180'],
                'address' => ['required', 'string', 'max:500'],
                'maps_url' => ['required', 'string', 'max:500'],
                'selfie' => ['required', 'string'],
                'notes' => ['nullable', 'string', 'max:1000'],
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }

        $clockInTime = now();
        $status = $this->determineStatus($clockInTime);

        $selfiePath = null;

        try {
            $selfiePath = $this->saveSelfie($request->input('selfie'), 'check-in', $user->id, $date);

            $attendance = DB::transaction(function () use ($user, $date, $clockInTime, $status, $request, $selfiePath) {
                $existing = Attendance::where('user_id', $user->id)
                    ->whereDate('date', $date)
                    ->lockForUpdate()
                    ->first();

                if ($existing && $existing->clock_in) {
                    abort(409, 'Anda sudah melakukan check-in hari ini.');
                }

                $payload = [
                    'clock_in' => $clockInTime,
                    'clock_in_lat' => $request->input('latitude'),
                    'clock_in_lng' => $request->input('longitude'),
                    'clock_in_address' => $request->input('address'),
                    'clock_in_maps_url' => $request->input('maps_url'),
                    'clock_in_selfie' => $selfiePath,
                    'status' => $status,
                    'absence_note' => null,
                    'notes' => $request->filled('notes') ? trim($request->input('notes')) : null,
                ];

                if ($existing) {
                    if ($existing->is_canceled && ! $request->filled('notes')) {
                        $payload['notes'] = null;
                    }

                    $existing->update($payload);

                    return $existing;
                }

                return Attendance::create(array_merge($payload, [
                    'user_id' => $user->id,
                    'date' => $date,
                ]));
            });

            $userRoleLabel = $user->role_label;
            ActivityNotificationService::notifyAdmins([
                'activity_type' => 'attendance_check_in',
                'title' => "{$user->name} ({$userRoleLabel}) melakukan Check-In Presensi.",
                'message' => "{$user->name} ({$userRoleLabel}) berhasil melakukan Check-In Presensi pada {$clockInTime->format('H:i')}.",
                'actor_id' => (string) $user->id,
                'actor_name' => $user->name,
                'actor_role' => $userRoleLabel,
                'info' => "{$userRoleLabel} • " . $clockInTime->format('H.i'),
                'url' => route('admin.attendance.index', ['search' => $user->name, 'date' => $date]),
                'related_id' => (string) $attendance->id,
                'related_type' => 'Attendance',
                'activity_time' => $clockInTime->toIso8601String(),
            ]);
        } catch (\Throwable $e) {
            if ($selfiePath) {
                Storage::disk('public')->delete($selfiePath);
            }

            return $this->errorResponse($e);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Check-in berhasil pada '.$clockInTime->format('H:i'),
            'data' => [
                'id' => $attendance->id,
                'clock_in' => $attendance->clock_in->format('H:i:s'),
                'date' => $attendance->date->format('Y-m-d'),
                'status' => 'checked_in',
                'selfie_url' => Storage::url($selfiePath),
            ],
        ]);
    }

    public function checkOut(Request $request): JsonResponse
    {
        $user = auth()->user();
        $date = now()->toDateString();

        if ($user->isSuperAdmin()) {
            abort(403, 'Super Admin tidak dapat melakukan presensi.');
        }

        try {
            $request->validate([
                'latitude' => ['required', 'numeric', 'between:-90,90'],
                'longitude' => ['required', 'numeric', 'between:-180,180'],
                'address' => ['required', 'string', 'max:500'],
                'maps_url' => ['required', 'string', 'max:500'],
                'selfie' => ['required', 'string'],
                'notes' => ['nullable', 'string', 'max:1000'],
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }

        $selfiePath = null;

        try {
            $selfiePath = $this->saveSelfie($request->input('selfie'), 'check-out', $user->id, $date);

            $attendance = DB::transaction(function () use ($user, $date, $request, $selfiePath) {
                $attendance = Attendance::where('user_id', $user->id)
                    ->whereDate('date', $date)
                    ->lockForUpdate()
                    ->first();

                if (! $attendance) {
                    abort(409, 'Anda belum melakukan check-in hari ini. Silakan check-in terlebih dahulu.');
                }

                if ($attendance->isAbsence()) {
                    $statusLabel = $attendance->status === 'izin' ? 'Izin' : 'Sakit';
                    abort(409, "Presensi dengan status {$statusLabel} tidak memerlukan Check-Out.");
                }

                if (! $attendance->clock_in) {
                    abort(409, 'Anda belum melakukan check-in hari ini. Silakan check-in terlebih dahulu.');
                }

                if ($attendance->clock_out) {
                    abort(409, 'Anda sudah melakukan check-out hari ini.');
                }

                $checkoutPayload = [
                    'clock_out' => now(),
                    'clock_out_lat' => $request->input('latitude'),
                    'clock_out_lng' => $request->input('longitude'),
                    'clock_out_address' => $request->input('address'),
                    'clock_out_maps_url' => $request->input('maps_url'),
                    'clock_out_selfie' => $selfiePath,
                    'status' => $attendance->status === 'late' ? 'late' : 'present',
                ];

                if ($request->filled('notes')) {
                    $newNotes = trim($request->input('notes'));
                    if ($attendance->notes) {
                        $checkoutPayload['notes'] = $attendance->notes . ' | Check-out: ' . $newNotes;
                    } else {
                        $checkoutPayload['notes'] = $newNotes;
                    }
                }

                $attendance->update($checkoutPayload);

                return $attendance;
            });

            $clockOutTime = $attendance->clock_out;
            $userRoleLabel = $user->role_label;
            ActivityNotificationService::notifyAdmins([
                'activity_type' => 'attendance_check_out',
                'title' => "{$user->name} ({$userRoleLabel}) melakukan Check-Out Presensi.",
                'message' => "{$user->name} ({$userRoleLabel}) berhasil melakukan Check-Out Presensi pada {$clockOutTime->format('H:i')}.",
                'actor_id' => (string) $user->id,
                'actor_name' => $user->name,
                'actor_role' => $userRoleLabel,
                'info' => "{$userRoleLabel} • " . $clockOutTime->format('H.i'),
                'url' => route('admin.attendance.index', ['search' => $user->name, 'date' => $date]),
                'related_id' => (string) $attendance->id,
                'related_type' => 'Attendance',
                'activity_time' => $clockOutTime->toIso8601String(),
            ]);
        } catch (\Throwable $e) {
            if ($selfiePath) {
                Storage::disk('public')->delete($selfiePath);
            }

            return $this->errorResponse($e);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Check-out berhasil pada '.$attendance->clock_out->format('H:i'),
            'data' => [
                'id' => $attendance->id,
                'clock_out' => $attendance->clock_out->format('H:i:s'),
                'date' => $attendance->date->format('Y-m-d'),
                'status' => 'completed',
                'selfie_url' => Storage::url($selfiePath),
            ],
        ]);
    }

    public function history(): View|RedirectResponse
    {
        if (auth()->user()->isSuperAdmin()) {
            return redirect()->route('admin.attendance.index');
        }

        $records = Attendance::where('user_id', auth()->id())
            ->orderBy('date', 'desc')
            ->paginate(31);

        return view('attendance.history', compact('records'));
    }

    public function status(): JsonResponse
    {
        if (auth()->user()->isSuperAdmin()) {
            abort(403, 'Super Admin tidak memiliki presensi pribadi.');
        }

        $today = Attendance::where('user_id', auth()->id())
            ->whereDate('date', now()->toDateString())
            ->first();

        return response()->json([
            'checked_in' => $today && $today->clock_in,
            'checked_out' => $today && $today->clock_out,
            'clock_in_time' => $today?->clock_in?->format('H:i:s'),
            'clock_out_time' => $today?->clock_out?->format('H:i:s'),
            'status' => $today?->status ?? 'absent',
            'is_absence' => $today ? $today->isAbsence() : false,
        ]);
    }

    public function submitAbsence(Request $request): JsonResponse
    {
        $user = auth()->user();
        $date = now()->toDateString();

        if ($user->isSuperAdmin()) {
            abort(403, 'Super Admin tidak dapat melakukan presensi.');
        }

        try {
            $request->validate([
                'type' => ['required', 'in:izin,sakit'],
                'absence_note' => ['required', 'string', 'min:3', 'max:1000'],
                'selfie' => ['required', 'string'],
                'latitude' => ['required', 'numeric', 'between:-90,90'],
                'longitude' => ['required', 'numeric', 'between:-180,180'],
                'address' => ['required', 'string', 'max:500'],
                'maps_url' => ['required', 'string', 'max:500'],
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }

        $type = $request->input('type');
        $selfiePath = null;

        try {
            $selfiePath = $this->saveSelfie($request->input('selfie'), $type, $user->id, $date);

            $attendance = DB::transaction(function () use ($user, $date, $type, $request, $selfiePath) {
                $existing = Attendance::where('user_id', $user->id)
                    ->whereDate('date', $date)
                    ->lockForUpdate()
                    ->first();

                if ($existing && ($existing->clock_in || $existing->isAbsence())) {
                    abort(409, 'Anda sudah melakukan presensi hari ini.');
                }

                $payload = [
                    'status' => $type,
                    'absence_note' => trim($request->input('absence_note')),
                    'clock_in_selfie' => $selfiePath,
                    'clock_in_lat' => $request->input('latitude'),
                    'clock_in_lng' => $request->input('longitude'),
                    'clock_in_address' => $request->input('address'),
                    'clock_in_maps_url' => $request->input('maps_url'),
                ];

                if ($existing) {
                    if ($existing->is_canceled) {
                        $payload['notes'] = null;
                    }
                    $existing->update($payload);
                    return $existing;
                }

                return Attendance::create(array_merge($payload, [
                    'user_id' => $user->id,
                    'date' => $date,
                ]));
            });

            $submittedAt = now();
            $userRoleLabel = $user->role_label;
            $typeLabel = $type === 'izin' ? 'Izin' : 'Sakit';

            ActivityNotificationService::notifyAdmins([
                'activity_type' => 'attendance_' . $type,
                'title' => "{$user->name} ({$userRoleLabel}) mengajukan Presensi {$typeLabel}.",
                'message' => "{$user->name} ({$userRoleLabel}) mengajukan Presensi {$typeLabel} pada {$submittedAt->format('H:i')}.",
                'actor_id' => (string) $user->id,
                'actor_name' => $user->name,
                'actor_role' => $userRoleLabel,
                'info' => "{$userRoleLabel} • {$typeLabel} • " . $submittedAt->format('H.i'),
                'url' => route('admin.attendance.index', ['search' => $user->name, 'date' => $date]),
                'related_id' => (string) $attendance->id,
                'related_type' => 'Attendance',
                'activity_time' => $submittedAt->toIso8601String(),
            ]);
        } catch (\Throwable $e) {
            if ($selfiePath) {
                Storage::disk('public')->delete($selfiePath);
            }

            return $this->errorResponse($e);
        }

        $typeLabel = $type === 'izin' ? 'Izin' : 'Sakit';

        return response()->json([
            'status' => 'success',
            'message' => "Presensi {$typeLabel} berhasil dikirim.",
            'data' => [
                'id' => $attendance->id,
                'date' => $attendance->date->format('Y-m-d'),
                'status' => $type,
                'photo_url' => Storage::url($selfiePath),
            ],
        ]);
    }

    // ---- Admin Monitoring ----

    public function adminIndex(Request $request): View
    {
        $period = $request->get('period', 'today');
        $fromDate = null;
        $toDate = null;

        switch ($period) {
            case 'today':
                $fromDate = now()->toDateString();
                $toDate = now()->toDateString();
                break;
            case 'date':
                $fromDate = $request->get('date') ?: now()->toDateString();
                $toDate = $fromDate;
                break;
            case 'week':
                $fromDate = now()->startOfWeek()->toDateString();
                $toDate = now()->endOfWeek()->toDateString();
                break;
            case 'month':
                $fromDate = now()->startOfMonth()->toDateString();
                $toDate = now()->endOfMonth()->toDateString();
                break;
            case 'custom':
                $fromDate = $request->get('from_date') ?: now()->toDateString();
                $toDate = $request->get('to_date') ?: now()->toDateString();
                break;
            default:
                $fromDate = now()->toDateString();
                $toDate = now()->toDateString();
        }

        if ($fromDate > $toDate) {
            [$fromDate, $toDate] = [$toDate, $fromDate];
        }

        // 1. Determine scoped roles (Sales, Driver, Staff) - Admin & Super Admin excluded
        $allowedRoles = ['sales', 'driver', 'staff', 'field-supervisor'];
        $roleFilter = $request->get('role');

        if ($roleFilter && in_array($roleFilter, $allowedRoles, true)) {
            $scopedRoles = [$roleFilter];
        } else {
            $scopedRoles = $allowedRoles;
        }

        // 2. Fetch scoped users (base query)
        $usersQuery = User::with('roles')
            ->whereHas('roles', fn ($q) => $q->whereIn('name', $scopedRoles))
            ->where('status', 'active');

        if ($request->filled('search')) {
            $search = trim($request->search);
            $usersQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%");
            });
        }

        $scopedUsers = $usersQuery->orderBy('name')->get();
        $scopedUserIds = $scopedUsers->pluck('id');

        // 3. Fetch attendance records for scoped users within the date range
        $attendancesQuery = Attendance::with(['user.roles'])
            ->whereIn('user_id', $scopedUserIds)
            ->whereDate('date', '>=', $fromDate)
            ->whereDate('date', '<=', $toDate)
            ->orderBy('date', 'desc')
            ->orderBy('clock_in', 'desc');

        $allAttendances = $attendancesQuery->get();

        // 4. Calculate accurate, role-filtered statistics
        $totalUsersCount = $scopedUsers->count();
        $activeAttendances = $allAttendances->where('status', '!=', 'canceled');

        $absenceStatuses = \App\Models\Attendance::ABSENCE_STATUSES;

        $presentUserIds = $activeAttendances->pluck('user_id')->unique();
        $presentCount = $presentUserIds->count();

        $checkedInCount = $activeAttendances
            ->filter(fn ($a) => $a->clock_in !== null && $a->clock_out === null && ! $a->isAbsence())
            ->pluck('user_id')
            ->unique()
            ->count();

        $checkedOutCount = $activeAttendances
            ->filter(fn ($a) => $a->clock_out !== null)
            ->pluck('user_id')
            ->unique()
            ->count();

        $izinCount = $activeAttendances->filter(fn ($a) => $a->status === 'izin')->count();
        $sakitCount = $activeAttendances->filter(fn ($a) => $a->status === 'sakit')->count();

        $canceledCount = $allAttendances->where('status', 'canceled')->count();
        $notPresentCount = max(0, $totalUsersCount - $presentCount);
        $percentage = $totalUsersCount > 0 ? round(($presentCount / $totalUsersCount) * 100) : 0;

        $stats = [
            'total_users' => $totalUsersCount,
            'present' => $presentCount,
            'total' => $presentCount,
            'checked_in' => $checkedInCount,
            'checked_out' => $checkedOutCount,
            'izin' => $izinCount,
            'sakit' => $sakitCount,
            'not_present' => $notPresentCount,
            'canceled' => $canceledCount,
            'percentage' => $percentage,
        ];

        // 5. Build combined list for table monitoring (Present + Belum Presensi)
        $statusFilter = $request->get('status');
        $rows = collect();

        if ($fromDate === $toDate) {
            // Single date monitoring (today or specific date): every scoped user has an entry
            $attendanceByUser = $allAttendances->keyBy('user_id');

            foreach ($scopedUsers as $u) {
                $att = $attendanceByUser->get($u->id);

                if ($att) {
                    $rows->push($att);
                } else {
                    $virtual = new Attendance([
                        'user_id' => $u->id,
                        'date' => $fromDate,
                        'status' => 'not_present',
                    ]);
                    $virtual->setRelation('user', $u);
                    $virtual->exists = false;
                    $rows->push($virtual);
                }
            }
        } else {
            // Range monitoring: all existing attendance records
            $attendancesGroupedByUser = $allAttendances->groupBy('user_id');

            foreach ($allAttendances as $att) {
                $rows->push($att);
            }

            // Also include scoped users who have NO attendance at all during the range
            foreach ($scopedUsers as $u) {
                if (! $attendancesGroupedByUser->has($u->id)) {
                    $virtual = new Attendance([
                        'user_id' => $u->id,
                        'date' => $fromDate,
                        'status' => 'not_present',
                    ]);
                    $virtual->setRelation('user', $u);
                    $virtual->exists = false;
                    $rows->push($virtual);
                }
            }
        }

        // Apply status filter to table rows
        if ($statusFilter) {
            $rows = $rows->filter(function ($row) use ($statusFilter) {
                $st = $row->status_presensi;

                if ($statusFilter === 'checked_in') {
                    return $st === 'checked_in';
                }

                if ($statusFilter === 'checked_out') {
                    return $st === 'checked_out';
                }

                if ($statusFilter === 'canceled') {
                    return $st === 'canceled';
                }

                if ($statusFilter === 'izin') {
                    return $st === 'izin';
                }

                if ($statusFilter === 'sakit') {
                    return $st === 'sakit';
                }

                if (in_array($statusFilter, ['not_present', 'belum_presensi'], true)) {
                    return in_array($st, ['not_present', 'none'], true);
                }

                if ($statusFilter === 'hadir') {
                    return in_array($st, ['checked_in', 'checked_out'], true);
                }

                if (in_array($statusFilter, ['present', 'sudah_presensi'], true)) {
                    return in_array($st, ['checked_in', 'checked_out', 'izin', 'sakit'], true);
                }

                return true;
            });
        }

        // Sort rows: real attendances first (by date desc, clock_in desc), then virtual Belum Presensi (by name asc)
        $rows = $rows->sort(function ($a, $b) {
            $aPresent = $a->clock_in !== null || $a->isAbsence();
            $bPresent = $b->clock_in !== null || $b->isAbsence();

            if ($aPresent !== $bPresent) {
                return $bPresent <=> $aPresent;
            }

            if ($aPresent && $bPresent) {
                $dateA = $a->date?->format('Y-m-d') ?? '';
                $dateB = $b->date?->format('Y-m-d') ?? '';

                if ($dateA !== $dateB) {
                    return strcmp($dateB, $dateA);
                }

                $timeA = $a->clock_in?->format('H:i:s') ?? '';
                $timeB = $b->clock_in?->format('H:i:s') ?? '';

                return strcmp($timeB, $timeA);
            }

            return strcasecmp($a->user->name ?? '', $b->user->name ?? '');
        })->values();

        // Paginate rows
        $perPage = 10;
        $currentPage = \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPage();
        $currentItems = $rows->slice(($currentPage - 1) * $perPage, $perPage)->values();

        $attendances = new \Illuminate\Pagination\LengthAwarePaginator(
            $currentItems,
            $rows->count(),
            $perPage,
            $currentPage,
            [
                'path' => \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPath(),
                'query' => $request->query(),
            ]
        );

        $cancellerIds = collect($attendances->items())
            ->map(fn ($attendance) => $attendance->cancelled_meta['cancelled_by'] ?? null)
            ->filter()
            ->unique()
            ->values();

        $cancellers = User::whereIn('id', $cancellerIds)->get()->keyBy('id');
        $roles = Role::whereIn('name', ['sales', 'staff', 'driver'])->get();

        return view('attendance.admin', compact(
            'attendances', 'stats', 'period', 'fromDate', 'toDate', 'roles', 'cancellers'
        ));
    }

    public function cancel(Request $request, Attendance $attendance): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'min:3', 'max:1000'],
        ]);

        if ($attendance->is_canceled) {
            return back()->with('error', 'Presensi ini sudah dibatalkan sebelumnya.');
        }

        $reasonText = trim($data['reason']);
        $canceller = auth()->user();
        $actorRoleLabel = $canceller->hasRole('super-admin') ? 'Super Admin' : 'Admin';
        $attendanceDateFormatted = $attendance->date ? \Carbon\Carbon::parse($attendance->date)->translatedFormat('d F Y') : now()->translatedFormat('d F Y');
        $clockInFormatted = $attendance->clock_in ? \Carbon\Carbon::parse($attendance->clock_in)->format('H:i') : null;
        $previousStatus = $attendance->status;

        $statusTypeLabel = match ($previousStatus) {
            'izin' => 'Izin',
            'sakit' => 'Sakit',
            default => 'Hadir',
        };

        if ($previousStatus === 'izin') {
            $messageText = "Presensi Izin Anda pada {$attendanceDateFormatted} telah dibatalkan oleh {$actorRoleLabel}.";
        } elseif ($previousStatus === 'sakit') {
            $messageText = "Presensi Sakit Anda pada {$attendanceDateFormatted} telah dibatalkan oleh {$actorRoleLabel}.";
        } else {
            $messageText = "Presensi Hadir Anda pada {$attendanceDateFormatted}" . ($clockInFormatted ? " pukul {$clockInFormatted}" : '') . " telah dibatalkan oleh {$actorRoleLabel}.";
        }

        if ($reasonText !== '') {
            $messageText .= " Alasan: {$reasonText}";
        }

        $attendance->update([
            'status' => 'canceled',
            'clock_in' => null,
            'clock_out' => null,
            'notes' => json_encode([
                'cancelled_by' => auth()->id(),
                'cancelled_at' => now()->toDateTimeString(),
                'reason' => $reasonText,
                'previous_status' => $previousStatus,
            ], JSON_UNESCAPED_UNICODE),
        ]);

        ActivityNotificationService::notifyUser($attendance->user_id, [
            'activity_type' => 'attendance_canceled',
            'title' => "Presensi {$statusTypeLabel} dibatalkan",
            'message' => $messageText,
            'actor_id' => (string) $canceller->id,
            'actor_name' => $canceller->name,
            'actor_role' => $actorRoleLabel,
            'reason' => $reasonText,
            'date' => $attendanceDateFormatted,
            'info' => "Presensi • Dibatalkan",
            'url' => route('attendance.index'),
            'related_id' => (string) $attendance->id,
            'related_type' => 'Attendance',
            'activity_time' => now()->toIso8601String(),
        ]);

        return back()->with('success', 'Presensi berhasil dibatalkan.');
    }

    public function destroy(Attendance $attendance): RedirectResponse
    {
        try {
            foreach ([$attendance->clock_in_selfie, $attendance->clock_out_selfie] as $selfie) {
                if ($selfie) {
                    Storage::disk('public')->delete($selfie);
                }
            }

            $attendance->delete();
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal menghapus presensi. Data masih digunakan oleh sistem lain.');
        }

        return back()->with('success', 'Presensi berhasil dihapus.');
    }

    private function saveSelfie(string $base64Image, string $type, string $userId, string $date): string
    {
        $image = str_replace('data:image/jpeg;base64,', '', $base64Image);
        $image = str_replace('data:image/png;base64,', '', $image);
        $image = str_replace('data:image/webp;base64,', '', $image);
        $image = str_replace(' ', '+', $image);

        $decoded = base64_decode($image, true);

        if ($decoded === false) {
            throw new \RuntimeException('Data selfie tidak valid.');
        }

        $filename = "attendances/{$userId}/{$date}/{$type}_".now()->format('His').'_'.Str::random(6).'.jpg';
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

    private function errorResponse(\Throwable $e): JsonResponse
    {
        $status = 500;

        if ($e instanceof HttpExceptionInterface) {
            $status = $e->getStatusCode();
        }

        if ($status === 409) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 409);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Terjadi kesalahan saat menyimpan data. Silakan coba lagi.',
        ], 500);
    }

    private function determineStatus(Carbon $clockIn): string
    {
        $lateThreshold = config('attendance.late_threshold', '08:00');
        $deadline = Carbon::parse($clockIn->toDateString().' '.$lateThreshold);

        return $clockIn->gt($deadline) ? 'late' : 'present';
    }
}
