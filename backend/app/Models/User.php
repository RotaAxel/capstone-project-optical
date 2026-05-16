<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name', 'email', 'phone', 'role', 'is_active', 'password',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function isAdmin(): bool { return $this->role === 'admin'; }
    public function isReceptionist(): bool { return $this->role === 'receptionist'; }
    public function isOptometrist(): bool { return $this->role === 'optometrist'; }
    public function isInventoryStaff(): bool { return $this->role === 'inventory_staff'; }

    public function patients() { return $this->hasMany(Patient::class, 'created_by'); }
    public function prescriptions() { return $this->hasMany(Prescription::class, 'optometrist_id'); }
    public function sales() { return $this->hasMany(Sale::class, 'cashier_id'); }
}
