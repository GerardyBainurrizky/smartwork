<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Route as WorkRoute;
use App\Models\Visit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;

class NotificationController extends Controller
{
    /**
     * Display the notifications center page.
     */
    public function index(Request $request): View
    {
        $user = auth()->user();

        $query = $user->notifications();

        // Filter by Activity Category
        if ($request->filled('type')) {
            $type = $request->input('type');

            if ($type === 'attendance') {
                $query->whereIn('data->activity_type', [
                    'attendance_check_in',
                    'attendance_check_out',
                    'attendance_canceled',
                    'attendance_izin',
                    'attendance_sakit',
                ]);
            } elseif ($type === 'route_created') {
                $query->whereIn('data->activity_type', ['route_created', 'admin_route_created', 'delivery_route_created']);
            } elseif ($type === 'route_started') {
                $query->where('data->activity_type', 'route_started');
            } elseif ($type === 'route_stop_added') {
                $query->whereIn('data->activity_type', ['route_stop_added', 'admin_stop_added']);
            } elseif ($type === 'route_stop_skipped') {
                $query->where('data->activity_type', 'route_stop_skipped');
            } elseif ($type === 'route') {
                $query->whereIn('data->activity_type', [
                    'route_created',
                    'route_started',
                    'route_stop_added',
                    'route_stop_skipped',
                    'admin_route_created',
                    'admin_route_updated',
                    'admin_stop_added',
                    'admin_stop_updated',
                    'admin_route_deleted',
                    'delivery_route_created',
                ]);
            } elseif ($type === 'visit') {
                $query->whereIn('data->activity_type', [
                    'visit_check_in',
                    'visit_check_out',
                    'delivery_check_in',
                    'delivery_check_out',
                ]);
            } elseif ($type === 'store_submission') {
                $query->whereIn('data->activity_type', [
                    'store_submission_created',
                    'store_submission_approved',
                    'store_submission_rejected',
                ]);
            }
        }

        // Filter by Read Status
        if ($request->filled('status')) {
            $status = $request->input('status');

            if ($status === 'unread') {
                $query->whereNull('read_at');
            } elseif ($status === 'read') {
                $query->whereNotNull('read_at');
            }
        }

        $notifications = $query->latest()->paginate(10)->withQueryString();

        $unreadCount = $user->unreadNotifications()->count();

        return view('notifications.index', compact('notifications', 'unreadCount'));
    }

    /**
     * Get unread count and latest notifications for navbar dropdown via AJAX.
     */
    public function dropdown(): JsonResponse
    {
        $user = auth()->user();

        $unreadCount = $user->unreadNotifications()->count();
        $recentNotifications = $user->notifications()->latest()->take(7)->get();

        $items = $recentNotifications->map(function ($notification) use ($user) {
            $data = $notification->data;

            $readUrl = $user->hasAnyRole(['admin', 'super-admin'])
                ? route('admin.notifications.read', $notification->id)
                : route('notifications.read', $notification->id);

            return [
                'id' => $notification->id,
                'read' => $notification->read_at !== null,
                'title' => $data['title'] ?? 'Notifikasi Aktivitas',
                'message' => $data['message'] ?? '',
                'info' => $data['info'] ?? '',
                'activity_type' => $data['activity_type'] ?? 'default',
                'url' => $readUrl,
                'time_ago' => $notification->created_at->diffForHumans(),
            ];
        });

        return response()->json([
            'unread_count' => $unreadCount,
            'unread_label' => $unreadCount > 99 ? '99+' : (string) $unreadCount,
            'items' => $items,
        ]);
    }

    /**
     * Mark a single notification as read and redirect safely to target URL.
     */
    public function read(string $id): RedirectResponse
    {
        $user = auth()->user();

        /** @var DatabaseNotification $notification */
        $notification = $user->notifications()->findOrFail($id);

        if (is_null($notification->read_at)) {
            $notification->markAsRead();
        }

        $targetUrl = $this->resolveNotificationUrl($notification, $user);

        return redirect()->to($targetUrl);
    }

