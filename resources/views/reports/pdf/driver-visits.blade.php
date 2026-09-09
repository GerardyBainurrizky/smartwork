@include('reports.pdf.partials.styles')

@php
    $selectedUserLabel = $selectedUserLabel ?? 'Semua Driver';
    $statusLabel = $statusLabel ?? 'Semua Status';
    $trxStatusLabel = $trxStatusLabel ?? 'Semua';
    $printedByRole = $printedByRole ?? 'Admin';
    $skipped = $skipped ?? collect();
    $dataCount = ($items->count()) + ($skipped->count());
@endphp

@include('reports.pdf.partials.header')

{{-- Extended metadata for Driver Visits --}}
<table class="report-meta" style="margin-top: -6px; margin-bottom: 14px;">
    <tr>
        <td class="k">Driver</td>
        <td class="v">{{ $selectedUserLabel }}</td>
        <td class="k">Status</td>
        <td class="v">{{ $statusLabel }}</td>
    </tr>
    <tr>
        <td class="k">Status Transaksi</td>
        <td class="v">{{ $trxStatusLabel }}</td>
        <td class="k">Role Pencetak</td>
        <td class="v">{{ $printedByRole }}</td>
    </tr>
</table>

@foreach($items as $index => $visit)
    @php
        $photoCheckIn = $visit->check_in_selfie ?: $visit->storefront_photo;
        $inPhoto = null;
        if ($photoCheckIn && file_exists(public_path('storage/' . $photoCheckIn))) {
            $inPhoto = public_path('storage/' . $photoCheckIn);
        } elseif ($photoCheckIn && file_exists(storage_path('app/public/' . $photoCheckIn))) {
            $inPhoto = storage_path('app/public/' . $photoCheckIn);
        }

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
        $docPhotoPaths = array_slice($docPhotoPaths, 0, 6);

        $stopNotes = $visit->routeStop?->notes ?? null;

        $duration = '-';
        if ($visit->check_in_at && $visit->check_out_at) {
            $minutes = max(0, (int) $visit->check_in_at->diffInMinutes($visit->check_out_at));
            $duration = $minutes >= 60
                ? (intdiv($minutes, 60) . ' Jam ' . ($minutes % 60) . ' Menit')
                : $minutes . ' Menit';
        }

        $hasCheckOut = (bool) $visit->check_out_at;
        $hasCheckInCoord = $visit->check_in_lat && $visit->check_in_lng;
        $hasCheckOutCoord = $hasCheckOut && $visit->check_out_lat && $visit->check_out_lng;
        $checkInCoord = $hasCheckInCoord ? round((float) $visit->check_in_lat, 6) . ', ' . round((float) $visit->check_in_lng, 6) : '-';
        $checkOutCoord = $hasCheckOutCoord ? round((float) $visit->check_out_lat, 6) . ', ' . round((float) $visit->check_out_lng, 6) : 'Belum Check Out';
        $checkOutAddress = $hasCheckOut ? ($visit->check_out_address ?? '-') : 'Belum Check Out';
        $checkInMapUrl = $hasCheckInCoord ? 'https://www.google.com/maps?q=' . $visit->check_in_lat . ',' . $visit->check_in_lng : null;
        $checkOutMapUrl = $hasCheckOutCoord ? 'https://www.google.com/maps?q=' . $visit->check_out_lat . ',' . $visit->check_out_lng : null;
    @endphp
    <div class="section">
        <div class="section-title">
            Pengiriman #{{ $index + 1 }} &mdash; {{ $visit->store->name ?? 'Tujuan' }}
            @if($visit->status === 'completed')
                <span class="badge badge-green">Selesai</span>
            @else
                <span class="badge badge-cyan">Sedang Dikirim</span>
            @endif
        </div>
        <div class="section-body">
            <table class="detail">
                <tr>
                    <td class="k">Nama Driver</td>
                    <td class="v">{{ $visit->user->name ?? '-' }}</td>
                    <td class="k">Rute Pengiriman</td>
                    <td class="v">{{ $visit->route->name ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="k">Tujuan / Toko</td>
                    <td class="v">{{ $visit->store->name ?? '-' }}</td>
                    <td class="k">Kode Toko</td>
                    <td class="v">{{ $visit->store->code ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="k">Alamat Tujuan</td>
                    <td class="v" colspan="3">{{ $visit->store->address ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="k">Catatan Toko / Tujuan</td>
                    <td class="v" colspan="3">{{ $stopNotes ?: '-' }}</td>
                </tr>
                <tr>
                    <td class="k">Check In</td>
                    <td class="v">{{ $visit->check_in_at?->format('d M Y, H:i') ?? '-' }}</td>
                    <td class="k">Check Out</td>
                    <td class="v">{{ $visit->check_out_at?->format('d M Y, H:i') ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="k">Durasi Pengiriman</td>
                    <td class="v">{{ $duration }}</td>
                    <td class="k">Status Pengiriman</td>
                    <td class="v">{{ $visit->status === 'completed' ? 'Selesai' : 'Sedang Dikirim' }}</td>
                </tr>
                <tr>
                    <td class="k">Catatan Awal</td>
                    <td class="v">{{ $visit->initial_notes ?? '-' }}</td>
                    <td class="k">Hasil Pengiriman</td>
                    <td class="v">{{ $visit->visit_result ?? '-' }}</td>
                </tr>
                @if($visit->delivered_goods_summary)
                <tr>
                    <td class="k">Barang Terkirim</td>
                    <td class="v" colspan="3">{{ $visit->delivered_goods_summary }}</td>
                </tr>
                @endif
                @if($visit->returned_goods)
                <tr>
                    <td class="k">Barang Retur</td>
                    <td class="v" colspan="3">{{ $visit->returned_goods }}</td>
                </tr>
                @endif
                @if($visit->final_notes)
                <tr>
                    <td class="k">Catatan Tambahan</td>
                    <td class="v" colspan="3">{{ $visit->final_notes }}</td>
                </tr>
                @endif
                <tr>
                    <td class="k">Lokasi Check In</td>
                    <td class="v">{{ $visit->check_in_address ?? '-' }}</td>
                    <td class="k">Lokasi Check Out</td>
                    <td class="v">{{ $checkOutAddress }}</td>
                </tr>
                @if($checkInMapUrl || $checkOutMapUrl)
                <tr>
                    <td class="k">Peta Check In</td>
                    <td class="v">
                        @if($checkInMapUrl)
                            <a class="maps-link" href="{{ $checkInMapUrl }}">Buka Lokasi Check In di Google Maps</a>
                        @else
                            -
                        @endif
                    </td>
                    <td class="k">Peta Check Out</td>
                    <td class="v">
                        @if($checkOutMapUrl)
                            <a class="maps-link" href="{{ $checkOutMapUrl }}">Buka Lokasi Check Out di Google Maps</a>
                        @else
                            -
                        @endif
                    </td>
                </tr>
                @endif
            </table>

            {{-- ===== SECTION TRANSAKSI ===== --}}
            <div style="margin-top: 12px; font-weight: bold; font-size: 11px; color: #0DA4CE; border-bottom: 1px solid #0DA4CE; padding-bottom: 2px;">
                TRANSAKSI
            </div>
            @if($visit->transaction_amount > 0)
                <table class="detail" style="margin-top: 5px;">
                    <tr>
                        <td class="k">Nominal Transaksi</td>
                        <td class="v" style="color: #059669; font-size: 10px;">Rp {{ number_format((float) $visit->transaction_amount, 0, ',', '.') }}</td>
                        <td class="k">Metode Pembayaran</td>
                        <td class="v">{{ strtoupper($visit->payment_method ?? '-') }}</td>
                    </tr>
                    <tr>
                        <td class="k">Status Pembayaran</td>
                        <td class="v" colspan="3"><span class="badge badge-green">{{ $visit->transaction_status === 'paid' ? 'Lunas' : ucfirst((string) $visit->transaction_status) }}</span></td>
                    </tr>
                </table>
            @else
                <div style="font-size: 9px; color: #6b7280; padding: 6px 0; font-style: italic;">
                    Tidak ada transaksi
                </div>
            @endif

            {{-- Dokumentasi Check In (1 Foto) --}}
            <div style="margin-top: 12px; font-weight: bold; font-size: 11px; color: #0DA4CE;">
                DOKUMENTASI CHECK IN
            </div>
            <table class="photos" style="width: auto; margin-top: 5px;">
                <tr>
                    <td style="width: 160px; text-align: left;">
                        @if($inPhoto)
                            <img src="{{ $inPhoto }}" style="max-height: 100px;">
                        @else
                            <div class="no-photo" style="width: 140px; text-align: center;">Tidak ada foto</div>
                        @endif
                        <div class="cap">Foto Check In</div>
                    </td>
                </tr>
            </table>

            {{-- Dokumentasi Barang / Dokumen (Dinamis) --}}
            <div style="margin-top: 10px; font-weight: bold; font-size: 11px; color: #0DA4CE;">
                DOKUMENTASI BARANG / DOKUMEN ({{ count($docPhotoPaths) }} FOTO)
            </div>
            @if(count($docPhotoPaths) > 0)
                <table class="photos" style="margin-top: 5px;">
                    @foreach(array_chunk($docPhotoPaths, 3) as $photoChunk)
                    <tr>
                        @foreach($photoChunk as $pIdx => $pPath)
                            @php
                                $docImg = null;
                                if ($pPath && file_exists(public_path('storage/' . $pPath))) {
                                    $docImg = public_path('storage/' . $pPath);
                                } elseif ($pPath && file_exists(storage_path('app/public/' . $pPath))) {
                                    $docImg = storage_path('app/public/' . $pPath);
                                }
                            @endphp
                            <td style="width: 33.33%;">
                                @if($docImg)
                                    <img src="{{ $docImg }}" style="max-height: 100px;">
                                @else
                                    <div class="no-photo">Tidak ada foto</div>
                                @endif
                                <div class="cap">Foto #{{ ($loop->parent->index * 3) + $pIdx + 1 }}</div>
                            </td>
                        @endforeach
                        @for($i = count($photoChunk); $i < 3; $i++)
                            <td style="width: 33.33%;"></td>
                        @endfor
                    </tr>
                    @endforeach
                </table>
            @else
                <div class="no-photo" style="margin-top: 5px; height: 35px; line-height: 35px; text-align: center;">Tidak ada foto dokumen/barang</div>
            @endif
        </div>
    </div>
@endforeach

@if($skipped->isNotEmpty())
    <div class="section">
        <div class="section-title" style="background:#b91c1c;">
            PENGIRIMAN DILEWATI (SKIP)
        </div>
        <div class="section-body">
            <table class="data">
                <thead>
                    <tr>
                        <th style="width:4%">No</th>
                        <th>Nama Driver</th>
                        <th>Rute Pengiriman</th>
                        <th>Toko / Tujuan</th>
                        <th>Tanggal Route</th>
                        <th>Alasan</th>
                        <th style="width:22%; text-align:center;">Foto Bukti Dilewati</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($skipped as $sIndex => $stop)
                        @php
                            $skipVisit = $stop->visit ?? $stop->anyVisit;
                            $skipPhoto = $skipVisit?->final_store_photo ?: $skipVisit?->storefront_photo;
                            $skipReason = $stop->notes ?? '-';

                            if (empty($skipPhoto) && !empty($stop->notes) && str_starts_with($stop->notes, '{')) {
                                $meta = json_decode($stop->notes, true);
                                if (is_array($meta)) {
                                    $skipPhoto = $meta['photo_path'] ?? null;
                                    $skipReason = $meta['reason'] ?? $skipReason;
                                }
                            }

                            $fullSkipPhoto = null;
                            if ($skipPhoto) {
                                if (file_exists(public_path('storage/' . $skipPhoto))) {
                                    $fullSkipPhoto = public_path('storage/' . $skipPhoto);
                                } elseif (file_exists(storage_path('app/public/' . $skipPhoto))) {
                                    $fullSkipPhoto = storage_path('app/public/' . $skipPhoto);
                                } elseif (\Illuminate\Support\Facades\Storage::disk('public')->exists($skipPhoto)) {
                                    $fullSkipPhoto = \Illuminate\Support\Facades\Storage::disk('public')->path($skipPhoto);
                                }
                            }
                        @endphp
                        <tr>
                            <td>{{ $sIndex + 1 }}</td>
                            <td>{{ $stop->route->user->name ?? '-' }}</td>
                            <td>{{ $stop->route->name ?? '-' }}</td>
                            <td>{{ $stop->store->name ?? '-' }}</td>
                            <td>{{ $stop->route->date ? \Carbon\Carbon::parse($stop->route->date)->format('d M Y') : '-' }}</td>
                            <td>{{ $skipReason }}</td>
                            <td style="text-align:center; vertical-align:middle; padding:4px;">
                                @if($fullSkipPhoto)
                                    <img src="{{ $fullSkipPhoto }}" style="max-width:100px; max-height:80px; width:auto; height:auto; border:1px solid #e5e7eb; border-radius:4px; display:inline-block;">
                                @else
                                    <div class="no-photo" style="height:40px; line-height:40px; font-size:8px;">Tidak ada foto</div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

@if($items->isEmpty() && $skipped->isEmpty())
    <div class="empty">Tidak ada data pengiriman driver pada periode dan filter yang dipilih.</div>
@endif

@include('reports.pdf.partials.footer')
