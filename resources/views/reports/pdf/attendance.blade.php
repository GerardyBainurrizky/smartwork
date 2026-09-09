@include('reports.pdf.partials.styles')

@php
    $dataCount = $items->count();
    $statusLabelOf = fn ($item) => ($staffView ?? false)
        ? $item->status_presensi_label_staff
        : $item->status_presensi_label;
@endphp

@include('reports.pdf.partials.header')

@foreach($items as $index => $item)
    @php
        $isAbsence = $item->isAbsence();
        $inPhoto = $item->clock_in_selfie ? public_path('storage/' . $item->clock_in_selfie) : null;
        $outPhoto = $item->clock_out_selfie ? public_path('storage/' . $item->clock_out_selfie) : null;
        $statusBadge = $item->is_canceled ? 'badge-red' : ($item->status === 'izin' ? 'badge-amber' : ($item->status === 'sakit' ? 'badge-red' : ($item->clock_out !== null ? 'badge-green' : 'badge-cyan')));

        $duration = '-';
        if ($item->clock_in && $item->clock_out) {
            $minutes = max(0, (int) $item->clock_in->diffInMinutes($item->clock_out));
            $duration = $minutes >= 60
                ? (intdiv($minutes, 60) . ' Jam ' . ($minutes % 60) . ' Menit')
                : $minutes . ' Menit';
        }

        $hasCheckInCoord = !empty($item->clock_in_lat) && !empty($item->clock_in_lng);
        $checkInCoord = $hasCheckInCoord ? round((float) $item->clock_in_lat, 6) . ', ' . round((float) $item->clock_in_lng, 6) : '-';
        $checkInMapUrl = $hasCheckInCoord ? 'https://www.google.com/maps?q=' . $item->clock_in_lat . ',' . $item->clock_in_lng : null;

        $hasCheckOut = (bool) $item->clock_out;
        $hasCheckOutCoord = $hasCheckOut && !empty($item->clock_out_lat) && !empty($item->clock_out_lng);
        $checkOutCoord = $hasCheckOutCoord ? round((float) $item->clock_out_lat, 6) . ', ' . round((float) $item->clock_out_lng, 6) : null;
        $checkOutAddress = $isAbsence ? '-' : ($hasCheckOut ? ($item->clock_out_address ?? '-') : 'Belum Check Out');
        $checkOutMapUrl = $hasCheckOutCoord ? 'https://www.google.com/maps?q=' . $item->clock_out_lat . ',' . $item->clock_out_lng : null;
    @endphp
    <div class="section">
        <div class="section-title">
            Presensi #{{ $index + 1 }} &mdash; {{ $item->user->name ?? 'Pengguna' }}
            &nbsp;<span class="badge {{ $statusBadge }}">{{ $statusLabelOf($item) }}</span>
        </div>
        <div class="section-body">
            <table class="detail">
                <tr>
                    <td class="k">Nama</td>
                    <td class="v">{{ $item->user->name ?? '-' }}</td>
                    <td class="k">Role</td>
                    <td class="v">{{ $item->user?->getRoleNames()->implode(', ') ?: '-' }}</td>
                </tr>
                <tr>
                    <td class="k">Tanggal</td>
                    <td class="v">{{ $item->date->format('d M Y') }}</td>
                    <td class="k">Status</td>
                    <td class="v">{{ $statusLabelOf($item) }}</td>
                </tr>
                <tr>
                    <td class="k">{{ $isAbsence ? 'Waktu Pengajuan' : 'Jam Check In' }}</td>
                    <td class="v">{{ $item->clock_in?->format('H:i') ?? ($isAbsence ? $item->created_at?->format('H:i') : '-') }}</td>
                    <td class="k">Jam Check Out</td>
                    <td class="v">{{ $isAbsence ? '-' : ($item->clock_out?->format('H:i') ?? '-') }}</td>
                </tr>
                @if($item->absence_note)
                <tr>
                    <td class="k">Catatan</td>
                    <td class="v" colspan="3">{{ $item->absence_note }}</td>
                </tr>
                @endif
                <tr>
                    <td class="k">Durasi</td>
                    <td class="v" colspan="3">{{ $isAbsence ? '-' : $duration }}</td>
                </tr>
                <tr>
                    <td class="k">{{ $isAbsence ? 'Lokasi Pengajuan' : 'Lokasi Check In' }}</td>
                    <td class="v">{{ $item->clock_in_address ?? '-' }}</td>
                    <td class="k">Lokasi Check Out</td>
                    <td class="v">{{ $checkOutAddress }}</td>
                </tr>
                <tr>
                    <td class="k">{{ $isAbsence ? 'Koordinat Pengajuan' : 'Koordinat Check In' }}</td>
                    <td class="v">
                        @if ($checkInMapUrl)
                            <a class="maps-link" href="{{ $checkInMapUrl }}">Lihat Lokasi di Google Maps</a>
                            <div style="font-weight:400;color:#6b7280;font-size:8px;margin-top:2px;">{{ $checkInCoord }}</div>
                        @else
                            -
                        @endif
                    </td>
                    <td class="k">Koordinat Check Out</td>
                    <td class="v">
                        @if ($checkOutMapUrl)
                            <a class="maps-link" href="{{ $checkOutMapUrl }}">Lihat Lokasi Check Out di Google Maps</a>
                            <div style="font-weight:400;color:#6b7280;font-size:8px;margin-top:2px;">{{ $checkOutCoord }}</div>
                        @else
                            {{ $isAbsence ? '-' : ($hasCheckOut ? '-' : 'Belum Check Out') }}
                        @endif
                    </td>
                </tr>
            </table>

            <table class="photos">
                <tr>
                    <td>
                        @if ($inPhoto && file_exists($inPhoto))
                            <img src="{{ $inPhoto }}">
                        @else
                            <div class="no-photo">-</div>
                        @endif
                        <div class="cap">{{ $isAbsence ? 'Foto Dokumentasi' : 'Selfie Check In' }}</div>
                    </td>
                    @if(! $isAbsence)
                    <td>
                        @if ($outPhoto && file_exists($outPhoto))
                            <img src="{{ $outPhoto }}">
                        @else
                            <div class="no-photo">-</div>
                        @endif
                        <div class="cap">Selfie Check Out</div>
                    </td>
                    @endif
                    <td></td>
                </tr>
            </table>
        </div>
    </div>
@endforeach

@if($items->isEmpty())
    <div class="empty">Tidak ada data presensi pada periode ini.</div>
@endif

@include('reports.pdf.partials.footer')
