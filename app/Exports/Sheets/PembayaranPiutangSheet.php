<?php

namespace App\Exports\Sheets;

use App\Exports\BaseReportExport;
use Maatwebsite\Excel\Events\AfterSheet;

class PembayaranPiutangSheet extends BaseReportExport
{
    protected $visits;

    public function __construct(array $params = [], $visits = null)
    {
        parent::__construct($params);
        $this->visits = $visits ?? collect();
    }

    public function title(): string
    {
        return 'Pembayaran Piutang';
    }

    protected function reportTitle(): string
    {
        return 'RINCIAN PEMBAYARAN PIUTANG LAMA PADA KUNJUNGAN SALES';
    }

    protected function emptyMessage(): ?string
    {
        return 'Tidak ada pembayaran piutang lama yang dilakukan pada kunjungan di periode ini.';
    }

    public function headings(): array
    {
        return [
            'Visit ID',
            'Tanggal Kunjungan',
            'Toko',
            'Kode Toko',
            'Transaction ID',
            'Kode Transaksi / Faktur',
            'Saldo Sebelum Pembayaran',
            'Metode Pembayaran',
            'Nominal Dibayar',
            'Sisa Setelah Pembayaran',
            'Status Setelah Pembayaran',
        ];
    }

    public function dataRows(): array
    {
        $rows = [];

        foreach ($this->visits as $visit) {
            $vPayments = $visit->payments ?? collect();

            foreach ($vPayments as $pay) {
                // JANGAN masukkan initial_payment
                if ($pay->source === 'initial_payment') {
                    continue;
                }

                $payAmount = (float) $pay->amount;
                if ($payAmount <= 0) {
                    continue;
                }

                $trx = $pay->transaction;

                if ($trx) {
                    $totalTrxAmount = (float) $trx->transaction_amount;
                    // Ambil pembayaran transaksi ini yang tercatat sebelum payment spesifik ini
                    $allPayments = $trx->relationLoaded('payments') ? $trx->payments : $trx->payments()->get();
                    $priorPayments = $allPayments
                        ->filter(function ($otherPay) use ($pay) {
                            if ($otherPay->id === $pay->id) {
                                return false;
                            }
                            if ($otherPay->payment_date && $pay->payment_date && $otherPay->payment_date != $pay->payment_date) {
                                return $otherPay->payment_date < $pay->payment_date;
                            }
                            if ($otherPay->created_at && $pay->created_at && $otherPay->created_at != $pay->created_at) {
                                return $otherPay->created_at < $pay->created_at;
                            }

                            return $otherPay->id < $pay->id;
                        })
                        ->sum('amount');

                    $balanceBefore = max(0.0, round($totalTrxAmount - (float) $priorPayments, 2));
                    $balanceAfter = max(0.0, round($balanceBefore - $payAmount, 2));
                } else {
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

                $rows[] = [
                    (string) $visit->id,
                    $visit->check_in_at?->format('d/m/Y') ?? '-',
                    $visit->store->name ?? '-',
                    $visit->store->code ?? '-',
                    $trx ? (string) $trx->id : '-',
                    $trx?->transaction_code ?? '-',
                    $balanceBefore,
                    $payMethod,
                    $payAmount,
                    $balanceAfter,
                    $statusAfter,
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
                // Currency format columns G, I, J (7, 9, 10: Saldo Sebelum, Nominal Dibayar, Sisa Setelah)
                $sheet->getStyle("G{$dataStart}:G{$highestRow}")
                    ->getNumberFormat()
                    ->setFormatCode('"Rp "#,##0;[Red]("-Rp "#,##0);"-"');

                $sheet->getStyle("I{$dataStart}:J{$highestRow}")
                    ->getNumberFormat()
                    ->setFormatCode('"Rp "#,##0;[Red]("-Rp "#,##0);"-"');

                for ($r = $dataStart; $r <= $highestRow; $r++) {
                    $sheet->getStyle("A{$r}:K{$r}")->getAlignment()->setWrapText(true)->setVertical('center');

                    // Center: Visit ID (A), Tanggal Kunjungan (B), Transaction ID (E), Kode Transaksi (F), Metode (H), Status (K)
                    $sheet->getStyle("A{$r}:B{$r}")->getAlignment()->setHorizontal('center');
                    $sheet->getStyle("E{$r}:F{$r}")->getAlignment()->setHorizontal('center');
                    $sheet->getStyle("H{$r}")->getAlignment()->setHorizontal('center');
                    $sheet->getStyle("K{$r}")->getAlignment()->setHorizontal('center');

                    // Left: Toko (C), Kode Toko (D)
                    $sheet->getStyle("C{$r}:D{$r}")->getAlignment()->setHorizontal('left');

                    // Right: Nominal Numeric (G, I, J)
                    $sheet->getStyle("G{$r}")->getAlignment()->setHorizontal('right');
                    $sheet->getStyle("I{$r}:J{$r}")->getAlignment()->setHorizontal('right');

                    $statusVal = $sheet->getCell("K{$r}")->getValue();
                    if ($statusVal === 'LUNAS') {
                        $sheet->getStyle("K{$r}")->applyFromArray(['font' => ['color' => ['rgb' => '15803D'], 'bold' => true]]);
                    } elseif ($statusVal === 'SEBAGIAN') {
                        $sheet->getStyle("K{$r}")->applyFromArray(['font' => ['color' => ['rgb' => 'D97706'], 'bold' => true]]);
                    }
                }

                // Summary footer row for Total Pembayaran Piutang
                $totalRow = $highestRow + 1;
                $sheet->setCellValue("F{$totalRow}", 'TOTAL PEMBAYARAN PIUTANG:');
                $sheet->setCellValue("I{$totalRow}", "=SUM(I{$dataStart}:I{$highestRow})");

                $sheet->getStyle("F{$totalRow}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => '0F172A']],
                    'alignment' => ['horizontal' => 'right', 'vertical' => 'center'],
                ]);
                $sheet->getStyle("I{$totalRow}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => '15803D']],
                    'alignment' => ['horizontal' => 'right', 'vertical' => 'center'],
                ]);
                $sheet->getStyle("I{$totalRow}")->getNumberFormat()->setFormatCode('"Rp "#,##0;[Red]("-Rp "#,##0);"-"');

                $sheet->getStyle("A{$dataStart}:K{$totalRow}")->applyFromArray([
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
