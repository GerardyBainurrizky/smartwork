<?php

namespace App\Exports\Sheets;

use App\Exports\BaseReportExport;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class TransaksiPiutangSheet extends BaseReportExport
{
    protected $visits;

    public function __construct(array $params = [], $visits = null)
    {
        parent::__construct($params);
        $this->visits = $visits ?? collect();
    }

    public function title(): string
    {
        return 'Transaksi & Piutang';
    }

    protected function reportTitle(): string
    {
        return 'RINCIAN TRANSAKSI & PEMBAYARAN PIUTANG KUNJUNGAN SALES';
    }

    protected function emptyMessage(): ?string
    {
        return 'Tidak ada transaksi baru maupun pembayaran piutang pada periode ini.';
    }

    public function headings(): array
    {
        return [
            'No',
            'Tanggal Kunjungan',
            'Sales',
            'Toko',
            'Kode Toko',
            'Kode Transaksi',
            'Nilai Transaksi (Rp)',
            'Saldo Sebelum (Rp)',
            'Pembayaran pada Kunjungan (Rp)',
            'Sisa Setelah Kunjungan (Rp)',
            'Metode Pembayaran',
            'Status',
        ];
    }

    public function dataRows(): array
    {
        $rows = [];

        // Kumpulkan data Transaksi Baru
        $newTxItems = [];
        foreach ($this->visits as $visit) {
            $salesName = $visit->user->name ?? '-';
            $dateStr = $visit->check_in_at ? $visit->check_in_at->format('d M Y') : '-';
            $sortDate = $visit->check_in_at ? $visit->check_in_at->format('Y-m-d H:i:s') : ($visit->created_at ? $visit->created_at->format('Y-m-d H:i:s') : '1970-01-01 00:00:00');
            $storeName = $visit->store->name ?? '-';
            $storeCode = $visit->store->code ?? '-';

            $vTransactions = $visit->transactions ?? collect();
            if ($vTransactions->isNotEmpty()) {
                foreach ($vTransactions as $newTx) {
                    $txAmount = (float) $newTx->transaction_amount;
                    $allTxPayments = $newTx->relationLoaded('payments') ? $newTx->payments : $newTx->payments()->get();
                    $initialPayments = $allTxPayments->filter(fn ($p) =>
                        $p->source === 'initial_payment' || str_contains(strtolower($p->notes ?? ''), 'pembayaran awal')
                    );
                    $paidAmount = (float) $initialPayments->sum('amount');
                    $remainingAmount = max(0.0, round($txAmount - $paidAmount, 2));

                    if ($remainingAmount <= 0.005) {
                        $txStatus = 'LUNAS';
                    } elseif ($paidAmount > 0.005) {
                        $txStatus = 'SEBAGIAN';
                    } else {
                        $txStatus = 'BELUM_LUNAS';
                    }

                    $method = '-';
                    if ($initialPayments->isNotEmpty()) {
                        $firstPay = $initialPayments->first();
                        $method = strtoupper((string) ($firstPay->payment_method ?: '-'));
                    }

                    $newTxItems[] = [
                        'sort_date' => $sortDate,
                        'date' => $dateStr,
                        'sales' => $salesName,
                        'store' => $storeName,
                        'code' => $storeCode,
                        'trx_code' => $newTx->transaction_code ?? '-',
                        'amount' => $txAmount,
                        'paid' => $paidAmount,
                        'remaining' => $remainingAmount,
                        'method' => $method,
                        'status' => $txStatus,
                    ];
                }
            } elseif ($visit->transaction_amount && in_array($visit->transaction_status, ['paid', 'mixed'], true)) {
                $txAmount = (float) $visit->transaction_amount;
                $initialPaid = $visit->transaction_status === 'paid' ? $txAmount : 0.0;
                $remainingAmount = max(0.0, round($txAmount - $initialPaid, 2));
                $txStatus = $visit->transaction_status === 'paid' ? 'LUNAS' : 'BELUM_LUNAS';

                $newTxItems[] = [
                    'sort_date' => $sortDate,
                    'date' => $dateStr,
                    'sales' => $salesName,
                    'store' => $storeName,
                    'code' => $storeCode,
                    'trx_code' => 'TRX-VISIT-' . substr((string) $visit->id, 0, 8),
                    'amount' => $txAmount,
                    'paid' => $initialPaid,
                    'remaining' => $remainingAmount,
                    'method' => strtoupper((string) ($visit->payment_method ?: 'TUNAI')),
                    'status' => $txStatus,
                ];
            }
        }

        usort($newTxItems, fn ($a, $b) => strcmp($a['sort_date'], $b['sort_date']));

        // Kumpulkan data Pembayaran Piutang Lama
        $oldDebtItems = [];
        foreach ($this->visits as $visit) {
            $salesName = $visit->user->name ?? '-';
            $dateStr = $visit->check_in_at ? $visit->check_in_at->format('d M Y') : '-';
            $sortDate = $visit->check_in_at ? $visit->check_in_at->format('Y-m-d H:i:s') : ($visit->created_at ? $visit->created_at->format('Y-m-d H:i:s') : '1970-01-01 00:00:00');
            $storeName = $visit->store->name ?? '-';
            $storeCode = $visit->store->code ?? '-';

            $vPayments = ($visit->payments ?? collect())->filter(fn ($p) => $p->source !== 'initial_payment');
            foreach ($vPayments as $pay) {
                $payAmount = (float) $pay->amount;
                if ($payAmount <= 0) {
                    continue;
                }

                $trx = $pay->transaction;
                if ($trx) {
                    $totalTrxAmount = (float) $trx->transaction_amount;
                    $allPayments = $trx->relationLoaded('payments') ? $trx->payments : $trx->payments()->get();
                    $priorPayments = (float) $allPayments
                        ->filter(function ($otherPay) use ($pay) {
                            if ($otherPay->id === $pay->id) return false;
                            if ($otherPay->payment_date && $pay->payment_date && $otherPay->payment_date != $pay->payment_date) {
                                return $otherPay->payment_date < $pay->payment_date;
                            }
                            if ($otherPay->created_at && $pay->created_at && $otherPay->created_at != $pay->created_at) {
                                return $otherPay->created_at < $pay->created_at;
                            }
                            return $otherPay->id < $pay->id;
                        })
                        ->sum('amount');

                    $balanceBefore = max(0.0, round($totalTrxAmount - $priorPayments, 2));
                    $balanceAfter = max(0.0, round($balanceBefore - $payAmount, 2));
                } else {
                    $totalTrxAmount = $payAmount;
                    $balanceBefore = $payAmount;
                    $balanceAfter = 0.0;
                }

                $statusAfter = $balanceAfter <= 0.005 ? 'LUNAS' : 'SEBAGIAN';
                $payMethod = match (strtolower((string) $pay->payment_method)) {
                    'tunai' => 'TUNAI',
                    'transfer' => 'TRANSFER',
                    'qris' => 'QRIS',
                    default => strtoupper((string) ($pay->payment_method ?: 'TUNAI')),
                };

                $oldDebtItems[] = [
                    'sort_date' => $sortDate,
                    'date' => $dateStr,
                    'sales' => $salesName,
                    'store' => $storeName,
                    'code' => $storeCode,
                    'trx_code' => $trx?->transaction_code ?? '-',
                    'orig_amount' => $totalTrxAmount,
                    'balance_before' => $balanceBefore,
                    'paid' => $payAmount,
                    'balance_after' => $balanceAfter,
                    'method' => $payMethod,
                    'status' => $statusAfter,
                ];
            }
        }

        usort($oldDebtItems, fn ($a, $b) => strcmp($a['sort_date'], $b['sort_date']));

        // Section 1: Rincian Transaksi Baru
        $rows[] = ['RINCIAN TRANSAKSI BARU'];
        $rows[] = [
            'No',
            'Tanggal Kunjungan',
            'Sales',
            'Toko',
            'Kode Toko',
            'Kode Transaksi Baru',
            'Nilai Transaksi Baru (Rp)',
            'Pembayaran Transaksi Baru (Rp)',
            'Sisa Transaksi Baru (Rp)',
            'Metode Pembayaran',
            'Status Transaksi Baru',
        ];

        if (empty($newTxItems)) {
            $rows[] = ['-', '-', '-', '-', '-', 'Tidak ada transaksi baru pada periode ini', 0.0, 0.0, 0.0, '-', '-'];
        } else {
            $no = 0;
            foreach ($newTxItems as $item) {
                $no++;
                $rows[] = [
                    $no,
                    $item['date'],
                    $item['sales'],
                    $item['store'],
                    $item['code'],
                    $item['trx_code'],
                    $item['amount'],
                    $item['paid'],
                    $item['remaining'],
                    $item['method'],
                    $item['status'],
                ];
            }
        }

        // Spacer Row
        $rows[] = ['', '', '', '', '', '', '', '', '', '', '', ''];

        // Section 2: Rincian Pembayaran Piutang Lama
        $rows[] = ['RINCIAN PEMBAYARAN PIUTANG LAMA'];
        $rows[] = [
            'No',
            'Tanggal Kunjungan',
            'Sales',
            'Toko',
            'Kode Toko',
            'Kode Transaksi Piutang',
            'Nilai Awal Transaksi/Piutang (Rp)',
            'Saldo Piutang Sebelum Pembayaran (Rp)',
            'Pembayaran Piutang pada Kunjungan (Rp)',
            'Sisa Piutang Setelah Pembayaran (Rp)',
            'Metode Pembayaran',
            'Status Piutang',
        ];

        if (empty($oldDebtItems)) {
            $rows[] = ['-', '-', '-', '-', '-', 'Tidak ada pembayaran piutang lama pada periode ini', 0.0, 0.0, 0.0, 0.0, '-', '-'];
        } else {
            $no = 0;
            foreach ($oldDebtItems as $item) {
                $no++;
                $rows[] = [
                    $no,
                    $item['date'],
                    $item['sales'],
                    $item['store'],
                    $item['code'],
                    $item['trx_code'],
                    $item['orig_amount'],
                    $item['balance_before'],
                    $item['paid'],
                    $item['balance_after'],
                    $item['method'],
                    $item['status'],
                ];
            }
        }

        return $rows;
    }

    public function registerEvents(): array
    {
        return $this->wrapAfterSheet(function (AfterSheet $event) {
            $sheet = $event->sheet->getDelegate();
            $highestRow = $sheet->getHighestRow();
            $headingRow = $this->headingRow();
            $dataStart = $headingRow + 1;

            if ($dataStart <= $highestRow) {
                for ($r = $dataStart; $r <= $highestRow; $r++) {
                    $cellA = (string) $sheet->getCell("A{$r}")->getValue();

                    // Section 1 Header Banner
                    if ($cellA === 'RINCIAN TRANSAKSI BARU') {
                        $sheet->mergeCells("A{$r}:K{$r}");
                        $sheet->getStyle("A{$r}:K{$r}")->applyFromArray([
                            'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
                            'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '097A99']],
                            'alignment' => ['horizontal' => 'left', 'vertical' => 'center'],
                        ]);
                        $sheet->getRowDimension($r)->setRowHeight(24);
                        continue;
                    }

                    // Section 2 Header Banner
                    if ($cellA === 'RINCIAN PEMBAYARAN PIUTANG LAMA') {
                        $sheet->mergeCells("A{$r}:L{$r}");
                        $sheet->getStyle("A{$r}:L{$r}")->applyFromArray([
                            'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
                            'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '15803D']],
                            'alignment' => ['horizontal' => 'left', 'vertical' => 'center'],
                        ]);
                        $sheet->getRowDimension($r)->setRowHeight(24);
                        continue;
                    }

                    // Section Column Headings
                    if ($cellA === 'No' && $sheet->getCell("B{$r}")->getValue() === 'Tanggal Kunjungan') {
                        $lastCol = $sheet->getCell("L{$r}")->getValue() ? 'L' : 'K';
                        $sheet->getStyle("A{$r}:{$lastCol}{$r}")->applyFromArray([
                            'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => 'FFFFFF']],
                            'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '0DA4CE']],
                            'alignment' => ['horizontal' => 'center', 'vertical' => 'center', 'wrapText' => true],
                            'borders' => ['allBorders' => ['borderStyle' => 'thin', 'color' => ['rgb' => '0B7494']]],
                        ]);
                        $sheet->getRowDimension($r)->setRowHeight(26);
                        continue;
                    }

                    // Empty spacer row
                    if ($cellA === '' && $sheet->getCell("B{$r}")->getValue() === '') {
                        $sheet->getRowDimension($r)->setRowHeight(12);
                        continue;
                    }

                    // Regular Data Rows
                    $sheet->getStyle("A{$r}:L{$r}")->getAlignment()->setWrapText(true)->setVertical('center');
                    $sheet->getStyle("A{$r}:B{$r}")->getAlignment()->setHorizontal('center');
                    $sheet->getStyle("C{$r}:E{$r}")->getAlignment()->setHorizontal('left');
                    $sheet->getStyle("F{$r}")->getAlignment()->setHorizontal('center');

                    // Check if Section 1 (Cols G, H, I numeric) or Section 2 (Cols G, H, I, J numeric)
                    if (is_numeric($sheet->getCell("J{$r}")->getValue())) {
                        // Section 2: G=orig, H=before, I=paid, J=after, K=method, L=status
                        $sheet->getStyle("G{$r}:J{$r}")->getAlignment()->setHorizontal('right');
                        $sheet->getStyle("G{$r}:J{$r}")->getNumberFormat()->setFormatCode('"Rp "#,##0;[Red]("-Rp "#,##0);"-"');
                        $sheet->getStyle("K{$r}:L{$r}")->getAlignment()->setHorizontal('center');

                        $statusVal = $sheet->getCell("L{$r}")->getValue();
                        if ($statusVal === 'LUNAS') {
                            $sheet->getStyle("L{$r}")->applyFromArray(['font' => ['color' => ['rgb' => '15803D'], 'bold' => true]]);
                        } elseif ($statusVal === 'SEBAGIAN') {
                            $sheet->getStyle("L{$r}")->applyFromArray(['font' => ['color' => ['rgb' => 'D97706'], 'bold' => true]]);
                        }
                    } else {
                        // Section 1: G=amount, H=paid, I=remaining, J=method, K=status
                        $sheet->getStyle("G{$r}:I{$r}")->getAlignment()->setHorizontal('right');
                        $sheet->getStyle("G{$r}:I{$r}")->getNumberFormat()->setFormatCode('"Rp "#,##0;[Red]("-Rp "#,##0);"-"');
                        $sheet->getStyle("J{$r}:K{$r}")->getAlignment()->setHorizontal('center');

                        $statusVal = $sheet->getCell("K{$r}")->getValue();
                        if ($statusVal === 'LUNAS') {
                            $sheet->getStyle("K{$r}")->applyFromArray(['font' => ['color' => ['rgb' => '15803D'], 'bold' => true]]);
                        } elseif ($statusVal === 'SEBAGIAN') {
                            $sheet->getStyle("K{$r}")->applyFromArray(['font' => ['color' => ['rgb' => 'D97706'], 'bold' => true]]);
                        } elseif ($statusVal === 'BELUM_LUNAS') {
                            $sheet->getStyle("K{$r}")->applyFromArray(['font' => ['color' => ['rgb' => 'DC2626'], 'bold' => true]]);
                        }
                    }

                    $sheet->getStyle("A{$r}:L{$r}")->applyFromArray([
                        'borders' => ['allBorders' => ['borderStyle' => 'thin', 'color' => ['rgb' => 'CBD5E1']]],
                    ]);
                }
            }
        });
    }

    protected function landscape(): bool
    {
        return true;
    }
}
