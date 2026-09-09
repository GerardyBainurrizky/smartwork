<?php

namespace App\Exports\Sheets;

use App\Exports\BaseReportExport;
use Carbon\Carbon;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class DokumentasiPengirimanDriverSheet extends BaseReportExport
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
        return 'Dokumentasi Pengiriman';
    }

    protected function reportTitle(): string
    {
        return 'DOKUMENTASI FOTO PENGIRIMAN DRIVER';
    }

    protected function emptyMessage(): ?string
    {
        return 'Tidak ada foto dokumentasi pengiriman pada periode yang dipilih.';
    }

    public function headings(): array
    {
        return [
            'No',
            'Tanggal Pengiriman',
            'Driver',
            'Toko / Tujuan',
            'Jenis Dokumentasi',
            'Foto Dokumentasi',
        ];
    }

    public function dataRows(): array
    {
        $rows = [];
        $this->photoRowMap = [];
        $no = 0;

        foreach ($this->visits as $visit) {
            $dateStr = $visit->check_in_at ? $visit->check_in_at->format('d M Y') : '-';
            $driverName = $visit->user->name ?? '-';
            $storeName = $visit->store->name ?? '-';

            // 1. Check In Photo (Foto Toko / Selfie)
            $checkInPhoto = $visit->check_in_selfie ?: $visit->storefront_photo;
            if ($checkInPhoto) {
                $no++;
                $rows[] = [
                    $no,
                    $dateStr,
                    $driverName,
                    $storeName,
                    'Foto Toko / Selfie',
                    '',
                ];
                $this->photoRowMap[count($rows)] = $checkInPhoto;
            }

            // 2. Dynamic Documentation Photos (Foto Barang / Dokumen)
            $checkoutPhotos = $visit->relationLoaded('checkoutPhotos') ? $visit->checkoutPhotos : $visit->checkoutPhotos()->get();
            if ($checkoutPhotos->isEmpty()) {
                $allPhotos = $visit->relationLoaded('photos') ? $visit->photos : $visit->photos()->get();
                $checkoutPhotos = $allPhotos->where('type', 'checkout_documentation');
            }
            $docPhotoPaths = $checkoutPhotos->pluck('photo_path')->filter()->values()->all();
            if (empty($docPhotoPaths)) {
                if ($visit->final_store_photo) {
                    $docPhotoPaths[] = $visit->final_store_photo;
                }
                if ($visit->check_out_selfie) {
                    $docPhotoPaths[] = $visit->check_out_selfie;
                }
            }
            $docPhotoPaths = array_slice($docPhotoPaths, 0, 6);

            foreach ($docPhotoPaths as $pIdx => $pPath) {
                $no++;
                $label = 'Foto Barang/Dokumen #' . ($pIdx + 1);
                $rows[] = [
                    $no,
                    $dateStr,
                    $driverName,
                    $storeName,
                    $label,
                    '',
                ];
                $this->photoRowMap[count($rows)] = $pPath;
            }

            // If visit has no photos at all
            if (! $checkInPhoto && empty($docPhotoPaths)) {
                $no++;
                $rows[] = [
                    $no,
                    $dateStr,
                    $driverName,
                    $storeName,
                    'Dokumentasi Pengiriman',
                    'Tidak ada foto',
                ];
            }
        }

        // 3. Skipped Deliveries Documentation
        foreach ($this->skippedStops as $stop) {
            $route = $stop->route;
            $driver = $route?->user;
            $store = $stop->store;
            $vRecord = $stop->visit ?? $stop->anyVisit;

            $dateStr = $route?->date ? Carbon::parse($route->date)->format('d M Y') : '-';
            $driverName = $driver->name ?? '-';
            $storeName = $store->name ?? '-';

            $skipPhoto = $vRecord?->final_store_photo ?: $vRecord?->storefront_photo;
            $no++;
            $rows[] = [
                $no,
                $dateStr,
                $driverName,
                $storeName,
                'Foto Dilewati',
                $skipPhoto ? '' : 'Tidak ada foto',
            ];
            if ($skipPhoto) {
                $this->photoRowMap[count($rows)] = $skipPhoto;
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

            $sheet->getColumnDimension('F')->setWidth(26);

            if ($dataStart <= $highestRow && $sheet->getCell("A{$dataStart}")->getValue() !== $this->emptyMessage()) {
                for ($r = $dataStart; $r <= $highestRow; $r++) {
                    $sheet->getStyle("A{$r}:F{$r}")->getAlignment()->setWrapText(true)->setVertical('center');
                    $sheet->getStyle("A{$r}")->getAlignment()->setHorizontal('center');
                    $sheet->getStyle("B{$r}")->getAlignment()->setHorizontal('center');
                    $sheet->getStyle("E{$r}")->getAlignment()->setHorizontal('center');
                    $sheet->getStyle("F{$r}")->getAlignment()->setHorizontal('center');

                    $dataIndex = $r - $headingRow;
                    $photoPath = $this->photoRowMap[$dataIndex] ?? null;

                    if ($photoPath) {
                        try {
                            $hasPhoto = $this->addRowPhotos($sheet, $r, [
                                ['col' => 6, 'path' => $photoPath],
                            ], 72);

                            if ($hasPhoto) {
                                $sheet->getRowDimension($r)->setRowHeight(84);
                            } else {
                                $sheet->setCellValue("F{$r}", 'Tidak ada foto');
                                $sheet->getStyle("F{$r}")->applyFromArray(['font' => ['italic' => true, 'color' => ['rgb' => '94A3B8']]]);
                            }
                        } catch (\Throwable $e) {
                            $sheet->setCellValue("F{$r}", 'Tidak ada foto');
                        }
                    } else {
                        if (empty($sheet->getCell("F{$r}")->getValue())) {
                            $sheet->setCellValue("F{$r}", 'Tidak ada foto');
                        }
                        $sheet->getStyle("F{$r}")->applyFromArray(['font' => ['italic' => true, 'color' => ['rgb' => '94A3B8']]]);
                    }
                }

                $sheet->getStyle("A{$dataStart}:F{$highestRow}")->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => 'thin', 'color' => ['rgb' => 'CBD5E1']]],
                ]);
            }
        });
    }

    protected function landscape(): bool
    {
        return false;
    }
}
