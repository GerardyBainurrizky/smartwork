@include('visit.pdf.partials.styles')

@foreach ($items as $index => $item)
    <div class="page" style="{{ $index > 0 ? 'page-break-before: always;' : '' }}">
        @if ($item['type'] === 'skipped')
            @include('visit.pdf.skipped', $item['data'])
        @elseif (!empty($isDriver))
            @include('visit.pdf.detail-driver', array_merge($item['data'], ['pageTitle' => 'REKAPITULASI PENGIRIMAN DRIVER']))
        @else
            @include('visit.pdf.detail', array_merge($item['data'], ['pageTitle' => 'REKAPITULASI KUNJUNGAN SALES']))
        @endif
    </div>
@endforeach

@if ($items->isEmpty())
    <div class="empty">{{ !empty($isDriver) ? 'Tidak ada pengiriman yang selesai pada periode ini.' : 'Tidak ada kunjungan yang selesai pada periode ini.' }}</div>
@endif
