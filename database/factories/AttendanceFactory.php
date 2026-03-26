<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attendance>
 */
class AttendanceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $workDate = CarbonImmutable::now(config('app.timezone'))->startOfDay();
        $siteLatitude = (float) config('attendance.site.latitude', 14.5547);
        $siteLongitude = (float) config('attendance.site.longitude', 121.0244);

        return [
            'user_id' => User::factory(),
            'work_date' => $workDate->toDateString(),
            'clock_in_at' => $workDate->setTime(9, 0),
            'clock_out_at' => $workDate->setTime(18, 0),
            'clock_in_latitude' => $siteLatitude,
            'clock_in_longitude' => $siteLongitude,
            'clock_in_distance_meters' => 0,
            'clock_in_within_geofence' => true,
            'clock_in_photo_path' => 'attendance/photos/test-clock-in.jpg',
            'clock_out_latitude' => $siteLatitude,
            'clock_out_longitude' => $siteLongitude,
            'clock_out_distance_meters' => 0,
            'clock_out_within_geofence' => true,
            'clock_out_photo_path' => 'attendance/photos/test-clock-out.jpg',
            'timesheet_start_at' => null,
            'timesheet_end_at' => null,
            'timesheet_notes' => null,
        ];
    }

    public function open(): static
    {
        return $this->state(fn (): array => [
            'clock_out_at' => null,
            'clock_out_latitude' => null,
            'clock_out_longitude' => null,
            'clock_out_distance_meters' => null,
            'clock_out_within_geofence' => false,
            'clock_out_photo_path' => null,
        ]);
    }
}
