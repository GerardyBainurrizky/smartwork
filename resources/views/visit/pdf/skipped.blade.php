@include('visit.pdf.partials.styles')

@php
    $reason = $stop->notes ?? ($reason ?? '-');
    $photoSkip = null;
    $skipLat = null;
    $skipLng = null;
    $skipAddress = null;
    $skipMapUrl = null;

    // 1. Cek dari relasi visit jika stop memiliki visit record (yang disimpan saat skip)
    if (isset($stop->visit) && $stop->visit) {
        $photoSkip = $stop->visit->final_store_photo ?: $stop->visit->storefront_photo;
        $skipLat = $stop->visit->check_in_lat ?? $stop->visit->check_out_lat;
        $skipLng = $stop->visit->check_in_lng ?? $stop->visit->check_out_lng;
        $skipAddress = $stop->visit->check_in_address ?? $stop->visit->check_out_address;
        if ($stop->visit->initial_notes) {
            $reason = $stop->visit->initial_notes;
        } elseif (str_starts_with((string)$stop->visit->visit_result, 'Dilewati: ')) {
            $reason = trim(substr($stop->visit->visit_result, 10));
        }
    }

    // 2. Cek jika notes berupa JSON meta
    if (empty($photoSkip) && !empty($stop->notes) && str_starts_with($stop->notes, '{')) {
        $meta = json_decode($stop->notes, true);
        if (is_array($meta)) {
            $reason = $meta['reason'] ?? $reason;
            $photoSkip = $meta['photo_path'] ?? null;
            $skipLat = $meta['latitude'] ?? $skipLat;
            $skipLng = $meta['longitude'] ?? $skipLng;
            $skipAddress = $meta['address'] ?? $skipAddress;
            $skipMapUrl = $meta['maps_url'] ?? null;
        }
    }

    if (!$skipMapUrl && $skipLat && $skipLng) {
        $skipMapUrl = "https://www.google.com/maps?q={$skipLat},{$skipLng}";
    }
@endphp

@php
    $isDriver = isset($stop->route->user) && $stop->route->user->hasRole('driver');
    $headerTitle = $isDriver ? 'LAPORAN PENGIRIMAN DILEWATI' : 'LAPORAN KUNJUNGAN DILEWATI';
    $statusText = $isDriver ? 'STATUS PENGIRIMAN DILEWATI' : 'STATUS KUNJUNGAN DILEWATI';
@endphp

@include('visit.pdf.partials.header', ['title' => $headerTitle])

<div class="skip-banner">
    <div class="skip-label">{{ $statusText }}</div>
    <div class="skip-text">DILEWATI</div>
</div>

<table class="detail" style="margin-bottom:12px;">
    <tr>
        <td class="k">{{ $isDriver ? 'Nama Driver' : 'Nama Sales' }}</td>
        <td class="v">{{ $salesName }}</td>
        <td class="k">Role / Jabatan</td>
        <td class="v">{{ $salesRole }}</td>
    </tr>
    <tr>
        <td class="k">Tanggal Rencana</td>
        <td class="v">{{ $date ? \Carbon\Carbon::parse($date)->format('d M Y') : '-' }}</td>
        <td class="k">Rute & Urutan</td>
        <td class="v">{{ ($stop->route->name ?? ($isDriver ? 'Rute Pengiriman' : 'Rute')) . ' (Urutan #' . ($stop->sequence ?? 1) . ')' }}</td>
    </tr>
</table>

<div class="section">
    <div class="section-title">{{ $isDriver ? 'INFORMASI TUJUAN PENGIRIMAN' : 'INFORMASI TOKO' }}</div>
    <div class="section-body">
        <table class="detail detail-2col">
            <tr><td class="k">Nama Toko</td><td class="v">{{ $store->name ?? '-' }}</td><td class="k">Kode Toko</td><td class="v">{{ $store->code ?? '-' }}</td></tr>
            <tr><td class="k">Alamat</td><td class="v" colspan="3">{{ $store->address ?? '-' }}</td></tr>
            <tr><td class="k">Kota / Provinsi</td><td class="v" colspan="3">{{ implode(', ', array_filter([$store->city ?? null, $store->province ?? null])) ?: '-' }}</td></tr>
        </table>
    </div>
</div>

<div class="section">
    <div class="section-title">ALASAN & LOKASI DILEWATI</div>
    <div class="section-body">
        <table class="detail detail-2col">
            <tr>
                <td class="k">Alasan Lewati</td>
                <td class="v" colspan="3" style="color: #b91c1c; font-weight: bold;">{{ $reason ?: '-' }}</td>
            </tr>
            @if ($skipLat && $skipLng)
            <tr>
                <td class="k">Koordinat</td>
                <td class="v" colspan="3">{{ round((float)$skipLat, 6) . ', ' . round((float)$skipLng, 6) }}</td>
            </tr>
            @endif
            @if ($skipAddress)
            <tr>
                <td class="k">Alamat Pengambilan</td>
                <td class="v" colspan="3">{{ $skipAddress }}</td>
            </tr>
            @endif
            @if ($skipMapUrl)
            <tr>
                <td class="k">Peta Lokasi</td>
                <td class="v" colspan="3"><a class="maps-link" href="{{ $skipMapUrl }}">Buka Lokasi di Google Maps</a></td>
            </tr>
            @endif
        </table>
    </div>
</div>

@if ($photoSkip)
<div class="section">
    <div class="section-title">FOTO BUKTI DILEWATI</div>
    <div class="section-body">
        <table class="photos">
            <tr>
                <td>
                    @if (file_exists(public_path('storage/' . $photoSkip)))
                        <img src="{{ public_path('storage/' . $photoSkip) }}">
                    @elseif (file_exists(storage_path('app/public/' . $photoSkip)))
                        <img src="{{ storage_path('app/public/' . $photoSkip) }}">
                    @else
                        <div class="no-photo">Foto tidak tersedia</div>
                    @endif
                    <div class="cap">Foto Bukti Dilewati</div>
                </td>
            </tr>
        </table>
    </div>
</div>
@endif

@include('visit.pdf.partials.footer')
