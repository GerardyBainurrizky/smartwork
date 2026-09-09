<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use Carbon\Carbon;

abstract class BaseReportExport implements FromArray, WithTitle, ShouldAutoSize, WithEvents
{
    protected string $companyName = 'PT ISA TRI SELARAS GEMILANG';
    protected string $companySubtitle = 'ISA SmartWork';

    protected array $params = [];
    protected ?string $fromDate;
    protected ?string $toDate;
    protected ?string $userId;
    protected ?string $role;
    protected ?string $status;
    protected ?string $search;
    protected bool $withArchived = false;
    protected ?string $archiveId = null;

    public function __construct(array $params = [])
    {
        $this->params = $params;
        $this->fromDate = $params['fromDate'] ?? null;
        $this->toDate = $params['toDate'] ?? null;
        $this->userId = $params['userId'] ?? null;
        $this->role = $params['role'] ?? null;
        $this->status = $params['status'] ?? null;
        $this->search = $params['search'] ?? null;
        $this->withArchived = (bool) ($params['withArchived'] ?? false);
        $this->archiveId = $params['archiveId'] ?? null;
    }

    abstract public function headings(): array;

    abstract public function dataRows(): array;

    abstract protected function reportTitle(): string;

    protected function landscape(): bool
    {
        return false;
    }

    public function title(): string
    {
        return $this->reportTitle();
    }

    protected function periodLabel(): string
    {
        if ($this->fromDate && $this->toDate) {
            $from = Carbon::parse($this->fromDate)->locale('id')->translatedFormat('d F Y');
            $to   = Carbon::parse($this->toDate)->locale('id')->translatedFormat('d F Y');

            return $from === $to ? $from : $from . ' - ' . $to;
        }

        return 'Semua Periode';
    }

    protected function extraMetaRows(): array
    {
        return [];
    }

    protected function emptyMessage(): ?string
    {
        return null;
    }

    protected function headingRow(): int
    {
        return 8 + count($this->extraMetaRows());
    }

    public function array(): array
    {
        $dataRows = $this->dataRows();

        $rows = [];
        $rows[] = ['', $this->companyName];
        $rows[] = ['', $this->companySubtitle];
        $rows[] = [$this->reportTitle()];
        $rows[] = ['Periode: ' . $this->periodLabel()];
        foreach ($this->extraMetaRows() as $meta) {
            $rows[] = [$meta];
        }
        $rows[] = ['Tanggal Cetak: ' . now()->locale('id')->translatedFormat('d F Y, H:i') . ' WIB'];
        $rows[] = ['Dicetak Oleh: ' . (auth()->check() ? auth()->user()->name : 'Administrator')];
        $rows[] = ['Jumlah Data: ' . count($dataRows) . ' data'];

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

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => fn (AfterSheet $event) => $this->afterSheet($event),
        ];
    }

    protected function afterSheet(AfterSheet $event): void
    {
        $sheet = $event->sheet->getDelegate();
        $columnCount = count($this->headings());
        $lastColumn = Coordinate::stringFromColumnIndex($columnCount);
        $highestRow = $sheet->getHighestRow();
        $headingRow = $this->headingRow();
        $metaEnd = $headingRow - 1;
        $dataStart = $headingRow + 1;

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

        $sheet->getRowDimension($headingRow)->setRowHeight(22);
        $sheet->getStyle("A{$headingRow}:{$lastColumn}{$headingRow}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '0DA4CE']],
            'alignment' => ['horizontal' => 'center', 'vertical' => 'center', 'wrapText' => true],
            'borders' => [
                'allBorders' => ['borderStyle' => 'thin', 'color' => ['rgb' => '0B7494']],
            ],
        ]);

        if ($dataStart <= $highestRow) {
            $sheet->getStyle("A{$dataStart}:{$lastColumn}{$highestRow}")->applyFromArray([
                'borders' => [
                    'allBorders' => ['borderStyle' => 'thin', 'color' => ['rgb' => 'CBD5E1']],
                ],
                'alignment' => ['vertical' => 'center'],
            ]);
            $sheet->getStyle("A{$dataStart}:{$lastColumn}{$highestRow}")
                ->getAlignment()->setWrapText(true);
            for ($r = $dataStart; $r <= $highestRow; $r++) {
                if ($sheet->getRowDimension($r)->getRowHeight() == -1) {
                    $sheet->getRowDimension($r)->setRowHeight(18);
                }
            }
        }

        $sheet->freezePane("A{$dataStart}");
        $sheet->setAutoFilter("A{$headingRow}:{$lastColumn}{$headingRow}");
        $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd($headingRow, $headingRow);
        $sheet->setSelectedCell("A{$headingRow}");

        $sheet->getStyle("A{$headingRow}:{$lastColumn}{$highestRow}")->getAlignment()->setVertical('center');
        $sheet->getDefaultRowDimension()->setRowHeight(18);

        $logoPath = public_path('assets/images/logo-isa-smartwork.png');
        if (file_exists($logoPath)) {
            try {
                $drawing = new Drawing();
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

        $sheet->getPageSetup()->setFitToWidth(1);
        $sheet->getPageSetup()->setFitToHeight(0);
        $sheet->getPageSetup()->setOrientation($this->landscape()
            ? \PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE
            : \PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_PORTRAIT);
        $sheet->getPageSetup()->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);
        $sheet->getPageSetup()->setHorizontalCentered(true);
        $sheet->getPageSetup()->setFitToPage(true);
        $sheet->getPageSetup()->setPrintArea("A1:{$lastColumn}{$highestRow}");
        $sheet->getPageMargins()->setTop(0.4);
        $sheet->getPageMargins()->setBottom(0.4);
        $sheet->getPageMargins()->setLeft(0.3);
        $sheet->getPageMargins()->setRight(0.3);
        $sheet->getPageMargins()->setHeader(0.2);
        $sheet->getPageMargins()->setFooter(0.2);
        $sheet->getHeaderFooter()->setOddHeader('&C&"Calibri,Bold"&10 PT ISA TRI SELARAS GEMILANG - ISA SmartWork');
        $sheet->getHeaderFooter()->setOddFooter('&L&D &T &C Halaman &P dari &N &R Dicetak oleh ISA SmartWork');
        $sheet->getSheetView()->setZoomScale(90);
    }

    /**
     * Wrap the base AfterSheet styling so child exports can inject
     * extra behaviour (e.g. embedding photos) without duplicating layout.
     */
    protected function wrapAfterSheet(callable $extra): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) use ($extra) {
                $this->afterSheet($event);
                $extra($event);
            },
        ];
    }

    protected function addRowPhotos(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, int $row, array $photos, int $height = 64): bool
    {
        $hasPhoto = false;

        foreach ($photos as $photo) {
            $path = $photo['path'] ?? null;

            if (empty($path)) {
                continue;
            }

            $full = \Illuminate\Support\Facades\Storage::disk('public')->path($path);
            if (! file_exists($full)) {
                $full = storage_path('app/public/' . $path);
            }

            if (! file_exists($full)) {
                continue;
            }

            $drawing = new Drawing();
            $drawing->setName('Foto');
            $drawing->setPath($full);
            $drawing->setHeight($height);
            $drawing->setOffsetX(2);
            $drawing->setOffsetY(2);
            $colLetter = Coordinate::stringFromColumnIndex((int) $photo['col']);
            $drawing->setCoordinates($colLetter . $row);
            $drawing->setWorksheet($sheet);

            $hasPhoto = true;
        }

        return $hasPhoto;
    }
}
