@include('visit.pdf.partials.styles')

@php
    $checkIn = $visit->check_in_at ? $visit->check_in_at->format('d M Y, H:i') : '-';
    $checkOut = $visit->check_out_at ? $visit->check_out_at->format('d M Y, H:i') : '-';
    $date = $visit->check_in_at ? $visit->check_in_at->format('d M Y') : '-';
    $mapsUrl = null;
    if ($visit->check_in_maps_url) {
        $mapsUrl = $visit->check_in_maps_url;
    } elseif ($store && $store->latitude && $store->longitude) {
        $mapsUrl = 'https://www.google.com/maps?q=' . $store->latitude . ',' . $store->longitude;
    }
    
    // Check in photo: selfie Check In
    $photoCheckIn = $visit->check_in_selfie;
    
    // Check out photos: Foto Etalase & Foto Selfie Check Out
    $photoEtalase = $visit->storefront_photo ?? $visit->final_store_photo;
    $photoCheckOutSelfie = $visit->check_out_selfie;

    $hasCheckInCoord = $visit->check_in_lat && $visit->check_in_lng;
    $checkInCoord = $hasCheckInCoord ? round((float) $visit->check_in_lat, 6) . ', ' . round((float) $visit->check_in_lng, 6) : '-';
    $hasCheckOut = (bool) $visit->check_out_at;
    $hasCheckOutCoord = $hasCheckOut && $visit->check_out_lat && $visit->check_out_lng;
    $checkOutCoord = $hasCheckOutCoord ? round((float) $visit->check_out_lat, 6) . ', ' . round((float) $visit->check_out_lng, 6) : 'Belum Check Out';
    $checkOutAddress = $hasCheckOut ? ($visit->check_out_address ?: '-') : 'Belum Check Out';
    $checkInMapUrl = $hasCheckInCoord ? 'https://www.google.com/maps?q=' . $visit->check_in_lat . ',' . $visit->check_in_lng : null;
    $checkOutMapUrl = $hasCheckOutCoord ? 'https://www.google.com/maps?q=' . $visit->check_out_lat . ',' . $visit->check_out_lng : null;

    $txStatus = $visit->transaction_status ?? 'none';
    $txStatusLabel = match ($txStatus) {
        'piutang' => 'Bayar Piutang Lama',
        'paid' => 'Transaksi Baru',
        'mixed' => 'Bayar Piutang + Transaksi Baru',
        default => 'Tidak Ada Transaksi / Pembayaran',
    };

    $visitPayments = ($visit->payments ?? collect())->filter(function ($p) {
        return $p->source !== 'initial_payment' && ! str_contains(strtolower($p->notes ?? ''), 'pembayaran awal');
    });
    $visitTransactions = $visit->transactions ?? collect();
    $totalOldDebtPaid = (float) $visitPayments->sum('amount');
    $totalNewTx = (float) $visitTransactions->sum('transaction_amount');
    if ($totalNewTx <= 0 && $totalOldDebtPaid <= 0 && $visit->transaction_amount && in_array($txStatus, ['paid', 'mixed'])) {
        $totalNewTx = (float) $visit->transaction_amount;
    }

    // Gunakan service ledger terpusat untuk financial summary
    $recSummary = \App\Services\StoreReceivableService::getVisitReceivableSummary($visit);
    $storeBalanceBefore = $recSummary['balance_before'];
    $storeBalanceAfter = $recSummary['balance_after'];
    $newInitialPaidTotal = $recSummary['new_tx_initial_paid'];

    // Rencana Kunjungan (Estimasi Durasi & Catatan Rencana per Toko)
    $routeStopEstimated = $visit->routeStop?->estimated_duration_minutes ? $visit->routeStop->estimated_duration_minutes . ' menit' : ($estimatedDurationLabel ?? '-');
    $routeStopNotes = $stopNotes ?? $visit->routeStop?->notes ?? null;
@endphp

@include('visit.pdf.partials.header', ['title' => $pageTitle ?? 'LAPORAN KUNJUNGAN TOKO'])

