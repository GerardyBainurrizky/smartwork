<?php

namespace App\Http\Controllers;

use App\Exports\DriverVisitsExport;
use App\Exports\VisitsExport;
use App\Models\RouteStop;
use App\Models\Visit;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class VisitPdfController extends Controller
{
    public function detail(string $visitId): Response
    {
        $visit = Visit::with([
            'store',
            'user.roles',
            'routeStop.route',
            'payments.transaction',
            'transactions.payments',
            'photos',
            'checkoutPhotos',
        ])->findOrFail($visitId);

        if ($visit->status !== 'completed' || ! $visit->check_in_at || ! $visit->check_out_at) {
            abort(404, 'Laporan PDF hanya tersedia untuk kunjungan/pengiriman yang telah selesai.');
        }

        $isDriver = $visit->user && $visit->user->hasRole('driver');
        $data = $this->visitData($visit);

        if ($isDriver) {
            $data['pageTitle'] = 'LAPORAN HASIL PENGIRIMAN';
            $pdf = Pdf::loadView('visit.pdf.detail-driver', $data)->setPaper('a4', 'portrait');
            $driverName = str_replace(' ', '-', $visit->user->name ?? 'driver');
            return $pdf->download("laporan-pengiriman-{$driverName}-".$visit->check_in_at->format('Ymd').'.pdf');
        }

        $pdf = Pdf::loadView('visit.pdf.detail', $data)->setPaper('a4', 'portrait');
        $salesName = str_replace(' ', '-', $visit->user->name ?? 'sales');

        return $pdf->download("laporan-kunjungan-{$salesName}-".$visit->check_in_at->format('Ymd').'.pdf');
    }

    public function rekap(Request $request): Response|JsonResponse
    {
        $user = auth()->user();
        $isDriver = $user->hasRole('driver');
        $fromDate = $request->filled('from_date') ? $request->input('from_date') : null;
        $toDate = $request->filled('to_date') ? $request->input('to_date') : null;
        $status = $request->filled('status') ? $request->input('status') : null;

        if (! $fromDate && ! $toDate && ! $status) {
            return response()->json([
                'status' => 'error',
                'message' => 'Silakan pilih filter terlebih dahulu (tanggal atau status) sebelum mengunduh PDF.',
            ], 422);
        }

        $items = $this->buildRekapItems($user->id, $fromDate, $toDate, $status);

        if ($items->isEmpty()) {
            $msg = $isDriver
                ? 'Tidak ada data pengiriman yang sesuai dengan filter yang dipilih.'
                : 'Tidak ada data kunjungan yang sesuai dengan filter yang dipilih.';
            return response()->json([
                'status' => 'error',
                'message' => $msg,
            ], 422);
        }

        $periodLabel = ($fromDate && $toDate) ? "{$fromDate} - {$toDate}" : 'Semua Periode';
        $generatedAt = now()->format('d M Y, H:i');

        $pdf = Pdf::loadView('visit.pdf.rekap', [
            'items' => $items,
            'isDriver' => $isDriver,
            'logoPath' => public_path('assets/images/logo-isa-smartwork.png'),
            'periodLabel' => $periodLabel,
            'generatedAt' => $generatedAt,
        ])->setPaper('a4', 'portrait');

        $userName = str_replace(' ', '-', $user->name);
        $defaultName = $isDriver ? "rekap-pengiriman-{$userName}-".now()->format('Ymd') : "rekap-kunjungan-{$userName}-".now()->format('Ymd');
        $filename = $this->downloadName($request->get('filename'), $defaultName, 'pdf');

        return $pdf->download($filename);
    }

    public function rekapExcel(Request $request): JsonResponse|Response|BinaryFileResponse
    {
        $user = auth()->user();
        $fromDate = $request->filled('from_date') ? $request->input('from_date') : null;
        $toDate = $request->filled('to_date') ? $request->input('to_date') : null;
        $status = $request->filled('status') ? $request->input('status') : null;

        if (! $fromDate && ! $toDate && ! $status) {
            return response()->json([
                'status' => 'error',
                'message' => 'Silakan pilih filter terlebih dahulu (tanggal atau status) sebelum mengunduh Excel.',
            ], 422);
        }

        $visits = Visit::with(['store', 'user'])
            ->where('user_id', $user->id)
            ->orderBy('check_in_at', 'asc');

        if ($fromDate) {
            $visits->whereDate('check_in_at', '>=', $fromDate);
        }
        if ($toDate) {
            $visits->whereDate('check_in_at', '<=', $toDate);
        }
        if ($status) {
            $visits->where('status', $status);
        }

        $visitsCount = $visits->count();

        $skippedCount = 0;
        if (! $status || $status === 'skipped') {
            $skippedCount = RouteStop::where('status', 'skipped')
                ->whereHas('route', function ($r) use ($user, $fromDate, $toDate) {
                    $r->where('user_id', $user->id);
                    if ($fromDate) {
                        $r->whereDate('date', '>=', $fromDate);
                    }
                    if ($toDate) {
                        $r->whereDate('date', '<=', $toDate);
                    }
                })->count();
        }

        if ($visitsCount === 0 && $skippedCount === 0) {
            $msg = $user->hasRole('driver')
                ? 'Tidak ada data pengiriman yang sesuai dengan filter yang dipilih.'
                : 'Tidak ada data kunjungan yang sesuai dengan filter yang dipilih.';
            return response()->json([
                'status' => 'error',
                'message' => $msg,
            ], 422);
        }

        $salesName = str_replace(' ', '-', $user->name);
        if ($user->hasRole('driver')) {
            $defaultName = "rekap-pengiriman-{$salesName}-".now()->format('Ymd');
            $filename = $this->downloadName($request->get('filename'), $defaultName, 'xlsx');
            $params = [
                'fromDate' => $fromDate ?? '2000-01-01',
                'toDate' => $toDate ?? '2100-12-31',
                'userId' => $user->id,
                'status' => $status,
            ];
            return Excel::download(new DriverVisitsExport($params), $filename);
        }

        $defaultName = "rekap-kunjungan-{$salesName}-".now()->format('Ymd');
        $filename = $this->downloadName($request->get('filename'), $defaultName, 'xlsx');
        $params = [
            'fromDate' => $fromDate ?? '2000-01-01',
            'toDate' => $toDate ?? '2100-12-31',
            'userId' => $user->id,
            'status' => $status,
        ];
        return Excel::download(new VisitsExport($params), $filename);
    }

    private function buildRekapItems(?int $userId, ?string $fromDate, ?string $toDate, ?string $status = null): Collection
    {
        $query = Visit::with([
            'store',
            'user.roles',
            'routeStop.route',
            'payments.transaction',
            'transactions.payments',
            'photos',
            'checkoutPhotos',
        ])
            ->where('status', 'completed')
            ->whereNotNull('check_in_at')
            ->whereNotNull('check_out_at')
            ->orderBy('check_in_at', 'asc');

        if ($userId) {
            $query->where('user_id', $userId);
        }
        if ($fromDate) {
            $query->whereDate('check_in_at', '>=', $fromDate);
        }
        if ($toDate) {
            $query->whereDate('check_in_at', '<=', $toDate);
        }
        if ($status) {
            $query->where('status', $status);
        }

        $visits = $query->get();

        $skippedStops = RouteStop::with(['store', 'route.user', 'visit'])
            ->where('status', 'skipped')
            ->when($userId, fn ($q) => $q->whereHas('route', fn ($r) => $r->where('user_id', $userId)))
            ->when(! $status || $status === 'skipped', function ($q) use ($fromDate, $toDate) {
                $q->whereHas('route', function ($r) use ($fromDate, $toDate) {
                    if ($fromDate) {
                        $r->whereDate('date', '>=', $fromDate);
                    }
                    if ($toDate) {
                        $r->whereDate('date', '<=', $toDate);
                    }
                });
            })
            ->orderBy('route_id')
            ->get();

        $generatedAt = now()->format('d M Y, H:i');

        $items = collect();

        foreach ($visits as $visit) {
            $data = $this->visitData($visit);
            $data['footerLine3'] = 'Tanggal Cetak: '.$generatedAt;
            $items->push(['type' => 'visit', 'data' => $data]);
        }

        if (! $status || $status === 'skipped') {
            foreach ($skippedStops as $stop) {
                $data = $this->skippedData($stop);
                $data['footerLine3'] = 'Tanggal Cetak: '.$generatedAt;
                $items->push(['type' => 'skipped', 'data' => $data]);
            }
        }

        return $items;
    }

    private function visitData(Visit $visit): array
    {
        $durationLabel = '-';

        if ($visit->check_in_at && $visit->check_out_at) {
            $minutes = max(0, (int) $visit->check_in_at->diffInMinutes($visit->check_out_at));
            $hours = intdiv($minutes, 60);
            $remainder = $minutes % 60;

            $durationLabel = $hours > 0
                ? ($remainder > 0 ? "{$hours} Jam {$remainder} Menit" : "{$hours} Jam")
                : "{$minutes} Menit";
        }

        $storeBalance = $visit->store_id ? (float) \App\Services\StoreReceivableService::balanceForStore($visit->store_id) : 0;

        $estimatedMinutes = $visit->routeStop?->estimated_duration_minutes;
        $estimatedDurationLabel = $estimatedMinutes ? "{$estimatedMinutes} Menit" : '-';

        return [
            'visit' => $visit,
            'store' => $visit->store,
            'salesName' => $visit->user->name ?? '-',
            'salesRole' => $visit->user->getRoleNames()->first() ? ucfirst($visit->user->getRoleNames()->first()) : 'Sales',
            'durationLabel' => $durationLabel,
            'estimatedDurationLabel' => $estimatedDurationLabel,
            'estimatedMinutes' => $estimatedMinutes,
            'storeBalance' => $storeBalance,
            'stopNotes' => $visit->routeStop?->notes ?: null,
            'logoPath' => public_path('assets/images/logo-isa-smartwork.png'),
            'generatedAt' => now()->format('d M Y, H:i'),
            'footerLine1' => 'PT ISA TRI SELARAS GEMILANG',
            'footerLine2' => 'Dokumen dibuat otomatis oleh ISA SmartWork.',
        ];
    }

    private function skippedData(RouteStop $stop): array
    {
        return [
            'stop' => $stop,
            'store' => $stop->store,
            'salesName' => $stop->route->user->name ?? '-',
            'salesRole' => $stop->route->user->getRoleNames()->first() ?? '-',
            'date' => $stop->route->date,
            'reason' => $stop->notes,
            'logoPath' => public_path('assets/images/logo-isa-smartwork.png'),
            'generatedAt' => now()->format('d M Y, H:i'),
            'footerLine1' => 'PT ISA TRI SELARAS GEMILANG',
            'footerLine2' => 'Dokumen dibuat otomatis oleh ISA SmartWork.',
        ];
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