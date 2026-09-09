<?php

namespace App\Exports\Sheets;

use App\Exports\BaseReportExport;
use App\Models\Store;
use App\Models\User;
use App\Services\StoreReceivableService;
use Maatwebsite\Excel\Events\AfterSheet;

class TransaksiRingkasanSheet extends BaseReportExport
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
        return 'Ringkasan Transaksi';
    }

    protected function reportTitle(): string
    {
        return 'LAPORAN TRANSAKSI KUNJUNGAN SALES';
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
        return 'Tidak ada data transaksi kunjungan sales yang sesuai dengan filter pada periode ini.';
    }

    public function headings(): array
    {
        return [
            'No',
            'Tanggal Kunjungan',
            'Waktu Check In',
            'Sales Penanggung Jawab',
            'Nama Toko',
            'Kode Toko',
            'Kode Transaksi',
            'Nilai Transaksi Baru (Rp)',
            'Pembayaran Transaksi Baru (Rp)',
            'Piutang Baru dari Transaksi (Rp)',
            'Saldo Piutang Sebelum Kunjungan (Rp)',
            'Pembayaran Piutang Lama (Rp)',
            'Saldo Piutang Setelah Kunjungan (Rp)',
            'Status Transaksi',
            'Hasil Kunjungan',
            'Catatan Kunjungan',
            'Lokasi Check In',
        ];
    }

    public function dataRows(): array
    {
        $rows = [];
        $no = 0;

        foreach ($this->visits as $visit) {
            $no++;

            $summary = StoreReceivableService::getVisitReceivableSummary($visit);
            $vTxCount = $visit->transactions->count() ?: (($visit->transaction_amount > 0 && in_array($visit->transaction_status, ['paid', 'mixed'], true)) ? 1 : 0);

            $rowTrxCodes = $visit->transactions->pluck('transaction_code')->filter()->implode(', ');
            if (! $rowTrxCodes && $vTxCount > 0) {
                $rowTrxCodes = 'TRX-VISIT-' . substr((string) $visit->id, 0, 8);
            }

            $statusLabel = match ($visit->transaction_status) {
                'paid' => 'Transaksi Baru',
                'piutang' => 'Bayar Piutang',
                'mixed' => 'Transaksi + Piutang',
                default => 'Tanpa Transaksi',
            };

            $notes = $visit->final_notes ?: ($visit->initial_notes ?: '-');
            $location = $visit->check_in_address ?: ($visit->check_in_lat && $visit->check_in_lng ? round((float) $visit->check_in_lat, 6) . ', ' . round((float) $visit->check_in_lng, 6) : '-');

            $rows[] = [
                $no,
                $visit->check_in_at?->format('d/m/Y') ?? '-',
                $visit->check_in_at?->format('H:i') ?? '-',
                $visit->user->name ?? '-',
                $visit->store->name ?? '-',
                $visit->store->code ?? '-',
                $rowTrxCodes ?: '-',
                (float) $summary['new_tx_total'],
                (float) $summary['new_tx_initial_paid'],
                (float) $summary['new_tx_remaining'],
                (float) $summary['balance_before'],
                (float) $summary['old_debt_paid'],
                (float) $summary['balance_after'],
                $statusLabel,
                $visit->visit_result ?? '-',
                $notes,
                $location,
            ];
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
                'K' => ['width' => 24, 'horizontal' => 'right', 'vertical' => 'center', 'rupiah' => true],
                'L' => ['width' => 22, 'horizontal' => 'right', 'vertical' => 'center', 'rupiah' => true],
                'M' => ['width' => 24, 'horizontal' => 'right', 'vertical' => 'center', 'rupiah' => true],
                'N' => ['width' => 20, 'horizontal' => 'center', 'vertical' => 'center'],
                'O' => ['width' => 55, 'horizontal' => 'left', 'vertical' => 'top', 'wrapText' => true],
                'P' => ['width' => 35, 'horizontal' => 'left', 'vertical' => 'top', 'wrapText' => true],
                'Q' => ['width' => 35, 'horizontal' => 'left', 'vertical' => 'top', 'wrapText' => true],
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
