@php
    $generatedAt = $generatedAt ?? now()->format('d M Y, H:i');
    $landscape = $landscape ?? false;
    $pageWidth = $landscape ? 815 : 540;
    $footerY = $landscape ? 560 : 812;
@endphp
<script type="text/php">
    if (isset($pdf)) {
        $font = $fontMetrics->getFont("DejaVu Sans", "normal");
        $size = 8;
        $color = array(120, 120, 120);
        $pdf->page_text(20, {{ $footerY }}, "Tanggal Cetak: {{ $generatedAt }}", $font, $size, $color);
        $pdf->page_text({{ $pageWidth }} - 120, {{ $footerY }}, "Halaman {PAGE_NUM} dari {PAGE_COUNT}", $font, $size, $color);
    }
</script>
