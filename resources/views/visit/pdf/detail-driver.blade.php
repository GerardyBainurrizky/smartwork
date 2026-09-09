@include('visit.pdf.partials.styles')

@php
    $checkIn = $visit->check_in_at ? $visit->check_in_at->format('d M Y, H:i') : '-';
    $checkOut = $visit->check_out_at ? $visit->check_out_at->format('d M Y, H:i') : '-';
    $date = $visit->check_in_at ? $visit->check_in_at->format('d M Y') : '-';
    $mapsUrl = null;
    if ($visit->check_in_maps_url) {
        $mapsUrl = $visit->check_in_maps_url;
    } elseif ($store && $store->latitude && $store->longitude) {
        $mapsUrl = 'https://www.google.com/maps?q=' . $store->latitude . ',' . $store->longitude;
    }

    $hasCheckInCoord = $visit->check_in_lat && $visit->check_in_lng;
    $checkInCoord = $hasCheckInCoord ? round((float) $visit->check_in_lat, 6) . ', ' . round((float) $visit->check_in_lng, 6) : '-';
    $hasCheckOut = (bool) $visit->check_out_at;
    $hasCheckOutCoord = $hasCheckOut && $visit->check_out_lat && $visit->check_out_lng;
    $checkOutCoord = $hasCheckOutCoord ? round((float) $visit->check_out_lat, 6) . ', ' . round((float) $visit->check_out_lng, 6) : 'Belum Check Out';
    $checkOutAddress = $hasCheckOut ? ($visit->check_out_address ?: '-') : 'Belum Check Out';
    $checkInMapUrl = $hasCheckInCoord ? 'https://www.google.com/maps?q=' . $visit->check_in_lat . ',' . $visit->check_in_lng : null;
    $checkOutMapUrl = $hasCheckOutCoord ? 'https://www.google.com/maps?q=' . $visit->check_out_lat . ',' . $visit->check_out_lng : null;

    // Foto Check In: Selfie / Foto Toko Check In
    $photoCheckIn = $visit->check_in_selfie ?: $visit->storefront_photo;

    // Foto Check Out Dokumentasi Barang (Dinamis untuk Driver)
    $checkoutPhotos = $visit->relationLoaded('checkoutPhotos') ? $visit->checkoutPhotos : $visit->checkoutPhotos()->get();
    if ($checkoutPhotos->isEmpty()) {
        $allPhotos = $visit->relationLoaded('photos') ? $visit->photos : $visit->photos()->get();
        $checkoutPhotos = $allPhotos->where('type', 'checkout_documentation');
    }
    $docPhotoPaths = $checkoutPhotos->pluck('photo_path')->filter()->values()->all();
    if (empty($docPhotoPaths)) {
        if ($visit->final_store_photo) {
            $docPhotoPaths[] = $visit->final_store_photo;
        }
        if ($visit->check_out_selfie) {
            $docPhotoPaths[] = $visit->check_out_selfie;
        }
    }
    // Limit maks 6 foto untuk ditampilkan di laporan
    $docPhotoPaths = array_slice($docPhotoPaths, 0, 6);

    // Catatan Toko / Tujuan dari RouteStop
    $stopNotesValue = $stopNotes ?? $visit->routeStop?->notes ?? null;
@endphp

@include('visit.pdf.partials.header', [
    'title' => $pageTitle ?? 'LAPORAN HASIL PENGIRIMAN',
    'subtitle' => 'Sistem Dokumentasi Pengiriman Driver — ISA SmartWork'
])

<table class="detail" style="margin-bottom:10px;">
    <tr>
        <td class="k">Nama Driver</td>
        <td class="v">{{ $salesName ?? ($visit->user->name ?? '-') }}</td>
        <td class="k">Jabatan</td>
        <td class="v">{{ $salesRole ?? ($visit->user->getRoleNames()->first() ? ucfirst($visit->user->getRoleNames()->first()) : 'Driver') }}</td>
    </tr>
    <tr>
        <td class="k">Tanggal Pengiriman</td>
        <td class="v">{{ $date }}</td>
        <td class="k">Rute & Urutan</td>
        <td class="v">{{ ($visit->route->name ?? 'Rute Pengiriman') . ' (Urutan #' . ($visit->routeStop->sequence ?? 1) . ')' }}</td>
    </tr>
    <tr>
        <td class="k">Jam Check In</td>
        <td class="v">{{ $checkIn }}</td>
        <td class="k">Jam Check Out</td>
        <td class="v">{{ $checkOut }}</td>
    </tr>
    <tr>
        <td class="k">Durasi Pengiriman</td>
        <td class="v">{{ $durationLabel ?? '-' }}</td>
        <td class="k">Status Pengiriman</td>
        <td class="v" style="color: {{ $visit->status === 'completed' ? '#15803d' : '#0da4ce' }}; font-weight: bold;">
            {{ $visit->status === 'completed' ? 'Selesai' : 'Sedang Dikirim' }}
        </td>
    </tr>
