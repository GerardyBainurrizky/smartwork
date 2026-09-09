<?php

namespace App\Exports\Sheets;

use App\Exports\BaseReportExport;
use App\Models\Store;
use App\Models\User;
use App\Services\StoreReceivableService;
use Maatwebsite\Excel\Events\AfterSheet;

class TransaksiPiutangLamaSheet extends BaseReportExport
{
    protected $visits;
    protected ?string $storeId;
    protected ?string $transactionStatus;

    public function __construct(array $params = [], $visits = null)
    {
        parent::__construct($params);
        $this->visits = $visits ?? collect();
        $this->storeId = $params['store_id'] ?? null;
        $this->transactionStatus = $params['transaction_status'] ?? null;
    }

    public function title(): string
    {
        return 'Detail Piutang Lama';
    }

    protected function reportTitle(): string
    {
        return 'RINCIAN PEMBAYARAN PIUTANG LAMA KUNJUNGAN SALES';
    }

    protected function extraMetaRows(): array
    {
        $rows = [];

        if ($this->userId) {
            $user = User::find($this->userId);
            $rows[] = 'Sales Penanggung Jawab: ' . ($user->name ?? '-');
        } else {
            $rows[] = 'Sales Penanggung Jawab: Semua Sales';
        }

        if ($this->storeId) {
            $store = Store::find($this->storeId);
            $rows[] = 'Toko Pelanggan: ' . ($store->name ?? '-') . ($store && $store->code ? ' (' . $store->code . ')' : '');
        }

        if ($this->transactionStatus) {
            $stLabel = match ($this->transactionStatus) {
                'has_transaction' => 'Ada Transaksi Baru',
                'has_old_debt_payment' => 'Ada Pembayaran Piutang Lama',
                'paid' => 'Transaksi Baru (Paid)',
                'piutang' => 'Bayar Piutang',
                'mixed' => 'Transaksi + Piutang',
                'none' => 'Tanpa Transaksi',
                default => ucfirst($this->transactionStatus),
            };
            $rows[] = 'Status Transaksi: ' . $stLabel;
        } else {
            $rows[] = 'Status Transaksi: Semua Status';
        }

        if ($this->search) {
            $rows[] = 'Pencarian: ' . $this->search;
        }

        return $rows;
    }

    protected function emptyMessage(): ?string
    {
        return 'Tidak ada riwayat pembayaran piutang lama pada kunjungan di periode ini.';
    }

    public function headings(): array
    {
        return [
            'No',
            'Tanggal Kunjungan',
            'Waktu Kunjungan',
            'Sales Penanggung Jawab',
            'Nama Toko',
            'Kode Toko',
            'Kode Transaksi Piutang Lama',
            'Nilai Transaksi Awal (Rp)',
            'Saldo Sebelum Pembayaran (Rp)',
            'Pembayaran Piutang Lama (Rp)',
            'Sisa Setelah Pembayaran (Rp)',
            'Metode Pembayaran',
            'Tanggal Pembayaran',
            'Catatan Pembayaran',
        ];
    }

