<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Patient extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'patient_code', 'first_name', 'last_name', 'date_of_birth',
        'gender', 'phone', 'email', 'address',
        'emergency_contact_name', 'emergency_contact_phone',
        'medical_history', 'created_by',
    ];

    protected $casts = ['date_of_birth' => 'date'];

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function createdBy() { return $this->belongsTo(User::class, 'created_by'); }
    public function prescriptions() { return $this->hasMany(Prescription::class); }
    public function appointments() { return $this->hasMany(Appointment::class); }
    public function sales() { return $this->hasMany(Sale::class); }
}
