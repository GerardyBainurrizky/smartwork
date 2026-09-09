@include('reports.pdf.partials.styles')

<style>
    .trx-sub-title {
        font-size: 9.5px;
        font-weight: bold;
        color: #0b7494;
        margin-top: 8px;
        margin-bottom: 4px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 1px solid #e0f2fe;
        padding-bottom: 2px;
    }
    .summary-card-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 0;
    }
    .summary-card-table td {
        padding: 5px 8px;
        font-size: 8.5px;
        border: 1px solid #e5e7eb;
        vertical-align: middle;
    }
    .summary-card-table td.lbl {
        background: #f8fafc;
        color: #4b5563;
        font-weight: 600;
        width: 18%;
    }
    .summary-card-table td.val {
        font-weight: bold;
        width: 15%;
    }
    .text-box {
        font-size: 8.5px;
        line-height: 1.4;
        padding: 5px 8px;
        background: #f9fafb;
        border: 1px solid #e5e7eb;
        border-radius: 4px;
        color: #1f2937;
        white-space: pre-wrap;
        word-wrap: break-word;
    }
    .formula-box {
        font-size: 8px;
        color: #334155;
        margin-top: 5px;
        padding: 4px 8px;
        background: #f0f9ff;
        border: 1px solid #bae6fd;
        border-radius: 4px;
        line-height: 1.35;
    }
    .table-sub {
        width: 100%;
        border-collapse: collapse;
        margin-top: 3px;
        margin-bottom: 4px;
    }
    .table-sub th {
        background: #0DA4CE;
        color: #ffffff;
        font-size: 8px;
        font-weight: bold;
        padding: 4px 5px;
        border: 1px solid #0B7494;
        text-align: left;
    }
    .table-sub td {
        font-size: 8px;
        padding: 4px 5px;
        border: 1px solid #e5e7eb;
        vertical-align: top;
    }
    .table-sub tr:nth-child(even) td {
        background: #f8fafc;
    }
    .table-sub tfoot td {
        background: #f1f5f9;
        font-weight: bold;
        border-top: 1px solid #cbd5e1;
    }
    .empty-note {
        font-style: italic;
        color: #64748b;
        font-size: 8.5px;
        margin: 3px 0 6px 0;
        padding: 4px 6px;
        background: #f8fafc;
        border-radius: 3px;
        border: 1px dashed #e2e8f0;
    }
</style>

@php
    $selectedUserLabel = $selectedUserLabel ?? 'Semua Sales';
    $txStatusLabel = $txStatusLabel ?? 'Semua Status';
    $dataCount = $items->count();

    $sumTransactionsCount = 0;
    $sumNilaiTransaksi = 0.0;
    $sumPembayaranBaru = 0.0;
    $sumPembayaranLama = 0.0;
    $sumSisaPiutang = 0.0;

    foreach ($items as $item) {
        $summary = \App\Services\StoreReceivableService::getVisitReceivableSummary($item);
        $vTxCount = $item->transactions->count() ?: (($item->transaction_amount > 0 && in_array($item->transaction_status, ['paid', 'mixed'], true)) ? 1 : 0);

        $item->calc_tx_count = $vTxCount;
        $item->calc_nilai_tx = $summary['new_tx_total'];
        $item->calc_bayar_baru = $summary['new_tx_initial_paid'];
        $item->calc_sisa = $summary['new_tx_remaining'];
        $item->calc_saldo_sebelum = $summary['balance_before'];
        $item->calc_bayar_lama = $summary['old_debt_paid'];
        $item->calc_saldo_setelah = $summary['balance_after'];

        $sumTransactionsCount += $vTxCount;
        $sumNilaiTransaksi += $summary['new_tx_total'];
        $sumPembayaranBaru += $summary['new_tx_initial_paid'];
        $sumPembayaranLama += $summary['old_debt_paid'];
        $sumSisaPiutang += $summary['new_tx_remaining'];
    }

    $sumTotalCashIn = $sumPembayaranBaru + $sumPembayaranLama;
    $dataCountLabel = $dataCount . ' Kunjungan (' . $sumTransactionsCount . ' Transaksi Baru)';
@endphp

@include('reports.pdf.partials.header')

