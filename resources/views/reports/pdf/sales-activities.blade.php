@include('reports.pdf.partials.styles')

@php
    $dataCount = $items->count();

    $sumAttendance = 0;
    $sumRoutes = 0;
    $sumCompletedRoutes = 0;
    $sumVisits = 0;
    $sumCompletedVisits = 0;
    $sumNewTxAmount = 0.0;
    $sumNewTxPaid = 0.0;
    $sumOldDebtPaid = 0.0;
    $sumTotalCashIn = 0.0;

    foreach ($items as $item) {
        $perf = $salesData[$item->id] ?? [];
        $sumAttendance += ($perf['attendance'] ?? 0);
        $sumRoutes += ($perf['routes'] ?? 0);
        $sumCompletedRoutes += ($perf['completed_routes'] ?? 0);
        $sumVisits += ($perf['visits'] ?? 0);
        $sumCompletedVisits += ($perf['completed_visits'] ?? 0);
        $sumNewTxAmount += ($perf['new_tx_amount'] ?? 0);
        $sumNewTxPaid += ($perf['new_tx_paid'] ?? 0);
        $sumOldDebtPaid += ($perf['old_debt_paid'] ?? 0);
        $sumTotalCashIn += ($perf['total_cash_in'] ?? 0);
    }

    $avgCompletion = $sumRoutes > 0 ? round(($sumCompletedRoutes / $sumRoutes) * 100) : 0;
@endphp

@include('reports.pdf.partials.header')