</table>

<div class="section">
    <div class="section-title">INFORMASI TUJUAN PENGIRIMAN</div>
    <div class="section-body">
        <table class="detail detail-2col">
            <tr>
                <td class="k">Nama Toko / Tujuan</td><td class="v">{{ $store->name ?? '-' }}</td>
                <td class="k">Kode Toko</td><td class="v">{{ $store->code ?? '-' }}</td>
            </tr>
            <tr>
                <td class="k">Alamat Tujuan</td><td class="v" colspan="3">{{ $store->address ?? '-' }}</td>
            </tr>
            <tr>
                <td class="k">Kota / Provinsi</td>
                <td class="v">{{ implode(', ', array_filter([$store->city ?? null, $store->province ?? null])) ?: '-' }}</td>
                <td class="k">Koordinat Toko</td>
                <td class="v">{{ $store && $store->latitude && $store->longitude ? round($store->latitude, 6) . ', ' . round($store->longitude, 6) : '-' }}</td>
            </tr>
            <tr>
                <td class="k">Link Google Maps</td>
                <td class="v" colspan="3">
                    @if ($mapsUrl)
                        <a class="maps-link" href="{{ $mapsUrl }}">Buka Lokasi di Google Maps</a>
                    @else
                        -
                    @endif
                </td>
            </tr>
            <tr>
                <td class="k">Catatan Toko / Tujuan</td>
                <td class="v" colspan="3">
                    @if ($stopNotesValue)
                        <div class="multiline-text">{{ $stopNotesValue }}</div>
                    @else
                        <span style="color: #9ca3af; font-style: italic;">Tidak ada catatan toko.</span>
                    @endif
                </td>
            </tr>
        </table>
    </div>
</div>

<div class="section">
    <div class="section-title">CHECK IN PENGIRIMAN</div>
    <div class="section-body">
        <table class="detail detail-2col">
            <tr>
                <td class="k">Waktu Check In</td><td class="v">{{ $checkIn }}</td>
                <td class="k">Koordinat Check In</td><td class="v">{{ $checkInCoord }}</td>
            </tr>
            <tr>
                <td class="k">Alamat Check In</td><td class="v" colspan="3">{{ $visit->check_in_address ?: '-' }}</td>
            </tr>
            <tr>
                <td class="k">Catatan Awal</td>
                <td class="v" colspan="3">
                    <div class="multiline-text">{{ $visit->initial_notes ?: 'Tidak ada catatan check in.' }}</div>
                </td>
            </tr>
            @if ($checkInMapUrl)
            <tr>
                <td class="k">Peta Lokasi</td>
                <td class="v" colspan="3">
                    <a class="maps-link" href="{{ $checkInMapUrl }}">Buka Lokasi Check In di Google Maps</a>
                </td>
            </tr>
            @endif
        </table>
    </div>
</div>

<div class="section">
    <div class="section-title">HASIL PENGIRIMAN</div>
    <div class="section-body">
        <table class="detail detail-2col">
            <tr>
                <td class="k" style="width: 25%;">Hasil Pengiriman</td>
                <td class="v" colspan="3" style="width: 75%;">
                    <div class="multiline-text">{{ $visit->visit_result ?: ($visit->status === 'completed' ? 'Terkirim Selesai' : '-') }}</div>
                </td>
            </tr>
        </table>
    </div>
</div>

<div class="section">
    <div class="section-title">CATATAN TAMBAHAN</div>
    <div class="section-body">
        <table class="detail detail-2col">
            <tr>
                <td class="k" style="width: 25%;">Catatan Tambahan</td>
                <td class="v" colspan="3" style="width: 75%;">
                    <div class="multiline-text">{{ $visit->final_notes ?: 'Tidak ada catatan tambahan.' }}</div>
                </td>
            </tr>
        </table>
    </div>
</div>

