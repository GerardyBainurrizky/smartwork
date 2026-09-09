<?php

namespace App\Exports;

use App\Models\Attendance;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class AttendanceExport extends BaseReportExport
{
    protected $items;

    public function title(): string
    {
        return 'Laporan Presensi';
    }

    protected function reportTitle(): string
    {
        return 'LAPORAN PRESENSI';
    }

    public function headings(): array
    {
        return [
            'No', 'Nama', 'Role', 'Tanggal', 'Jam Masuk', 'Jam Keluar',
            'Durasi', 'Status', 'Lokasi Check In', 'Latitude Check In', 'Longitude Check In', 'Link Google Maps Check In',
            'Lokasi Check Out', 'Latitude Check Out', 'Longitude Check Out', 'Link Google Maps Check Out',
            'Foto Check In', 'Foto Check Out',
        ];
    }

    public function dataRows(): array
    {
        if ($this->withArchived) {
            $query = Attendance::withoutGlobalScope('notArchived')
                ->with('user');

            if ($this->archiveId) {
                $query->where('data_archive_id', $this->archiveId);
            } else {
                $query->whereNotNull('archived_at')
                    ->whereBetween('date', [$this->fromDate, $this->toDate]);
            }
        } else {
            $query = Attendance::with('user')
                ->whereDate('date', '>=', $this->fromDate)
                ->whereDate('date', '<=', $this->toDate);

            if ($this->status === 'canceled') {
                $query->where('status', 'canceled');
            } elseif ($this->status === 'checked_out') {
                $query->where('status', '!=', 'canceled')->whereNotNull('clock_out');
            } elseif ($this->status === 'checked_in') {
                $query->where('status', '!=', 'canceled')->whereNotNull('clock_in')->whereNull('clock_out')->whereNotIn('status', ['izin', 'sakit']);
            } elseif ($this->status === 'izin') {
                $query->where('status', 'izin');
            } elseif ($this->status === 'sakit') {
                $query->where('status', 'sakit');
            } else {
                $query->where('status', '!=', 'canceled');
            }
        }

        if ($this->role) {
            if ($this->role === 'sales') {
                $query->whereHas('user.roles', fn ($q) => $q->whereIn('name', ['sales', 'field-supervisor']));
            } elseif (in_array($this->role, ['driver', 'staff'], true)) {
                $query->whereHas('user.roles', fn ($q) => $q->where('name', $this->role));
            }
        }
        if ($this->search) {
            $search = $this->search;
            $query->whereHas('user', fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('username', 'like', "%{$search}%"));
        }

        if ($this->userId) {
            $query->where('user_id', $this->userId);
        }

        $this->items = $query->orderBy('date', 'desc')->get();

        return $this->buildRows();
    }

    protected function buildRows(): array
    {
        $rows = [];
        $no = 0;

        foreach ($this->items as $attendance) {
            $no++;
            $isAbsence = $attendance->isAbsence();
            $hasCheckOut = $attendance->clock_out !== null;

            $rows[] = [
                $no,
                $attendance->user->name ?? '-',
                $attendance->user?->getRoleNames()->implode(', ') ?: '-',
                $attendance->date->format('d/m/Y'),
                $attendance->clock_in?->format('H:i') ?? '-',
                $isAbsence ? '-' : ($attendance->clock_out?->format('H:i') ?? '-'),
                $isAbsence ? '-' : $this->durationLabel($attendance->clock_in, $attendance->clock_out),
                $this->statusLabel($attendance),
                $attendance->clock_in_address ?? '-',
                $attendance->clock_in_lat ?? '-',
                $attendance->clock_in_lng ?? '-',
                '',
                $isAbsence ? '-' : ($hasCheckOut ? ($attendance->clock_out_address ?? '-') : 'Belum Check Out'),
                $isAbsence ? '-' : ($hasCheckOut ? ($attendance->clock_out_lat ?? '-') : 'Belum Check Out'),
                $isAbsence ? '-' : ($hasCheckOut ? ($attendance->clock_out_lng ?? '-') : 'Belum Check Out'),
                '',
                '',
                '',
            ];
        }

        return $rows;
    }

    public function registerEvents(): array
    {
        return $this->wrapAfterSheet(function (AfterSheet $event) {
            $sheet = $event->sheet->getDelegate();

            $sheet->getColumnDimension('L')->setWidth(22);
            $sheet->getColumnDimension('P')->setWidth(22);
            $sheet->getColumnDimension('Q')->setWidth(16);
            $sheet->getColumnDimension('R')->setWidth(16);

            $row = 9;
            foreach ($this->items ?? [] as $item) {
                $hasPhoto = $this->addRowPhotos($sheet, $row, [
                    ['col' => 17, 'path' => $item->clock_in_selfie],
                    ['col' => 18, 'path' => $item->clock_out_selfie],
                ], 72);

                $this->addMapLink($sheet, $row, 12, $item->clock_in_lat, $item->clock_in_lng, 'Lihat Lokasi Check In');

                if ($item->clock_out !== null && !empty($item->clock_out_lat) && !empty($item->clock_out_lng)) {
                    $this->addMapLink($sheet, $row, 16, $item->clock_out_lat, $item->clock_out_lng, 'Lihat Lokasi Check Out');
                }

                if ($hasPhoto) {
                    $sheet->getRowDimension($row)->setRowHeight(84);
                }

                $row++;
            }
        });
    }

    private function addMapLink($sheet, int $row, int $col, $lat, $lng, string $label): void
    {
        if ($lat === null || $lat === '' || $lng === null || $lng === '') {
            return;
        }

        $coord = Coordinate::stringFromColumnIndex($col) . $row;
        $sheet->setCellValue($coord, $label);
        $sheet->getCell($coord)->getHyperlink()->setUrl('https://www.google.com/maps?q=' . $lat . ',' . $lng);
        $sheet->getStyle($coord)->applyFromArray([
            'font' => [
                'color' => ['rgb' => '0560FC'],
                'underline' => 'single',
            ],
            'alignment' => ['horizontal' => 'center'],
        ]);
    }

    protected function statusLabel(Attendance $attendance): string
    {
        return $attendance->status_presensi_label;
    }

    private function durationLabel($start, $end): string
    {
        if (! $start || ! $end) {
            return '-';
        }

        $minutes = max(0, (int) $start->diffInMinutes($end));
        $hours = intdiv($minutes, 60);
        $remainder = $minutes % 60;

        return $hours > 0
            ? ($remainder > 0 ? "{$hours}j {$remainder}m" : "{$hours} jam")
            : "{$minutes} menit";
    }

    protected function landscape(): bool
    {
        return true;
    }
}
