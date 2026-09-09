<?php

namespace App\Http\Controllers;

use App\Exports\StaffAttendanceExport;
use App\Models\Attendance;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StaffReportController extends Controller
{
    public function index(Request $request): View
    {
        [$fromDate, $toDate] = $this->resolvePeriod($request);
        $status = $this->resolveStatus($request);

        $attendances = $this->ownQuery($fromDate, $toDate, $status)
            ->paginate(10)
            ->withQueryString();

        $stats = $this->periodStats($fromDate, $toDate);

        $periodLabel = $this->periodLabel($fromDate, $toDate);

        return view('staff.report', compact('attendances', 'stats', 'fromDate', 'toDate', 'periodLabel', 'status'));
    }

    public function exportPdf(Request $request): Response
    {
        [$fromDate, $toDate] = $this->resolvePeriod($request);
        $status = $this->resolveStatus($request);
        $user = auth()->user();

        $data = [
            'title' => 'LAPORAN PRESENSI KARYAWAN',
            'items' => $this->ownQuery($fromDate, $toDate, $status)->get(),
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'periodLabel' => $this->periodLabel($fromDate, $toDate),
            'generatedAt' => now()->format('d M Y, H:i'),
            'printedBy' => $user->name,
            'companyName' => 'PT ISA Tri Selaras Gemilang',
            'companyFullName' => 'PT ISA TRI SELARAS GEMILANG',
            'logoPath' => public_path('assets/images/logo-isa-smartwork.png'),
            'landscape' => false,
            'staffView' => true,
        ];

        $pdf = Pdf::loadView('reports.pdf.attendance', $data)->setPaper('a4', 'portrait');

        return $pdf->download($this->safeFileName("Laporan Presensi Karyawan - {$fromDate} - {$toDate}", 'pdf'));
    }

    public function exportExcel(Request $request): BinaryFileResponse
    {
        [$fromDate, $toDate] = $this->resolvePeriod($request);
        $status = $this->resolveStatus($request);

        $params = [
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'userId' => auth()->id(),
            'status' => $status,
        ];

        return Excel::download(new StaffAttendanceExport($params), $this->safeFileName("Laporan Presensi Karyawan - {$fromDate} - {$toDate}", 'xlsx'));
    }

    private function ownQuery(string $fromDate, string $toDate, ?string $status)
    {
        $query = Attendance::with('user')
            ->where('user_id', auth()->id())
            ->whereBetween('date', [$fromDate, $toDate])
            ->orderBy('date', 'desc')
            ->orderBy('clock_in', 'desc');

        $status = trim((string) $status);

        if ($status === 'checked_in') {
            $query->where('status', '!=', 'canceled')->whereNotNull('clock_in')->whereNull('clock_out');
        } elseif ($status === 'checked_out') {
            $query->where('status', '!=', 'canceled')->whereNotNull('clock_out');
        } elseif ($status === 'canceled') {
            $query->where('status', 'canceled');
        }

        return $query;
    }

    private function resolveStatus(Request $request): string
    {
        $status = trim((string) $request->get('status', ''));

        if (in_array($status, ['all', 'semua', 'semua-status'], true)) {
            return '';
        }

        return $status;
    }

    private function periodStats(string $fromDate, string $toDate): array
    {
        $range = Attendance::where('user_id', auth()->id())
            ->whereBetween('date', [$fromDate, $toDate])
            ->get();

        return [
            'total' => $range->where('is_canceled', false)->count(),
            'checked_out' => $range->where('is_canceled', false)->whereNotNull('clock_out')->count(),
            'checked_in' => $range->where('is_canceled', false)->whereNotNull('clock_in')->whereNull('clock_out')->count(),
            'canceled' => $range->where('is_canceled', true)->count(),
        ];
    }

    private function resolvePeriod(Request $request): array
    {
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');

        if (! $this->validDate($fromDate) || ! $this->validDate($toDate)) {
            $fromDate = now()->startOfMonth()->toDateString();
            $toDate = now()->endOfMonth()->toDateString();
        }

        if ($fromDate > $toDate) {
            [$fromDate, $toDate] = [$toDate, $fromDate];
        }

        return [$fromDate, $toDate];
    }

    private function validDate(?string $date): bool
    {
        if (! $date) {
            return false;
        }

        try {
            Carbon::parse($date);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function periodLabel(string $fromDate, string $toDate): string
    {
        $from = Carbon::parse($fromDate);
        $to = Carbon::parse($toDate);

        if ($from->equalTo($to)) {
            return 'Periode: '.$from->translatedFormat('d F Y');
        }

        return 'Periode: '.$from->translatedFormat('d F Y').' - '.$to->translatedFormat('d F Y');
    }

    private function safeFileName(string $name, string $extension): string
    {
        $name = preg_replace('/[\\\\\/:*?"<>|\r\n]+/', ' ', trim($name));
        $name = preg_replace('/\s+/', ' ', trim($name));

        if ($name === '') {
            $name = 'Laporan Presensi Karyawan';
        }

        return $name.'.'.$extension;
    }
}