{{-- Metadata Filter Tambahan --}}
<table class="report-meta" style="margin-bottom: 10px;">
    <tr>
        <td class="k" style="width: 18%;">Sales Penanggung Jawab</td>
        <td class="v" style="width: 32%;">{{ $selectedUserLabel }}</td>
        <td class="k" style="width: 18%;">Status Transaksi</td>
        <td class="v" style="width: 32%;">{{ $txStatusLabel }}</td>
    </tr>
    @if(!empty($selectedStoreLabel))
    <tr>
        <td class="k">Toko Pelanggan</td>
        <td class="v" colspan="3">{{ $selectedStoreLabel }}</td>
    </tr>
    @endif
</table>

{{-- Ringkasan Statistik Global --}}
<div class="section" style="margin-bottom: 14px;">
    <div class="section-title">RINGKASAN STATISTIK TRANSAKSI &amp; KUNJUNGAN SALES</div>
    <div class="section-body" style="padding: 6px;">
        <table class="summary-card-table">
            <tr>
                <td class="lbl">Jumlah Kunjungan</td>
                <td class="val" style="color: #1f2937;">{{ $dataCount }} Kunjungan</td>
                <td class="lbl">Total Transaksi Baru</td>
                <td class="val" style="color: #0b7494;">{{ $sumTransactionsCount }} Transaksi</td>
                <td class="lbl">Total Uang Masuk</td>
                <td class="val" style="color: #15803d;">Rp {{ number_format($sumTotalCashIn, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="lbl">Nilai Transaksi Baru</td>
                <td class="val" style="color: #0b7494;">Rp {{ number_format($sumNilaiTransaksi, 0, ',', '.') }}</td>
                <td class="lbl">Pembayaran Baru</td>
                <td class="val" style="color: #166534;">Rp {{ number_format($sumPembayaranBaru, 0, ',', '.') }}</td>
                <td class="lbl">Piutang Baru Transaksi</td>
                <td class="val" style="color: #b91c1c;">Rp {{ number_format($sumSisaPiutang, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="lbl">Pembayaran Piutang Lama</td>
                <td class="val" style="color: #0f766e;" colspan="5">Rp {{ number_format($sumPembayaranLama, 0, ',', '.') }}</td>
            </tr>
        </table>
    </div>
</div>

{{-- Detail Setiap Kunjungan --}}
@forelse($items as $index => $visit)
    @php
        $recSummary = \App\Services\StoreReceivableService::getVisitReceivableSummary($visit);

        // 1. Kumpulkan data Transaksi Baru
        $vTransactions = $visit->transactions ?? collect();
        $newTxList = collect();

        if ($vTransactions->isNotEmpty()) {
            foreach ($vTransactions as $tx) {
                $txAmount = (float) $tx->transaction_amount;
                $txPayments = $tx->relationLoaded('payments') ? $tx->payments : $tx->payments()->get();
                $initPaid = (float) $txPayments->filter(fn($p) =>
                    $p->source === 'initial_payment' || str_contains(strtolower($p->notes ?? ''), 'pembayaran awal')
                )->sum('amount');
                $remaining = max(0.0, round($txAmount - $initPaid, 2));

                $initPayObj = $txPayments->filter(fn($p) =>
                    $p->source === 'initial_payment' || str_contains(strtolower($p->notes ?? ''), 'pembayaran awal')
                )->first();
                $payMethod = $initPayObj ? strtoupper((string)($initPayObj->payment_method ?: '-')) : ($tx->payments->first() ? strtoupper((string)$tx->payments->first()->payment_method) : '-');
                $status = $remaining <= 0.005 ? 'LUNAS' : ($initPaid > 0.005 ? 'SEBAGIAN' : 'BELUM LUNAS');
                $txDate = $tx->transaction_date ? $tx->transaction_date->format('d/m/Y') : ($tx->created_at ? $tx->created_at->format('d/m/Y') : '-');

                $newTxList->push([
                    'code' => $tx->transaction_code ?? '-',
                    'date' => $txDate,
                    'amount' => $txAmount,
                    'paid' => $initPaid,
                    'remaining' => $remaining,
                    'method' => $payMethod,
                    'status' => $status,
                ]);
            }
        } elseif ((float) $visit->transaction_amount > 0 && in_array($visit->transaction_status, ['paid', 'mixed'], true)) {
            $txAmount = (float) $visit->transaction_amount;
            $initPaid = $visit->transaction_status === 'paid' ? (float) ($visit->cash_received ?: $txAmount) : 0.0;
            $remaining = max(0.0, round($txAmount - $initPaid, 2));
            $status = $visit->transaction_status === 'paid' ? 'LUNAS' : 'BELUM LUNAS';
            $newTxList->push([
                'code' => 'TRX-VISIT-' . substr((string) $visit->id, 0, 8),
                'date' => $visit->check_in_at ? $visit->check_in_at->format('d/m/Y') : '-',
                'amount' => $txAmount,
                'paid' => $initPaid,
                'remaining' => $remaining,
                'method' => strtoupper((string) ($visit->payment_method ?: 'TUNAI')),
                'status' => $status,
            ]);
        }

        // 2. Kumpulkan data Pembayaran Piutang Lama
        $vPayments = ($visit->payments ?? collect())->filter(fn($p) =>
            $p->source !== 'initial_payment' && !str_contains(strtolower($p->notes ?? ''), 'pembayaran awal')
        );
        $oldDebtList = collect();

        if ($vPayments->isNotEmpty()) {
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
                    $trxCode = $trx->transaction_code ?? '-';
                } else {
                    $totalTrxAmount = $payAmount;
                    $balBefore = $payAmount;
                    $balAfter = 0.0;
                    $trxCode = 'Piutang Toko';
                }
                $payDate = $pay->payment_date ? $pay->payment_date->format('d/m/Y') : ($pay->created_at ? $pay->created_at->format('d/m/Y') : ($visit->check_in_at ? $visit->check_in_at->format('d/m/Y') : '-'));
                $oldDebtList->push([
                    'trx_code' => $trxCode,
                    'orig_amount' => $totalTrxAmount,
                    'balance_before' => $balBefore,
                    'paid' => $payAmount,
                    'balance_after' => $balAfter,
                    'method' => strtoupper((string) ($pay->payment_method ?: 'TUNAI')),
                    'date' => $payDate,
                    'notes' => $pay->notes ?: '-',
                ]);
            }
        } elseif ($recSummary['old_debt_paid'] > 0) {
            $oldDebtList->push([
                'trx_code' => 'Piutang Toko',
                'orig_amount' => $recSummary['old_debt_paid'],
                'balance_before' => $recSummary['old_debt_paid'],
                'paid' => $recSummary['old_debt_paid'],
                'balance_after' => 0.0,
                'method' => strtoupper((string) ($visit->payment_method ?: 'TUNAI')),
                'date' => $visit->check_in_at ? $visit->check_in_at->format('d/m/Y') : '-',
                'notes' => $visit->final_notes ?: '-',
            ]);
        }

        $statusTrxLabel = match ($visit->transaction_status) {
            'paid' => 'Transaksi Baru',
            'piutang' => 'Bayar Piutang',
            'mixed' => 'Transaksi + Piutang',
            default => 'Tanpa Transaksi',
        };

        $statusBadgeClass = in_array($visit->transaction_status, ['paid', 'mixed']) ? 'badge-green' : ($visit->transaction_status === 'piutang' ? 'badge-amber' : 'badge-gray');
    @endphp

    <div class="section" style="margin-bottom: 14px; page-break-inside: avoid;">
        {{-- Header Bar Kunjungan --}}
        <div class="section-title" style="display: table; width: 100%; box-sizing: border-box;">
            <div style="display: table-cell; vertical-align: middle;">
                KUNJUNGAN #{{ $index + 1 }} &mdash; {{ $visit->store->name ?? 'Toko' }} ({{ $visit->store->code ?? '-' }})
            </div>
            <div style="display: table-cell; text-align: right; vertical-align: middle;">
                <span class="badge badge-green" style="background:#ffffff; color:#0b7494; margin-right: 4px;">Selesai</span>
                <span class="badge" style="background:#ffffff; color:#1f2937;">{{ $statusTrxLabel }}</span>
            </div>
        </div>

        <div class="section-body" style="padding: 8px 10px;">
            {{-- Tabel Metadata Kunjungan --}}
            <table class="detail" style="margin-bottom: 6px;">
                <tr>
                    <td class="k" style="width: 16%;">Tanggal &amp; Waktu</td>
                    <td class="v" style="width: 34%;">
                        {{ $visit->check_in_at?->format('d/m/Y') ?? '-' }}
                        <span style="color:#6b7280; font-weight:normal;">({{ $visit->check_in_at?->format('H:i') ?? '-' }} - {{ $visit->check_out_at?->format('H:i') ?? 'Selesai' }} WIB)</span>
                    </td>
                    <td class="k" style="width: 16%;">Petugas Sales</td>
                    <td class="v" style="width: 34%;">{{ $visit->user->name ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="k">Toko Pelanggan</td>
                    <td class="v">{{ $visit->store->name ?? '-' }} ({{ $visit->store->code ?? '-' }})</td>
                    <td class="k">ID Kunjungan</td>
                    <td class="v" style="font-family: monospace; font-size: 8px;">{{ $visit->id }}</td>
                </tr>
                <tr>
                    <td class="k">Alamat Toko</td>
                    <td class="v" colspan="3">{{ $visit->store->address ?? '-' }}</td>
                </tr>
            </table>

            {{-- A. TRANSAKSI BARU --}}
            <div class="trx-sub-title">A. TRANSAKSI BARU</div>
            @if($newTxList->isNotEmpty())
                <table class="table-sub">
                    <thead>
                        <tr>
                            <th style="width: 18%;">Kode Transaksi</th>
                            <th style="width: 12%;">Tanggal</th>
                            <th style="width: 18%; text-align: right;">Nilai Transaksi (Rp)</th>
                            <th style="width: 18%; text-align: right;">Pembayaran Baru (Rp)</th>
                            <th style="width: 18%; text-align: right;">Piutang Baru (Rp)</th>
                            <th style="width: 8%; text-align: center;">Metode</th>
                            <th style="width: 8%; text-align: center;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($newTxList as $ntx)
                            <tr>
                                <td style="font-family: monospace; font-weight: bold; color: #0b7494;">{{ $ntx['code'] }}</td>
                                <td>{{ $ntx['date'] }}</td>
                                <td style="text-align: right; font-weight: bold; color: #0b7494;">Rp {{ number_format($ntx['amount'], 0, ',', '.') }}</td>
                                <td style="text-align: right; font-weight: bold; color: #166534;">Rp {{ number_format($ntx['paid'], 0, ',', '.') }}</td>
                                <td style="text-align: right; font-weight: bold; color: #b91c1c;">Rp {{ number_format($ntx['remaining'], 0, ',', '.') }}</td>
                                <td style="text-align: center;">{{ $ntx['method'] }}</td>
                                <td style="text-align: center;">
                                    <span class="badge {{ $ntx['status'] === 'LUNAS' ? 'badge-green' : ($ntx['status'] === 'SEBAGIAN' ? 'badge-amber' : 'badge-red') }}">
                                        {{ $ntx['status'] }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    @if($newTxList->count() > 1)
                        <tfoot>
                            <tr>
                                <td colspan="2" style="text-align: right; font-weight: bold;">TOTAL TRANSAKSI BARU:</td>
                                <td style="text-align: right; color: #0b7494;">Rp {{ number_format($recSummary['new_tx_total'], 0, ',', '.') }}</td>
                                <td style="text-align: right; color: #166534;">Rp {{ number_format($recSummary['new_tx_initial_paid'], 0, ',', '.') }}</td>
                                <td style="text-align: right; color: #b91c1c;">Rp {{ number_format($recSummary['new_tx_remaining'], 0, ',', '.') }}</td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            @else
                <div class="empty-note">Tidak ada transaksi baru pada kunjungan ini.</div>
            @endif

            {{-- B. PEMBAYARAN PIUTANG LAMA --}}
            <div class="trx-sub-title" style="margin-top: 8px;">B. PEMBAYARAN PIUTANG LAMA</div>
            @if($oldDebtList->isNotEmpty())
                <table class="table-sub">
                    <thead>
                        <tr>
                            <th style="width: 4%; text-align: center;">No</th>
                            <th style="width: 20%;">Kode Transaksi Piutang Lama</th>
                            <th style="width: 15%; text-align: right;">Nilai Transaksi (Rp)</th>
                            <th style="width: 15%; text-align: right;">Saldo Sebelum (Rp)</th>
                            <th style="width: 15%; text-align: right;">Pembayaran (Rp)</th>
                            <th style="width: 15%; text-align: right;">Sisa Setelah (Rp)</th>
                            <th style="width: 8%; text-align: center;">Metode</th>
                            <th style="width: 8%; text-align: center;">Tanggal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($oldDebtList as $pIndex => $op)
                            <tr>
                                <td style="text-align: center;">{{ $pIndex + 1 }}</td>
                                <td style="font-family: monospace; font-weight: bold; color: #0f766e;">{{ $op['trx_code'] }}</td>
                                <td style="text-align: right;">Rp {{ number_format($op['orig_amount'], 0, ',', '.') }}</td>
                                <td style="text-align: right; color: #d97706;">Rp {{ number_format($op['balance_before'], 0, ',', '.') }}</td>
                                <td style="text-align: right; font-weight: bold; color: #0f766e;">Rp {{ number_format($op['paid'], 0, ',', '.') }}</td>
                                <td style="text-align: right; font-weight: bold; color: {{ $op['balance_after'] > 0 ? '#b91c1c' : '#166534' }};">Rp {{ number_format($op['balance_after'], 0, ',', '.') }}</td>
                                <td style="text-align: center;">{{ $op['method'] }}</td>
                                <td style="text-align: center;">{{ $op['date'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="4" style="text-align: right; font-weight: bold;">TOTAL PEMBAYARAN PIUTANG LAMA:</td>
                            <td style="text-align: right; font-weight: bold; color: #0f766e;">Rp {{ number_format($recSummary['old_debt_paid'], 0, ',', '.') }}</td>
                            <td colspan="3"></td>
                        </tr>
                    </tfoot>
                </table>
            @else
                <div class="empty-note">Tidak ada pembayaran piutang lama pada kunjungan ini.</div>
            @endif

            {{-- C. RINGKASAN PERUBAHAN PIUTANG TOKO --}}
            <div class="trx-sub-title" style="margin-top: 8px;">C. RINGKASAN PERUBAHAN PIUTANG TOKO</div>
            <table class="summary-card-table" style="margin-top: 3px;">
                <tr>
                    <td class="lbl">Saldo Piutang Sebelum Kunjungan</td>
                    <td class="val" style="color: #d97706;">Rp {{ number_format($recSummary['balance_before'], 0, ',', '.') }}</td>
                    <td class="lbl">Nilai Transaksi Baru</td>
                    <td class="val" style="color: #0b7494;">Rp {{ number_format($recSummary['new_tx_total'], 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td class="lbl">Pembayaran Piutang Lama</td>
                    <td class="val" style="color: #0f766e;">- Rp {{ number_format($recSummary['old_debt_paid'], 0, ',', '.') }}</td>
                    <td class="lbl">Pembayaran Transaksi Baru</td>
                    <td class="val" style="color: #166534;">Rp {{ number_format($recSummary['new_tx_initial_paid'], 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td class="lbl" style="background: #eef2ff;">Piutang Baru dari Transaksi</td>
                    <td class="val" style="background: #eef2ff; color: #b91c1c;">+ Rp {{ number_format($recSummary['new_tx_remaining'], 0, ',', '.') }}</td>
                    <td class="lbl" style="background: #eef2ff; font-weight: bold; color: #312e81;">Saldo Piutang Setelah Kunjungan</td>
                    <td class="val" style="background: #eef2ff; color: #3730a3; font-size: 9.5px;">Rp {{ number_format($recSummary['balance_after'], 0, ',', '.') }}</td>
                </tr>
            </table>
            <div class="formula-box">
                <strong>Formula Rekonsiliasi:</strong> Saldo Sebelum (Rp {{ number_format($recSummary['balance_before'], 0, ',', '.') }}) &minus; Pembayaran Piutang Lama (Rp {{ number_format($recSummary['old_debt_paid'], 0, ',', '.') }}) + Piutang Baru dari Transaksi (Rp {{ number_format($recSummary['new_tx_remaining'], 0, ',', '.') }}) = Saldo Setelah (Rp {{ number_format($recSummary['balance_after'], 0, ',', '.') }})
            </div>

            {{-- D. HASIL KUNJUNGAN --}}
            <div class="trx-sub-title" style="margin-top: 8px;">D. HASIL KUNJUNGAN</div>
            <div class="text-box">{{ $visit->visit_result ?: '-' }}</div>

            {{-- E. CATATAN KUNJUNGAN --}}
            <div class="trx-sub-title" style="margin-top: 8px;">E. CATATAN KUNJUNGAN</div>
            <div class="text-box">{{ ($visit->final_notes ?: $visit->initial_notes) ?: '-' }}</div>
        </div>
    </div>
@empty
    <div class="empty">Tidak ada data transaksi kunjungan sales yang sesuai dengan filter pada periode ini.</div>
@endforelse

@include('reports.pdf.partials.footer')
