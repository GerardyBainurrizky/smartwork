<div class="footer">
    <div>{{ $footerLine1 ?? 'PT ISA TRI SELARAS GEMILANG' }}</div>
    <div>{{ $footerLine2 ?? 'Dokumen dibuat otomatis oleh ISA SmartWork.' }}</div>
    @if(!empty($footerLine3))
        <div>{{ $footerLine3 }}</div>
    @endif
</div>
