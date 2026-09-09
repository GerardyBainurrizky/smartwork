<?php

namespace App\Exports;

use App\Exports\Sheets\TransaksiBaruDetailSheet;
use App\Exports\Sheets\TransaksiKunjunganDetailSheet;
use App\Exports\Sheets\TransaksiPiutangLamaSheet;
use App\Exports\Sheets\TransaksiRingkasanSheet;
use App\Models\Visit;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;

class TransactionsExport implements WithMultipleSheets, WithTitle
{
    protected array $params;
    protected ?string $fromDate;
    protected ?string $toDate;
    protected ?string $userId;
    protected ?string $storeId;
    protected ?string $role;
    protected ?string $status;
    protected ?string $search;
    protected ?string $transactionStatus;

    protected $visits;

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
        $this->transactionStatus = $params['transaction_status'] ?? null;
    }

    public function title(): string
    {
        return 'Laporan Transaksi Sales';
    }

    public function loadVisits()
    {
        if ($this->visits === null) {
            $withArchived = ! empty($this->params['withArchived']);
            $archiveId = $this->params['archiveId'] ?? null;

            if ($withArchived) {
                $query = Visit::withoutGlobalScope('notArchived')->with([
                    'user.roles',
                    'store',
                    'route',
                    'routeStop',
                    'photos',
                    'transactions' => fn ($t) => $t->withoutGlobalScope('notArchived')->with('payments'),
                    'payments' => fn ($p) => $p->withoutGlobalScope('notArchived')->with('transaction.payments'),
                ])
                ->whereHas('user.roles', fn ($q) => $q->whereIn('name', ['sales', 'field-supervisor']))
                ->where('status', 'completed');

                if ($archiveId) {
                    $query->where(function ($q) use ($archiveId) {
                        $q->where('data_archive_id', $archiveId)
                          ->orWhereHas('transactions', fn ($tq) => $tq->withoutGlobalScope('notArchived')->where('data_archive_id', $archiveId))
                          ->orWhereHas('payments', fn ($pq) => $pq->withoutGlobalScope('notArchived')->where('data_archive_id', $archiveId));
                    });
                } else {
                    $query->whereNotNull('archived_at')
                        ->when($this->fromDate && $this->toDate, function ($q) {
                            $q->whereBetween('check_in_at', [$this->fromDate . ' 00:00:00', $this->toDate . ' 23:59:59']);
                        });
                }
            } else {
                $query = Visit::with([
                    'user.roles',
                    'store',
                    'route',
                    'routeStop',
                    'photos',
                    'transactions.payments',
                    'payments.transaction.payments',
                ])
                ->whereHas('user.roles', fn ($q) => $q->whereIn('name', ['sales', 'field-supervisor']))
                ->when($this->fromDate && $this->toDate, function ($q) {
                    $q->whereBetween('check_in_at', [$this->fromDate . ' 00:00:00', $this->toDate . ' 23:59:59']);
                })
                ->where('status', 'completed');
            }

            if ($this->userId) {
                $query->where('user_id', $this->userId);
            }

            if ($this->storeId) {
                $query->where('store_id', $this->storeId);
            }

            if ($this->search) {
                $search = $this->search;
                $query->where(function ($q) use ($search) {
                    $q->whereHas('store', function ($sq) use ($search) {
                        $sq->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%")
                            ->orWhere('address', 'like', "%{$search}%");
                    })
                    ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('transactions', fn ($tq) => $tq->where('transaction_code', 'like', "%{$search}%"));
                });
            }

            if ($this->transactionStatus) {
                $st = $this->transactionStatus;
                if ($st === 'has_transaction') {
                    $query->where(function ($q) {
                        $q->whereHas('transactions')
                            ->orWhere(function ($qq) {
                                $qq->where('transaction_amount', '>', 0)
                                   ->whereIn('transaction_status', ['paid', 'mixed']);
                            });
                    });
                } elseif ($st === 'has_old_debt_payment') {
                    $query->where(function ($q) {
                        $q->whereHas('payments', fn ($pq) => $pq->where('source', '!=', 'initial_payment')->where('notes', 'not like', '%Pembayaran awal%'))
                            ->orWhere(function ($qq) {
                                $qq->whereIn('transaction_status', ['piutang', 'mixed'])
                                   ->where('cash_received', '>', 0);
                            });
                    });
                } else {
                    $query->where('transaction_status', $st);
                }
            }

            $this->visits = $query->orderBy('check_in_at', 'desc')->get();
        }

        return $this->visits;
    }

    public function sheets(): array
    {
        $this->loadVisits();

        return [
            new TransaksiRingkasanSheet($this->params, $this->visits),
            new TransaksiPiutangLamaSheet($this->params, $this->visits),
            new TransaksiBaruDetailSheet($this->params, $this->visits),
            new TransaksiKunjunganDetailSheet($this->params, $this->visits),
        ];
    }

    public function headings(): array
    {
        $this->loadVisits();

        return (new TransaksiRingkasanSheet($this->params, $this->visits))->headings();
    }

    public function dataRows(): array
    {
        $this->loadVisits();

        return (new TransaksiRingkasanSheet($this->params, $this->visits))->dataRows();
    }
}
