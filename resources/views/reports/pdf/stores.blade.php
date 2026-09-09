@include('reports.pdf.partials.styles')

@php $dataCount = $items->count(); @endphp

@include('reports.pdf.partials.header')

<table class="data">
    <thead>
        <tr>
            <th style="width:3%">No</th>
            <th style="width:7%">Kode</th>
            <th>Nama Toko</th>
            <th>Pemilik</th>
            <th>Sales Penanggung Jawab</th>
            <th>Alamat Lengkap</th>
            <th style="width:8%">Kecamatan</th>
            <th style="width:8%">Kota</th>
            <th style="width:8%">Provinsi</th>
            <th style="width:8%">Latitude</th>
            <th style="width:8%">Longitude</th>
            <th style="width:6%">Maps</th>
            <th style="width:6%">Status</th>
            <th style="width:7%">Tanggal Dibuat</th>
        </tr>
    </thead>
    <tbody>
        @foreach($items as $index => $item)
            @php
                $isItemActive = ($item->status === 'active' && !$item->trashed());
                $hasCoords = !empty($item->latitude) && !empty($item->longitude);
                $mapUrl = $hasCoords ? "https://www.google.com/maps?q={$item->latitude},{$item->longitude}" : null;
            @endphp
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $item->code ?? '-' }}</td>
                <td>{{ $item->name ?? '-' }}</td>
                <td>{{ $item->owner ?? '-' }}</td>
                <td>{{ $item->salesPenanggungJawab?->name ?? 'Belum Ada' }}</td>
                <td>{{ $item->address ?? '-' }}</td>
                <td>{{ $item->kecamatan ?? '-' }}</td>
                <td>{{ $item->city ?? '-' }}</td>
                <td>{{ $item->province ?? '-' }}</td>
                <td>{{ $item->latitude ?? '-' }}</td>
                <td>{{ $item->longitude ?? '-' }}</td>
                <td>
                    @if($mapUrl)
                        <a class="maps-link" href="{{ $mapUrl }}">Buka Lokasi</a>
                    @else
                        -
                    @endif
                </td>
                <td>
                    @if($isItemActive)
                        <span class="badge badge-green">Aktif</span>
                    @else
                        <span class="badge badge-red">Nonaktif</span>
                    @endif
                </td>
                <td>{{ $item->created_at?->format('d M Y') ?? '-' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

@if($items->isEmpty())
    <div class="empty">Tidak ada data toko pada periode ini.</div>
@endif

@include('reports.pdf.partials.footer')
