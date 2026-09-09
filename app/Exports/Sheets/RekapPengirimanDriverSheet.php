<?php

namespace App\Exports\Sheets;

use App\Exports\BaseReportExport;
use App\Models\User;
use App\Models\Visit;
use Carbon\Carbon;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class RekapPengirimanDriverSheet extends BaseReportExport
{
    protected $visits;
    protected $skippedStops;
    protected int $maxCheckoutPhotos = 1;
    protected array $photoRowMap = [];

    public function __construct(array $params = [], $visits = null, $skippedStops = null)
    {
        parent::__construct($params);
        $this->visits = $visits ?? collect();
        $this->skippedStops = $skippedStops ?? collect();
        $this->maxCheckoutPhotos = $this->calculateMaxCheckoutPhotos();
    }

    public function title(): string
    {
        return 'Rekap Pengiriman';
    }

    protected function reportTitle(): string
    {
        return 'REKAPITULASI HASIL PENGIRIMAN DRIVER';
    }

    /**
     * Hitung jumlah foto barang / dokumentasi checkout terbanyak dari data aktual export (maksimal 6).
     */
    protected function calculateMaxCheckoutPhotos(): int
    {
        $max = 0;

        foreach ($this->visits as $visit) {
            $checkoutPhotos = $visit->relationLoaded('checkoutPhotos')
                ? $visit->checkoutPhotos
                : ($visit->relationLoaded('photos')
                    ? $visit->photos->where('type', 'checkout_documentation')
                    : $visit->checkoutPhotos()->get());

            $docPhotoPaths = $checkoutPhotos->pluck('photo_path')->filter()->values()->all();

            if (empty($docPhotoPaths)) {
                if (! empty($visit->final_store_photo)) {
                    $docPhotoPaths[] = $visit->final_store_photo;
                }
                if (! empty($visit->check_out_selfie)) {
                    $docPhotoPaths[] = $visit->check_out_selfie;
                }
            }

            $count = count($docPhotoPaths);
            if ($count > $max) {
                $max = $count;
            }
        }

        return min(6, max(1, $max));
    }

    /**
     * Sub-headings (Baris Header 2)
     */
    public function headings(): array
    {
        $subHeaders = [
            'No',
            'Tanggal Pengiriman',
            'Driver',
            'Toko / Tujuan',
            'Kode Toko',
            'Urutan',
            'Catatan Toko / Tujuan Pengiriman',
            'Status',
            'Waktu Check In',
            'Lokasi Check In',
            'Foto Toko / Selfie',
            'Waktu Check Out',
            'Lokasi Check Out',
            'Transaksi Pengiriman (Rp)',
            'Metode Pembayaran',
            'Hasil Pengiriman',
            'Catatan Tambahan',
        ];

        // Foto Check Out - Barang / Dokumentasi (Dinamis Foto 1 .. Foto N, maks 6)
        for ($i = 1; $i <= $this->maxCheckoutPhotos; $i++) {
            $subHeaders[] = "Foto {$i}";
        }

        // Foto Dilewati (Terpisah)
        $subHeaders[] = 'Foto Dilewati';

        return $subHeaders;
    }

    /**
     * Grouped Headings (Baris Header 1)
     */
    public function groupedHeadings(): array
    {
        $groupHeaders = [
            'INFORMASI PENGIRIMAN', '', '', '', '', '', '', '', // 1..8 (A-H)
            'CHECK IN PENGIRIMAN', '', '',                      // 9..11 (I-K)
            'CHECK OUT PENGIRIMAN', '',                         // 12..13 (L-M)
            'TRANSAKSI PENGIRIMAN', '',                         // 14..15 (N-O)
            'HASIL PENGIRIMAN', '',                             // 16..17 (P-Q)
        ];

        // FOTO CHECK OUT — BARANG / DOKUMENTASI (Span maxCheckoutPhotos kolom)
        $groupHeaders[] = 'FOTO CHECK OUT — BARANG / DOKUMENTASI';
        for ($i = 1; $i < $this->maxCheckoutPhotos; $i++) {
            $groupHeaders[] = '';
        }

        // FOTO DILEWATI (1 kolom)
        $groupHeaders[] = 'FOTO DILEWATI';

        return $groupHeaders;
    }

    /**
     * Override array() to include 2-tier grouped headers.
     */
    public function array(): array
    {
        $dataRows = $this->dataRows();

        $driverLabel = 'Semua Driver';
        if ($this->userId) {
            $driverUser = User::find($this->userId);
            $driverLabel = $driverUser?->name ?? 'Semua Driver';
        }
        $statusLabel = match ($this->status) {
            'completed' => 'Selesai',
            'in_progress' => 'Sedang Dikirim',
            default => 'Semua Status',
        };

        $rows = [];
        $rows[] = ['', $this->companyName];
        $rows[] = ['', $this->companySubtitle];
        $rows[] = [$this->reportTitle()];
        $rows[] = ['Periode: ' . $this->periodLabel() . '   |   Driver: ' . $driverLabel . '   |   Status: ' . $statusLabel];
        $rows[] = ['Tanggal Cetak: ' . now()->format('d M Y, H:i')];
        $rows[] = ['Dicetak Oleh: ' . (auth()->check() ? auth()->user()->name : 'Administrator')];
        $rows[] = ['Jumlah Data: ' . count($dataRows)];

        // Baris Header 1: Grouped Headings
        $rows[] = $this->groupedHeadings();

        // Baris Header 2: Sub Headings
        $rows[] = $this->headings();

        if (empty($dataRows) && $this->emptyMessage()) {
            $emptyRow = array_fill(0, count($this->headings()), '');
            $emptyRow[0] = $this->emptyMessage();
            $rows[] = $emptyRow;
        } else {
            foreach ($dataRows as $row) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    public function dataRows(): array
    {
        $rows = [];
        $this->photoRowMap = [];
        $no = 0;

        foreach ($this->visits as $visit) {
            $no++;
            $sequence = $visit->routeStop->sequence ?? '-';
            $storeNote = $visit->routeStop?->notes ?: '-';

            $statusLabel = match ($visit->status) {
                'completed' => 'Selesai',
                'in_progress' => 'Sedang Dikirim',
                default => ucfirst((string) $visit->status),
            };

            $checkInLoc = $visit->check_in_address ?: ($visit->check_in_lat && $visit->check_in_lng ? "{$visit->check_in_lat}, {$visit->check_in_lng}" : '-');
            $checkOutLoc = $visit->check_out_at ? ($visit->check_out_address ?: ($visit->check_out_lat && $visit->check_out_lng ? "{$visit->check_out_lat}, {$visit->check_out_lng}" : '-')) : 'Belum Check Out';

            $hasTx = (float) ($visit->transaction_amount ?? 0) > 0;
            $txAmount = $hasTx ? (float) $visit->transaction_amount : '-';
            $payMethod = $hasTx ? strtoupper($visit->payment_method ?? 'tunai') : '-';

            // Check In Photo (Hanya 1 Foto)
            $checkInPhoto = $visit->check_in_selfie ?: $visit->storefront_photo;

            // Checkout Goods / Documentation Photos (Bisa Banyak, Maks 6)
            $checkoutPhotos = $visit->relationLoaded('checkoutPhotos')
                ? $visit->checkoutPhotos
                : ($visit->relationLoaded('photos')
                    ? $visit->photos->where('type', 'checkout_documentation')
                    : $visit->checkoutPhotos()->get());

            $docPhotoPaths = $checkoutPhotos->pluck('photo_path')->filter()->values()->all();
            if (empty($docPhotoPaths)) {
                if (! empty($visit->final_store_photo)) {
                    $docPhotoPaths[] = $visit->final_store_photo;
                }
                if (! empty($visit->check_out_selfie)) {
                    $docPhotoPaths[] = $visit->check_out_selfie;
                }
            }
            $docPhotoPaths = array_slice($docPhotoPaths, 0, 6);

            $row = [
                $no,
                $visit->check_in_at ? $visit->check_in_at->format('d M Y') : '-',
                $visit->user->name ?? '-',
                $visit->store->name ?? '-',
                $visit->store->code ?? '-',
                $sequence,
                $storeNote,
                $statusLabel,
                $visit->check_in_at ? $visit->check_in_at->format('H:i') : '-',
                $checkInLoc,
                $checkInPhoto ? '' : 'Tidak ada foto', // Col K (11)
                $visit->check_out_at ? $visit->check_out_at->format('H:i') : '-',
                $checkOutLoc,
                $txAmount,
                $payMethod,
                $visit->visit_result ?: ($visit->status === 'completed' ? 'Terkirim Selesai' : '-'),
                $visit->final_notes ?: '-',
            ];

            // Dynamic columns for checkout photos (Col 18 .. 17+max)
            $rowPhotos = [
                11 => $checkInPhoto, // Col K
            ];

            for ($i = 0; $i < $this->maxCheckoutPhotos; $i++) {
                $pPath = $docPhotoPaths[$i] ?? null;
                $colIdx = 18 + $i;
                $row[] = $pPath ? '' : 'Tidak ada foto';
                $rowPhotos[$colIdx] = $pPath;
            }

            // Skip photo column (Col 18+max)
            $skipColIdx = 18 + $this->maxCheckoutPhotos;
            $row[] = '-'; // Not skipped
            $rowPhotos[$skipColIdx] = null;

            $rows[] = $row;
            $this->photoRowMap[count($rows)] = $rowPhotos;
        }

        // Include Skipped Deliveries if present
        foreach ($this->skippedStops as $stop) {
            $no++;
            $route = $stop->route;
            $driver = $route?->user;
            $store = $stop->store;
            $vRecord = $stop->visit ?? $stop->anyVisit;
            $storeNote = $stop->notes ?: '-';

            $dateStr = $route?->date ? Carbon::parse($route->date)->format('d M Y') : '-';
            $skipTime = $vRecord?->check_in_at ? $vRecord->check_in_at->format('H:i') : '-';
            $skipLoc = $vRecord?->check_in_address ?: ($vRecord?->check_in_lat && $vRecord?->check_in_lng ? "{$vRecord->check_in_lat}, {$vRecord->check_in_lng}" : '-');
            $reason = $vRecord?->initial_notes ?: ($stop->notes ?: '-');

            $skipPhoto = $vRecord?->final_store_photo ?: $vRecord?->storefront_photo;
            if (! $skipPhoto && ! empty($stop->notes) && str_starts_with($stop->notes, '{')) {
                $skipMeta = json_decode($stop->notes, true);
                $skipPhoto = $skipMeta['photo_path'] ?? null;
            }

            $row = [
                $no,
                $dateStr,
                $driver->name ?? '-',
                $store->name ?? '-',
                $store->code ?? '-',
                $stop->sequence ?? '-',
                $storeNote,
                'Dilewati',
                $skipTime,
                $skipLoc,
                'Tidak ada foto', // No checkin photo on skip
                '-',
                '-',
                '-',
                '-',
                'Dilewati: ' . $reason,
                $reason,
            ];

            $rowPhotos = [
                11 => null,
            ];

            // Fill empty checkout photos for skipped delivery
            for ($i = 0; $i < $this->maxCheckoutPhotos; $i++) {
                $colIdx = 18 + $i;
                $row[] = 'Tidak ada foto';
                $rowPhotos[$colIdx] = null;
            }

            // Skip photo column
            $skipColIdx = 18 + $this->maxCheckoutPhotos;
            $row[] = $skipPhoto ? '' : 'Tidak ada foto';
            $rowPhotos[$skipColIdx] = $skipPhoto;

            $rows[] = $row;
            $this->photoRowMap[count($rows)] = $rowPhotos;
        }

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $this->afterSheetCustom($event);
            },
        ];
    }

    protected function afterSheetCustom(AfterSheet $event): void
    {
        $sheet = $event->sheet->getDelegate();
        $columnCount = count($this->headings());
        $lastColumn = Coordinate::stringFromColumnIndex($columnCount);
        $highestRow = $sheet->getHighestRow();

        $metaEnd = 7;
        $groupHeaderRow = 8;
        $subHeaderRow = 9;
        $dataStart = 10;

        // 1. Meta Header Styling (Rows 1..7)
        $sheet->mergeCells("B1:{$lastColumn}1");
        $sheet->getRowDimension(1)->setRowHeight(64);
        $sheet->getStyle("B1:{$lastColumn}1")->applyFromArray([
            'font' => ['bold' => true, 'size' => 20, 'color' => ['rgb' => '0F172A']],
            'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
        ]);

        $sheet->mergeCells("B2:{$lastColumn}2");
        $sheet->getRowDimension(2)->setRowHeight(20);
        $sheet->getStyle("B2:{$lastColumn}2")->applyFromArray([
            'font' => ['size' => 11, 'italic' => true, 'color' => ['rgb' => '64748B']],
            'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
        ]);

        $sheet->mergeCells("A3:{$lastColumn}3");
        $sheet->getRowDimension(3)->setRowHeight(28);
        $sheet->getStyle("A3:{$lastColumn}3")->applyFromArray([
            'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => '0DA4CE']],
            'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
        ]);

        for ($metaRow = 4; $metaRow <= $metaEnd; $metaRow++) {
            $sheet->mergeCells("A{$metaRow}:{$lastColumn}{$metaRow}");
            $sheet->getRowDimension($metaRow)->setRowHeight(18);
            $sheet->getStyle("A{$metaRow}:{$lastColumn}{$metaRow}")->applyFromArray([
                'font' => ['size' => 10, 'color' => ['rgb' => '334155']],
                'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
            ]);
        }

        // 2. Baris Header 1: Grouped Headers Merging & Styling
        $sheet->mergeCells("A{$groupHeaderRow}:H{$groupHeaderRow}"); // INFORMASI PENGIRIMAN (A-H, 8 kolom)
        $sheet->mergeCells("I{$groupHeaderRow}:K{$groupHeaderRow}"); // CHECK IN PENGIRIMAN (I-K, 3 kolom)
        $sheet->mergeCells("L{$groupHeaderRow}:M{$groupHeaderRow}"); // CHECK OUT PENGIRIMAN (L-M, 2 kolom)
        $sheet->mergeCells("N{$groupHeaderRow}:O{$groupHeaderRow}"); // TRANSAKSI PENGIRIMAN (N-O, 2 kolom)
        $sheet->mergeCells("P{$groupHeaderRow}:Q{$groupHeaderRow}"); // HASIL PENGIRIMAN (P-Q, 2 kolom)

        // Merge Foto Check Out Group Header (R .. 17+max)
        $firstDocCol = 'R';
        $lastDocCol = Coordinate::stringFromColumnIndex(17 + $this->maxCheckoutPhotos);
        $sheet->mergeCells("{$firstDocCol}{$groupHeaderRow}:{$lastDocCol}{$groupHeaderRow}");

        // Skip Photo Group Header (Last column)
        $skipCol = Coordinate::stringFromColumnIndex(18 + $this->maxCheckoutPhotos);

        $sheet->getRowDimension($groupHeaderRow)->setRowHeight(26);
        $sheet->getStyle("A{$groupHeaderRow}:{$lastColumn}{$groupHeaderRow}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '085A75']],
            'alignment' => ['horizontal' => 'center', 'vertical' => 'center', 'wrapText' => true],
            'borders' => [
                'allBorders' => ['borderStyle' => 'thin', 'color' => ['rgb' => '053A4C']],
            ],
        ]);

        // 3. Baris Header 2: Sub Headers Styling
        $sheet->getRowDimension($subHeaderRow)->setRowHeight(24);
        $sheet->getStyle("A{$subHeaderRow}:{$lastColumn}{$subHeaderRow}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 9.5, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '0DA4CE']],
            'alignment' => ['horizontal' => 'center', 'vertical' => 'center', 'wrapText' => true],
            'borders' => [
                'allBorders' => ['borderStyle' => 'thin', 'color' => ['rgb' => '0B7494']],
            ],
        ]);

        // 4. Column Widths
        $sheet->getColumnDimension('A')->setWidth(7);
        $sheet->getColumnDimension('B')->setWidth(16);
        $sheet->getColumnDimension('C')->setWidth(22);
        $sheet->getColumnDimension('D')->setWidth(28);
        $sheet->getColumnDimension('E')->setWidth(14);
        $sheet->getColumnDimension('F')->setWidth(10);
        $sheet->getColumnDimension('G')->setWidth(34); // Catatan Toko / Tujuan Pengiriman
        $sheet->getColumnDimension('H')->setWidth(16); // Status
        $sheet->getColumnDimension('I')->setWidth(15);
        $sheet->getColumnDimension('J')->setWidth(30);
        $sheet->getColumnDimension('K')->setWidth(24); // Foto Toko / Selfie
        $sheet->getColumnDimension('L')->setWidth(15);
        $sheet->getColumnDimension('M')->setWidth(30);
        $sheet->getColumnDimension('N')->setWidth(22); // Transaksi Pengiriman
        $sheet->getColumnDimension('O')->setWidth(18); // Metode Pembayaran
        $sheet->getColumnDimension('P')->setWidth(38); // Hasil Pengiriman (Luas)
        $sheet->getColumnDimension('Q')->setWidth(32); // Catatan Tambahan (Luas)

        for ($i = 1; $i <= $this->maxCheckoutPhotos; $i++) {
            $colLetter = Coordinate::stringFromColumnIndex(17 + $i);
            $sheet->getColumnDimension($colLetter)->setWidth(24);
        }
        $sheet->getColumnDimension($skipCol)->setWidth(24);

        // 5. Data Rows Formatting & Photo Embedding
        if ($dataStart <= $highestRow) {
            $sheet->getStyle("A{$dataStart}:{$lastColumn}{$highestRow}")->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => 'thin', 'color' => ['rgb' => 'CBD5E1']]],
            ]);

            for ($r = $dataStart; $r <= $highestRow; $r++) {
                $sheet->getStyle("A{$r}:{$lastColumn}{$r}")->getAlignment()->setWrapText(true);

                // Default alignment: vertical center
                $sheet->getStyle("A{$r}:O{$r}")->getAlignment()->setVertical('center');
                $sheet->getStyle("R{$r}:{$lastColumn}{$r}")->getAlignment()->setVertical('center');

                // Catatan Toko (G), Hasil Pengiriman (P) & Catatan Tambahan (Q): Vertical Top, Left
                $sheet->getStyle("G{$r}")->getAlignment()->setVertical('top')->setHorizontal('left');
                $sheet->getStyle("P{$r}:Q{$r}")->getAlignment()->setVertical('top')->setHorizontal('left');

                // Center specific columns
                $sheet->getStyle("A{$r}")->getAlignment()->setHorizontal('center');
                $sheet->getStyle("B{$r}")->getAlignment()->setHorizontal('center');
                $sheet->getStyle("E{$r}:F{$r}")->getAlignment()->setHorizontal('center');
                $sheet->getStyle("H{$r}:I{$r}")->getAlignment()->setHorizontal('center');
                $sheet->getStyle("K{$r}:L{$r}")->getAlignment()->setHorizontal('center');
                $sheet->getStyle("O{$r}")->getAlignment()->setHorizontal('center');
                $sheet->getStyle("R{$r}:{$lastColumn}{$r}")->getAlignment()->setHorizontal('center');

                // Format column N (Transaksi Pengiriman)
                $txVal = $sheet->getCell("N{$r}")->getValue();
                if (is_numeric($txVal)) {
                    $sheet->getStyle("N{$r}")->getNumberFormat()->setFormatCode('#,##0');
                    $sheet->getStyle("N{$r}")->getAlignment()->setHorizontal('right');
                } else {
                    $sheet->getStyle("N{$r}")->getAlignment()->setHorizontal('center');
                }

                // Status Badge coloring (Col H)
                $statusVal = $sheet->getCell("H{$r}")->getValue();
                if ($statusVal === 'Selesai') {
                    $sheet->getStyle("H{$r}")->applyFromArray(['font' => ['color' => ['rgb' => '15803D'], 'bold' => true]]);
                } elseif ($statusVal === 'Sedang Dikirim') {
                    $sheet->getStyle("H{$r}")->applyFromArray(['font' => ['color' => ['rgb' => '0DA4CE'], 'bold' => true]]);
                } elseif ($statusVal === 'Dilewati') {
                    $sheet->getStyle("H{$r}")->applyFromArray(['font' => ['color' => ['rgb' => 'DC2626'], 'bold' => true]]);
                }

                // Photo Embedding for this row
                $dataIndex = $r - $subHeaderRow;
                $rowPhotos = $this->photoRowMap[$dataIndex] ?? [];
                $hasAnyEmbeddedPhoto = false;

                // 1. Check In Photo (Col 11 / K)
                $checkInPath = $rowPhotos[11] ?? null;
                if ($checkInPath) {
                    try {
                        $hasPhoto = $this->addRowPhotos($sheet, $r, [['col' => 11, 'path' => $checkInPath]], 72);
                        if ($hasPhoto) {
                            $hasAnyEmbeddedPhoto = true;
                        } else {
                            $sheet->setCellValue("K{$r}", 'Tidak ada foto');
                            $sheet->getStyle("K{$r}")->applyFromArray(['font' => ['italic' => true, 'color' => ['rgb' => '94A3B8']]]);
                        }
                    } catch (\Throwable $e) {
                        $sheet->setCellValue("K{$r}", 'Tidak ada foto');
                    }
                } else {
                    $sheet->setCellValue("K{$r}", 'Tidak ada foto');
                    $sheet->getStyle("K{$r}")->applyFromArray(['font' => ['italic' => true, 'color' => ['rgb' => '94A3B8']]]);
                }

                // 2. Dynamic Checkout Photos (Col 18 .. 17+max)
                for ($i = 1; $i <= $this->maxCheckoutPhotos; $i++) {
                    $colIdx = 17 + $i;
                    $colLetter = Coordinate::stringFromColumnIndex($colIdx);
                    $docPath = $rowPhotos[$colIdx] ?? null;

                    if ($docPath) {
                        try {
                            $hasPhoto = $this->addRowPhotos($sheet, $r, [['col' => $colIdx, 'path' => $docPath]], 72);
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

                // 3. Skip Photo (Col 18+max)
                $skipColIdx = 18 + $this->maxCheckoutPhotos;
                $skipColLetter = Coordinate::stringFromColumnIndex($skipColIdx);
                $skipPath = $rowPhotos[$skipColIdx] ?? null;

                if ($skipPath) {
                    try {
                        $hasPhoto = $this->addRowPhotos($sheet, $r, [['col' => $skipColIdx, 'path' => $skipPath]], 72);
                        if ($hasPhoto) {
                            $hasAnyEmbeddedPhoto = true;
                        } else {
                            $sheet->setCellValue("{$skipColLetter}{$r}", 'Tidak ada foto');
                            $sheet->getStyle("{$skipColLetter}{$r}")->applyFromArray(['font' => ['italic' => true, 'color' => ['rgb' => '94A3B8']]]);
                        }
                    } catch (\Throwable $e) {
                        $sheet->setCellValue("{$skipColLetter}{$r}", 'Tidak ada foto');
                    }
                } else {
                    if ($statusVal === 'Dilewati') {
                        $sheet->setCellValue("{$skipColLetter}{$r}", 'Tidak ada foto');
                        $sheet->getStyle("{$skipColLetter}{$r}")->applyFromArray(['font' => ['italic' => true, 'color' => ['rgb' => '94A3B8']]]);
                    } else {
                        $sheet->setCellValue("{$skipColLetter}{$r}", '-');
                    }
                }

                if ($hasAnyEmbeddedPhoto) {
                    $sheet->getRowDimension($r)->setRowHeight(84);
                } else {
                    $sheet->getRowDimension($r)->setRowHeight(24);
                }
            }
        }

        // Freeze pane below sub-header
        $sheet->freezePane("A{$dataStart}");
        $sheet->setAutoFilter("A{$subHeaderRow}:{$lastColumn}{$subHeaderRow}");
        $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd($groupHeaderRow, $subHeaderRow);
        $sheet->setSelectedCell("A{$subHeaderRow}");

        // Logo
        $logoPath = public_path('assets/images/logo-isa-smartwork.png');
        if (file_exists($logoPath)) {
            try {
                $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
                $drawing->setName('Logo ISA SmartWork');
                $drawing->setDescription('Logo ISA SmartWork');
                $drawing->setPath($logoPath);
                $drawing->setHeight(62);
                $drawing->setOffsetX(4);
                $drawing->setOffsetY(2);
                $drawing->setCoordinates('A1');
                $drawing->setWorksheet($sheet);
            } catch (\Throwable $e) {
            }
        }

        // Print Setup
        $sheet->getPageSetup()->setFitToWidth(1);
        $sheet->getPageSetup()->setFitToHeight(0);
        $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
        $sheet->getPageSetup()->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);
        $sheet->getPageSetup()->setHorizontalCentered(true);
        $sheet->getPageSetup()->setFitToPage(true);
        $sheet->getPageSetup()->setPrintArea("A1:{$lastColumn}{$highestRow}");
        $sheet->getSheetView()->setZoomScale(85);
    }

    protected function landscape(): bool
    {
        return true;
    }
}