<div class="section">
    <div class="section-title">CHECK OUT PENGIRIMAN</div>
    <div class="section-body">
        <table class="detail detail-2col">
            <tr>
                <td class="k">Waktu Check Out</td><td class="v">{{ $checkOut }}</td>
                <td class="k">Koordinat Check Out</td><td class="v">{{ $checkOutCoord }}</td>
            </tr>
            <tr>
                <td class="k">Alamat Check Out</td><td class="v" colspan="3">{{ $checkOutAddress }}</td>
            </tr>
            @if ($checkOutMapUrl)
            <tr>
                <td class="k">Peta Lokasi Check Out</td>
                <td class="v" colspan="3">
                    <a class="maps-link" href="{{ $checkOutMapUrl }}">Buka Lokasi Check Out di Google Maps</a>
                </td>
            </tr>
            @endif
        </table>
    </div>
</div>

<div class="section">
    <div class="section-title">TRANSAKSI PENGIRIMAN</div>
    <div class="section-body">
        <table class="detail detail-2col">
            @php
                $driverHasTx = (float) ($visit->transaction_amount ?? 0) > 0;
            @endphp
            @if ($driverHasTx)
            <tr>
                <td class="k">Nominal Transaksi</td>
                <td class="v" style="font-weight: bold; color: #15803d;">Rp {{ number_format($visit->transaction_amount, 0, ',', '.') }}</td>
                <td class="k">Metode Pembayaran</td>
                <td class="v" style="font-weight: bold; text-transform: uppercase;">{{ $visit->payment_method ?? 'Tunai' }}</td>
            </tr>
            @else
            <tr>
                <td class="k">Transaksi</td>
                <td class="v" colspan="3" style="color: #64748b; font-style: italic;">Tidak Ada Transaksi</td>
            </tr>
            @endif
        </table>
    </div>
</div>

<div class="section">
    <div class="section-title">DOKUMENTASI CHECK IN (FOTO TOKO / SELFIE)</div>
    <div class="section-body">
        <table class="photos" style="width: auto;">
            <tr>
                <td style="width: 180px; text-align: left;">
                    @php
                        $inImgPath = null;
                        if ($photoCheckIn && file_exists(public_path('storage/' . $photoCheckIn))) {
                            $inImgPath = public_path('storage/' . $photoCheckIn);
                        } elseif ($photoCheckIn && file_exists(storage_path('app/public/' . $photoCheckIn))) {
                            $inImgPath = storage_path('app/public/' . $photoCheckIn);
                        }
                    @endphp
                    @if ($inImgPath)
                        <img src="{{ $inImgPath }}" style="max-height: 110px;">
                    @else
                        <div class="no-photo" style="width: 140px; text-align: center;">Tidak ada foto</div>
                    @endif
                    <div class="cap">Foto Toko / Selfie</div>
                </td>
            </tr>
        </table>
    </div>
</div>

<div class="section">
    <div class="section-title">DOKUMENTASI BARANG / DOKUMEN PENGIRIMAN ({{ count($docPhotoPaths) }} FOTO)</div>
    <div class="section-body">
        @if (count($docPhotoPaths) > 0)
            <table class="photos">
                @foreach (array_chunk($docPhotoPaths, 3) as $photoChunk)
                <tr>
                    @foreach ($photoChunk as $idx => $pPath)
                    @php
                        $docImgPath = null;
                        if ($pPath && file_exists(public_path('storage/' . $pPath))) {
                            $docImgPath = public_path('storage/' . $pPath);
                        } elseif ($pPath && file_exists(storage_path('app/public/' . $pPath))) {
                            $docImgPath = storage_path('app/public/' . $pPath);
                        }
                    @endphp
                    <td style="width: 33.33%;">
                        @if ($docImgPath)
                            <img src="{{ $docImgPath }}" style="max-height: 105px;">
                        @else
                            <div class="no-photo">Tidak ada foto</div>
                        @endif
                        <div class="cap">Foto Barang/Dokumen #{{ ($loop->parent->index * 3) + $idx + 1 }}</div>
                    </td>
                    @endforeach
                    @for ($i = count($photoChunk); $i < 3; $i++)
                    <td style="width: 33.33%;"></td>
                    @endfor
                </tr>
                @endforeach
            </table>
        @else
            <div class="no-photo" style="height: 40px; line-height: 40px; text-align: center;">Tidak ada foto barang/dokumen</div>
        @endif
    </div>
</div>

@include('visit.pdf.partials.footer')
