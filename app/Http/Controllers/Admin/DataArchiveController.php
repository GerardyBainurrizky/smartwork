<?php

namespace App\Http\Controllers\Admin;

use App\Exports\AttendanceExport;
use App\Exports\DriverRoutesExport;
use App\Exports\DriverVisitsExport;
use App\Exports\ReceivablesExport;
use App\Exports\RoutesExport;
use App\Exports\SalesActivitiesExport;
use App\Exports\TransactionsExport;
use App\Exports\VisitsExport;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\DataArchive;
use App\Models\Route as RouteModel;
use App\Models\RouteStop;
use App\Models\Store;
use App\Models\StoreReceivable;
use App\Models\StoreTransaction;
use App\Models\StoreTransactionPayment;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitPhoto;
use App\Services\StoreReceivableService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DataArchiveController extends Controller
{
    public function index(Request $request): View
    {
        $type = $request->get('type', 'all');
        $status = $request->get('status', 'all');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $search = $request->get('search');

        // 1. Presensi
        $totalAttendanceActive = Attendance::withoutGlobalScope('notArchived')->whereNull('archived_at')->count();
        $totalAttendanceArchived = Attendance::withoutGlobalScope('notArchived')->whereNotNull('archived_at')->count();

        // 2. Rencana Kunjungan Sales
        $totalRouteActive = RouteModel::withoutGlobalScope('notArchived')
            ->whereNull('archived_at')
            ->whereHas('user.roles', fn ($q) => $q->whereIn('name', ['sales', 'field-supervisor']))
            ->count();
        $totalRouteArchived = RouteModel::withoutGlobalScope('notArchived')
            ->whereNotNull('archived_at')
            ->whereHas('user.roles', fn ($q) => $q->whereIn('name', ['sales', 'field-supervisor']))
            ->count();

        // 3. Kunjungan Sales
        $totalVisitActive = Visit::withoutGlobalScope('notArchived')
            ->whereNull('archived_at')
            ->whereHas('user.roles', fn ($q) => $q->whereIn('name', ['sales', 'field-supervisor']))
            ->count();
        $totalVisitArchived = Visit::withoutGlobalScope('notArchived')
            ->whereNotNull('archived_at')
            ->whereHas('user.roles', fn ($q) => $q->whereIn('name', ['sales', 'field-supervisor']))
            ->count();

        // 4. Rencana Pengiriman Driver
        $totalRouteDriverActive = RouteModel::withoutGlobalScope('notArchived')
            ->whereNull('archived_at')
            ->whereHas('user.roles', fn ($q) => $q->where('name', 'driver'))
            ->count();
        $totalRouteDriverArchived = RouteModel::withoutGlobalScope('notArchived')
            ->whereNotNull('archived_at')
            ->whereHas('user.roles', fn ($q) => $q->where('name', 'driver'))
            ->count();

        // 5. Pengiriman Driver
        $totalVisitDriverActive = Visit::withoutGlobalScope('notArchived')
            ->whereNull('archived_at')
            ->whereHas('user.roles', fn ($q) => $q->where('name', 'driver'))
            ->count();
        $totalVisitDriverArchived = Visit::withoutGlobalScope('notArchived')
            ->whereNotNull('archived_at')
            ->whereHas('user.roles', fn ($q) => $q->where('name', 'driver'))
            ->count();

        // 6. Transaksi
        $totalTransactionActive = StoreTransaction::withoutGlobalScope('notArchived')->whereNull('archived_at')->count();
        $totalTransactionArchived = StoreTransaction::withoutGlobalScope('notArchived')->whereNotNull('archived_at')->count();

        // 7. Piutang (Open transactions)
        $totalReceivableActive = StoreTransaction::withoutGlobalScope('notArchived')
            ->whereNull('archived_at')
            ->whereIn('status', [StoreTransaction::STATUS_BELUM_LUNAS, StoreTransaction::STATUS_SEBAGIAN])
            ->count();
        $totalReceivableArchived = StoreTransaction::withoutGlobalScope('notArchived')
            ->whereNotNull('archived_at')
            ->whereIn('status', [StoreTransaction::STATUS_BELUM_LUNAS, StoreTransaction::STATUS_SEBAGIAN])
            ->count();

        $totalActive = $totalAttendanceActive + $totalRouteActive + $totalRouteDriverActive + $totalVisitActive + $totalVisitDriverActive + $totalTransactionActive;
        $totalArchived = $totalAttendanceArchived + $totalRouteArchived + $totalRouteDriverArchived + $totalVisitArchived + $totalVisitDriverArchived + $totalTransactionArchived;

        $archiveQuery = DataArchive::with('user');

        if ($type && $type !== 'all') {
            $archiveQuery->where('data_type', $type);
        }

        if ($status && $status !== 'all') {
            $archiveQuery->where('status', $status);
        }

        if ($startDate && $endDate) {
            $archiveQuery->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('start_date', [$startDate, $endDate])
                  ->orWhereBetween('end_date', [$startDate, $endDate])
                  ->orWhere(function ($sub) use ($startDate, $endDate) {
                      $sub->where('start_date', '<=', $startDate)
                          ->where('end_date', '>=', $endDate);
                  });
            });
        }

        if ($search) {
            $archiveQuery->where(function ($q) use ($search) {
                $q->where('notes', 'like', "%{$search}%")
                  ->orWhere('id', 'like', "%{$search}%")
                  ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$search}%"));
            });
        }

        $archives = $archiveQuery->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        return view('admin.archives.index', compact(
            'totalAttendanceActive', 'totalAttendanceArchived',
            'totalRouteActive', 'totalRouteArchived',
            'totalRouteDriverActive', 'totalRouteDriverArchived',
            'totalVisitActive', 'totalVisitArchived',
            'totalVisitDriverActive', 'totalVisitDriverArchived',
            'totalTransactionActive', 'totalTransactionArchived',
            'totalReceivableActive', 'totalReceivableArchived',
            'totalActive', 'totalArchived',
            'archives',
            'type', 'status', 'startDate', 'endDate', 'search'
        ));
    }

    public function preview(Request $request): JsonResponse
    {
        $request->validate([
            'data_type' => ['required', 'string', 'in:attendance,route,route_driver,visit,visit_driver,transaction,transaction_result,receivable,finance,all'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        $dataType = $request->input('data_type');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $counts = [
            'attendance' => 0,
            'route' => 0,
            'visit' => 0,
            'route_driver' => 0,
            'visit_driver' => 0,
            'transaction' => 0,
            'transaction_result' => 0,
            'receivable' => 0,
            'finance' => 0,
        ];

        if (in_array($dataType, ['attendance', 'all'], true)) {
            $counts['attendance'] = Attendance::withoutGlobalScope('notArchived')
                ->whereNull('archived_at')
                ->whereBetween('date', [$startDate, $endDate])
                ->count();
        }

        if (in_array($dataType, ['route', 'all'], true)) {
            $counts['route'] = RouteModel::withoutGlobalScope('notArchived')
                ->whereNull('archived_at')
                ->whereDate('date', '>=', $startDate)
                ->whereDate('date', '<=', $endDate)
                ->whereHas('user.roles', fn ($q) => $q->whereIn('name', ['sales', 'field-supervisor']))
                ->count();
        }

        if (in_array($dataType, ['visit', 'all'], true)) {
            $counts['visit'] = Visit::withoutGlobalScope('notArchived')
                ->whereNull('archived_at')
                ->whereBetween('check_in_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                ->whereHas('user.roles', fn ($q) => $q->whereIn('name', ['sales', 'field-supervisor']))
                ->count();
        }

        if (in_array($dataType, ['route_driver', 'all'], true)) {
            $counts['route_driver'] = RouteModel::withoutGlobalScope('notArchived')
                ->whereNull('archived_at')
                ->whereDate('date', '>=', $startDate)
                ->whereDate('date', '<=', $endDate)
                ->whereHas('user.roles', fn ($q) => $q->where('name', 'driver'))
                ->count();
        }

        if (in_array($dataType, ['visit_driver', 'all'], true)) {
            $counts['visit_driver'] = Visit::withoutGlobalScope('notArchived')
                ->whereNull('archived_at')
                ->whereBetween('check_in_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                ->whereHas('user.roles', fn ($q) => $q->where('name', 'driver'))
                ->count();
        }

        if (in_array($dataType, ['transaction', 'all'], true)) {
            $counts['transaction'] = StoreTransaction::withoutGlobalScope('notArchived')
                ->whereNull('archived_at')
                ->whereBetween('transaction_date', [$startDate, $endDate])
                ->count();
        }

        if ($dataType === 'transaction_result') {
            $counts['transaction_result'] = Visit::withoutGlobalScope('notArchived')
                ->whereNull('archived_at')
                ->whereBetween('check_in_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                ->whereHas('user.roles', fn ($q) => $q->whereIn('name', ['sales', 'field-supervisor']))
                ->where('status', 'completed')
                ->where(function ($q) {
                    $q->whereHas('transactions')
                      ->orWhere('transaction_amount', '>', 0)
                      ->orWhereHas('payments');
                })
                ->count();
        }

        if ($dataType === 'receivable') {
            $counts['receivable'] = StoreTransaction::withoutGlobalScope('notArchived')
                ->whereNull('archived_at')
                ->whereBetween('transaction_date', [$startDate, $endDate])
                ->whereIn('status', [StoreTransaction::STATUS_BELUM_LUNAS, StoreTransaction::STATUS_SEBAGIAN])
                ->count();
        }

        if ($dataType === 'finance') {
            $counts['finance'] = StoreTransaction::withoutGlobalScope('notArchived')
                ->whereNull('archived_at')
                ->whereBetween('transaction_date', [$startDate, $endDate])
                ->count();
        }

        $totalCount = $dataType === 'all'
            ? ($counts['attendance'] + $counts['route'] + $counts['visit'] + $counts['route_driver'] + $counts['visit_driver'] + $counts['transaction'])
            : $counts[$dataType];

        return response()->json([
            'status' => 'success',
            'counts' => $counts,
            'total' => $totalCount,
            'period' => Carbon::parse($startDate)->isoFormat('D MMMM YYYY') . ' - ' . Carbon::parse($endDate)->isoFormat('D MMMM YYYY'),
        ]);
    }

    public function archive(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'data_type' => ['required', 'string', 'in:attendance,route,route_driver,visit,visit_driver,transaction,transaction_result,receivable,finance,all'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $dataType = $validated['data_type'];
        $startDate = $validated['start_date'];
        $endDate = $validated['end_date'];
        $notes = $validated['notes'] ?? null;
        $now = now();

        DB::beginTransaction();
        try {
            $counts = [
                'attendance' => 0,
                'route' => 0,
                'visit' => 0,
                'route_driver' => 0,
                'visit_driver' => 0,
                'transaction' => 0,
                'transaction_result' => 0,
                'receivable' => 0,
                'finance' => 0,
            ];

            // 1. Buat sesi arsip terlebih dahulu untuk mendapatkan ID unik sesi
            $archive = DataArchive::create([
                'user_id' => auth()->id(),
                'data_type' => $dataType,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'records_count' => 0,
                'details' => $counts,
                'status' => 'archived',
                'notes' => $notes,
                'archived_at' => $now,
            ]);

            $archiveId = $archive->id;

            // 2. Update record data dengan data_archive_id sesi ini
            if (in_array($dataType, ['attendance', 'all'], true)) {
                $counts['attendance'] = Attendance::withoutGlobalScope('notArchived')
                    ->whereNull('archived_at')
                    ->whereBetween('date', [$startDate, $endDate])
                    ->update([
                        'archived_at' => $now,
                        'data_archive_id' => $archiveId,
                    ]);
            }

            if (in_array($dataType, ['route', 'all'], true)) {
                $counts['route'] = RouteModel::withoutGlobalScope('notArchived')
                    ->whereNull('archived_at')
                    ->whereDate('date', '>=', $startDate)
                    ->whereDate('date', '<=', $endDate)
                    ->whereHas('user.roles', fn ($q) => $q->whereIn('name', ['sales', 'field-supervisor']))
                    ->update([
                        'archived_at' => $now,
                        'data_archive_id' => $archiveId,
                    ]);
            }

            if (in_array($dataType, ['visit', 'all'], true)) {
                $counts['visit'] = Visit::withoutGlobalScope('notArchived')
                    ->whereNull('archived_at')
                    ->whereBetween('check_in_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                    ->whereHas('user.roles', fn ($q) => $q->whereIn('name', ['sales', 'field-supervisor']))
                    ->update([
                        'archived_at' => $now,
                        'data_archive_id' => $archiveId,
                    ]);
            }

            if (in_array($dataType, ['route_driver', 'all'], true)) {
                $counts['route_driver'] = RouteModel::withoutGlobalScope('notArchived')
                    ->whereNull('archived_at')
                    ->whereDate('date', '>=', $startDate)
                    ->whereDate('date', '<=', $endDate)
                    ->whereHas('user.roles', fn ($q) => $q->where('name', 'driver'))
                    ->update([
                        'archived_at' => $now,
                        'data_archive_id' => $archiveId,
                    ]);
            }

            if (in_array($dataType, ['visit_driver', 'all'], true)) {
                $counts['visit_driver'] = Visit::withoutGlobalScope('notArchived')
                    ->whereNull('archived_at')
                    ->whereBetween('check_in_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                    ->whereHas('user.roles', fn ($q) => $q->where('name', 'driver'))
                    ->update([
                        'archived_at' => $now,
                        'data_archive_id' => $archiveId,
                    ]);
            }

            if (in_array($dataType, ['transaction', 'all'], true)) {
                $matchingTrxIds = StoreTransaction::withoutGlobalScope('notArchived')
                    ->whereNull('archived_at')
                    ->whereBetween('transaction_date', [$startDate, $endDate])
                    ->pluck('id');

                $counts['transaction'] = StoreTransaction::withoutGlobalScope('notArchived')
                    ->whereIn('id', $matchingTrxIds)
                    ->update([
                        'archived_at' => $now,
                        'data_archive_id' => $archiveId,
                    ]);

                // Archive related payments and legacy store_receivables
                StoreTransactionPayment::withoutGlobalScope('notArchived')
                    ->whereNull('archived_at')
                    ->whereIn('store_transaction_id', $matchingTrxIds)
                    ->update([
                        'archived_at' => $now,
                        'data_archive_id' => $archiveId,
                    ]);

                StoreReceivable::withoutGlobalScope('notArchived')
                    ->whereNull('archived_at')
                    ->whereIn('reference_id', $matchingTrxIds)
                    ->update([
                        'archived_at' => $now,
                        'data_archive_id' => $archiveId,
                    ]);
            }

            if ($dataType === 'transaction_result') {
                $resultVisits = Visit::withoutGlobalScope('notArchived')
                    ->whereNull('archived_at')
                    ->whereBetween('check_in_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                    ->whereHas('user.roles', fn ($q) => $q->whereIn('name', ['sales', 'field-supervisor']))
                    ->where('status', 'completed')
                    ->where(function ($q) {
                        $q->whereHas('transactions')
                          ->orWhere('transaction_amount', '>', 0)
                          ->orWhereHas('payments');
                    })
                    ->get();

                $counts['transaction_result'] = $resultVisits->count();
                $visitIds = $resultVisits->pluck('id');

                Visit::withoutGlobalScope('notArchived')
                    ->whereIn('id', $visitIds)
                    ->update([
                        'archived_at' => $now,
                        'data_archive_id' => $archiveId,
                    ]);

                StoreTransaction::withoutGlobalScope('notArchived')
                    ->whereNull('archived_at')
                    ->where('reference_type', 'visit')
                    ->whereIn('reference_id', $visitIds)
                    ->update([
                        'archived_at' => $now,
                        'data_archive_id' => $archiveId,
                    ]);

                StoreTransactionPayment::withoutGlobalScope('notArchived')
                    ->whereNull('archived_at')
                    ->where('source', 'visit')
                    ->whereIn('source_id', $visitIds)
                    ->update([
                        'archived_at' => $now,
                        'data_archive_id' => $archiveId,
                    ]);
            }

            if ($dataType === 'receivable') {
                $matchingTrxIds = StoreTransaction::withoutGlobalScope('notArchived')
                    ->whereNull('archived_at')
                    ->whereBetween('transaction_date', [$startDate, $endDate])
                    ->whereIn('status', [StoreTransaction::STATUS_BELUM_LUNAS, StoreTransaction::STATUS_SEBAGIAN])
                    ->pluck('id');

                $counts['receivable'] = StoreTransaction::withoutGlobalScope('notArchived')
                    ->whereIn('id', $matchingTrxIds)
                    ->update([
                        'archived_at' => $now,
                        'data_archive_id' => $archiveId,
                    ]);

                StoreTransactionPayment::withoutGlobalScope('notArchived')
                    ->whereNull('archived_at')
                    ->whereIn('store_transaction_id', $matchingTrxIds)
                    ->update([
                        'archived_at' => $now,
                        'data_archive_id' => $archiveId,
                    ]);

                StoreReceivable::withoutGlobalScope('notArchived')
                    ->whereNull('archived_at')
                    ->whereIn('reference_id', $matchingTrxIds)
                    ->update([
                        'archived_at' => $now,
                        'data_archive_id' => $archiveId,
                    ]);
            }

            if ($dataType === 'finance') {
                $matchingTrxIds = StoreTransaction::withoutGlobalScope('notArchived')
                    ->whereNull('archived_at')
                    ->whereBetween('transaction_date', [$startDate, $endDate])
                    ->pluck('id');

                $counts['finance'] = StoreTransaction::withoutGlobalScope('notArchived')
                    ->whereIn('id', $matchingTrxIds)
                    ->update([
                        'archived_at' => $now,
                        'data_archive_id' => $archiveId,
                    ]);

                StoreTransactionPayment::withoutGlobalScope('notArchived')
                    ->whereNull('archived_at')
                    ->whereIn('store_transaction_id', $matchingTrxIds)
                    ->update([
                        'archived_at' => $now,
                        'data_archive_id' => $archiveId,
                    ]);
            }

            $totalCount = $dataType === 'all'
                ? ($counts['attendance'] + $counts['route'] + $counts['visit'] + $counts['route_driver'] + $counts['visit_driver'] + $counts['transaction'])
                : $counts[$dataType];

            if ($totalCount === 0) {
                DB::rollBack();
                return back()->with('error', 'Tidak ditemukan data aktif pada periode dan jenis data yang dipilih.');
            }

            // Update sesi arsip dengan jumlah data yang benar
            $archive->update([
                'records_count' => $totalCount,
                'details' => $counts,
            ]);

            DB::commit();

            return redirect()->route('admin.archives.index')
                ->with('success', "Berhasil mengarsipkan {$totalCount} data operasional.");
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal memproses arsip data: ' . $e->getMessage());
        }
    }

    public function restore(string $id): RedirectResponse
    {
        $archive = DataArchive::findOrFail($id);

        if ($archive->status !== 'archived') {
            return back()->with('error', 'Hanya data berstatus arsip yang dapat dipulihkan.');
        }

        DB::beginTransaction();
        try {
            // Restore across all operational models linked to this archive session
            Attendance::withoutGlobalScope('notArchived')
                ->where('data_archive_id', $archive->id)
                ->update(['archived_at' => null, 'data_archive_id' => null]);

            RouteModel::withTrashed()
                ->withoutGlobalScope('notArchived')
                ->where('data_archive_id', $archive->id)
                ->update(['archived_at' => null, 'data_archive_id' => null]);

            Visit::withoutGlobalScope('notArchived')
                ->where('data_archive_id', $archive->id)
                ->update(['archived_at' => null, 'data_archive_id' => null]);

            StoreTransaction::withoutGlobalScope('notArchived')
                ->where('data_archive_id', $archive->id)
                ->update(['archived_at' => null, 'data_archive_id' => null]);

            StoreTransactionPayment::withoutGlobalScope('notArchived')
                ->where('data_archive_id', $archive->id)
                ->update(['archived_at' => null, 'data_archive_id' => null]);

            StoreReceivable::withoutGlobalScope('notArchived')
                ->where('data_archive_id', $archive->id)
                ->update(['archived_at' => null, 'data_archive_id' => null]);

            $archive->update([
                'status' => 'restored',
                'restored_at' => now(),
            ]);

            DB::commit();

            return redirect()->route('admin.archives.index')
                ->with('success', 'Data arsip berhasil dipulihkan kembali ke status aktif.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal memulihkan data: ' . $e->getMessage());
        }
    }

    public function purge(Request $request, string $id): RedirectResponse
    {
        $request->validate([
            'confirmation' => ['required', 'string', 'in:HAPUS PERMANEN'],
        ], [
            'confirmation.in' => 'Konfirmasi teks tidak sesuai. Ketik "HAPUS PERMANEN" untuk melanjutkan.',
        ]);

        $archive = DataArchive::findOrFail($id);

        if ($archive->status === 'restored') {
            return back()->with('error', 'Data yang sudah dipulihkan tidak dapat dihapus permanen. Arsipkan terlebih dahulu jika ingin menghapus.');
        }

        DB::beginTransaction();
        try {
            // 1. Delete attendances
            Attendance::withoutGlobalScope('notArchived')
                ->where('data_archive_id', $archive->id)
                ->delete();

            // 2. Delete routes and their route stops
            $routes = RouteModel::withTrashed()
                ->withoutGlobalScope('notArchived')
                ->where('data_archive_id', $archive->id)
                ->get();

            foreach ($routes as $route) {
                RouteStop::where('route_id', $route->id)->delete();
                $route->forceDelete();
            }

            // 3. Delete visit photos and visits
            VisitPhoto::whereHas('visit', function ($q) use ($archive) {
                $q->withoutGlobalScope('notArchived')->where('data_archive_id', $archive->id);
            })->delete();

            Visit::withoutGlobalScope('notArchived')
                ->where('data_archive_id', $archive->id)
                ->delete();

            // 4. Delete payments, transactions, and store receivables
            StoreTransactionPayment::withoutGlobalScope('notArchived')
                ->where('data_archive_id', $archive->id)
                ->delete();

            StoreTransaction::withoutGlobalScope('notArchived')
                ->where('data_archive_id', $archive->id)
                ->delete();

            StoreReceivable::withoutGlobalScope('notArchived')
                ->where('data_archive_id', $archive->id)
                ->delete();

            $archive->update([
                'status' => 'purged',
            ]);

            DB::commit();

            return redirect()->route('admin.archives.index')
                ->with('success', 'Data arsip berhasil dihapus secara permanen dari database.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menghapus data permanen: ' . $e->getMessage());
        }
    }

    /**
     * Delete an archive session history log without deleting the underlying operational/archived records.
     */
    public function destroyHistory(string $id): RedirectResponse
    {
        $archive = DataArchive::findOrFail($id);

        try {
            // Unlink data_archive_id on child records safely if foreign key constraint isn't already ON DELETE SET NULL
            Attendance::withoutGlobalScope('notArchived')->where('data_archive_id', $archive->id)->update(['data_archive_id' => null]);
            RouteModel::withTrashed()->withoutGlobalScope('notArchived')->where('data_archive_id', $archive->id)->update(['data_archive_id' => null]);
            Visit::withoutGlobalScope('notArchived')->where('data_archive_id', $archive->id)->update(['data_archive_id' => null]);
            StoreTransaction::withoutGlobalScope('notArchived')->where('data_archive_id', $archive->id)->update(['data_archive_id' => null]);
            StoreTransactionPayment::withoutGlobalScope('notArchived')->where('data_archive_id', $archive->id)->update(['data_archive_id' => null]);
            StoreReceivable::withoutGlobalScope('notArchived')->where('data_archive_id', $archive->id)->update(['data_archive_id' => null]);

            $archive->delete();

            return redirect()->route('admin.archives.index')
                ->with('success', 'Riwayat sesi arsip berhasil dihapus.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal menghapus riwayat sesi arsip: ' . $e->getMessage());
        }
    }

    public function exportPdf(Request $request, string $id): Response|RedirectResponse
    {
        $archive = DataArchive::findOrFail($id);

        if ($archive->status === 'purged') {
            return back()->with('error', 'Data arsip ini sudah dihapus permanen dan tidak dapat diekspor.');
        }

        $startDate = $archive->start_date->toDateString();
        $endDate = $archive->end_date->toDateString();
        $dataType = $archive->data_type;
        $exportType = $request->get('type', $dataType === 'all' ? 'attendance' : $dataType);

        $periodLabel = 'Arsip: ' . $archive->start_date->isoFormat('D MMMM YYYY') . ' - ' . $archive->end_date->isoFormat('D MMMM YYYY');

        $data = [
            'periodLabel' => $periodLabel,
            'fromDate' => $startDate,
            'toDate' => $endDate,
            'generatedAt' => now()->locale('id')->translatedFormat('d F Y, H:i') . ' WIB',
            'printedBy' => auth()->user()->name ?? 'Super Admin',
            'printedByRole' => 'Super Admin',
            'companyName' => 'PT ISA Tri Selaras Gemilang',
            'companyFullName' => 'PT ISA TRI SELARAS GEMILANG',
            'logoPath' => public_path('assets/images/logo-isa-smartwork.png'),
            'title' => '',
            'items' => collect(),
        ];

        $landscape = false;

        if ($exportType === 'attendance' && in_array($dataType, ['attendance', 'all'], true)) {
            $data['title'] = 'LAPORAN PRESENSI ARSIP';
            $data['items'] = Attendance::withoutGlobalScope('notArchived')
                ->where('data_archive_id', $archive->id)
                ->with(['user', 'user.roles'])
                ->orderBy('date', 'desc')
                ->get();
            $view = 'reports.pdf.attendance';
        } elseif ($exportType === 'route' && in_array($dataType, ['route', 'all'], true)) {
            $data['title'] = 'LAPORAN RENCANA KUNJUNGAN SALES ARSIP';
            $data['items'] = RouteModel::withTrashed()
                ->withoutGlobalScope('notArchived')
                ->where('data_archive_id', $archive->id)
                ->whereHas('user.roles', fn ($q) => $q->whereIn('name', ['sales', 'field-supervisor']))
                ->with(['user.roles', 'stops.store', 'stops.visit', 'creator'])
                ->orderBy('date', 'desc')
                ->get();
            $view = 'reports.pdf.routes';
            $landscape = true;
        } elseif (in_array($exportType, ['route_driver', 'driver-route', 'driver-routes', 'driver_route'], true) && in_array($dataType, ['route_driver', 'all'], true)) {
            $data['title'] = 'LAPORAN RENCANA PENGIRIMAN DRIVER ARSIP';
            $data['items'] = RouteModel::withTrashed()
                ->withoutGlobalScope('notArchived')
                ->where('data_archive_id', $archive->id)
                ->whereHas('user.roles', fn ($q) => $q->where('name', 'driver'))
                ->with(['user.roles', 'stops.store', 'stops.visit', 'creator'])
                ->orderBy('date', 'desc')
                ->get();
            $view = 'reports.pdf.driver-routes';
            $landscape = true;
        } elseif ($exportType === 'visit' && in_array($dataType, ['visit', 'all'], true)) {
            $data['title'] = 'LAPORAN KUNJUNGAN SALES ARSIP';
            $data['items'] = Visit::withoutGlobalScope('notArchived')
                ->where('data_archive_id', $archive->id)
                ->whereHas('user.roles', fn ($q) => $q->whereIn('name', ['sales', 'field-supervisor']))
                ->with(['user', 'store', 'route', 'routeStop', 'payments.transaction.payments', 'transactions.payments', 'photos', 'checkoutPhotos'])
                ->orderBy('check_in_at', 'desc')
                ->get();
            $data['skipped'] = RouteStop::with(['store', 'route.user'])
                ->where('status', 'skipped')
                ->whereHas('route', function ($q) use ($archive) {
                    $q->withoutGlobalScope('notArchived')
                        ->where('data_archive_id', $archive->id)
                        ->whereHas('user.roles', fn ($sq) => $sq->whereIn('name', ['sales', 'field-supervisor']));
                })
                ->orderBy('route_id')
                ->get();
            $view = 'reports.pdf.visits';
        } elseif (in_array($exportType, ['visit_driver', 'driver-visit', 'driver_visit', 'driver-visits'], true) && in_array($dataType, ['visit_driver', 'all'], true)) {
            $data['title'] = 'LAPORAN PENGIRIMAN DRIVER ARSIP';
            $data['items'] = Visit::withoutGlobalScope('notArchived')
                ->where('data_archive_id', $archive->id)
                ->whereHas('user.roles', fn ($q) => $q->where('name', 'driver'))
                ->with(['user', 'store', 'route', 'routeStop', 'photos', 'checkoutPhotos'])
                ->orderBy('check_in_at', 'desc')
                ->get();
            $data['skipped'] = RouteStop::with(['store', 'route.user'])
                ->where('status', 'skipped')
                ->whereHas('route', function ($q) use ($archive) {
                    $q->withoutGlobalScope('notArchived')
                        ->where('data_archive_id', $archive->id)
                        ->whereHas('user.roles', fn ($sq) => $sq->where('name', 'driver'));
                })
                ->orderBy('route_id')
                ->get();
            $view = 'reports.pdf.driver-visits';
        } elseif (in_array($exportType, ['transaction', 'transactions', 'transaction_result'], true) && in_array($dataType, ['transaction', 'transaction_result', 'all'], true)) {
            $data['title'] = $exportType === 'transaction_result' ? 'LAPORAN HASIL TRANSAKSI ARSIP' : 'LAPORAN TRANSAKSI ARSIP';
            $data['items'] = Visit::withoutGlobalScope('notArchived')
                ->where(function ($q) use ($archive) {
                    $q->where('data_archive_id', $archive->id)
                      ->orWhereHas('transactions', fn ($tq) => $tq->withoutGlobalScope('notArchived')->where('data_archive_id', $archive->id))
                      ->orWhereHas('payments', fn ($pq) => $pq->withoutGlobalScope('notArchived')->where('data_archive_id', $archive->id));
                })
                ->whereHas('user.roles', fn ($q) => $q->whereIn('name', ['sales', 'field-supervisor']))
                ->with([
                    'user.roles',
                    'store',
                    'route',
                    'routeStop',
                    'photos',
                    'transactions' => fn ($t) => $t->withoutGlobalScope('notArchived')->with('payments'),
                    'payments' => fn ($p) => $p->withoutGlobalScope('notArchived')->with('transaction.payments'),
                ])
                ->orderBy('check_in_at', 'desc')
                ->get();
            $view = 'reports.pdf.transactions';
            $landscape = true;
        } elseif ($exportType === 'receivable' && in_array($dataType, ['receivable', 'transaction', 'all'], true)) {
            $data['title'] = 'LAPORAN PIUTANG ARSIP';
            $stores = Store::whereHas('transactions', function ($t) use ($archive) {
                $t->withoutGlobalScope('notArchived')->where('data_archive_id', $archive->id);
            })->with(['salesPenanggungJawab:id,name', 'transactions' => fn ($t) => $t->withoutGlobalScope('notArchived')->with('payments')])->get();

            foreach ($stores as $st) {
                $archivedTrx = $st->transactions->filter(fn ($t) => $t->data_archive_id === $archive->id);
                $totalTrx = (float) $archivedTrx->sum('transaction_amount');
                $totalPaid = 0.0;
                foreach ($archivedTrx as $atx) {
                    $totalPaid += (float) ($atx->payments ? $atx->payments->sum('amount') : 0);
                }
                $st->receivable_balance = max(0.0, round($totalTrx - $totalPaid, 2));
            }

            $data['items'] = $stores;
            $view = 'reports.pdf.receivables';
            $landscape = false;
        } elseif ($exportType === 'finance' && in_array($dataType, ['finance', 'all'], true)) {
            $data['title'] = 'LAPORAN PERFORMA KEUANGAN SALES ARSIP';
            $salesUsers = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['sales', 'field-supervisor']))->orderBy('name')->get();
            $perfData = StoreReceivableService::getSalesActivitiesPerformanceData($salesUsers, $startDate, $endDate, $archive->id);
            $data['items'] = $salesUsers;
            $data['salesData'] = collect($perfData['performance'])->keyBy('id')->all();
            $data['salesActivitiesData'] = $perfData;
            $view = 'reports.pdf.sales-activities';
            $landscape = true;
        } else {
            return back()->with('error', 'Jenis data yang dipilih tidak tersedia dalam sesi arsip ini.');
        }

        $data['landscape'] = $landscape;
        $pdf = Pdf::loadView($view, $data)->setPaper('a4', $landscape ? 'landscape' : 'portrait');

        $typeSlug = str_replace(['/', ' '], '-', $exportType);
        $defaultFilename = "arsip-{$typeSlug}-{$startDate}-{$endDate}";
        $filename = $this->downloadName($request->get('filename'), $defaultFilename, 'pdf');

        return $pdf->download($filename);
    }

    public function exportExcel(Request $request, string $id): BinaryFileResponse|RedirectResponse
    {
        $archive = DataArchive::findOrFail($id);

        if ($archive->status === 'purged') {
            return back()->with('error', 'Data arsip ini sudah dihapus permanen dan tidak dapat diekspor.');
        }

        $startDate = $archive->start_date->toDateString();
        $endDate = $archive->end_date->toDateString();
        $dataType = $archive->data_type;
        $exportType = $request->get('type', $dataType === 'all' ? 'attendance' : $dataType);

        $params = [
            'fromDate' => $startDate,
            'toDate' => $endDate,
            'withArchived' => true,
            'archiveId' => $archive->id,
        ];

        if ($exportType === 'attendance' && in_array($dataType, ['attendance', 'all'], true)) {
            $exportClass = AttendanceExport::class;
            $defaultFilename = "arsip-presensi-{$startDate}-{$endDate}";
        } elseif ($exportType === 'route' && in_array($dataType, ['route', 'all'], true)) {
            $exportClass = RoutesExport::class;
            $defaultFilename = "arsip-rencana-kunjungan-sales-{$startDate}-{$endDate}";
        } elseif (in_array($exportType, ['route_driver', 'driver-route', 'driver-routes', 'driver_route'], true) && in_array($dataType, ['route_driver', 'all'], true)) {
            $exportClass = DriverRoutesExport::class;
            $defaultFilename = "arsip-rencana-pengiriman-driver-{$startDate}-{$endDate}";
        } elseif ($exportType === 'visit' && in_array($dataType, ['visit', 'all'], true)) {
            $exportClass = VisitsExport::class;
            $defaultFilename = "arsip-kunjungan-sales-{$startDate}-{$endDate}";
        } elseif (in_array($exportType, ['visit_driver', 'driver-visit', 'driver_visit', 'driver-visits'], true) && in_array($dataType, ['visit_driver', 'all'], true)) {
            $exportClass = DriverVisitsExport::class;
            $defaultFilename = "arsip-pengiriman-driver-{$startDate}-{$endDate}";
        } elseif (in_array($exportType, ['transaction', 'transactions', 'transaction_result'], true) && in_array($dataType, ['transaction', 'transaction_result', 'all'], true)) {
            $exportClass = TransactionsExport::class;
            $defaultFilename = "arsip-transaksi-{$startDate}-{$endDate}";
        } elseif ($exportType === 'receivable' && in_array($dataType, ['receivable', 'transaction', 'all'], true)) {
            $exportClass = ReceivablesExport::class;
            $defaultFilename = "arsip-piutang-{$startDate}-{$endDate}";
        } elseif ($exportType === 'finance' && in_array($dataType, ['finance', 'all'], true)) {
            $exportClass = SalesActivitiesExport::class;
            $defaultFilename = "arsip-keuangan-sales-{$startDate}-{$endDate}";
        } else {
            return back()->with('error', 'Jenis data yang dipilih tidak tersedia dalam sesi arsip ini.');
        }

        $filename = $this->downloadName($request->get('filename'), $defaultFilename, 'xlsx');

        return Excel::download(new $exportClass($params), $filename);
    }

    private function downloadName(?string $filename, string $default, string $extension): string
    {
        if ($filename) {
            $name = preg_replace('/\.(pdf|xlsx?)$/i', '', trim($filename));
            $name = preg_replace('/[\\\\\/:*?"<>|\r\n]+/', ' ', (string) $name);
            $name = preg_replace('/\s+/', ' ', trim($name));

            if ($name !== '') {
                return mb_substr($name, 0, 120) . '.' . $extension;
            }
        }

        return $default . '.' . $extension;
    }
}