    /**
     * Resolve target URL safely based on user role, notification metadata, and entity existence.
     */
    protected function resolveNotificationUrl(DatabaseNotification $notification, $user): string
    {
        $data = is_array($notification->data) ? $notification->data : [];
        $activityType = (string) ($data['activity_type'] ?? '');
        $relatedType = (string) ($data['related_type'] ?? '');
        $relatedId = $data['related_id'] ?? null;
        $rawUrl = (string) ($data['url'] ?? '');

        $isAdminOrSuperAdmin = $user->hasAnyRole(['admin', 'super-admin']);
        $isStaff = $user->hasRole('staff') && ! $isAdminOrSuperAdmin;
        $isSales = $user->hasRole('sales') && ! $isAdminOrSuperAdmin;
        $isDriver = $user->hasRole('driver') && ! $isAdminOrSuperAdmin;

        // 1. Role STAFF handling
        if ($isStaff) {
            // Staff can access Attendance features
            if (str_starts_with($activityType, 'attendance') || $relatedType === 'Attendance' || str_contains($rawUrl, 'attendance')) {
                return route('attendance.index');
            }

            // Staff can access Staff Report features
            if (str_starts_with($activityType, 'report') || str_contains($rawUrl, 'laporan-presensi')) {
                return route('staff.report.index');
            }

            // Fallback for Staff: prevent accessing non-staff routes
            session()->flash('info', 'Detail notifikasi tidak ditemukan atau data sudah tidak tersedia.');
            return route('notifications.index');
        }

        // 2. Normalize raw URL relative to current application host/port
        $normalizedUrl = null;
        if (! empty($rawUrl)) {
            $parsed = parse_url($rawUrl);
            if (isset($parsed['path'])) {
                $path = $parsed['path'];
                $query = isset($parsed['query']) ? '?' . $parsed['query'] : '';
                $fragment = isset($parsed['fragment']) ? '#' . $parsed['fragment'] : '';
                $normalizedUrl = url($path . $query . $fragment);
            }
        }

        // 3. Role SALES & DRIVER safety checks
        if ($isSales || $isDriver) {
            // Prevent Sales / Driver from hitting /admin/* routes
            if ($normalizedUrl) {
                $path = parse_url($normalizedUrl, PHP_URL_PATH) ?? '';
                if (str_starts_with($path, '/admin/') || str_contains($path, '/admin/')) {
                    if (str_starts_with($activityType, 'attendance') || $relatedType === 'Attendance') {
                        return route('attendance.index');
                    }
                    if (str_starts_with($activityType, 'store_submission')) {
                        return route('stores.submissions.index');
                    }
                    if (str_starts_with($activityType, 'route') || $relatedType === 'Route') {
                        return route('route.index');
                    }
                    if (str_starts_with($activityType, 'visit') || str_starts_with($activityType, 'delivery') || $relatedType === 'Visit') {
                        return route('visit.index');
                    }
                    session()->flash('info', 'Detail notifikasi tidak ditemukan atau data sudah tidak tersedia.');
                    return route('notifications.index');
                }
            }

            // Check if Route entity exists before redirecting
            if (($relatedType === 'Route' || str_contains($activityType, 'route')) && $relatedId) {
                $routeExists = WorkRoute::where('id', $relatedId)->exists();
                if (! $routeExists) {
                    session()->flash('info', 'Rute yang dituju sudah tidak tersedia atau telah dihapus.');
                    return route('route.index');
                }
            }

            // Check if Visit entity exists before redirecting
            if (($relatedType === 'Visit' || str_contains($activityType, 'visit') || str_contains($activityType, 'delivery')) && $relatedId) {
                $visitExists = Visit::where('id', $relatedId)->exists();
                if (! $visitExists) {
                    session()->flash('info', 'Kunjungan yang dituju sudah tidak tersedia atau telah dihapus.');
                    return route('visit.index');
                }
            }
        }

        // 4. Role ADMIN & SUPER ADMIN handling
        if ($isAdminOrSuperAdmin) {
            if ($normalizedUrl) {
                return $normalizedUrl;
            }
            return route('admin.notifications.index');
        }

        if ($normalizedUrl) {
            return $normalizedUrl;
        }

        return route('notifications.index');
    }

    /**
     * Mark a single notification as read (AJAX / button) without redirecting away.
     */
    public function markRead(string $id): RedirectResponse|JsonResponse
    {
        $user = auth()->user();

        /** @var DatabaseNotification $notification */
        $notification = $user->notifications()->findOrFail($id);

        if (is_null($notification->read_at)) {
            $notification->markAsRead();
        }

        if (request()->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Notifikasi ditandai sudah dibaca.',
            ]);
        }

        return back()->with('success', 'Notifikasi berhasil ditandai sudah dibaca.');
    }

    /**
     * Delete a single notification owned by current user.
     */
    public function destroy(string $id): RedirectResponse|JsonResponse
    {
        $user = auth()->user();

        /** @var DatabaseNotification $notification */
        $notification = $user->notifications()->findOrFail($id);

        $notification->delete();

        if (request()->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Notifikasi berhasil dihapus.',
            ]);
        }

        return back()->with('success', 'Notifikasi berhasil dihapus.');
    }

    /**
     * Mark all unread notifications of current user as read.
     */
    public function markAllAsRead(): RedirectResponse|JsonResponse
    {
        $user = auth()->user();

        $user->unreadNotifications->markAsRead();

        if (request()->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Semua notifikasi berhasil ditandai sudah dibaca.',
            ]);
        }

        return back()->with('success', 'Semua notifikasi berhasil ditandai sudah dibaca.');
    }

    /**
     * Delete all notifications of currently logged in user (Admin, Super Admin, Sales, Staff, Driver).
     */
    public function destroyAll(): RedirectResponse|JsonResponse
    {
        $user = auth()->user();

        if (! $user->hasAnyRole(['super-admin', 'admin', 'sales', 'staff', 'driver'])) {
            abort(403, 'Anda tidak memiliki hak akses menghapus semua notifikasi.');
        }

        try {
            $user->notifications()->delete();

            if (request()->wantsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Semua notifikasi berhasil dihapus.',
                ]);
            }

            $redirectRoute = $user->hasAnyRole(['admin', 'super-admin'])
                ? route('admin.notifications.index')
                : route('notifications.index');

            return redirect()->to($redirectRoute)->with('success', 'Semua notifikasi berhasil dihapus.');
        } catch (\Throwable $e) {
            if (request()->wantsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Notifikasi gagal dihapus. Silakan coba lagi.',
                ], 500);
            }

            $redirectRoute = $user->hasAnyRole(['admin', 'super-admin'])
                ? route('admin.notifications.index')
                : route('notifications.index');

            return redirect()->to($redirectRoute)->with('error', 'Notifikasi gagal dihapus. Silakan coba lagi.');
        }
    }
}
