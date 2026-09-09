<?php

namespace App\Exports\Sheets;

use App\Exports\BaseReportExport;
use App\Models\RouteStop;
use App\Models\StoreTransaction;
use App\Models\StoreTransactionPayment;
use App\Models\User;
use App\Services\StoreReceivableService;
use Carbon\Carbon;
use Maatwebsite\Excel\Events\AfterSheet;

class SisaPiutangTokoSheet extends BaseReportExport
{
    protected $visits;
    protected $skippedStops;

    public function __construct(array $params = [], $visits = null, $skippedStops = null)
    {
        parent::__construct($params);
        $this->visits = $visits ?? collect();
        $this->skippedStops = $skippedStops ?? collect();
    }

    public function title(): string
    {
        return 'Sisa Piutang Toko';
    }

    protected function reportTitle(): string
    {
        return 'REKAPITULASI SISA PIUTANG TOKO HASIL KUNJUNGAN SALES';
    }

    protected function emptyMessage(): ?string
    {
        return 'Tidak ada data piutang toko pada periode yang dipilih.';
    }

    public function headings(): array
    {
        return [
            'Visit ID',
            'Tanggal Kunjungan',
            'Toko',
            'Kode Toko',
            'Total Piutang Sebelum Kunjungan',
            'Pembayaran Piutang Lama Saat Kunjungan',
            'Transaksi Baru',
            'Pembayaran Awal Transaksi Baru',
            'Sisa Piutang Toko Setelah Kunjungan',
        ];
    }

    public function dataRows(): array
    {
        $rows = [];

        foreach ($this->visits as $visit) {
            $storeId = $visit->store_id;

            // Financial summary via centralized StoreReceivableService ledger
            $recSummary = StoreReceivableService::getVisitReceivableSummary($visit);
            $oldDebtPaid = $recSummary['old_debt_paid'];
            $newTxTotal = $recSummary['new_tx_total'];
            $newInitialPaid = $recSummary['new_tx_initial_paid'];
            $storeBalanceBefore = $recSummary['balance_before'];
            $storeBalanceAfter = $recSummary['balance_after'];

            $rows[] = [
                (string) $visit->id,
                $visit->check_in_at?->format('d/m/Y') ?? '-',
                $visit->store->name ?? '-',
                $visit->store->code ?? '-',
                $storeBalanceBefore,
                $oldDebtPaid,
                $newTxTotal,
                $newInitialPaid,
                $storeBalanceAfter,
            ];
        }

        foreach ($this->skippedStops as $stop) {
            $storeId = $stop->store_id;
            $storeBalance = $storeId ? (float) StoreReceivableService::balanceForStore($storeId) : 0.0;
            $visitId = $stop->visit ? (string) $stop->visit->id : ($stop->anyVisit ? (string) $stop->anyVisit->id : '-');

            $rows[] = [
                $visitId,
                $stop->route->date ? Carbon::parse($stop->route->date)->format('d/m/Y') : '-',
                $stop->store->name ?? '-',
                $stop->store->code ?? '-',
                $storeBalance,
                0.0,
                0.0,
                0.0,
                $storeBalance,
            ];
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

            if ($dataStart <= $highestRow && $sheet->getCell("A{$dataStart}")->getValue() !== $this->emptyMessage()) {
                // Currency format columns E to I (5, 6, 7, 8, 9)
                $sheet->getStyle("E{$dataStart}:I{$highestRow}")
                    ->getNumberFormat()
                    ->setFormatCode('"Rp "#,##0;[Red]("-Rp "#,##0);"-"');

                for ($r = $dataStart; $r <= $highestRow; $r++) {
                    $sheet->getStyle("A{$r}:I{$r}")->getAlignment()->setWrapText(true)->setVertical('center');

                    // Center: Visit ID (A), Tanggal Kunjungan (B)
                    $sheet->getStyle("A{$r}:B{$r}")->getAlignment()->setHorizontal('center');

                    // Left: Toko (C), Kode Toko (D)
                    $sheet->getStyle("C{$r}:D{$r}")->getAlignment()->setHorizontal('left');

                    // Right: Nominal Numeric (E, F, G, H, I)
                    $sheet->getStyle("E{$r}:I{$r}")->getAlignment()->setHorizontal('right');
                }

                $sheet->getStyle("A{$dataStart}:I{$highestRow}")->applyFromArray([
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
