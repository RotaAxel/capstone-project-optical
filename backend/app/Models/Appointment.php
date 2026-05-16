<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    protected $fillable = [
        'patient_id', 'optometrist_id', 'created_by',
        'appointment_date', 'type', 'status', 'reason', 'notes',
    ];

    protected $casts = ['appointment_date' => 'datetime'];

    public function patient() { return $this->belongsTo(Patient::class); }
    public function optometrist() { return $this->belongsTo(User::class, 'optometrist_id'); }
    public function createdBy() { return $this->belongsTo(User::class, 'created_by'); }
}
