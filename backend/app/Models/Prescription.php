<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

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

    /**
     * Control number: RX-<exam date YYYYMMDD>-<sequence of that exam date>, e.g.
     * RX-20260922-002 is the second prescription examined on Sep 22, 2026. It tells
     * apart visits on the same day (morning vs afternoon) and never changes once given.
     */
    protected static function booted(): void
    {
        static::creating(function (Prescription $rx) {
            if (!$rx->control_number && $rx->exam_date) {
                $rx->control_number = static::nextControlNumber($rx->exam_date);
            }
        });
    }

    public static function controlPrefix($examDate): string
    {
        return 'RX-' . Carbon::parse($examDate)->format('Ymd') . '-';
    }

    public static function nextControlNumber($examDate): string
    {
        $prefix = static::controlPrefix($examDate);
        $last   = DB::table('prescriptions')->where('control_number', 'like', $prefix . '%')
            ->orderByRaw('LENGTH(control_number) DESC')->orderByDesc('control_number')
            ->value('control_number');
        $seq = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;
        return $prefix . str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Numbers any prescriptions that have none yet (rows inserted directly, such as
     * by the seeders); within each exam date, in entry order. Returns how many were numbered.
     */
    public static function assignMissingControlNumbers(): int
    {
        $next  = [];
        $count = 0;
        // chunkById, not chunk: the rows being updated drop out of the whereNull filter,
        // and offset paging would then skip every other page.
        DB::table('prescriptions')->whereNull('control_number')->whereNotNull('exam_date')
            ->select('id', 'exam_date')
            ->chunkById(1000, function ($rows) use (&$next, &$count) {
                foreach ($rows as $row) {
                    $prefix = static::controlPrefix($row->exam_date);
                    if (!isset($next[$prefix])) {
                        $next[$prefix] = (int) substr(static::nextControlNumber($row->exam_date), strlen($prefix));
                    }
                    DB::table('prescriptions')->where('id', $row->id)->update([
                        'control_number' => $prefix . str_pad((string) $next[$prefix]++, 3, '0', STR_PAD_LEFT),
                    ]);
                    $count++;
                }
            });
        return $count;
    }
}
