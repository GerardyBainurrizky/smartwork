@include('reports.pdf.partials.styles')

@php $dataCount = $items->count(); @endphp

@include('reports.pdf.partials.header')

<table class="data">
    <thead>
        <tr>
            <th style="width:4%; text-align:center;">No</th>
            <th style="width:20%;">Nama Pengguna</th>
            <th style="width:14%;">Username</th>
            <th style="width:20%;">Email</th>
            <th style="width:13%;">No. Telepon</th>
            <th style="width:11%;">Role</th>
            <th style="width:8%; text-align:center;">Status</th>
            <th style="width:10%;">Tanggal Dibuat</th>
        </tr>
    </thead>
    <tbody>
        @foreach($items as $index => $item)
            @php
                $roleNames = $item->roles->map(function ($r) {
                    return match($r->name) {
                        'super-admin' => 'Super Admin',
                        'admin' => 'Admin',
                        'sales' => 'Sales',
                        'driver' => 'Driver',
                        'staff' => 'Staff',
                        default => ucfirst($r->name)
                    };
                })->implode(', ') ?: '-';
            @endphp
            <tr>
                <td style="text-align:center;">{{ $index + 1 }}</td>
                <td><strong>{{ $item->name }}</strong></td>
                <td>{{ $item->username ?: '-' }}</td>
                <td>{{ $item->email ?: '-' }}</td>
                <td>{{ $item->phone ?: '-' }}</td>
                <td>{{ $roleNames }}</td>
                <td style="text-align:center;">
                    @if($item->status === 'active')
                        <span class="badge badge-green">Aktif</span>
                    @elseif($item->status === 'inactive')
                        <span class="badge badge-gray">Nonaktif</span>
                    @else
                        <span class="badge badge-gray">{{ $item->status ?? '-' }}</span>
                    @endif
                </td>
                <td>{{ $item->created_at?->format('d/m/Y H:i') ?? '-' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

@if($items->isEmpty())
    <div class="empty">Tidak ada data pengguna yang sesuai filter.</div>
@endif

@include('reports.pdf.partials.footer')
