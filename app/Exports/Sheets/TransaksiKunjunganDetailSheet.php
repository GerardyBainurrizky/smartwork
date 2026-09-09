<?php

namespace App\Exports\Sheets;

use App\Exports\BaseReportExport;
use App\Models\Store;
use App\Models\User;
use Maatwebsite\Excel\Events\AfterSheet;

class TransaksiKunjunganDetailSheet extends BaseReportExport
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
        return 'Detail Kunjungan';
    }

    protected function reportTitle(): string
    {
        return 'RINCIAN AKTIVITAS KUNJUNGAN SALES';
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
        return 'Tidak ada data aktivitas kunjungan sales pada periode ini.';
    }

    public function headings(): array
    {
        return [
            'No',
            'ID Kunjungan',
            'Tanggal Kunjungan',
            'Waktu Check In',
            'Waktu Check Out',
            'Durasi Kunjungan',
            'Sales Penanggung Jawab',
            'Nama Toko',
            'Kode Toko',
            'Alamat Toko',
            'Status Kunjungan',
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

            $duration = '-';
            if ($visit->check_in_at && $visit->check_out_at) {
                $min = (int) $visit->check_in_at->diffInMinutes($visit->check_out_at);
                $duration = $min >= 60 ? (intdiv($min, 60) . ' jam ' . ($min % 60) . ' menit') : ($min . ' menit');
            }

            $statusTrx = match ($visit->transaction_status) {
                'paid' => 'Transaksi Baru',
                'piutang' => 'Bayar Piutang',
                'mixed' => 'Transaksi + Piutang',
                default => 'Tanpa Transaksi',
            };

            $statusVisit = $visit->status === 'completed' ? 'Selesai' : ucfirst((string) ($visit->status ?? '-'));
            $notes = $visit->final_notes ?: ($visit->initial_notes ?: '-');
            $location = $visit->check_in_address ?: ($visit->check_in_lat && $visit->check_in_lng ? round((float) $visit->check_in_lat, 6) . ', ' . round((float) $visit->check_in_lng, 6) : '-');

            $rows[] = [
                $no,
                (string) $visit->id,
                $visit->check_in_at?->format('d/m/Y') ?? '-',
                $visit->check_in_at?->format('H:i') ?? '-',
                $visit->check_out_at?->format('H:i') ?? 'Selesai',
                $duration,
                $visit->user->name ?? '-',
                $visit->store->name ?? '-',
                $visit->store->code ?? '-',
                $visit->store->address ?? '-',
                $statusVisit,
                $statusTrx,
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

            $colMap = [
                'A' => ['width' => 6, 'horizontal' => 'center', 'vertical' => 'center'],
                'B' => ['width' => 38, 'horizontal' => 'left', 'vertical' => 'top', 'wrapText' => true],
                'C' => ['width' => 18, 'horizontal' => 'center', 'vertical' => 'center'],
                'D' => ['width' => 14, 'horizontal' => 'center', 'vertical' => 'center'],
                'E' => ['width' => 14, 'horizontal' => 'center', 'vertical' => 'center'],
                'F' => ['width' => 16, 'horizontal' => 'center', 'vertical' => 'center'],
                'G' => ['width' => 25, 'horizontal' => 'left', 'vertical' => 'top', 'wrapText' => true],
                'H' => ['width' => 30, 'horizontal' => 'left', 'vertical' => 'top', 'wrapText' => true],
                'I' => ['width' => 16, 'horizontal' => 'center', 'vertical' => 'center'],
                'J' => ['width' => 35, 'horizontal' => 'left', 'vertical' => 'top', 'wrapText' => true],
                'K' => ['width' => 16, 'horizontal' => 'center', 'vertical' => 'center'],
                'L' => ['width' => 20, 'horizontal' => 'center', 'vertical' => 'center'],
                'M' => ['width' => 60, 'horizontal' => 'left', 'vertical' => 'top', 'wrapText' => true],
                'N' => ['width' => 40, 'horizontal' => 'left', 'vertical' => 'top', 'wrapText' => true],
                'O' => ['width' => 35, 'horizontal' => 'left', 'vertical' => 'top', 'wrapText' => true],
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
                }
            }
        });
    }
}
