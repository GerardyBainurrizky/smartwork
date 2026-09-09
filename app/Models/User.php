<?php

namespace App\Models;

use App\Models\Route as RouteModel;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, SoftDeletes;

    protected $fillable = [
        'username',
        'name',
        'email',
        'phone',
        'avatar',
        'password',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class);
    }

    public function routes(): HasMany
    {
        return $this->hasMany(RouteModel::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function assignedStores(): HasMany
    {
        return $this->hasMany(Store::class, 'sales_penanggung_jawab_id');
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super-admin');
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function getRoleLabelAttribute(): string
    {
        $role = $this->roles->first()?->name;

        return match ($role) {
            'super-admin' => 'Super Admin',
            'admin' => 'Admin',
            'sales' => 'Sales',
            'driver' => 'Driver',
            'staff' => 'Staff',
            default => ucfirst($role ?? 'Pengguna'),
        };
    }
}
