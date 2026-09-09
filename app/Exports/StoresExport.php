<?php

namespace App\Exports;

use App\Models\Store;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class StoresExport extends BaseReportExport
{
    protected ?string $province;
    protected ?string $city;
    protected ?string $kecamatan;
    protected ?string $salesId;
    protected ?string $delivery;
    protected $items;

    public function __construct(array $params = [])
    {
        parent::__construct($params);

        $this->province = $params['province'] ?? null;
        $this->city = $params['city'] ?? null;
        $this->kecamatan = $params['kecamatan'] ?? null;
        $this->salesId = $params['salesId'] ?? null;
        $this->delivery = $params['delivery'] ?? null;
    }

    public function title(): string
    {
        return 'Laporan Master Toko';
    }

    protected function reportTitle(): string
    {
        return 'LAPORAN MASTER TOKO';
    }

    public function headings(): array
    {
        return [
            'No', 'Kode', 'Nama Toko', 'Pemilik', 'Sales Penanggung Jawab', 'Alamat Lengkap',
            'Kecamatan', 'Kabupaten/Kota', 'Provinsi', 'Latitude', 'Longitude', 'Google Maps', 'Status', 'Tanggal Dibuat',
        ];
    }

    public function dataRows(): array
    {
        $query = Store::withTrashed()->with('salesPenanggungJawab');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('owner', 'like', "%{$this->search}%")
                    ->orWhere('address', 'like', "%{$this->search}%")
                    ->orWhere('code', 'like', "%{$this->search}%");
            });
        }

        if ($this->province) {
            $query->where('province', $this->province);
        }

        if ($this->city) {
            $query->where('city', $this->city);
        }

        if ($this->kecamatan) {
            $query->where('kecamatan', $this->kecamatan);
        }

        if ($this->salesId === 'unassigned') {
            $query->whereNull('sales_penanggung_jawab_id');
        } elseif ($this->salesId) {
            $query->where('sales_penanggung_jawab_id', $this->salesId);
        }

        if ($this->delivery === 'yes') {
            $query->where('is_delivery_destination', true);
        } elseif ($this->delivery === 'no') {
            $query->where('is_delivery_destination', false);
        }

        if ($this->status) {
            if ($this->status === 'active') {
                $query->where('status', 'active')->whereNull('deleted_at');
            } elseif ($this->status === 'inactive') {
                $query->where(function ($q) {
                    $q->where('status', 'inactive')->orWhereNotNull('deleted_at');
                });
            }
        }

        $this->items = $query->orderBy('created_at', 'desc')->get();

        $rows = [];
        $no = 0;

        foreach ($this->items as $store) {
            $no++;
            $isStoreActive = ($store->status === 'active' && ! $store->trashed());

            $rows[] = [
                $no,
                $store->code ?? '-',
                $store->name ?? '-',
                $store->owner ?? '-',
                $store->salesPenanggungJawab?->name ?? 'Belum Ada',
                $store->address ?? '-',
                $store->kecamatan ?? '-',
                $store->city ?? '-',
                $store->province ?? '-',
                $store->latitude ?? '-',
                $store->longitude ?? '-',
                '',
                $isStoreActive ? 'Aktif' : 'Nonaktif',
                $store->created_at?->format('d/m/Y H:i') ?? '-',
            ];
        }

        return $rows;
    }

    public function registerEvents(): array
    {
        return $this->wrapAfterSheet(function (AfterSheet $event) {
            $sheet = $event->sheet->getDelegate();

            // Set lebar kolom Google Maps (Kolom L = 12)
            $sheet->getColumnDimension('L')->setWidth(20);

            $row = 9; // Baris awal data laporan
            foreach ($this->items ?? [] as $store) {
                if ($store->latitude !== null && $store->longitude !== null && is_numeric($store->latitude) && is_numeric($store->longitude)) {
                    $this->addMapLink($sheet, $row, 12, $store->latitude, $store->longitude, 'Buka Google Maps');
                } else {
                    $coord = Coordinate::stringFromColumnIndex(12) . $row;
                    $sheet->setCellValue($coord, '-');
                }
                $row++;
            }
        });
    }

    private function addMapLink($sheet, int $row, int $col, $lat, $lng, string $label): void
    {
        if ($lat === null || $lat === '' || $lng === null || $lng === '') {
            return;
        }

        $coord = Coordinate::stringFromColumnIndex($col) . $row;
        $sheet->setCellValue($coord, $label);
        $sheet->getCell($coord)->getHyperlink()->setUrl('https://www.google.com/maps?q=' . $lat . ',' . $lng);
        $sheet->getStyle($coord)->applyFromArray([
            'font' => [
                'color' => ['rgb' => '0560FC'],
                'underline' => 'single',
            ],
            'alignment' => ['horizontal' => 'center'],
        ]);
    }

    protected function landscape(): bool
    {
        return true;
    }
}