<table class="detail" style="margin-bottom:10px;">
    <tr>
        <td class="k">Nama Sales</td>
        <td class="v">{{ $salesName }}</td>
        <td class="k">Role / Jabatan</td>
        <td class="v">{{ $salesRole }}</td>
    </tr>
    <tr>
        <td class="k">Tanggal Kunjungan</td>
        <td class="v">{{ $date }}</td>
        <td class="k">Rute & Urutan</td>
        <td class="v">{{ ($visit->route->name ?? 'Rute Kunjungan') . ' (Urutan #' . ($visit->routeStop->sequence ?? 1) . ')' }}</td>
    </tr>
    <tr>
        <td class="k">Jam Check In</td>
        <td class="v">{{ $checkIn }}</td>
        <td class="k">Jam Check Out</td>
        <td class="v">{{ $checkOut }}</td>
    </tr>
    <tr>
        <td class="k">Durasi Kunjungan</td>
        <td class="v">{{ $durationLabel }}</td>
        <td class="k">Status Kunjungan</td>
        <td class="v" style="color: {{ $visit->status === 'completed' ? '#15803d' : '#0da4ce' }}; font-weight: bold;">
            {{ $visit->status === 'completed' ? 'Selesai' : 'Sedang Dikunjungi' }}
        </td>
    </tr>
</table>

<div class="section">
    <div class="section-title">INFORMASI TOKO & RENCANA KUNJUNGAN</div>
    <div class="section-body">
        <table class="detail detail-2col">
            <tr>
                <td class="k">Nama Toko</td><td class="v">{{ $store->name ?? '-' }}</td>
                <td class="k">Kode Toko</td><td class="v">{{ $store->code ?? '-' }}</td>
            </tr>
            <tr>
                <td class="k">Alamat Toko</td><td class="v" colspan="3">{{ $store->address ?? '-' }}</td>
            </tr>
            <tr>
                <td class="k">Kota / Provinsi</td>
                <td class="v">{{ implode(', ', array_filter([$store->city ?? null, $store->province ?? null])) ?: '-' }}</td>
                <td class="k">Koordinat Toko</td>
                <td class="v">{{ $store && $store->latitude && $store->longitude ? round($store->latitude, 6) . ', ' . round($store->longitude, 6) : '-' }}</td>
            </tr>
            <tr>
                <td class="k">Estimasi Durasi</td>
                <td class="v">{{ $routeStopEstimated }}</td>
                <td class="k">Link Google Maps</td>
                <td class="v">
                    @if ($mapsUrl)
                        <a class="maps-link" href="{{ $mapsUrl }}">Buka Lokasi Toko di Google Maps</a>
                    @else
                        -
                    @endif
                </td>
            </tr>
            <tr>
                <td class="k">Catatan Rencana Kunjungan</td>
                <td class="v" colspan="3">
                    @if ($routeStopNotes)
                        <div class="multiline-text">{{ $routeStopNotes }}</div>
                    @else
                        -
                    @endif
                </td>
            </tr>
        </table>
    </div>
</div>

<div class="section">
    <div class="section-title">CHECK IN KUNJUNGAN</div>
    <div class="section-body">
        <table class="detail detail-2col">
            <tr>
                <td class="k">Waktu Check In</td><td class="v">{{ $checkIn }}</td>
                <td class="k">Koordinat</td><td class="v">{{ $checkInCoord }}</td>
            </tr>
            <tr>
                <td class="k">Alamat Check In</td><td class="v" colspan="3">{{ $visit->check_in_address ?: '-' }}</td>
            </tr>
            <tr>
                <td class="k">Catatan Kunjungan</td><td class="v" colspan="3">{{ $visit->initial_notes ?: 'Tidak ada catatan check in.' }}</td>
            </tr>
            @if ($checkInMapUrl)
            <tr>
                <td class="k">Peta Lokasi</td>
                <td class="v" colspan="3">
                    <a class="maps-link" href="{{ $checkInMapUrl }}">Buka Lokasi Check In di Google Maps</a>
                </td>
            </tr>
            @endif
        </table>
    </div>
