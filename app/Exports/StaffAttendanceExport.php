<?php

namespace App\Exports;

use App\Models\Attendance;

class StaffAttendanceExport extends AttendanceExport
{
    protected function reportTitle(): string
    {
        return 'LAPORAN PRESENSI KARYAWAN';
    }

    protected function statusLabel(Attendance $attendance): string
    {
        return $attendance->status_presensi_label_staff;
    }

    public function dataRows(): array
    {
        $query = Attendance::with('user')
            ->where('user_id', $this->userId)
            ->whereBetween('date', [$this->fromDate, $this->toDate]);

        $status = trim((string) $this->status);

        if ($status === 'checked_in') {
            $query->where('status', '!=', 'canceled')->whereNotNull('clock_in')->whereNull('clock_out');
        } elseif ($status === 'checked_out') {
            $query->where('status', '!=', 'canceled')->whereNotNull('clock_out');
        } elseif ($status === 'canceled') {
            $query->where('status', 'canceled');
        }

        $this->items = $query->orderBy('date', 'desc')->get();

        return $this->buildRows();
    }
}