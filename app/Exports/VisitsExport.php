<?php

namespace App\Exports;

use App\Exports\Sheets\FotoKunjunganSheet;
use App\Exports\Sheets\RekapKunjunganSheet;
use App\Exports\Sheets\TransaksiPiutangSheet;
use App\Models\RouteStop;
use App\Models\User;
use App\Models\Visit;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class VisitsExport implements WithMultipleSheets
{
    protected array $params;
    protected ?string $fromDate;
    protected ?string $toDate;
    protected ?string $userId;
    protected ?string $storeId;
    protected ?string $role;
    protected ?string $status;
    protected ?string $search;
    protected bool $withArchived = false;
    protected ?string $archiveId = null;

    protected $items;
    protected $skippedItems;

    public function __construct(array $params = [])
    {
        $this->params = $params;
        $this->fromDate = $params['fromDate'] ?? null;
        $this->toDate = $params['toDate'] ?? null;
        $this->userId = $params['userId'] ?? null;
        $this->storeId = $params['store_id'] ?? null;
        $this->role = $params['role'] ?? null;
        $this->status = $params['status'] ?? null;
        $this->search = $params['search'] ?? null;
        $this->withArchived = (bool) ($params['withArchived'] ?? false);
        $this->archiveId = $params['archiveId'] ?? null;
    }

    /**
     * Define the sheets for Sales Visit Export.
     * SHEET 1: Rekap Kunjungan (Ringkasan per visit)
     * SHEET 2: Transaksi & Piutang (Detail per transaksi & alokasi pembayaran piutang lama)
     * SHEET 3: Foto Kunjungan (Embed foto selfie, etalase, skip)
     */
    public function sheets(): array
    {
        $this->loadData();

        return [
            new RekapKunjunganSheet($this->params, $this->items, $this->skippedItems),
            new TransaksiPiutangSheet($this->params, $this->items),
            new FotoKunjunganSheet($this->params, $this->items, $this->skippedItems),
        ];
    }

    protected function loadData(): void
    {
        if ($this->items === null) {
            $visitQuery = $this->buildVisitQuery();
            $this->items = $visitQuery->get();
        }

        if ($this->skippedItems === null) {
            $skippedQuery = $this->buildSkippedQuery();
            $this->skippedItems = $skippedQuery->get();
        }
    }

    public function buildVisitQuery()
    {
        $relations = [
            'user.roles',
            'store',
            'route',
            'routeStop',
            'payments.transaction.payments',
            'transactions.payments',
            'photos',
            'checkoutPhotos',
        ];

        if ($this->withArchived) {
            $q = Visit::withoutGlobalScope('notArchived')
                ->with($relations)
                ->whereDoesntHave('routeStop', fn ($rs) => $rs->where('status', 'skipped'))
                ->when($this->userId, fn ($qq) => $qq->where('user_id', $this->userId))
                ->when($this->storeId, fn ($qq) => $qq->where('store_id', $this->storeId))
                ->when($this->search, function ($qq) {
                    $search = $this->search;
                    $qq->where(function ($sub) use ($search) {
                        $sub->whereHas('store', fn ($sq) => $sq->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%")->orWhere('address', 'like', "%{$search}%"))
                            ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$search}%"))
                            ->orWhereHas('route', fn ($rq) => $rq->where('name', 'like', "%{$search}%"));
                    });
                })
                ->when($this->status === 'completed', fn ($qq) => $qq->where('status', 'completed'))
                ->when($this->status === 'in_progress', fn ($qq) => $qq->where('status', 'in_progress'))
                ->orderBy('check_in_at', 'asc')
                ->orderBy('created_at', 'asc')
                ->orderBy('id', 'asc');

            if ($this->archiveId) {
                $q->where('data_archive_id', $this->archiveId);
            } else {
                $q->whereNotNull('archived_at')
                    ->whereBetween('check_in_at', [$this->fromDate . ' 00:00:00', $this->toDate . ' 23:59:59']);
            }

            return $q;
        }

        return Visit::with($relations)
            ->whereHas('user.roles', fn ($q) => $q->whereIn('name', ['sales', 'field-supervisor']))
            ->whereDoesntHave('routeStop', fn ($rs) => $rs->where('status', 'skipped'))
            ->whereBetween('check_in_at', [$this->fromDate . ' 00:00:00', $this->toDate . ' 23:59:59'])
            ->when($this->userId, fn ($qq) => $qq->where('user_id', $this->userId))
            ->when($this->storeId, fn ($qq) => $qq->where('store_id', $this->storeId))
            ->when($this->search, function ($qq) {
                $search = $this->search;
                $qq->where(function ($sub) use ($search) {
                    $sub->whereHas('store', fn ($sq) => $sq->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%")->orWhere('address', 'like', "%{$search}%"))
                        ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('route', fn ($rq) => $rq->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($this->status === 'completed', fn ($qq) => $qq->where('status', 'completed'))
            ->when($this->status === 'in_progress', fn ($qq) => $qq->where('status', 'in_progress'))
            ->orderBy('check_in_at', 'asc')
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc');
    }

    public function buildSkippedQuery()
    {
        $q = RouteStop::with(['store', 'route.user.roles', 'route', 'visit', 'anyVisit'])
            ->where('status', 'skipped')
            ->whereHas('route.user.roles', fn ($r) => $r->whereIn('name', ['sales', 'field-supervisor']))
            ->when($this->storeId, fn ($qq) => $qq->where('store_id', $this->storeId))
            ->when($this->search, function ($qq) {
                $search = $this->search;
                $qq->where(function ($sub) use ($search) {
                    $sub->whereHas('store', fn ($sq) => $sq->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%")->orWhere('address', 'like', "%{$search}%"))
                        ->orWhereHas('route.user', fn ($uq) => $uq->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('route', fn ($rq) => $rq->where('name', 'like', "%{$search}%"));
                });
            });

        if ($this->withArchived) {
            $q->whereHas('route', function ($qq) {
                $qq->withoutGlobalScope('notArchived');
                if ($this->archiveId) {
                    $qq->where('data_archive_id', $this->archiveId);
                } else {
                    $qq->whereNotNull('archived_at')
                        ->whereBetween('date', [$this->fromDate, $this->toDate]);
                }
                if ($this->userId) {
                    $qq->where('user_id', $this->userId);
                }
            });
        } else {
            $q->whereHas('route', function ($qq) {
                if ($this->fromDate && $this->toDate) {
                    $qq->whereDate('date', '>=', $this->fromDate)
                       ->whereDate('date', '<=', $this->toDate);
                } elseif ($this->fromDate) {
                    $qq->whereDate('date', '>=', $this->fromDate);
                } elseif ($this->toDate) {
                    $qq->whereDate('date', '<=', $this->toDate);
                }
                if ($this->userId) {
                    $qq->where('user_id', $this->userId);
                }
            });

            if (in_array($this->status, ['completed', 'in_progress'], true)) {
                return $q->whereRaw('1=0');
            }
            if ($this->status && $this->status !== 'skipped' && $this->status !== 'Dilewati') {
                return $q->whereRaw('1=0');
            }
        }

        return $q->orderBy('route_id')->orderBy('sequence');
    }

    /**
     * Backward compatibility proxies for tests calling headings() or dataRows() directly.
     */
    public function headings(): array
    {
        return (new RekapKunjunganSheet($this->params))->headings();
    }

    public function dataRows(): array
    {
        $this->loadData();
        return (new RekapKunjunganSheet($this->params, $this->items, $this->skippedItems))->dataRows();
    }
}
