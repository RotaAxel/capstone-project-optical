<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Appointment extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'patient_id', 'optometrist_id', 'created_by',
        'appointment_month', 'appointment_year', 'type', 'status', 'reason', 'notes',
    ];

    protected $casts = [
        'appointment_month' => 'integer',
        'appointment_year'  => 'integer',
    ];

    protected $appends = ['appointment_label'];

    const MONTH_NAMES = [
        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
        5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
        9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
    ];

    /** e.g. "March 2026" — for display; sorting/filtering always uses the raw year+month columns. */
    public function getAppointmentLabelAttribute(): string
    {
        $name = self::MONTH_NAMES[$this->appointment_month] ?? $this->appointment_month;
        return "{$name} {$this->appointment_year}";
    }

    public function patient() { return $this->belongsTo(Patient::class); }
    public function optometrist() { return $this->belongsTo(User::class, 'optometrist_id'); }
    public function createdBy() { return $this->belongsTo(User::class, 'created_by'); }
}
