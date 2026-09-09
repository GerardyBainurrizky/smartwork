<?php

namespace App\Exports;

use App\Exports\Sheets\DokumentasiPengirimanDriverSheet;
use App\Exports\Sheets\RekapPengirimanDriverSheet;
use App\Models\Visit;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class DriverVisitsExport implements WithMultipleSheets
{
    protected array $params;
    protected ?string $fromDate;
    protected ?string $toDate;
    protected ?string $userId;
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
        $this->role = $params['role'] ?? null;
        $this->status = $params['status'] ?? null;
        $this->search = $params['search'] ?? null;
        $this->withArchived = (bool) ($params['withArchived'] ?? false);
        $this->archiveId = $params['archiveId'] ?? null;
    }

    /**
     * Define the sheets for Driver Delivery Export.
     * SHEET 1: Rekap Pengiriman (1 baris = 1 pengiriman)
     * SHEET 2: Dokumentasi Pengiriman (1 baris = 1 foto dokumentasi dinamis)
     */
    public function sheets(): array
    {
        $this->loadData();

        return [
            new RekapPengirimanDriverSheet($this->params, $this->items, $this->skippedItems),
            new DokumentasiPengirimanDriverSheet($this->params, $this->items, $this->skippedItems),
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
            'photos',
            'checkoutPhotos',
        ];

        $q = Visit::with($relations)
            ->whereHas('user.roles', fn ($r) => $r->where('name', 'driver'))
            ->whereDoesntHave('routeStop', fn ($rs) => $rs->where('status', 'skipped'))
            ->orderBy('check_in_at', 'asc');

        if ($this->fromDate) {
            $q->whereDate('check_in_at', '>=', $this->fromDate);
        }
        if ($this->toDate) {
            $q->whereDate('check_in_at', '<=', $this->toDate);
        }
        if ($this->userId) {
            $q->where('user_id', $this->userId);
        }
        if ($this->status && in_array($this->status, ['completed', 'in_progress'], true)) {
            $q->where('status', $this->status);
        }

        if ($this->withArchived) {
            $q->withoutGlobalScope('notArchived');
            if ($this->archiveId) {
                $q->where('data_archive_id', $this->archiveId);
            } else {
                $q->whereNotNull('archived_at');
            }
        }

        return $q;
    }

    public function buildSkippedQuery()
    {
        $q = \App\Models\RouteStop::with(['store', 'route.user.roles', 'route', 'visit', 'anyVisit'])
            ->where('status', 'skipped')
            ->whereHas('route.user.roles', fn ($r) => $r->where('name', 'driver'));

        if ($this->withArchived) {
            $q->whereHas('route', function ($qq) {
                $qq->withoutGlobalScope('notArchived');
                if ($this->archiveId) {
                    $qq->where('data_archive_id', $this->archiveId);
                } else {
                    $qq->whereNotNull('archived_at');
                }
                if ($this->fromDate && $this->toDate) {
                    $qq->whereBetween('date', [$this->fromDate, $this->toDate]);
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

            if ($this->status && $this->status !== 'skipped' && $this->status !== 'Dilewati') {
                $q->whereRaw('1 = 0');
            }
        }

        return $q;
    }
}
