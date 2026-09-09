@include('reports.pdf.partials.styles')

@php
    $selectedUserLabel = $selectedUserLabel ?? 'Semua Driver';
    $statusFilterLabel = $statusFilterLabel ?? 'Semua Status';
    $selectedStoreLabel = $selectedStoreLabel ?? null;
    $dataCount = $items->count();
    $statusBadge = [
        'completed' => 'badge-green',
        'active' => 'badge-cyan',
        'draft' => 'badge-gray',
        'cancelled' => 'badge-red',
    ];
    $statusLabel = [
        'completed' => 'Selesai',
        'active' => 'Sedang Berjalan',
        'draft' => 'Menunggu',
        'cancelled' => 'Dibatalkan',
    ];
@endphp

@include('reports.pdf.partials.header')

{{-- Extended metadata for Driver Routes --}}
<table class="report-meta" style="margin-top: -6px; margin-bottom: 14px;">
    <tr>
        <td class="k">Driver</td>
        <td class="v">{{ $selectedUserLabel }}</td>
        <td class="k">Status Rute</td>
        <td class="v">{{ $statusFilterLabel }}</td>
    </tr>
    @if($selectedStoreLabel)
    <tr>
        <td class="k">Tujuan / Toko</td>
        <td class="v" colspan="3">{{ $selectedStoreLabel }}</td>
    </tr>
    @endif
</table>

@foreach($items as $index => $route)
    @php
        $badge = $statusBadge[$route->computed_status] ?? 'badge-gray';
        $label = $statusLabel[$route->computed_status] ?? $route->computed_status;

        $actual = '-';
        if ($route->started_at && $route->completed_at) {
            $minutes = max(0, (int) $route->started_at->diffInMinutes($route->completed_at));
            $actual = $minutes >= 60
                ? (intdiv($minutes, 60) . ' Jam ' . ($minutes % 60) . ' Menit')
                : $minutes . ' Menit';
        }
    @endphp
    <div class="section">
        <div class="section-title">
            Rencana Pengiriman #{{ $index + 1 }} &mdash; {{ $route->name }}
            &nbsp;<span class="badge {{ $badge }}">{{ $label }}</span>
        </div>
        <div class="section-body">
            <table class="detail">
                <tr>
                    <td class="k">Nama Driver</td>
                    <td class="v">{{ $route->user->name ?? '-' }}</td>
                    <td class="k">Tanggal Pengiriman</td>
                    <td class="v">{{ $route->date->format('d M Y') }}</td>
                </tr>
                <tr>
                    <td class="k">Rute Pengiriman</td>
                    <td class="v">{{ $route->name }}</td>
                    <td class="k">Status Pengiriman</td>
                    <td class="v">{{ $label }}</td>
                </tr>
                <tr>
                    <td class="k">Jumlah Tujuan</td>
                    <td class="v">{{ $route->stops->count() }} tujuan</td>
                    <td class="k">Tujuan Dikunjungi</td>
                    <td class="v">{{ $route->stops->where('status', 'visited')->count() }} tujuan</td>
                </tr>
                <tr>
                    <td class="k">Mulai / Selesai</td>
                    <td class="v">{{ $route->started_at?->format('H:i') ?? '-' }} / {{ $route->completed_at?->format('H:i') ?? '-' }}</td>
                    <td class="k">Durasi Aktual</td>
                    <td class="v">{{ $actual }}</td>
                </tr>
                <tr>
                    <td class="k">Dibuat Oleh</td>
                    <td class="v" colspan="3">{{ $route->creator->name ?? ($route->created_by_label ?? '-') }}</td>
                </tr>
                <tr>
                    <td class="k">Catatan Rencana</td>
                    <td class="v" colspan="3">{{ $route->notes ?: '-' }}</td>
                </tr>
            </table>

            <table class="data" style="margin-top:8px;">
                <thead>
                    <tr>
                        <th style="width:4%; text-align:center;">No</th>
                        <th style="width:23%;">Tujuan Pengiriman</th>
                        <th style="width:28%;">Alamat Tujuan</th>
                        <th style="width:13%; text-align:center;">Status</th>
                        <th style="width:22%;">Catatan</th>
                        <th style="width:10%; text-align:center;">Maps</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($route->stops as $sIndex => $stop)
                        @php
                            $store = $stop->store;
                            $hasCoords = $store && !empty($store->latitude) && !empty($store->longitude) && is_numeric($store->latitude) && is_numeric($store->longitude);
                            $mapUrl = $hasCoords ? "https://www.google.com/maps?q={$store->latitude},{$store->longitude}" : null;
                        @endphp
                        <tr>
                            <td style="text-align:center;">{{ $sIndex + 1 }}</td>
                            <td>{{ $store->name ?? '-' }}</td>
                            <td>{{ $store->address ?? '-' }}</td>
                            <td style="text-align:center;">
                                @if($stop->status === 'visited')
                                    <span class="badge badge-green">Dikunjungi</span>
                                @elseif($stop->status === 'skipped')
                                    <span class="badge badge-red">Dilewati</span>
                                @else
                                    <span class="badge badge-gray">Belum Dikunjungi</span>
                                @endif
                            </td>
                            <td>{{ $stop->notes ?: '-' }}</td>
                            <td style="text-align:center;">
                                @if($mapUrl)
                                    <a class="maps-link" href="{{ $mapUrl }}">Buka Maps</a>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endforeach

@if($items->isEmpty())
    <div class="empty">Tidak ada data rencana pengiriman pada periode ini.</div>
@endif

@include('reports.pdf.partials.footer')
