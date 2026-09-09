<?php

namespace App\Exports;

use App\Models\User;
use App\Services\StoreReceivableService;

class SalesActivitiesExport extends BaseReportExport
{
    public function title(): string
    {
        return 'Performa Aktivitas Sales';
    }

    protected function reportTitle(): string
    {
        return 'LAPORAN PERFORMA AKTIVITAS SALES';
    }

    protected function extraMetaRows(): array
    {
        $rows = [];
        if ($this->userId) {
            $user = User::find($this->userId);
            $rows[] = 'Sales: ' . ($user->name ?? '-');
        } else {
            $rows[] = 'Sales: Semua Sales';
        }

        return $rows;
    }

    protected function emptyMessage(): ?string
    {
        return 'Tidak ada data aktivitas sales pada periode yang dipilih.';
    }

    public function headings(): array
    {
        return [
            'No',
            'Petugas Sales',
            'Presensi',
            'Rencana',
            'Rencana Selesai',
            'Completion (%)',
            'Kunjungan',
            'Kunjungan Selesai',
            'Total Nilai Transaksi Baru (Rp)',
            'Uang Masuk Transaksi Baru (Rp)',
            'Pembayaran Piutang Lama (Rp)',
            'Total Uang Masuk (Rp)',
        ];
    }

    public function dataRows(): array
    {
        $salesUsers = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['sales', 'field-supervisor']));

        if ($this->userId) {
            $salesUsers->where('id', $this->userId);
        }

        $salesUsers = $salesUsers->orderBy('name')->get();

        $archiveId = ! empty($this->withArchived) ? ($this->archiveId ?? null) : null;
        $data = StoreReceivableService::getSalesActivitiesPerformanceData($salesUsers, $this->fromDate, $this->toDate, $archiveId);

        $rows = [];
        $no = 0;

        foreach ($data['performance'] as $p) {
            $no++;

            $rows[] = [
                $no,
                $p['name'],
                $p['attendance'],
                $p['routes'],
                $p['completed_routes'],
                $p['completion'],
                $p['visits'],
                $p['completed_visits'],
                $p['new_tx_amount'],
                $p['new_tx_paid'],
                $p['old_debt_paid'],
                $p['total_cash_in'],
            ];
        }

        return $rows;
    }

    protected function landscape(): bool
    {
        return true;
    }
}
