<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Prescription extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'patient_id', 'optometrist_id',
        'od_sphere', 'od_cylinder', 'od_axis', 'od_add', 'od_pd',
        'os_sphere', 'os_cylinder', 'os_axis', 'os_add', 'os_pd',
        'visual_acuity_od', 'visual_acuity_os', 'notes', 'exam_date', 'valid_until',
    ];

    protected $casts = ['exam_date' => 'date', 'valid_until' => 'date'];

    public function patient() { return $this->belongsTo(Patient::class); }
    public function optometrist() { return $this->belongsTo(User::class, 'optometrist_id'); }
}
