<?php

namespace App\Exports\Sheets;

use App\Exports\BaseReportExport;
use App\Models\RouteStop;
use App\Models\User;
use App\Models\Visit;
use Carbon\Carbon;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class FotoKunjunganSheet extends BaseReportExport
{
    protected $visits;
    protected $skippedStops;
    protected array $photoRowMap = [];

    public function __construct(array $params = [], $visits = null, $skippedStops = null)
    {
        parent::__construct($params);
        $this->visits = $visits ?? collect();
        $this->skippedStops = $skippedStops ?? collect();
    }

    public function title(): string
    {
        return 'Foto Kunjungan';
    }

    protected function reportTitle(): string
    {
        return 'FOTO DOKUMENTASI KUNJUNGAN SALES';
    }

    protected function emptyMessage(): ?string
    {
        return 'Tidak ada foto dokumentasi kunjungan pada periode yang dipilih.';
    }

    public function headings(): array
    {
        return [
            'Tanggal',
            'Toko',
            'Kode Toko',
            'Sales',
            'Status Kunjungan',
            'Waktu Check In',
            'Foto Check In',
            'Waktu Foto Etalase',
            'Foto Etalase',
            'Waktu Check Out',
            'Foto Check Out',
            'Waktu Dilewati',
            'Alasan Dilewati',
            'Foto Dilewati',
        ];
    }

    public function dataRows(): array
    {
        $allItems = [];

        foreach ($this->visits as $visit) {
            $visitDate = $visit->check_in_at ? $visit->check_in_at->format('Y-m-d') : ($visit->route?->date ? Carbon::parse($visit->route->date)->format('Y-m-d') : ($visit->created_at ? $visit->created_at->format('Y-m-d') : '1970-01-01'));
            $timeStr = $visit->check_in_at ? $visit->check_in_at->format('H:i:s') : ($visit->created_at ? $visit->created_at->format('H:i:s') : '00:00:00');
            $seq = (int) ($visit->routeStop->sequence ?? ($visit->sequence ?? 0));
            $allItems[] = [
                'type' => 'visit',
                'sort_date' => $visitDate,
                'sort_seq' => $seq,
                'sort_time' => $timeStr,
                'sort_id' => (string) $visit->id,
                'item' => $visit,
            ];
        }

        foreach ($this->skippedStops as $stop) {
            $stopDate = $stop->route?->date ? Carbon::parse($stop->route->date)->format('Y-m-d') : ($stop->created_at ? $stop->created_at->format('Y-m-d') : '1970-01-01');
            $timeStr = $stop->updated_at ? $stop->updated_at->format('H:i:s') : ($stop->created_at ? $stop->created_at->format('H:i:s') : '00:00:00');
            $seq = (int) ($stop->sequence ?? 0);
            $allItems[] = [
                'type' => 'skipped',
                'sort_date' => $stopDate,
                'sort_seq' => $seq,
                'sort_time' => $timeStr,
                'sort_id' => (string) $stop->id,
                'item' => $stop,
            ];
        }

        usort($allItems, function ($a, $b) {
            $cmpDate = strcmp($a['sort_date'], $b['sort_date']);
            if ($cmpDate !== 0) {
                return $cmpDate;
            }
            if ($a['sort_seq'] > 0 && $b['sort_seq'] > 0 && $a['sort_seq'] !== $b['sort_seq']) {
                return $a['sort_seq'] <=> $b['sort_seq'];
            }
            $cmpTime = strcmp($a['sort_time'], $b['sort_time']);
            if ($cmpTime !== 0) {
                return $cmpTime;
            }
            return strcmp($a['sort_id'], $b['sort_id']);
        });

        $rows = [];
        $this->photoRowMap = [];

        foreach ($allItems as $entry) {
            if ($entry['type'] === 'visit') {
                $visit = $entry['item'];
                $statusLabel = match ($visit->status) {
                    'completed' => 'Selesai',
                    'in_progress' => 'Sedang Berjalan',
                    'skipped' => 'Dilewati',
                    default => ucfirst((string) $visit->status),
                };

                $salesName = $visit->user->name ?? '-';
                $dateStr = $visit->check_in_at ? $visit->check_in_at->format('d M Y') : '-';
                $storeName = $visit->store->name ?? '-';
                $storeCode = $visit->store->code ?? '-';

                $waktuCheckIn = $visit->check_in_at ? $visit->check_in_at->format('H:i') : '-';
                $waktuEtalase = $visit->check_out_at ? $visit->check_out_at->format('H:i') : ($visit->check_in_at ? $visit->check_in_at->format('H:i') : '-');
                $waktuCheckOut = $visit->check_out_at ? $visit->check_out_at->format('H:i') : '-';

                $storePhoto = $visit->final_store_photo ?? $visit->storefront_photo;

                $rows[] = [
                    $dateStr,
                    $storeName,
                    $storeCode,
                    $salesName,
                    $statusLabel,
                    $waktuCheckIn,
                    $visit->check_in_selfie ? '' : 'Tidak ada foto', // Col G (7)
                    $waktuEtalase,
                    $storePhoto ? '' : 'Tidak ada foto',             // Col I (9)
                    $waktuCheckOut,
                    $visit->check_out_selfie ? '' : 'Tidak ada foto', // Col K (11)
                    '-',                                             // Waktu Dilewati
                    '-',                                             // Alasan Dilewati
                    'Tidak ada foto',                                // Col N (14)
                ];

                $rowIndex = count($rows);
                $this->photoRowMap[$rowIndex] = [
                    7 => $visit->check_in_selfie,
                    9 => $storePhoto,
                    11 => $visit->check_out_selfie,
                    14 => null,
                ];
            } else {
                $stop = $entry['item'];
                $vRecord = $stop->visit ?? $stop->anyVisit;
                $photoPath = $vRecord?->final_store_photo ?: $vRecord?->storefront_photo;
                $reason = $stop->notes ?: 'Kunjungan dilewati';

                if (empty($photoPath) && ! empty($stop->notes) && str_starts_with($stop->notes, '{')) {
                    $skipMeta = json_decode($stop->notes, true);
                    if (is_array($skipMeta)) {
                        $photoPath = $skipMeta['photo_path'] ?? null;
                        $reason = $skipMeta['reason'] ?? $reason;
                    }
                }

                $dateStr = $stop->route?->date ? Carbon::parse($stop->route->date)->format('d M Y') : '-';
                $userObj = $stop->route?->user ?? ($this->userId ? User::find($this->userId) : null);
                $salesName = $userObj->name ?? '-';

                $rows[] = [
                    $dateStr,
                    $stop->store->name ?? '-',
                    $stop->store->code ?? '-',
                    $salesName,
                    'Dilewati',
                    '-',
                    'Tidak ada foto',
                    '-',
                    'Tidak ada foto',
                    '-',
                    'Tidak ada foto',
                    $dateStr,
                    $reason,
                    $photoPath ? '' : 'Tidak ada foto',
                ];

                $rowIndex = count($rows);
                $this->photoRowMap[$rowIndex] = [
                    7 => null,
                    9 => null,
                    11 => null,
                    14 => $photoPath,
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

            $sheet->getColumnDimension('G')->setWidth(20);
            $sheet->getColumnDimension('I')->setWidth(20);
            $sheet->getColumnDimension('K')->setWidth(20);
            $sheet->getColumnDimension('N')->setWidth(20);

            if ($dataStart <= $highestRow && $sheet->getCell("A{$dataStart}")->getValue() !== $this->emptyMessage()) {
                for ($r = $dataStart; $r <= $highestRow; $r++) {
                    $sheet->getStyle("A{$r}:N{$r}")->getAlignment()->setWrapText(true)->setVertical('center');

                    // Center: Tanggal (A), Status (E), Waktu Check In (F), Foto Check In (G), Waktu Etalase (H), Foto Etalase (I), Waktu Check Out (J), Foto Check Out (K), Waktu Dilewati (L), Foto Dilewati (N)
                    $sheet->getStyle("A{$r}")->getAlignment()->setHorizontal('center');
                    $sheet->getStyle("E{$r}:L{$r}")->getAlignment()->setHorizontal('center');
                    $sheet->getStyle("N{$r}")->getAlignment()->setHorizontal('center');

                    // Left: Toko (B), Kode Toko (C), Sales (D), Alasan Dilewati (M)
                    $sheet->getStyle("B{$r}:D{$r}")->getAlignment()->setHorizontal('left');
                    $sheet->getStyle("M{$r}")->getAlignment()->setHorizontal('left');

                    $statusVal = $sheet->getCell("E{$r}")->getValue();
                    if ($statusVal === 'Selesai') {
                        $sheet->getStyle("E{$r}")->applyFromArray(['font' => ['color' => ['rgb' => '15803D'], 'bold' => true]]);
                    } elseif ($statusVal === 'Dilewati') {
                        $sheet->getStyle("E{$r}")->applyFromArray(['font' => ['color' => ['rgb' => '92400E'], 'bold' => true]]);
                    } elseif ($statusVal === 'Sedang Berjalan') {
                        $sheet->getStyle("E{$r}")->applyFromArray(['font' => ['color' => ['rgb' => '0DA4CE'], 'bold' => true]]);
                    }

                    // Embed Photos for this row
                    $dataIndex = $r - $headingRow;
                    $rowPhotos = $this->photoRowMap[$dataIndex] ?? [];
                    $hasAnyEmbeddedPhoto = false;

                    foreach ([7, 9, 11, 14] as $colIdx) {
                        $path = $rowPhotos[$colIdx] ?? null;
                        $colLetter = Coordinate::stringFromColumnIndex($colIdx);

                        if ($path) {
                            try {
                                $hasPhoto = $this->addRowPhotos($sheet, $r, [
                                    ['col' => $colIdx, 'path' => $path],
                                ], 72);

                                if ($hasPhoto) {
                                    $hasAnyEmbeddedPhoto = true;
                                } else {
                                    $sheet->setCellValue("{$colLetter}{$r}", 'Tidak ada foto');
                                    $sheet->getStyle("{$colLetter}{$r}")->applyFromArray(['font' => ['italic' => true, 'color' => ['rgb' => '94A3B8']]]);
                                }
                            } catch (\Throwable $e) {
                                $sheet->setCellValue("{$colLetter}{$r}", 'Tidak ada foto');
                            }
                        } else {
                            $sheet->setCellValue("{$colLetter}{$r}", 'Tidak ada foto');
                            $sheet->getStyle("{$colLetter}{$r}")->applyFromArray(['font' => ['italic' => true, 'color' => ['rgb' => '94A3B8']]]);
                        }
                    }

                    if ($hasAnyEmbeddedPhoto) {
                        $sheet->getRowDimension($r)->setRowHeight(84);
                    }
                }

                $sheet->getStyle("A{$dataStart}:N{$highestRow}")->applyFromArray([
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
