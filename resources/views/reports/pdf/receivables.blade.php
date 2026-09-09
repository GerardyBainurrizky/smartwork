@include('reports.pdf.partials.styles')

@php
    $dataCount = $items->count();
    $totalReceivable = 0.0;
    $indebtedCount = 0;

    foreach ($items as $item) {
        $bal = (float) ($item->receivable_balance ?? 0);
        $totalReceivable += $bal;
        if ($bal > 0.005) {
            $indebtedCount++;
        }
    }
@endphp

@include('reports.pdf.partials.header')

{{-- Ringkasan Statistik Piutang --}}
<div class="section" style="margin-bottom: 12px;">
    <div class="section-title">RINGKASAN STATISTIK PIUTANG TOKO</div>
    <div class="section-body" style="padding: 6px;">
        <table class="report-meta" style="margin-bottom: 0;">
            <tr>
                <td class="k" style="width: 20%;">Total Toko Ditampilkan</td>
                <td class="v" style="width: 30%;">{{ $dataCount }} Toko</td>
                <td class="k" style="width: 20%;">Toko Berpiutang</td>
                <td class="v" style="width: 30%; color: #b91c1c; font-weight: bold;">{{ $indebtedCount }} Toko</td>
            </tr>
            <tr>
                <td class="k">Total Saldo Piutang</td>
                <td class="v" colspan="3" style="color: #b91c1c; font-weight: bold; font-size: 10px;">
                    Rp {{ number_format($totalReceivable, 0, ',', '.') }}
                </td>
            </tr>
        </table>
    </div>
</div>

<table class="data">
    <thead>
        <tr>
            <th style="width: 4%; text-align: center;">No</th>
            <th style="width: 10%;">Kode Toko</th>
            <th style="width: 25%;">Nama Toko</th>
            <th style="width: 20%;">Sales Penanggung Jawab</th>
            <th style="width: 15%;">Kota / Wilayah</th>
            <th style="width: 16%; text-align: right;">Saldo Piutang</th>
            <th style="width: 10%; text-align: center;">Status</th>
        </tr>
    </thead>
    <tbody>
        @foreach($items as $index => $item)
            @php
                $bal = (float) ($item->receivable_balance ?? 0);
                $hasDebt = $bal > 0.005;
            @endphp
            <tr>
                <td style="text-align: center;">{{ $index + 1 }}</td>
                <td>{{ $item->code ?? '-' }}</td>
                <td><strong>{{ $item->name ?? '-' }}</strong></td>
                <td>{{ $item->salesPenanggungJawab?->name ?? '-' }}</td>
                <td>{{ $item->city ?? '-' }}</td>
                <td style="text-align: right; font-weight: bold; color: {{ $hasDebt ? '#b91c1c' : '#15803d' }};">
                    Rp {{ number_format($bal, 0, ',', '.') }}
                </td>
                <td style="text-align: center;">
                    @if($hasDebt)
                        <span class="badge badge-red">Berpiutang</span>
                    @else
                        <span class="badge badge-green">Lunas</span>
                    @endif
                </td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr style="background: #f1f5f9; font-weight: bold;">
            <td colspan="5" style="text-align: right; padding: 6px 5px;">TOTAL SALDO PIUTANG:</td>
            <td style="text-align: right; color: #b91c1c; padding: 6px 5px;">
                Rp {{ number_format($totalReceivable, 0, ',', '.') }}
            </td>
            <td style="text-align: center; padding: 6px 5px;">
                {{ $indebtedCount }} Toko
            </td>
        </tr>
    </tfoot>
</table>

@if($items->isEmpty())
    <div class="empty">Tidak ada data piutang pada periode ini.</div>
@endif

@include('reports.pdf.partials.footer')
