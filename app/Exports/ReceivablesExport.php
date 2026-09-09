<?php

namespace App\Exports;

use App\Models\Store;
use App\Models\StoreTransaction;
use App\Services\StoreReceivableService;

class ReceivablesExport extends BaseReportExport
{
    protected $stores;

    public function __construct($params = [])
    {
        if ($params instanceof \Illuminate\Support\Collection || (is_array($params) && ! empty($params) && ! isset($params['archiveId']) && ! isset($params['fromDate']) && ! isset($params['withArchived']) && isset($params[0]))) {
            parent::__construct([]);
            $this->stores = $params;
        } else {
            parent::__construct(is_array($params) ? $params : []);
            $this->stores = null;
        }
    }

    public function title(): string
    {
        return 'Laporan Piutang';
    }

    protected function reportTitle(): string
    {
        return 'LAPORAN PIUTANG';
    }

    public function headings(): array
    {
        return ['No', 'Kode Toko', 'Nama Toko', 'Sales Penanggung Jawab', 'Kota', 'Saldo Piutang (Rp)', 'Status'];
    }

    public function dataRows(): array
    {
        if ($this->stores === null) {
            $withArchived = ! empty($this->params['withArchived']);
            $archiveId = $this->params['archiveId'] ?? null;

            if ($withArchived && $archiveId) {
                $stores = Store::whereHas('transactions', function ($t) use ($archiveId) {
                    $t->withoutGlobalScope('notArchived')->where('data_archive_id', $archiveId);
                })->with(['salesPenanggungJawab:id,name', 'transactions' => fn ($t) => $t->withoutGlobalScope('notArchived')->with('payments')])->get();

                foreach ($stores as $st) {
                    $archivedTrx = $st->transactions->filter(fn ($t) => $t->data_archive_id === $archiveId);
                    $totalTrx = (float) $archivedTrx->sum('transaction_amount');
                    $totalPaid = 0.0;
                    foreach ($archivedTrx as $atx) {
                        $totalPaid += (float) ($atx->payments ? $atx->payments->sum('amount') : 0);
                    }
                    $st->receivable_balance = max(0.0, round($totalTrx - $totalPaid, 2));
                }
                $this->stores = $stores;
            } else {
                $stores = Store::where('status', 'active')
                    ->when($this->params['sales_id'] ?? null, fn ($q, $sid) => $q->where('sales_penanggung_jawab_id', $sid))
                    ->when($this->params['city'] ?? null, fn ($q, $city) => $q->where('city', $city))
                    ->with(['salesPenanggungJawab:id,name', 'transactions.payments'])
                    ->orderBy('name')
                    ->get();

                foreach ($stores as $st) {
                    $st->receivable_balance = (float) StoreReceivableService::balanceForStore($st->id);
                }

                $recStatus = $this->params['receivable_status'] ?? 'all';
                if ($recStatus === 'with') {
                    $stores = $stores->filter(fn ($s) => $s->receivable_balance > 0.005)->values();
                } elseif ($recStatus === 'without') {
                    $stores = $stores->filter(fn ($s) => $s->receivable_balance <= 0.005)->values();
                }

                $this->stores = $stores;
            }
        }

        $rows = [];
        $no = 0;
        foreach ($this->stores as $st) {
            $no++;
            $bal = (float) ($st->receivable_balance ?? 0);
            $rows[] = [
                $no,
                $st->code ?? '-',
                $st->name ?? '-',
                $st->salesPenanggungJawab?->name ?? '-',
                $st->city ?? '-',
                $bal,
                $bal > 0.005 ? 'Berpiutang' : 'Lunas',
            ];
        }
        return $rows;
    }
}