    public function dataRows(): array
    {
        $rows = [];
        $no = 0;

        foreach ($this->visits as $visit) {
            $vPayments = ($visit->payments ?? collect())->filter(fn ($p) =>
                $p->source !== 'initial_payment' && ! str_contains(strtolower($p->notes ?? ''), 'pembayaran awal')
            );

            if ($vPayments->isNotEmpty()) {
                foreach ($vPayments as $pay) {
                    $payAmount = (float) $pay->amount;
                    if ($payAmount <= 0) {
                        continue;
                    }

                    $no++;
                    $trx = $pay->transaction;
                    if ($trx) {
                        $totalTrxAmount = (float) $trx->transaction_amount;
                        $allTrxPayments = $trx->relationLoaded('payments') ? $trx->payments : $trx->payments()->get();
                        $priorPaid = (float) $allTrxPayments->filter(function ($op) use ($pay) {
                            if ($op->id === $pay->id) {
                                return false;
                            }
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
                    $notes = $pay->notes ?: ($visit->final_notes ?: '-');

                    $rows[] = [
                        $no,
                        $visit->check_in_at?->format('d/m/Y') ?? '-',
                        $visit->check_in_at?->format('H:i') ?? '-',
                        $visit->user->name ?? '-',
                        $visit->store->name ?? '-',
                        $visit->store->code ?? '-',
                        $trxCode,
                        $totalTrxAmount,
                        $balBefore,
                        $payAmount,
                        $balAfter,
                        strtoupper((string) ($pay->payment_method ?: 'TUNAI')),
                        $payDate,
                        $notes,
                    ];
                }
            } else {
                $recSummary = StoreReceivableService::getVisitReceivableSummary($visit);
                if ($recSummary['old_debt_paid'] > 0) {
                    $no++;
                    $rows[] = [
                        $no,
                        $visit->check_in_at?->format('d/m/Y') ?? '-',
                        $visit->check_in_at?->format('H:i') ?? '-',
                        $visit->user->name ?? '-',
                        $visit->store->name ?? '-',
                        $visit->store->code ?? '-',
                        'Piutang Toko',
                        $recSummary['old_debt_paid'],
                        $recSummary['old_debt_paid'],
                        $recSummary['old_debt_paid'],
                        0.0,
                        strtoupper((string) ($visit->payment_method ?: 'TUNAI')),
                        $visit->check_in_at?->format('d/m/Y') ?? '-',
                        $visit->final_notes ?: '-',
                    ];
                }
            }
        }

        return $rows;
    }

    protected function landscape(): bool
    {
        return true;
    }

    public function registerEvents(): array
    {
        return $this->wrapAfterSheet(function (AfterSheet $event) {
            $sheet = $event->sheet->getDelegate();
            $headingRow = $this->headingRow();
            $dataStart = $headingRow + 1;
            $highestRow = $sheet->getHighestRow();

            $rupiahFormat = '#,##0';

            $colMap = [
                'A' => ['width' => 6, 'horizontal' => 'center', 'vertical' => 'center'],
                'B' => ['width' => 18, 'horizontal' => 'center', 'vertical' => 'center'],
                'C' => ['width' => 14, 'horizontal' => 'center', 'vertical' => 'center'],
                'D' => ['width' => 25, 'horizontal' => 'left', 'vertical' => 'top', 'wrapText' => true],
                'E' => ['width' => 30, 'horizontal' => 'left', 'vertical' => 'top', 'wrapText' => true],
                'F' => ['width' => 16, 'horizontal' => 'center', 'vertical' => 'center'],
                'G' => ['width' => 24, 'horizontal' => 'left', 'vertical' => 'top', 'wrapText' => true],
                'H' => ['width' => 22, 'horizontal' => 'right', 'vertical' => 'center', 'rupiah' => true],
                'I' => ['width' => 22, 'horizontal' => 'right', 'vertical' => 'center', 'rupiah' => true],
                'J' => ['width' => 22, 'horizontal' => 'right', 'vertical' => 'center', 'rupiah' => true],
                'K' => ['width' => 22, 'horizontal' => 'right', 'vertical' => 'center', 'rupiah' => true],
                'L' => ['width' => 18, 'horizontal' => 'center', 'vertical' => 'center'],
                'M' => ['width' => 18, 'horizontal' => 'center', 'vertical' => 'center'],
                'N' => ['width' => 35, 'horizontal' => 'left', 'vertical' => 'top', 'wrapText' => true],
            ];

            foreach ($colMap as $col => $opts) {
                if (isset($opts['width'])) {
                    $sheet->getColumnDimension($col)->setAutoSize(false);
                    $sheet->getColumnDimension($col)->setWidth($opts['width']);
                }

                if ($dataStart <= $highestRow) {
                    $range = "{$col}{$dataStart}:{$col}{$highestRow}";
                    $alignment = ['horizontal' => $opts['horizontal'], 'vertical' => $opts['vertical'] ?? 'center'];
                    if (! empty($opts['wrapText'])) {
                        $alignment['wrapText'] = true;
                    }
                    $sheet->getStyle($range)->getAlignment()->applyFromArray($alignment);

                    if (! empty($opts['rupiah'])) {
                        $sheet->getStyle($range)->getNumberFormat()->setFormatCode($rupiahFormat);
                    }
                }
            }
        });
    }
}
