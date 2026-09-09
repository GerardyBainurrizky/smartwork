<?php

namespace App\Exports\Sheets;

use App\Exports\BaseReportExport;
use App\Models\RouteStop;
use App\Models\User;
use App\Models\Visit;
use App\Services\StoreReceivableService;
use Carbon\Carbon;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class RekapKunjunganSheet extends BaseReportExport
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
        return 'Rekap Kunjungan';
    }

    protected function reportTitle(): string
    {
        return 'REKAP KUNJUNGAN SALES';
    }

    public function headings(): array
    {
        return [
            'No',
            'Tanggal Kunjungan',
            'Sales',
            'Toko',
            'Kode Toko',
            'Urutan Kunjungan',
            'Estimasi Durasi (menit)',
            'Catatan Rencana Kunjungan',
            'Status Kunjungan',
            'Waktu Check In',
            'Lokasi Check In',
            'Waktu Check Out',
            'Lokasi Check Out',
            'Hasil Kunjungan',
            'Catatan Tambahan',
            'Saldo Piutang Lama Sebelum Kunjungan (Rp)',
            'Pembayaran Piutang Lama pada Kunjungan (Rp)',
            'Nilai Transaksi Baru pada Kunjungan (Rp)',
            'Pembayaran atas Transaksi Baru pada Kunjungan (Rp)',
            'Piutang Baru dari Transaksi Kunjungan (Rp)',
            'Saldo Piutang Toko Setelah Kunjungan (Rp)',
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
        $no = 0;

        foreach ($allItems as $entry) {
            $no++;
            if ($entry['type'] === 'visit') {
                $visit = $entry['item'];
                $sequence = $visit->routeStop->sequence ?? ($visit->route_stop_id ? ($visit->routeStop->sequence ?? '-') : '-');
                if ($sequence === null) {
                    $sequence = '-';
                }

                $estMinutes = $visit->routeStop?->estimated_duration_minutes;
                $estDurationText = $estMinutes !== null ? "{$estMinutes} menit" : '-';
                $routeStopNote = $visit->routeStop?->notes ?: '-';

                // Financial summary via centralized StoreReceivableService ledger
                $recSummary = StoreReceivableService::getVisitReceivableSummary($visit);
                $oldDebtPaid = $recSummary['old_debt_paid'];
                $newTxTotal = $recSummary['new_tx_total'];
                $newTxInitialPaid = $recSummary['new_tx_initial_paid'];
                $newTxRemaining = $recSummary['new_tx_remaining'];
                $storeBalanceAfter = $recSummary['balance_after'];
                $storeBalanceBefore = $recSummary['balance_before'];

                $statusLabel = match ($visit->status) {
                    'completed' => 'Selesai',
                    'in_progress' => 'Sedang Berjalan',
                    'skipped' => 'Dilewati',
                    default => ucfirst((string) $visit->status),
                };

                $checkInLoc = $visit->check_in_address ?: ($visit->check_in_lat && $visit->check_in_lng ? "{$visit->check_in_lat}, {$visit->check_in_lng}" : '-');
                $checkOutLoc = $visit->check_out_at ? ($visit->check_out_address ?: ($visit->check_out_lat && $visit->check_out_lng ? "{$visit->check_out_lat}, {$visit->check_out_lng}" : '-')) : '-';

                $rows[] = [
                    $no,
                    $visit->check_in_at ? $visit->check_in_at->format('d M Y') : '-',
                    $visit->user->name ?? '-',
                    $visit->store->name ?? '-',
                    $visit->store->code ?? '-',
                    $sequence,
                    $estDurationText,
                    $routeStopNote,
                    $statusLabel,
                    $visit->check_in_at ? $visit->check_in_at->format('H:i') : '-',
                    $checkInLoc,
                    $visit->check_out_at ? $visit->check_out_at->format('H:i') : '-',
                    $checkOutLoc,
                    $visit->visit_result ?: '-',
                    $visit->final_notes ?: '-',
                    $storeBalanceBefore,
                    $oldDebtPaid,
                    $newTxTotal,
                    $newTxInitialPaid,
                    $newTxRemaining,
                    $storeBalanceAfter,
                ];
            } else {
                $stop = $entry['item'];
                $userObj = $stop->route->user ?? ($this->userId ? User::find($this->userId) : null);

                $reason = $stop->notes ?? '-';
                $skipAddress = '-';
                if (! empty($stop->notes) && str_starts_with($stop->notes, '{')) {
                    $meta = json_decode($stop->notes, true);
                    if (is_array($meta)) {
                        $reason = $meta['reason'] ?? $reason;
                        $skipAddress = $meta['address'] ?? (! empty($meta['latitude']) && ! empty($meta['longitude']) ? "{$meta['latitude']}, {$meta['longitude']}" : '-');
                    }
                }

                $estMinutes = $stop->estimated_duration_minutes;
                $estDurationText = $estMinutes !== null ? "{$estMinutes} menit" : '-';
                $stopNoteText = $reason;

                $stopDate = $stop->route?->date;
                $storeBalance = $stop->store_id ? StoreReceivableService::historicalBalanceForStore($stop->store_id, $stopDate) : 0.0;

                $rows[] = [
                    $no,
                    $stop->route->date ? Carbon::parse($stop->route->date)->format('d M Y') : '-',
                    $userObj->name ?? '-',
                    $stop->store->name ?? '-',
                    $stop->store->code ?? '-',
                    $stop->sequence ?? '-',
                    $estDurationText,
                    $stopNoteText,
                    'Dilewati',
                    '-',
                    $skipAddress,
                    '-',
                    '-',
                    $reason,
                    '-',
                    $storeBalance,
                    0.0,
                    0.0,
                    0.0,
                    0.0,
                    $storeBalance,
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

            // Set Column Widths
            $sheet->getColumnDimension('A')->setWidth(7);
            $sheet->getColumnDimension('B')->setWidth(16);
            $sheet->getColumnDimension('C')->setWidth(20);
            $sheet->getColumnDimension('D')->setWidth(26);
            $sheet->getColumnDimension('E')->setWidth(14);
            $sheet->getColumnDimension('F')->setWidth(10);
            $sheet->getColumnDimension('G')->setWidth(18); // Estimasi Durasi (menit)
            $sheet->getColumnDimension('H')->setWidth(34); // Catatan Rencana Kunjungan
            $sheet->getColumnDimension('I')->setWidth(16); // Status Kunjungan
            $sheet->getColumnDimension('J')->setWidth(15);
            $sheet->getColumnDimension('K')->setWidth(28);
            $sheet->getColumnDimension('L')->setWidth(15);
            $sheet->getColumnDimension('M')->setWidth(28);
            $sheet->getColumnDimension('N')->setWidth(32); // Hasil Kunjungan
            $sheet->getColumnDimension('O')->setWidth(28); // Catatan Tambahan
            $sheet->getColumnDimension('P')->setWidth(22);
            $sheet->getColumnDimension('Q')->setWidth(22);
            $sheet->getColumnDimension('R')->setWidth(22);
            $sheet->getColumnDimension('S')->setWidth(22);
            $sheet->getColumnDimension('T')->setWidth(22);
            $sheet->getColumnDimension('U')->setWidth(22);

            if ($dataStart <= $highestRow && $sheet->getCell("A{$dataStart}")->getValue() !== $this->emptyMessage()) {
                // Format currency numeric columns P, Q, R, S, T, U (16 to 21)
                $sheet->getStyle("P{$dataStart}:U{$highestRow}")
                    ->getNumberFormat()
                    ->setFormatCode('"Rp "#,##0;[Red]("-Rp "#,##0);"-"');

                for ($r = $dataStart; $r <= $highestRow; $r++) {
                    $sheet->getStyle("A{$r}:U{$r}")->getAlignment()->setWrapText(true)->setVertical('center');

                    // Center: No (A), Tanggal (B), Urutan (F), Estimasi Durasi (G), Status (I), Check In (J), Check Out (L)
                    $sheet->getStyle("A{$r}:B{$r}")->getAlignment()->setHorizontal('center');
                    $sheet->getStyle("F{$r}:G{$r}")->getAlignment()->setHorizontal('center');
                    $sheet->getStyle("I{$r}:J{$r}")->getAlignment()->setHorizontal('center');
                    $sheet->getStyle("L{$r}")->getAlignment()->setHorizontal('center');

                    // Left: Sales (C), Toko (D), Kode Toko (E), Lokasi Check In (K), Lokasi Check Out (M)
                    $sheet->getStyle("C{$r}:E{$r}")->getAlignment()->setHorizontal('left');
                    $sheet->getStyle("K{$r}")->getAlignment()->setHorizontal('left');
                    $sheet->getStyle("M{$r}")->getAlignment()->setHorizontal('left');

                    // Multiline Left Top: Catatan Rencana (H), Hasil Kunjungan (N), Catatan Tambahan (O)
                    $sheet->getStyle("H{$r}")->getAlignment()->setVertical('top')->setHorizontal('left');
                    $sheet->getStyle("N{$r}:O{$r}")->getAlignment()->setVertical('top')->setHorizontal('left');

                    // Right: Nominal Numeric (P to U)
                    $sheet->getStyle("P{$r}:U{$r}")->getAlignment()->setHorizontal('right');

                    $statusVal = $sheet->getCell("I{$r}")->getValue();
                    if ($statusVal === 'Selesai') {
                        $sheet->getStyle("I{$r}")->applyFromArray(['font' => ['color' => ['rgb' => '15803D'], 'bold' => true]]);
                    } elseif ($statusVal === 'Dilewati') {
                        $sheet->getStyle("I{$r}")->applyFromArray(['font' => ['color' => ['rgb' => '92400E'], 'bold' => true]]);
                    } elseif ($statusVal === 'Sedang Berjalan') {
                        $sheet->getStyle("I{$r}")->applyFromArray(['font' => ['color' => ['rgb' => '0DA4CE'], 'bold' => true]]);
                    }
                }

                $sheet->getStyle("A{$dataStart}:U{$highestRow}")->applyFromArray([
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
