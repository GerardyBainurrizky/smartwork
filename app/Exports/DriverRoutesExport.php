<?php

namespace App\Exports;

use App\Models\Route;
use App\Models\Store;
use App\Models\User;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class DriverRoutesExport extends BaseReportExport
{
    protected array $mapLinks = [];

    public function title(): string
    {
        return 'Laporan Rencana Pengiriman';
    }

    protected function reportTitle(): string
    {
        return 'LAPORAN RENCANA PENGIRIMAN';
    }

    protected function extraMetaRows(): array
    {
        $rows = [];
        if ($this->userId) {
            $user = User::find($this->userId);
            $rows[] = 'Driver: ' . ($user->name ?? '-');
        } else {
            $rows[] = 'Driver: Semua Driver';
        }

        if (! empty($this->status)) {
            $statusLabel = match ($this->status) {
                'completed' => 'Selesai',
                'active' => 'Sedang Berjalan',
                'draft' => 'Draft',
                'cancelled' => 'Dibatalkan',
                default => ucfirst($this->status),
            };
            $rows[] = 'Status: ' . $statusLabel;
        } else {
            $rows[] = 'Status: Semua Status';
        }

        if (! empty($this->params['store_id'])) {
            $store = Store::find($this->params['store_id']);
            if ($store) {
                $rows[] = 'Tujuan / Toko: ' . $store->name;
            }
        }

        return $rows;
    }

    protected function emptyMessage(): ?string
    {
        return 'Tidak ada data rencana pengiriman pada periode yang dipilih.';
    }

    public function headings(): array
    {
        return [
            'No',
            'Nama Driver',
            'Rute Pengiriman',
            'Tanggal Pengiriman',
            'Jumlah Tujuan',
            'Tujuan Dikunjungi',
            'Status Pengiriman',
            'Durasi Aktual',
            'Dibuat Oleh',
            'Catatan Rencana',
            'Daftar Tujuan',
            'Status Tujuan',
            'Catatan',
            'Maps',
        ];
    }

    public function dataRows(): array
    {
        if ($this->withArchived) {
            $query = Route::withoutGlobalScope('notArchived')
                ->with(['user.roles', 'stops.store', 'creator'])
                ->whereHas('user.roles', fn ($q) => $q->where('name', 'driver'));

            if ($this->archiveId) {
                $query->where('data_archive_id', $this->archiveId);
            } else {
                $query->whereNotNull('archived_at')
                    ->whereBetween('date', [$this->fromDate, $this->toDate]);
            }
        } else {
            $query = Route::with(['user.roles', 'stops.store', 'creator'])
                ->whereHas('user.roles', fn ($q) => $q->where('name', 'driver'))
                ->whereBetween('date', [$this->fromDate, $this->toDate]);
        }

        if ($this->userId) {
            $query->where('user_id', $this->userId);
        }

        if (! empty($this->params['store_id'])) {
            $storeId = $this->params['store_id'];
            $query->whereHas('stops', fn ($q) => $q->where('store_id', $storeId));
        }

        if (! empty($this->params['search'])) {
            $search = $this->params['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('stops.store', fn ($sq) => $sq->where('name', 'like', "%{$search}%"));
            });
        }

        $routesCollection = $query->orderBy('date', 'desc')->get();

        if (! empty($this->status)) {
            $routesCollection = $routesCollection->filter(fn ($r) => $r->computed_status === $this->status);
        }

        $rows = [];
        $no = 0;
        $this->mapLinks = [];
        $currentRow = $this->headingRow();

        foreach ($routesCollection as $route) {
            $stops = $route->stops;

            if ($stops->isEmpty()) {
                $no++;
                $currentRow++;

                $rows[] = [
                    $no,
                    $route->user->name ?? '-',
                    $route->name,
                    $route->date->format('d/m/Y'),
                    0,
                    0,
                    $this->routeStatusLabel($route->computed_status),
                    $this->durationLabel($route->started_at, $route->completed_at),
                    $route->creator->name ?? ($route->created_by_label ?? '-'),
                    $route->notes ?: '-',
                    '-',
                    '-',
                    '-',
                    '-',
                ];
            } else {
                $totalStopsCount = $stops->count();
                $visitedStopsCount = $stops->where('status', 'visited')->count();

                foreach ($stops as $idx => $stop) {
                    $no++;
                    $currentRow++;

                    $store = $stop->store;
                    $storeName = $store->name ?? 'Tujuan Tidak Ditemukan';
                    $statusText = match ($stop->status) {
                        'visited' => 'Dikunjungi',
                        'skipped' => 'Dilewati',
                        'pending' => 'Belum Dikunjungi',
                        default => $stop->status ? ucfirst($stop->status) : 'Belum Dikunjungi',
                    };
                    $notes = ! empty($stop->notes) ? $stop->notes : '-';

                    $hasCoords = $store && ! empty($store->latitude) && ! empty($store->longitude) && is_numeric($store->latitude) && is_numeric($store->longitude);
                    if ($hasCoords) {
                        $mapsUrl = "https://www.google.com/maps?q={$store->latitude},{$store->longitude}";
                        $this->mapLinks[$currentRow] = $mapsUrl;
                        $mapsDisplay = 'Buka Maps';
                    } else {
                        $mapsDisplay = '-';
                    }

                    $rows[] = [
                        $no,
                        $route->user->name ?? '-',
                        $route->name,
                        $route->date->format('d/m/Y'),
                        $totalStopsCount,
                        $visitedStopsCount,
                        $this->routeStatusLabel($route->computed_status),
                        $this->durationLabel($route->started_at, $route->completed_at),
                        $route->creator->name ?? ($route->created_by_label ?? '-'),
                        $route->notes ?: '-',
                        $storeName,
                        $statusText,
                        $notes,
                        $mapsDisplay,
                    ];
                }
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

            // Set column widths
            $sheet->getColumnDimension('A')->setWidth(6);  // No
            $sheet->getColumnDimension('B')->setWidth(20); // Nama Driver
            $sheet->getColumnDimension('C')->setWidth(24); // Rute Pengiriman
            $sheet->getColumnDimension('D')->setWidth(16); // Tanggal Pengiriman
            $sheet->getColumnDimension('E')->setWidth(14); // Jumlah Tujuan
            $sheet->getColumnDimension('F')->setWidth(16); // Tujuan Dikunjungi
            $sheet->getColumnDimension('G')->setWidth(16); // Status Pengiriman
            $sheet->getColumnDimension('H')->setWidth(16); // Durasi Aktual
            $sheet->getColumnDimension('I')->setWidth(16); // Dibuat Oleh
            $sheet->getColumnDimension('J')->setWidth(25); // Catatan Rencana
            $sheet->getColumnDimension('K')->setWidth(28); // Daftar Tujuan
            $sheet->getColumnDimension('L')->setWidth(18); // Status Tujuan
            $sheet->getColumnDimension('M')->setWidth(30); // Catatan
            $sheet->getColumnDimension('N')->setWidth(15); // Maps

            foreach (range('A', 'N') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(false);
            }

            if ($dataStart <= $highestRow) {
                // Vertical align TOP for all data cells
                $sheet->getStyle("A{$dataStart}:N{$highestRow}")
                    ->getAlignment()
                    ->setVertical(Alignment::VERTICAL_TOP);

                // Wrap Text for text columns
                $sheet->getStyle("B{$dataStart}:C{$highestRow}")->getAlignment()->setWrapText(true);
                $sheet->getStyle("I{$dataStart}:K{$highestRow}")->getAlignment()->setWrapText(true);
                $sheet->getStyle("M{$dataStart}:M{$highestRow}")->getAlignment()->setWrapText(true);

                // Center alignment
                $sheet->getStyle("A{$dataStart}:A{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("D{$dataStart}:H{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("L{$dataStart}:L{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("N{$dataStart}:N{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Left alignment
                $sheet->getStyle("B{$dataStart}:C{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $sheet->getStyle("I{$dataStart}:K{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $sheet->getStyle("M{$dataStart}:M{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

                // Hyperlinks on Column N (Maps)
                foreach ($this->mapLinks as $rowNum => $url) {
                    if (! empty($url) && $rowNum <= $highestRow) {
                        $coord = "N{$rowNum}";
                        $sheet->getCell($coord)->getHyperlink()->setUrl($url);
                        $sheet->getStyle($coord)->applyFromArray([
                            'font' => [
                                'color' => ['rgb' => '0560FC'],
                                'underline' => 'single',
                                'bold' => true,
                            ],
                        ]);
                    }
                }
            }
        });
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

    private function routeStatusLabel(string $status): string
    {
        return match ($status) {
            'completed' => 'Selesai',
            'active' => 'Sedang Berjalan',
            'draft' => 'Menunggu',
            'cancelled' => 'Dibatalkan',
            default => ucfirst($status),
        };
    }

    protected function landscape(): bool
    {
        return true;
    }
}

