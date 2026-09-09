<?php

namespace App\Exports\Sheets;

use App\Exports\BaseReportExport;
use App\Models\Store;
use App\Models\User;
use Maatwebsite\Excel\Events\AfterSheet;

class TransaksiBaruDetailSheet extends BaseReportExport
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
        return 'Detail Transaksi Baru';
    }

    protected function reportTitle(): string
    {
        return 'RINCIAN TRANSAKSI BARU KUNJUNGAN SALES';
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
        return 'Tidak ada transaksi baru yang dibuat pada kunjungan di periode ini.';
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
            'Kode Transaksi Baru',
            'Tanggal Transaksi',
            'Nilai Transaksi Baru (Rp)',
            'Pembayaran Transaksi Baru (Rp)',
            'Piutang Baru dari Transaksi (Rp)',
            'Metode Pembayaran',
            'Status Transaksi',
            'Keterangan / Deskripsi',
        ];
    }

    public function dataRows(): array
    {
        $rows = [];
        $no = 0;

        foreach ($this->visits as $visit) {
            $vTransactions = $visit->transactions ?? collect();

            if ($vTransactions->isNotEmpty()) {
                foreach ($vTransactions as $newTx) {
                    $no++;
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
                        $txStatus = 'BELUM LUNAS';
                    }

                    $method = '-';
                    if ($initialPayments->isNotEmpty()) {
                        $method = strtoupper((string) ($initialPayments->first()->payment_method ?: '-'));
                    } elseif ($newTx->payments->isNotEmpty()) {
                        $method = strtoupper((string) ($newTx->payments->first()->payment_method ?: '-'));
                    }

                    $trxDate = $newTx->transaction_date ? $newTx->transaction_date->format('d/m/Y') : ($newTx->created_at ? $newTx->created_at->format('d/m/Y') : '-');
                    $desc = $newTx->description ?: ($visit->visit_result ?: '-');

                    $rows[] = [
                        $no,
                        $visit->check_in_at?->format('d/m/Y') ?? '-',
                        $visit->check_in_at?->format('H:i') ?? '-',
                        $visit->user->name ?? '-',
                        $visit->store->name ?? '-',
                        $visit->store->code ?? '-',
                        $newTx->transaction_code ?? '-',
                        $trxDate,
                        $txAmount,
                        $paidAmount,
                        $remainingAmount,
                        $method,
                        $txStatus,
                        $desc,
                    ];
                }
            } elseif ((float) $visit->transaction_amount > 0 && in_array($visit->transaction_status, ['paid', 'mixed'], true)) {
                $no++;
                $txAmount = (float) $visit->transaction_amount;
                $paidAmount = $visit->transaction_status === 'paid' ? (float) ($visit->cash_received ?: $txAmount) : 0.0;
                $remainingAmount = max(0.0, round($txAmount - $paidAmount, 2));
                $txStatus = $visit->transaction_status === 'paid' ? 'LUNAS' : 'BELUM LUNAS';
                $method = strtoupper((string) ($visit->payment_method ?: 'TUNAI'));
                $trxDate = $visit->check_in_at ? $visit->check_in_at->format('d/m/Y') : '-';
                $desc = $visit->visit_result ?: '-';

                $rows[] = [
                    $no,
                    $visit->check_in_at?->format('d/m/Y') ?? '-',
                    $visit->check_in_at?->format('H:i') ?? '-',
                    $visit->user->name ?? '-',
                    $visit->store->name ?? '-',
                    $visit->store->code ?? '-',
                    'TRX-VISIT-' . substr((string) $visit->id, 0, 8),
                    $trxDate,
                    $txAmount,
                    $paidAmount,
                    $remainingAmount,
                    $method,
                    $txStatus,
                    $desc,
                ];
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
                'H' => ['width' => 18, 'horizontal' => 'center', 'vertical' => 'center'],
                'I' => ['width' => 22, 'horizontal' => 'right', 'vertical' => 'center', 'rupiah' => true],
                'J' => ['width' => 22, 'horizontal' => 'right', 'vertical' => 'center', 'rupiah' => true],
                'K' => ['width' => 22, 'horizontal' => 'right', 'vertical' => 'center', 'rupiah' => true],
                'L' => ['width' => 18, 'horizontal' => 'center', 'vertical' => 'center'],
                'M' => ['width' => 18, 'horizontal' => 'center', 'vertical' => 'center'],
                'N' => ['width' => 40, 'horizontal' => 'left', 'vertical' => 'top', 'wrapText' => true],
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