{{-- Ringkasan Statistik Periode --}}
<div class="section" style="margin-bottom: 12px;">
    <div class="section-title">RINGKASAN PERFORMA AKTIVITAS SALES</div>
    <div class="section-body" style="padding: 6px;">
        <table class="report-meta" style="margin-bottom: 0;">
            <tr>
                <td class="k" style="width: 20%;">Total Sales</td>
                <td class="v" style="width: 30%;">{{ $dataCount }} orang</td>
                <td class="k" style="width: 20%;">Total Presensi</td>
                <td class="v" style="width: 30%; color: #0DA4CE;">{{ $sumAttendance }}</td>
            </tr>
            <tr>
                <td class="k">Total Rencana</td>
                <td class="v">{{ $sumRoutes }} (Selesai: {{ $sumCompletedRoutes }})</td>
                <td class="k">Rata-rata Completion</td>
                <td class="v" style="color: #0b7494;">{{ $avgCompletion }}%</td>
            </tr>
            <tr>
                <td class="k">Total Kunjungan</td>
                <td class="v" style="color: #166534;">{{ $sumVisits }} (Selesai: {{ $sumCompletedVisits }})</td>
                <td class="k">Total Nilai Transaksi Baru</td>
                <td class="v" style="color: #1d4ed8; font-weight: bold;">Rp {{ number_format($sumNewTxAmount, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="k">Uang Masuk Transaksi Baru</td>
                <td class="v" style="color: #0f766e; font-weight: bold;">Rp {{ number_format($sumNewTxPaid, 0, ',', '.') }}</td>
                <td class="k">Pembayaran Piutang Lama</td>
                <td class="v" style="color: #b45309; font-weight: bold;">Rp {{ number_format($sumOldDebtPaid, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="k">Total Uang Masuk</td>
                <td class="v" colspan="3" style="color: #15803d; font-weight: bold;">Rp {{ number_format($sumTotalCashIn, 0, ',', '.') }}</td>
            </tr>
        </table>
    </div>
</div>

{{-- Tabel Performa Sales --}}
<table class="data">
    <thead>
        <tr>
            <th style="width:3%; text-align:center;">No</th>
            <th style="width:15%;">Petugas Sales</th>
            <th style="width:6%; text-align:center;">Presensi</th>
            <th style="width:6%; text-align:center;">Rencana</th>
            <th style="width:7%; text-align:center;">Rencana Selesai</th>
            <th style="width:7%; text-align:center;">Completion</th>
            <th style="width:6%; text-align:center;">Kunjungan</th>
            <th style="width:7%; text-align:center;">Kunjungan Selesai</th>
            <th style="width:11%; text-align:right;">Nilai Transaksi Baru</th>
            <th style="width:11%; text-align:right;">Uang Masuk Tx Baru</th>
            <th style="width:10%; text-align:right;">Bayar Piutang Lama</th>
            <th style="width:11%; text-align:right;">Total Uang Masuk</th>
        </tr>
    </thead>
    <tbody>
        @foreach($items as $index => $item)
            @php
                $perf = $salesData[$item->id] ?? [];
                $routes = $perf['routes'] ?? 0;
                $completedRoutes = $perf['completed_routes'] ?? 0;
                $completion = $routes > 0 ? round(($completedRoutes / $routes) * 100) : 0;
            @endphp
            <tr>
                <td style="text-align:center;">{{ $index + 1 }}</td>
                <td><strong>{{ $item->name }}</strong></td>
                <td style="text-align:center;">{{ $perf['attendance'] ?? 0 }}</td>
                <td style="text-align:center;">{{ $routes }}</td>
                <td style="text-align:center; font-weight:bold; color:#0b7494;">{{ $completedRoutes }}</td>
                <td style="text-align:center; font-weight:bold;">{{ $completion }}%</td>
                <td style="text-align:center;">{{ $perf['visits'] ?? 0 }}</td>
                <td style="text-align:center; font-weight:bold; color:#166534;">{{ $perf['completed_visits'] ?? 0 }}</td>
                <td style="text-align:right; font-weight:bold; color:#1d4ed8;">
                    Rp {{ number_format($perf['new_tx_amount'] ?? 0, 0, ',', '.') }}
                </td>
                <td style="text-align:right; font-weight:bold; color:#0f766e;">
                    Rp {{ number_format($perf['new_tx_paid'] ?? 0, 0, ',', '.') }}
                </td>
                <td style="text-align:right; font-weight:bold; color:#b45309;">
                    Rp {{ number_format($perf['old_debt_paid'] ?? 0, 0, ',', '.') }}
                </td>
                <td style="text-align:right; font-weight:bold; color:#15803d;">
                    Rp {{ number_format($perf['total_cash_in'] ?? 0, 0, ',', '.') }}
                </td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr style="background:#f1f5f9; font-weight:bold;">
            <td colspan="2" style="text-align:right; padding:6px 5px;">TOTAL:</td>
            <td style="text-align:center; padding:6px 5px;">{{ $sumAttendance }}</td>
            <td style="text-align:center; padding:6px 5px;">{{ $sumRoutes }}</td>
            <td style="text-align:center; padding:6px 5px; color:#0b7494;">{{ $sumCompletedRoutes }}</td>
            <td style="text-align:center; padding:6px 5px;">{{ $avgCompletion }}%</td>
            <td style="text-align:center; padding:6px 5px;">{{ $sumVisits }}</td>
            <td style="text-align:center; padding:6px 5px; color:#166534;">{{ $sumCompletedVisits }}</td>
            <td style="text-align:right; color:#1d4ed8; padding:6px 5px;">
                Rp {{ number_format($sumNewTxAmount, 0, ',', '.') }}
            </td>
            <td style="text-align:right; color:#0f766e; padding:6px 5px;">
                Rp {{ number_format($sumNewTxPaid, 0, ',', '.') }}
            </td>
            <td style="text-align:right; color:#b45309; padding:6px 5px;">
                Rp {{ number_format($sumOldDebtPaid, 0, ',', '.') }}
            </td>
            <td style="text-align:right; color:#15803d; padding:6px 5px;">
                Rp {{ number_format($sumTotalCashIn, 0, ',', '.') }}
            </td>
        </tr>
    </tfoot>
</table>

@if($items->isEmpty())
    <div class="empty">Tidak ada data aktivitas sales pada periode ini.</div>
@endif

@include('reports.pdf.partials.footer')
