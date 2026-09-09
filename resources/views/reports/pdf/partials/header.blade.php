@php
    $printedBy = $printedBy ?? '-';
    $printedByRole = $printedByRole ?? 'Admin';
    $generatedAt = $generatedAt ?? now()->format('d M Y, H:i');
    $fromDate = $fromDate ?? null;
    $toDate = $toDate ?? null;

    if ($fromDate && $toDate) {
        $periodeFormatted = \Carbon\Carbon::parse($fromDate)->locale('id')->translatedFormat('d F Y')
            . ' - '
            . \Carbon\Carbon::parse($toDate)->locale('id')->translatedFormat('d F Y');
    } else {
        $periodeFormatted = 'Semua Periode';
    }

    $dataCount = $dataCount ?? 0;
    $dataCountLabel = $dataCountLabel ?? ($dataCount . ' data');
@endphp
<div class="report-header">
    <img src="{{ $logoPath }}" alt="Logo ISA SmartWork">
    <div class="company">PT ISA TRI SELARAS GEMILANG</div>
    <div class="subtitle">ISA SmartWork &middot; Enterprise Agricultural Field Management</div>
    <div class="title">{{ $title }}</div>
</div>
<table class="report-meta">
    <tr>
        <td class="k">Periode Laporan</td>
        <td class="v">{{ $periodeFormatted }}</td>
        <td class="k">Tanggal Cetak</td>
        <td class="v">{{ $generatedAt }}</td>
    </tr>
    <tr>
        <td class="k">Dicetak Oleh</td>
        <td class="v">{{ $printedBy }}</td>
        <td class="k">Role</td>
        <td class="v">{{ $printedByRole }}</td>
    </tr>
    <tr>
        <td class="k">Jumlah Data</td>
        <td class="v" colspan="3">{{ $dataCountLabel }}</td>
    </tr>
</table>
