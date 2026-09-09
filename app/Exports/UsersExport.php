<?php

namespace App\Exports;

use App\Models\User;

class UsersExport extends BaseReportExport
{
    public function title(): string
    {
        return 'Laporan Pengguna';
    }

    protected function reportTitle(): string
    {
        return 'LAPORAN PENGGUNA';
    }

    public function headings(): array
    {
        return ['No', 'Nama Pengguna', 'Username', 'Email', 'No. Telepon', 'Role', 'Status', 'Tanggal Dibuat'];
    }

    protected function extraMetaRows(): array
    {
        $meta = [];

        if ($this->search) {
            $meta[] = 'Pencarian: ' . $this->search;
        }

        if ($this->role) {
            $roleLabel = match ($this->role) {
                'super-admin' => 'Super Admin',
                'admin' => 'Admin (Super Admin & Admin)',
                'sales' => 'Sales',
                'driver' => 'Driver',
                'staff' => 'Staff',
                default => ucfirst($this->role),
            };
            $meta[] = 'Filter Role: ' . $roleLabel;
        }

        if ($this->status) {
            $statusLabel = match ($this->status) {
                'active' => 'Aktif',
                'inactive' => 'Nonaktif',
                default => ucfirst($this->status),
            };
            $meta[] = 'Filter Status: ' . $statusLabel;
        }

        return $meta;
    }

    protected function emptyMessage(): ?string
    {
        return 'Tidak ada data pengguna yang sesuai filter.';
    }

    public function dataRows(): array
    {
        $query = User::withTrashed()->with('roles');

        if ($this->search) {
            $query->where(function ($q) {
                $search = $this->search;
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($this->role) {
            if ($this->role === 'admin') {
                $query->whereHas('roles', fn ($q) => $q->whereIn('name', ['admin', 'super-admin']));
            } else {
                $query->whereHas('roles', fn ($q) => $q->where('name', $this->role));
            }
        }

        if ($this->status) {
            $query->where('status', $this->status);
        }

        $rows = [];
        $no = 0;

        foreach ($query->orderBy('created_at', 'desc')->get() as $user) {
            $no++;
            $roleNames = $user->roles->map(function ($r) {
                return match ($r->name) {
                    'super-admin' => 'Super Admin',
                    'admin' => 'Admin',
                    'sales' => 'Sales',
                    'driver' => 'Driver',
                    'staff' => 'Staff',
                    default => ucfirst($r->name),
                };
            })->implode(', ') ?: '-';

            $rows[] = [
                $no,
                $user->name,
                $user->username ?: '-',
                $user->email ?: '-',
                $user->phone ?: '-',
                $roleNames,
                $user->status === 'active' ? 'Aktif' : ($user->status === 'inactive' ? 'Nonaktif' : ($user->status ?: '-')),
                $user->created_at?->format('d/m/Y H:i') ?? '-',
            ];
        }

        return $rows;
    }
}