</div>

<div class="section">
    <div class="section-title">CHECK OUT & HASIL KUNJUNGAN</div>
    <div class="section-body">
        <table class="detail detail-2col">
            <tr>
                <td class="k">Waktu Check Out</td><td class="v">{{ $checkOut }}</td>
                <td class="k">Koordinat</td><td class="v">{{ $checkOutCoord }}</td>
            </tr>
            <tr>
                <td class="k">Alamat Check Out</td><td class="v" colspan="3">{{ $checkOutAddress }}</td>
            </tr>
            <tr>
                <td class="k">Hasil Kunjungan</td><td class="v" colspan="3">{{ $visit->visit_result ?: '-' }}</td>
            </tr>
            <tr>
                <td class="k">Status Transaksi</td><td class="v" colspan="3">{{ $txStatusLabel }}</td>
            </tr>
            <tr>
                <td class="k">Catatan Tambahan</td><td class="v" colspan="3">{{ $visit->final_notes ?: 'Tidak ada catatan tambahan.' }}</td>
            </tr>
        </table>
    </div>
</div>

@if ($visitTransactions->isNotEmpty() || ($totalNewTx > 0 && in_array($txStatus, ['paid', 'mixed'])))
<div class="section">
    <div class="section-title">TRANSAKSI BARU</div>
    <div class="section-body">
        @if ($visitTransactions->isNotEmpty())
        <table class="table-inner">
            <thead>
                <tr>
                    <th>Kode Transaksi</th>
                    <th>Tanggal</th>
                    <th>Nilai Transaksi</th>
                    <th>Uang Masuk Transaksi Baru</th>
                    <th>Sisa Menjadi Piutang</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($visitTransactions as $vTrx)
                @php
                    $allVTrxPayments = $vTrx->relationLoaded('payments') ? $vTrx->payments : $vTrx->payments()->get();
                    $trxInitialPaid = (float) $allVTrxPayments->filter(fn ($p) =>
                        (string)$p->source_id === (string)$visit->id ||
                        $p->source === 'initial_payment' ||
                        str_contains(strtolower($p->notes ?? ''), 'pembayaran awal')
                    )->sum('amount');
                    $trxAmount = (float) $vTrx->transaction_amount;
                    $trxRemaining = max(0.0, round($trxAmount - $trxInitialPaid, 2));
                    $trxStatusBadge = $trxRemaining <= 0.005 ? 'LUNAS' : ($trxInitialPaid > 0.005 ? 'SEBAGIAN' : 'BELUM_LUNAS');
                @endphp
                <tr>
                    <td style="font-weight: bold;">{{ $vTrx->transaction_code }}</td>
                    <td>{{ $vTrx->transaction_date ? $vTrx->transaction_date->format('d M Y') : '-' }}</td>
                    <td>Rp {{ number_format($trxAmount, 0, ',', '.') }}</td>
                    <td style="color: #15803d;">Rp {{ number_format($trxInitialPaid, 0, ',', '.') }}</td>
                    <td style="color: {{ $trxRemaining > 0.005 ? '#b91c1c' : '#15803d' }}; font-weight: bold;">Rp {{ number_format($trxRemaining, 0, ',', '.') }}</td>
                    <td>{{ $trxStatusBadge }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @elseif ($totalNewTx > 0 && in_array($txStatus, ['paid', 'mixed']))
        <table class="detail">
            <tr>
                <td class="k">Total Transaksi Baru</td>
                <td class="v">Rp {{ number_format($totalNewTx, 0, ',', '.') }}</td>
            </tr>
        </table>
        @endif
    </div>
</div>
@endif

@if ($visitPayments->isNotEmpty() || ($totalOldDebtPaid > 0 && in_array($txStatus, ['piutang', 'mixed'])))
<div class="section">
    <div class="section-title">PEMBAYARAN PIUTANG LAMA</div>
    <div class="section-body">
        @if ($visitPayments->isNotEmpty())
        <table class="table-inner">
            <thead>
                <tr>
                    <th>Nomor Faktur</th>
                    <th>Tanggal Transaksi</th>
                    <th>Nilai Faktur Awal</th>
                    <th>Saldo Sebelum Pembayaran</th>
                    <th>Metode</th>
                    <th>Pembayaran pada Kunjungan Ini</th>
                    <th>Sisa Piutang Faktur</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($visitPayments as $vPay)
                @if ((float) $vPay->amount > 0)
                @php
                    $trx = $vPay->transaction;
                    $payAmount = (float) $vPay->amount;
                    if ($trx) {
                        $totalTrxAmount = (float) $trx->transaction_amount;
                        $allPayments = $trx->relationLoaded('payments') ? $trx->payments : $trx->payments()->get();
                        $priorPayments = (float) $allPayments->filter(function ($otherPay) use ($vPay) {
                            if ($otherPay->id === $vPay->id) return false;
                            $opDate = $otherPay->payment_date ? $otherPay->payment_date->toDateString() : ($otherPay->created_at ? $otherPay->created_at->toDateString() : null);
                            $vpDate = $vPay->payment_date ? $vPay->payment_date->toDateString() : ($vPay->created_at ? $vPay->created_at->toDateString() : null);
                            if ($opDate && $vpDate && $opDate !== $vpDate) {
                                return $opDate < $vpDate;
                            }
                            if ($otherPay->created_at && $vPay->created_at && $otherPay->created_at != $vPay->created_at) {
                                return $otherPay->created_at < $vPay->created_at;
                            }
                            return $otherPay->id < $vPay->id;
                        })->sum('amount');
                        $trxBalanceBefore = max(0.0, round($totalTrxAmount - $priorPayments, 2));
                        $trxBalanceAfter = max(0.0, round($trxBalanceBefore - $payAmount, 2));
                    } else {
                        $totalTrxAmount = $payAmount;
                        $trxBalanceBefore = $payAmount;
                        $trxBalanceAfter = 0.0;
                    }
                @endphp
                <tr>
                    <td style="font-weight: bold;">{{ $trx?->transaction_code ?? 'TRX' }}</td>
                    <td>{{ $trx?->transaction_date ? $trx->transaction_date->format('d M Y') : '-' }}</td>
                    <td>Rp {{ number_format($totalTrxAmount, 0, ',', '.') }}</td>
                    <td style="font-weight: bold;">Rp {{ number_format($trxBalanceBefore, 0, ',', '.') }}</td>
                    <td style="text-transform: uppercase;">{{ $vPay->payment_method }}</td>
                    <td style="color: #15803d; font-weight: bold;">Rp {{ number_format($payAmount, 0, ',', '.') }}</td>
                    <td style="font-weight: bold; color: {{ $trxBalanceAfter > 0.005 ? '#b91c1c' : '#15803d' }};">
                        Rp {{ number_format($trxBalanceAfter, 0, ',', '.') }}
                    </td>
                </tr>
                @endif
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="5" style="text-align: right;">Total Pembayaran Piutang:</th>
                    <th colspan="2" style="color: #15803d; font-weight: bold;">Rp {{ number_format($totalOldDebtPaid, 0, ',', '.') }}</th>
                </tr>
            </tfoot>
        </table>
        @elseif ($totalOldDebtPaid > 0 && in_array($txStatus, ['piutang', 'mixed']))
        <table class="detail">
            <tr>
                <td class="k">Total Pembayaran Piutang</td>
                <td class="v" style="color: #15803d; font-weight: bold;">Rp {{ number_format($totalOldDebtPaid, 0, ',', '.') }}</td>
            </tr>
        </table>
        @endif
    </div>
</div>
@endif

<div class="section">
    <div class="section-title">RINGKASAN PIUTANG TOKO</div>
    <div class="section-body">
        <table class="summary-box" style="margin-bottom: 8px;">
            <tr>
                <td class="k" style="font-weight: bold; font-size: 10px;">Saldo Piutang Lama Sebelum Kunjungan</td>
                <td class="v" style="font-size: 10.5px;">Rp {{ number_format($storeBalanceBefore, 0, ',', '.') }}</td>
            </tr>
        </table>

        @if ($visitPayments->isNotEmpty())
        <div style="margin-top: 6px; margin-bottom: 3px; font-weight: bold; font-size: 9px; color: #475569;">
            PEMBAYARAN PIUTANG LAMA
        </div>
        <table class="table-inner" style="margin-bottom: 6px;">
            <thead>
                <tr>
                    <th>Kode Transaksi</th>
                    <th>Saldo Sebelum</th>
                    <th>Dibayar</th>
                    <th>Sisa</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($visitPayments as $vPay)
                @if ((float) $vPay->amount > 0)
                @php
                    $trx = $vPay->transaction;
                    $payAmount = (float) $vPay->amount;
                    if ($trx) {
                        $totalTrxAmount = (float) $trx->transaction_amount;
                        $allPayments = $trx->relationLoaded('payments') ? $trx->payments : $trx->payments()->get();
                        $priorPayments = (float) $allPayments->filter(function ($otherPay) use ($vPay) {
                            if ($otherPay->id === $vPay->id) return false;
                            if ($otherPay->payment_date && $vPay->payment_date && $otherPay->payment_date != $vPay->payment_date) {
                                return $otherPay->payment_date < $vPay->payment_date;
                            }
                            if ($otherPay->created_at && $vPay->created_at && $otherPay->created_at != $vPay->created_at) {
                                return $otherPay->created_at < $vPay->created_at;
                            }
                            return $otherPay->id < $vPay->id;
                        })->sum('amount');
                        $trxBalanceBefore = max(0.0, round($totalTrxAmount - $priorPayments, 2));
                        $trxBalanceAfter = max(0.0, round($trxBalanceBefore - $payAmount, 2));
                    } else {
                        $trxBalanceBefore = $payAmount;
                        $trxBalanceAfter = 0.0;
                    }
                @endphp
                <tr>
                    <td style="font-weight: bold;">{{ $trx?->transaction_code ?? 'TRX' }}</td>
                    <td>Rp {{ number_format($trxBalanceBefore, 0, ',', '.') }}</td>
                    <td style="color: #15803d; font-weight: bold;">Rp {{ number_format($payAmount, 0, ',', '.') }}</td>
                    <td style="font-weight: bold; color: {{ $trxBalanceAfter > 0.005 ? '#b91c1c' : '#15803d' }};">
                        Rp {{ number_format($trxBalanceAfter, 0, ',', '.') }}
                    </td>
                </tr>
                @endif
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="2" style="text-align: right;">Total Pembayaran Piutang Lama:</th>
                    <th colspan="2" style="color: #15803d; font-weight: bold;">Rp {{ number_format($totalOldDebtPaid, 0, ',', '.') }}</th>
                </tr>
            </tfoot>
        </table>
        @endif

        @if ($visitTransactions->isNotEmpty())
        <div style="margin-top: 6px; margin-bottom: 3px; font-weight: bold; font-size: 9px; color: #475569;">
            TRANSAKSI BARU
        </div>
        <table class="table-inner" style="margin-bottom: 6px;">
            <thead>
                <tr>
                    <th>Kode Transaksi</th>
                    <th>Nilai Transaksi</th>
                    <th>Uang Masuk Transaksi Baru</th>
                    <th>Sisa Menjadi Piutang</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($visitTransactions as $vTrx)
                @php
                    $allVTrxPayments = $vTrx->relationLoaded('payments') ? $vTrx->payments : $vTrx->payments()->get();
                    $trxInitialPaid = (float) $allVTrxPayments->filter(fn ($p) =>
                        (string)$p->source_id === (string)$visit->id ||
                        $p->source === 'initial_payment' ||
                        str_contains(strtolower($p->notes ?? ''), 'pembayaran awal')
                    )->sum('amount');
                    $trxAmount = (float) $vTrx->transaction_amount;
                    $trxRemaining = max(0.0, round($trxAmount - $trxInitialPaid, 2));
                @endphp
                <tr>
                    <td style="font-weight: bold;">{{ $vTrx->transaction_code }}</td>
                    <td>Rp {{ number_format($trxAmount, 0, ',', '.') }}</td>
                    <td style="color: #15803d;">Rp {{ number_format($trxInitialPaid, 0, ',', '.') }}</td>
                    <td style="font-weight: bold; color: {{ $trxRemaining > 0.005 ? '#b91c1c' : '#15803d' }};">
                        Rp {{ number_format($trxRemaining, 0, ',', '.') }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        <table class="summary-box" style="margin-top: 6px;">
            <tr>
                <td class="k">Saldo Piutang Sebelum Kunjungan</td>
                <td class="v" style="font-size: 10px;">Rp {{ number_format($storeBalanceBefore, 0, ',', '.') }}</td>
            </tr>
            @if ($totalOldDebtPaid > 0)
            <tr>
                <td class="k">(-) Pembayaran Piutang Lama</td>
                <td class="v" style="color: #15803d;">Rp {{ number_format($totalOldDebtPaid, 0, ',', '.') }}</td>
            </tr>
            @endif
            @if ($totalNewTx > 0)
            <tr>
                <td class="k">(+) Transaksi Baru</td>
                <td class="v">Rp {{ number_format($totalNewTx, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="k">(-) Uang Masuk Transaksi Baru</td>
                <td class="v" style="color: #15803d;">Rp {{ number_format($newInitialPaidTotal, 0, ',', '.') }}</td>
            </tr>
            @endif
            <tr class="highlight" style="border-top: 1.5px solid #b91c1c;">
                <td class="k" style="font-size: 11px;">Saldo Piutang Toko Setelah Kunjungan</td>
                <td class="v" style="font-size: 12px;">Rp {{ number_format($storeBalanceAfter, 0, ',', '.') }}</td>
            </tr>
        </table>
    </div>
</div>

<div class="section">
    <div class="section-title">DOKUMENTASI FOTO</div>
    <div class="section-body">
        <table class="photos">
            <tr>
                <td>
                    @if ($photoCheckIn && file_exists(public_path('storage/' . $photoCheckIn)))
                        <img src="{{ public_path('storage/' . $photoCheckIn) }}">
                    @elseif ($photoCheckIn && file_exists(storage_path('app/public/' . $photoCheckIn)))
                        <img src="{{ storage_path('app/public/' . $photoCheckIn) }}">
                    @else
                        <div class="no-photo">{{ $photoCheckIn ? 'Foto tidak tersedia' : 'Tidak ada foto' }}</div>
                    @endif
                    <div class="cap">Selfie Check In</div>
                </td>
                <td>
                    @if ($photoEtalase && file_exists(public_path('storage/' . $photoEtalase)))
                        <img src="{{ public_path('storage/' . $photoEtalase) }}">
                    @elseif ($photoEtalase && file_exists(storage_path('app/public/' . $photoEtalase)))
                        <img src="{{ storage_path('app/public/' . $photoEtalase) }}">
                    @else
                        <div class="no-photo">{{ $photoEtalase ? 'Foto tidak tersedia' : 'Tidak ada foto' }}</div>
                    @endif
                    <div class="cap">Foto Etalase / Toko</div>
                </td>
                <td>
                    @if ($photoCheckOutSelfie && file_exists(public_path('storage/' . $photoCheckOutSelfie)))
                        <img src="{{ public_path('storage/' . $photoCheckOutSelfie) }}">
                    @elseif ($photoCheckOutSelfie && file_exists(storage_path('app/public/' . $photoCheckOutSelfie)))
                        <img src="{{ storage_path('app/public/' . $photoCheckOutSelfie) }}">
                    @else
                        <div class="no-photo">{{ $photoCheckOutSelfie ? 'Foto tidak tersedia' : 'Tidak ada foto' }}</div>
                    @endif
                    <div class="cap">Selfie Check Out</div>
                </td>
            </tr>
        </table>
    </div>
</div>

@include('visit.pdf.partials.footer')
