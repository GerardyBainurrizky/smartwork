@include('reports.pdf.partials.styles')

@php
    use App\Services\StoreReceivableService;

    $skipped = $skipped ?? collect();
    $selectedUserLabel = $selectedUserLabel ?? 'Semua Sales';
    $statusLabel = $statusLabel ?? 'Semua Status';
    $printedByRole = $printedByRole ?? 'Admin';
    $dataCount = ($items->count()) + ($skipped->count());

    // Group visits by sales user
    $groupedBySales = $items->groupBy(fn($v) => $v->user_id ?? 'unknown');
@endphp

@include('reports.pdf.partials.header')

{{-- Extended metadata for Visits --}}
<table class="report-meta" style="margin-top: -6px; margin-bottom: 14px;">
    <tr>
        <td class="k">Sales</td>
        <td class="v">{{ $selectedUserLabel }}</td>
        <td class="k">Status</td>
        <td class="v">{{ $statusLabel }}</td>
    </tr>
    <tr>
        <td class="k">Role Pencetak</td>
        <td class="v" colspan="3">{{ $printedByRole }}</td>
    </tr>
</table>

@foreach($groupedBySales as $salesUserId => $salesVisits)
    @php
        $salesUser = $salesVisits->first()?->user;
        $salesName = $salesUser?->name ?? 'Sales Tidak Diketahui';
    @endphp

    @if($groupedBySales->count() > 1)
    <div style="background:#0DA4CE; color:#fff; font-weight:bold; font-size:12px; padding:7px 12px; margin-bottom:8px; border-radius:5px;">
        SALES: {{ $salesName }}
    </div>
    @endif

    @foreach($salesVisits as $index => $visit)
        @php
            $inPhoto = $visit->check_in_selfie ? public_path('storage/' . $visit->check_in_selfie) : null;
            $outPhoto = $visit->check_out_selfie ? public_path('storage/' . $visit->check_out_selfie) : null;
            $storePhoto = $visit->final_store_photo ?? $visit->storefront_photo;
            $storePhoto = $storePhoto ? public_path('storage/' . $storePhoto) : null;

            $duration = '-';
            if ($visit->check_in_at && $visit->check_out_at) {
                $minutes = max(0, (int) $visit->check_in_at->diffInMinutes($visit->check_out_at));
                $duration = $minutes >= 60
                    ? (intdiv($minutes, 60) . ' Jam ' . ($minutes % 60) . ' Menit')
                    : $minutes . ' Menit';
            }

            $hasCheckOut = (bool) $visit->check_out_at;
            $hasCheckInCoord = $visit->check_in_lat && $visit->check_in_lng;
            $hasCheckOutCoord = $hasCheckOut && $visit->check_out_lat && $visit->check_out_lng;
            $checkInCoord = $hasCheckInCoord ? round((float)$visit->check_in_lat, 6) . ', ' . round((float)$visit->check_in_lng, 6) : '-';
            $checkOutCoord = $hasCheckOutCoord ? round((float)$visit->check_out_lat, 6) . ', ' . round((float)$visit->check_out_lng, 6) : 'Belum Check Out';
            $checkOutAddress = $hasCheckOut ? ($visit->check_out_address ?? '-') : 'Belum Check Out';
            $checkInMapUrl = $hasCheckInCoord ? 'https://www.google.com/maps?q=' . $visit->check_in_lat . ',' . $visit->check_in_lng : null;
            $checkOutMapUrl = $hasCheckOutCoord ? 'https://www.google.com/maps?q=' . $visit->check_out_lat . ',' . $visit->check_out_lng : null;

            // ======= FINANSIAL KUNJUNGAN =======

            // Transaksi Baru (via ledger StoreTransaction)
            $vTransactions = $visit->transactions ?? collect();
            $newTxData = collect();

            if ($vTransactions->isNotEmpty()) {
                foreach ($vTransactions as $tx) {
                    $txAmount = (float) $tx->transaction_amount;
                    $txPayments = $tx->payments ?? collect();
                    $initPaid = (float) $txPayments->filter(fn($p) =>
                        $p->source === 'initial_payment' || str_contains(strtolower($p->notes ?? ''), 'pembayaran awal')
                    )->sum('amount');
                    $remaining = max(0.0, round($txAmount - $initPaid, 2));

                    $initPayObj = $txPayments->filter(fn($p) =>
                        $p->source === 'initial_payment' || str_contains(strtolower($p->notes ?? ''), 'pembayaran awal')
                    )->first();
                    $payMethod = $initPayObj ? strtoupper($initPayObj->payment_method ?? '-') : '-';

                    $newTxData->push([
                        'code' => $tx->transaction_code ?? '-',
                        'amount' => $txAmount,
                        'paid' => $initPaid,
                        'remaining' => $remaining,
                        'method' => $payMethod,
                    ]);
                }
            } elseif ($visit->transaction_amount > 0 && in_array($visit->transaction_status, ['paid', 'mixed'])) {
                $txAmount = (float) $visit->transaction_amount;
                $initPaid = $visit->transaction_status === 'paid' ? (float)($visit->cash_received ?: $txAmount) : 0.0;
                $remaining = max(0.0, round($txAmount - $initPaid, 2));
                $newTxData->push([
                    'code' => 'VISIT-' . substr((string)$visit->id, 0, 8),
                    'amount' => $txAmount,
                    'paid' => $initPaid,
                    'remaining' => $remaining,
                    'method' => strtoupper($visit->payment_method ?? 'TUNAI'),
                ]);
            }

            // Pembayaran Piutang Lama
            $vPayments = ($visit->payments ?? collect())->filter(fn($p) =>
                $p->source !== 'initial_payment' && !str_contains(strtolower($p->notes ?? ''), 'pembayaran awal')
            );
            $oldDebtData = collect();
            foreach ($vPayments as $pay) {
                $payAmount = (float) $pay->amount;
                if ($payAmount <= 0) continue;
                $trx = $pay->transaction;
                if ($trx) {
                    $totalTrxAmount = (float) $trx->transaction_amount;
                    $allTrxPayments = $trx->relationLoaded('payments') ? $trx->payments : $trx->payments()->get();
                    $priorPaid = (float) $allTrxPayments->filter(function($op) use ($pay) {
                        if ($op->id === $pay->id) return false;
                        $opDate = $op->payment_date ? $op->payment_date->toDateString() : ($op->created_at ? $op->created_at->toDateString() : null);
                        $payDate = $pay->payment_date ? $pay->payment_date->toDateString() : ($pay->created_at ? $pay->created_at->toDateString() : null);
                        if ($opDate && $payDate && $opDate !== $payDate) {
                            return $opDate < $payDate;
                        }
                        if ($op->created_at && $pay->created_at && $op->created_at != $pay->created_at) {
                            return $op->created_at < $pay->created_at;
                        }
                        return $op->id < $pay->id;
                    })->sum('amount');
                    $balBefore = max(0.0, round($totalTrxAmount - $priorPaid, 2));
                    $balAfter = max(0.0, round($balBefore - $payAmount, 2));
                } else {
                    $totalTrxAmount = $payAmount;
                    $balBefore = $payAmount;
                    $balAfter = 0.0;
                }
                $oldDebtData->push([
                    'trx_code' => $trx?->transaction_code ?? '-',
                    'balance_before' => $balBefore,
                    'paid' => $payAmount,
                    'balance_after' => $balAfter,
                    'method' => strtoupper($pay->payment_method ?? 'TUNAI'),
                ]);
            }

            // Ringkasan Piutang Toko (Menggunakan service ledger terpusat)
            $recSummary = StoreReceivableService::getVisitReceivableSummary($visit);
            $balanceBefore = $recSummary['balance_before'];
            $totalOldDebtPaid = $recSummary['old_debt_paid'];
            $totalNewTxRemaining = $recSummary['new_tx_remaining'];
            $balanceAfter = $recSummary['balance_after'];
        @endphp

        <div class="section">
            <div class="section-title">
                Kunjungan #{{ $index + 1 }} &mdash; {{ $visit->store->name ?? 'Toko' }}
                @if($visit->status === 'completed')
                    <span class="badge badge-green">Selesai</span>
                @else
                    <span class="badge badge-cyan">Sedang Berjalan</span>
                @endif
            </div>
            <div class="section-body">
                <table class="detail">
                    <tr>
                        <td class="k">Nama Petugas</td>
                        <td class="v">{{ $visit->user->name ?? '-' }}</td>
                        <td class="k">Rute Wilayah</td>
                        <td class="v">{{ $visit->route->name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="k">Nama Toko</td>
                        <td class="v">{{ $visit->store->name ?? '-' }}</td>
                        <td class="k">Kode Toko</td>
                        <td class="v">{{ $visit->store->code ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="k">Alamat Toko</td>
                        <td class="v" colspan="3">{{ $visit->store->address ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="k">Estimasi Durasi</td>
                        <td class="v">{{ $visit->routeStop?->estimated_duration_minutes ? $visit->routeStop->estimated_duration_minutes . ' menit' : '-' }}</td>
                        <td class="k">Catatan Rencana</td>
                        <td class="v">{{ $visit->routeStop?->notes ?: '-' }}</td>
                    </tr>
                    <tr>
                        <td class="k">Check In</td>
                        <td class="v">{{ $visit->check_in_at?->format('d M Y, H:i') ?? '-' }}</td>
                        <td class="k">Check Out</td>
                        <td class="v">{{ $visit->check_out_at?->format('d M Y, H:i') ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="k">Durasi</td>
                        <td class="v">{{ $duration }}</td>
                        <td class="k">Status</td>
                        <td class="v">{{ $visit->status === 'completed' ? 'Selesai' : 'Sedang Berjalan' }}</td>
                    </tr>
                    <tr>
                        <td class="k">Catatan Kunjungan</td>
                        <td class="v">{{ $visit->initial_notes ?? '-' }}</td>
                        <td class="k">Hasil Kunjungan</td>
                        <td class="v">{{ $visit->visit_result ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="k">Lokasi Check In</td>
                        <td class="v">{{ $visit->check_in_address ?? '-' }}</td>
                        <td class="k">Lokasi Check Out</td>
                        <td class="v">{{ $checkOutAddress }}</td>
                    </tr>
                    @if($checkInMapUrl || $checkOutMapUrl)
                    <tr>
                        <td class="k">Peta Check In</td>
                        <td class="v">
                            @if($checkInMapUrl)
                                <a class="maps-link" href="{{ $checkInMapUrl }}">Lihat Lokasi Check In</a>
                                <div style="font-weight:400;color:#6b7280;font-size:8px;">{{ $checkInCoord }}</div>
                            @else
                                -
                            @endif
                        </td>
                        <td class="k">Peta Check Out</td>
                        <td class="v">
                            @if($checkOutMapUrl)
                                <a class="maps-link" href="{{ $checkOutMapUrl }}">Lihat Lokasi Check Out</a>
                                <div style="font-weight:400;color:#6b7280;font-size:8px;">{{ $checkOutCoord }}</div>
                            @else
                                {{ $hasCheckOut ? '-' : 'Belum Check Out' }}
                            @endif
                        </td>
                    </tr>
                    @endif
                </table>

                {{-- ===== TRANSAKSI BARU ===== --}}
                <div style="margin-top:10px; font-weight:bold; font-size:10px; color:#097A99; border-bottom:1px solid #0DA4CE; padding-bottom:2px;">
                    TRANSAKSI BARU
                </div>
                @if($newTxData->isNotEmpty())
                    <table class="data" style="margin-top:5px; font-size:8.5px;">
                        <thead>
                            <tr>
                                <th>Kode Transaksi</th>
                                <th>Nilai Transaksi</th>
                                <th>Pembayaran Awal</th>
                                <th>Metode</th>
                                <th>Sisa Transaksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($newTxData as $tx)
                            <tr>
                                <td>{{ $tx['code'] }}</td>
                                <td style="text-align:right;">Rp {{ number_format($tx['amount'], 0, ',', '.') }}</td>
                                <td style="text-align:right;">Rp {{ number_format($tx['paid'], 0, ',', '.') }}</td>
                                <td style="text-align:center;">{{ $tx['method'] }}</td>
                                <td style="text-align:right;">Rp {{ number_format($tx['remaining'], 0, ',', '.') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div style="font-size:8.5px; color:#6b7280; padding:4px 0;">Tidak ada transaksi baru pada kunjungan ini.</div>
                @endif

                {{-- ===== PEMBAYARAN PIUTANG LAMA ===== --}}
                <div style="margin-top:10px; font-weight:bold; font-size:10px; color:#15803D; border-bottom:1px solid #16a34a; padding-bottom:2px;">
                    PEMBAYARAN PIUTANG LAMA
                </div>
                @if($oldDebtData->isNotEmpty())
                    <table class="data" style="margin-top:5px; font-size:8.5px;">
                        <thead>
                            <tr>
                                <th>Kode Transaksi</th>
                                <th>Saldo Sebelum</th>
                                <th>Pembayaran</th>
                                <th>Metode</th>
                                <th>Saldo Setelah</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($oldDebtData as $pay)
                            <tr>
                                <td>{{ $pay['trx_code'] }}</td>
                                <td style="text-align:right;">Rp {{ number_format($pay['balance_before'], 0, ',', '.') }}</td>
                                <td style="text-align:right;">Rp {{ number_format($pay['paid'], 0, ',', '.') }}</td>
                                <td style="text-align:center;">{{ $pay['method'] }}</td>
                                <td style="text-align:right;">Rp {{ number_format($pay['balance_after'], 0, ',', '.') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div style="font-size:8.5px; color:#6b7280; padding:4px 0;">Tidak ada pembayaran piutang lama pada kunjungan ini.</div>
                @endif

                {{-- ===== RINGKASAN PIUTANG TOKO ===== --}}
                <div style="margin-top:10px; font-weight:bold; font-size:10px; color:#7c3aed; border-bottom:1px solid #7c3aed; padding-bottom:2px;">
                    RINGKASAN PIUTANG TOKO
                </div>
                <table class="detail" style="margin-top:5px; font-size:8.5px;">
                    <tr>
                        <td class="k" style="color:#6b7280;">Saldo Piutang Lama Sebelum Kunjungan</td>
                        <td class="v">Rp {{ number_format($balanceBefore, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="k" style="color:#6b7280;">Pembayaran Piutang Lama</td>
                        <td class="v" style="color:#15803D;">- Rp {{ number_format($totalOldDebtPaid, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="k" style="color:#6b7280;">Piutang Baru dari Transaksi Kunjungan</td>
                        <td class="v" style="color:#dc2626;">+ Rp {{ number_format($totalNewTxRemaining, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="k" style="color:#6b7280; font-weight:bold;">Saldo Piutang Toko Setelah Kunjungan</td>
                        <td class="v" style="font-weight:bold;">Rp {{ number_format($balanceAfter, 0, ',', '.') }}</td>
                    </tr>
                </table>

                {{-- Foto --}}
                <table class="photos" style="margin-top:10px;">
                    <tr>
                        <td>
                            @if ($inPhoto && file_exists($inPhoto))
                                <img src="{{ $inPhoto }}">
                            @else
                                <div class="no-photo">Tidak ada foto</div>
                            @endif
                            <div class="cap">Selfie Check In</div>
                        </td>
                        <td>
                            @if ($outPhoto && file_exists($outPhoto))
                                <img src="{{ $outPhoto }}">
                            @else
                                <div class="no-photo">Tidak ada foto</div>
                            @endif
                            <div class="cap">Selfie Check Out</div>
                        </td>
                        <td>
                            @if ($storePhoto && file_exists($storePhoto))
                                <img src="{{ $storePhoto }}">
                            @else
                                <div class="no-photo">Tidak ada foto</div>
                            @endif
                            <div class="cap">Foto Display Toko</div>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    @endforeach

    @if($groupedBySales->count() > 1 && !$loop->last)
        <div style="border-top:2px solid #0DA4CE; margin: 16px 0 12px 0;"></div>
    @endif
@endforeach

@if($skipped->isNotEmpty())
    <div class="section">
        <div class="section-title" style="background:#b91c1c;">
            KUNJUNGAN DILEWATI (SKIP)
        </div>
        <div class="section-body">
            <table class="data">
                <thead>
                    <tr>
                        <th style="width:4%">No</th>
                        <th>Nama Petugas</th>
                        <th>Rute Wilayah</th>
                        <th>Nama Toko</th>
                        <th>Tanggal Route</th>
                        <th>Alasan</th>
                        <th style="width:22%; text-align:center;">Foto Bukti Dilewati</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($skipped as $sIndex => $stop)
                        @php
                            $skipVisit = $stop->visit ?? $stop->anyVisit;
                            $skipPhoto = $skipVisit?->final_store_photo ?: $skipVisit?->storefront_photo;
                            $skipReason = $stop->notes ?? '-';

                            if (empty($skipPhoto) && !empty($stop->notes) && str_starts_with($stop->notes, '{')) {
                                $meta = json_decode($stop->notes, true);
                                if (is_array($meta)) {
                                    $skipPhoto = $meta['photo_path'] ?? null;
                                    $skipReason = $meta['reason'] ?? $skipReason;
                                }
                            }

                            $fullSkipPhoto = null;
                            if ($skipPhoto) {
                                if (file_exists(public_path('storage/' . $skipPhoto))) {
                                    $fullSkipPhoto = public_path('storage/' . $skipPhoto);
                                } elseif (file_exists(storage_path('app/public/' . $skipPhoto))) {
                                    $fullSkipPhoto = storage_path('app/public/' . $skipPhoto);
                                } elseif (\Illuminate\Support\Facades\Storage::disk('public')->exists($skipPhoto)) {
                                    $fullSkipPhoto = \Illuminate\Support\Facades\Storage::disk('public')->path($skipPhoto);
                                }
                            }
                        @endphp
                        <tr>
                            <td>{{ $sIndex + 1 }}</td>
                            <td>{{ $stop->route->user->name ?? '-' }}</td>
                            <td>{{ $stop->route->name ?? '-' }}</td>
                            <td>{{ $stop->store->name ?? '-' }}</td>
                            <td>{{ $stop->route->date ? \Carbon\Carbon::parse($stop->route->date)->format('d M Y') : '-' }}</td>
                            <td>{{ $skipReason }}</td>
                            <td style="text-align:center; vertical-align:middle; padding:4px;">
                                @if($fullSkipPhoto)
                                    <img src="{{ $fullSkipPhoto }}" style="max-width:100px; max-height:80px; width:auto; height:auto; border:1px solid #e5e7eb; border-radius:4px; display:inline-block;">
                                @else
                                    <div class="no-photo" style="height:40px; line-height:40px; font-size:8px;">Tidak ada foto</div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

@if($items->isEmpty() && $skipped->isEmpty())
    <div class="empty">Tidak ada data kunjungan pada periode ini.</div>
@endif

@include('reports.pdf.partials.footer')
