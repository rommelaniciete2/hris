<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\AttendanceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'work_date',
    'clock_in_at',
    'clock_out_at',
    'clock_in_latitude',
    'clock_in_longitude',
    'clock_in_distance_meters',
    'clock_in_within_geofence',
    'clock_in_photo_path',
    'clock_out_latitude',
    'clock_out_longitude',
    'clock_out_distance_meters',
    'clock_out_within_geofence',
    'clock_out_photo_path',
    'timesheet_start_at',
    'timesheet_end_at',
    'timesheet_notes',
])]
class Attendance extends Model
{
    /** @use HasFactory<AttendanceFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'work_date' => 'date',
            'clock_in_at' => 'datetime',
            'clock_out_at' => 'datetime',
            'clock_in_latitude' => 'decimal:7',
            'clock_in_longitude' => 'decimal:7',
            'clock_in_within_geofence' => 'boolean',
            'clock_out_latitude' => 'decimal:7',
            'clock_out_longitude' => 'decimal:7',
            'clock_out_within_geofence' => 'boolean',
            'timesheet_start_at' => 'datetime',
            'timesheet_end_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForUser(Builder $query, User $user): void
    {
        $query->whereBelongsTo($user);
    }

    public function scopeForMonth(Builder $query, CarbonImmutable $month): void
    {
        $query->whereBetween('work_date', [
            $month->startOfMonth()->toDateString(),
            $month->endOfMonth()->toDateString(),
        ]);
    }

    public function effectiveClockIn(): ?CarbonImmutable
    {
        return $this->timesheet_start_at ?? $this->clock_in_at;
    }

    public function effectiveClockOut(): ?CarbonImmutable
    {
        return $this->timesheet_end_at ?? $this->clock_out_at;
    }
}
