<?php

namespace App\Exports\Sheets;

use App\Exports\BaseReportExport;
use Maatwebsite\Excel\Events\AfterSheet;

class TransaksiBaruSheet extends BaseReportExport
{
    protected $visits;

    public function __construct(array $params = [], $visits = null)
    {
        parent::__construct($params);
        $this->visits = $visits ?? collect();
    }

    public function title(): string
    {
        return 'Transaksi Baru';
    }

    protected function reportTitle(): string
    {
        return 'TRANSAKSI BARU HASIL KUNJUNGAN SALES';
    }

    protected function emptyMessage(): ?string
    {
        return 'Tidak ada transaksi baru yang dibuat pada kunjungan di periode ini.';
    }

    public function headings(): array
    {
        return [
            'Visit ID',
            'Tanggal Kunjungan',
            'Toko',
            'Kode Toko',
            'Transaction ID',
            'Kode Transaksi',
            'Tanggal Transaksi',
            'Nominal Transaksi',
            'Pembayaran Awal',
            'Sisa Piutang Transaksi',
            'Status Transaksi',
        ];
    }

    public function dataRows(): array
    {
        $rows = [];

        foreach ($this->visits as $visit) {
            $vTransactions = $visit->transactions ?? collect();

            if ($vTransactions->isNotEmpty()) {
                foreach ($vTransactions as $trx) {
                    $amount = (float) $trx->transaction_amount;

                    // Initial payment for this new transaction
                    $initialPayments = $trx->payments->where('source', 'initial_payment');
                    $paid = (float) $initialPayments->sum('amount');
                    $remaining = max(0.0, round($amount - $paid, 2));

                    if ($remaining <= 0.005) {
                        $status = 'LUNAS';
                    } elseif ($paid > 0.005) {
                        $status = 'SEBAGIAN';
                    } else {
                        $status = 'BELUM_LUNAS';
                    }

                    $rows[] = [
                        (string) $visit->id,
                        $visit->check_in_at?->format('d/m/Y') ?? '-',
                        $visit->store->name ?? '-',
                        $visit->store->code ?? '-',
                        (string) $trx->id,
                        $trx->transaction_code ?? '-',
                        $trx->transaction_date ? $trx->transaction_date->format('d/m/Y') : '-',
                        $amount,
                        $paid,
                        $remaining,
                        $status,
                    ];
                }
            } elseif ($visit->transaction_amount && in_array($visit->transaction_status, ['paid', 'mixed'], true)) {
                $amount = (float) $visit->transaction_amount;
                $initialPaid = $visit->transaction_status === 'paid' ? $amount : 0;
                $remaining = max(0.0, round($amount - $initialPaid, 2));
                $status = $visit->transaction_status === 'paid' ? 'LUNAS' : 'BELUM_LUNAS';

                $rows[] = [
                    (string) $visit->id,
                    $visit->check_in_at?->format('d/m/Y') ?? '-',
                    $visit->store->name ?? '-',
                    $visit->store->code ?? '-',
                    '-',
                    'TRX-VISIT-' . substr((string) $visit->id, 0, 8),
                    $visit->check_in_at?->format('d/m/Y') ?? '-',
                    $amount,
                    $initialPaid,
                    $remaining,
                    $status,
                ];
            }
        }

        return $rows;
    }

    public function registerEvents(): array
    {
        return $this->wrapAfterSheet(function (AfterSheet $event) {
            $sheet = $event->sheet->getDelegate();
            $headingRow = $this->headingRow();
            $dataStart = $headingRow + 1;
            $highestRow = $sheet->getHighestRow();

            if ($dataStart <= $highestRow && $sheet->getCell("A{$dataStart}")->getValue() !== $this->emptyMessage()) {
                // Format currency numeric columns H, I, J (8, 9, 10)
                $sheet->getStyle("H{$dataStart}:J{$highestRow}")
                    ->getNumberFormat()
                    ->setFormatCode('"Rp "#,##0;[Red]("-Rp "#,##0);"-"');

                for ($r = $dataStart; $r <= $highestRow; $r++) {
                    $sheet->getStyle("A{$r}:K{$r}")->getAlignment()->setWrapText(true)->setVertical('center');

                    // Center: Visit ID (A), Tanggal Kunjungan (B), Transaction ID (E), Kode Transaksi (F), Tanggal Transaksi (G), Status (K)
                    $sheet->getStyle("A{$r}:B{$r}")->getAlignment()->setHorizontal('center');
                    $sheet->getStyle("E{$r}:G{$r}")->getAlignment()->setHorizontal('center');
                    $sheet->getStyle("K{$r}")->getAlignment()->setHorizontal('center');

                    // Left: Toko (C), Kode Toko (D)
                    $sheet->getStyle("C{$r}:D{$r}")->getAlignment()->setHorizontal('left');

                    // Right: Nominal Numeric (H, I, J)
                    $sheet->getStyle("H{$r}:J{$r}")->getAlignment()->setHorizontal('right');

                    $statusVal = $sheet->getCell("K{$r}")->getValue();
                    if ($statusVal === 'LUNAS') {
                        $sheet->getStyle("K{$r}")->applyFromArray(['font' => ['color' => ['rgb' => '15803D'], 'bold' => true]]);
                    } elseif ($statusVal === 'SEBAGIAN') {
                        $sheet->getStyle("K{$r}")->applyFromArray(['font' => ['color' => ['rgb' => 'D97706'], 'bold' => true]]);
                    } elseif ($statusVal === 'BELUM_LUNAS') {
                        $sheet->getStyle("K{$r}")->applyFromArray(['font' => ['color' => ['rgb' => 'DC2626'], 'bold' => true]]);
                    }
                }

                $sheet->getStyle("A{$dataStart}:K{$highestRow}")->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => 'thin', 'color' => ['rgb' => 'CBD5E1']]],
                ]);
            }
        });
    }

    protected function landscape(): bool
    {
        return true;
    }
}
