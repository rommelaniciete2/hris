<?php

namespace Tests\Feature\Attendance;

use App\Models\Attendance;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AttendanceManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->configureAttendance();
        Storage::fake('public');
    }

    protected function tearDown(): void
    {
        $this->travelBack();

        parent::tearDown();
    }

    public function test_attendance_routes_require_authentication()
    {
        $this->get(route('attendance.index'))->assertRedirect(route('login'));
        $this->get(route('attendance.timesheets'))->assertRedirect(route('login'));
        $this->post(route('attendance.clock-in'))->assertRedirect(route('login'));
        $this->post(route('attendance.clock-out'))->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_view_the_attendance_dashboard()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('attendance.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('attendance/Index')
                ->where('site.name', 'Makati HQ')
                ->where('shift.label', '09:00 AM to 06:00 PM')
                ->where('clockState.canClockIn', true)
                ->where('clockState.canClockOut', false)
                ->where('today', null)
                ->has('summary', 4)
                ->has('recentAttendances', 0),
            );
    }

    public function test_authenticated_users_can_view_the_timesheets_page()
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->for($user)->create([
            'work_date' => '2026-03-25',
        ]);

        $this->actingAs($user)
            ->get(route('attendance.timesheets', ['month' => '2026-03']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('attendance/Timesheets')
                ->where('filters.month', '2026-03')
                ->where('filters.monthLabel', 'March 2026')
                ->has('entries', 1)
                ->where('entries.0.id', $attendance->id)
                ->where('entries.0.status', 'Complete')
                ->has('summary', 4),
            );
    }

    public function test_clock_in_succeeds_inside_the_geofence_with_a_face_photo()
    {
        $this->travelTo(CarbonImmutable::parse('2026-03-25 08:55:00'));

        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('attendance.clock-in'), $this->punchPayload())
            ->assertRedirect(route('attendance.index'));

        $attendance = Attendance::query()->first();

        $this->assertModelExists($attendance);
        $this->assertSame($user->id, $attendance->user_id);
        $this->assertSame('2026-03-25', $attendance->work_date?->toDateString());
        $this->assertTrue($attendance->clock_in_within_geofence);
        $this->assertNull($attendance->clock_out_at);
        Storage::disk('public')->assertExists((string) $attendance->clock_in_photo_path);
    }

    public function test_clock_in_is_rejected_outside_the_geofence()
    {
        $this->travelTo(CarbonImmutable::parse('2026-03-25 08:55:00'));

        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('attendance.index'))
            ->post(route('attendance.clock-in'), $this->punchPayload([
                'latitude' => 14.5657,
            ]))
            ->assertRedirect(route('attendance.index'))
            ->assertSessionHasErrors('location');

        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_clock_out_requires_an_open_attendance_record_for_today()
    {
        $this->travelTo(CarbonImmutable::parse('2026-03-25 18:05:00'));

        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('attendance.index'))
            ->post(route('attendance.clock-out'), $this->punchPayload())
            ->assertRedirect(route('attendance.index'))
            ->assertSessionHasErrors('clock');
    }

    public function test_clock_out_succeeds_for_an_open_attendance_record_inside_the_geofence()
    {
        $this->travelTo(CarbonImmutable::parse('2026-03-25 18:05:00'));

        $user = User::factory()->create();
        $attendance = Attendance::factory()
            ->open()
            ->for($user)
            ->create([
                'work_date' => '2026-03-25',
                'clock_in_at' => CarbonImmutable::parse('2026-03-25 09:02:00'),
            ]);

        $this->actingAs($user)
            ->post(route('attendance.clock-out'), $this->punchPayload())
            ->assertRedirect(route('attendance.index'));

        $attendance->refresh();

        $this->assertNotNull($attendance->clock_out_at);
        $this->assertTrue($attendance->clock_out_within_geofence);
        Storage::disk('public')->assertExists((string) $attendance->clock_out_photo_path);
    }

    public function test_user_cannot_clock_in_twice_for_the_same_day()
    {
        $this->travelTo(CarbonImmutable::parse('2026-03-25 10:00:00'));

        $user = User::factory()->create();

        Attendance::factory()
            ->open()
            ->for($user)
            ->create([
                'work_date' => '2026-03-25',
                'clock_in_at' => CarbonImmutable::parse('2026-03-25 09:05:00'),
            ]);

        $this->actingAs($user)
            ->from(route('attendance.index'))
            ->post(route('attendance.clock-in'), $this->punchPayload())
            ->assertRedirect(route('attendance.index'))
            ->assertSessionHasErrors('clock');

        $this->assertDatabaseCount('attendances', 1);
    }

    public function test_timesheet_updates_save_without_overwriting_raw_punch_times()
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->for($user)->create([
            'work_date' => '2026-03-25',
            'clock_in_at' => CarbonImmutable::parse('2026-03-25 09:05:00'),
            'clock_out_at' => CarbonImmutable::parse('2026-03-25 17:55:00'),
        ]);

        $this->actingAs($user)
            ->patch(route('attendance.timesheets.update', $attendance), [
                'timesheet_start_at' => '2026-03-25T09:15',
                'timesheet_end_at' => '2026-03-25T18:20',
                'timesheet_notes' => 'Manual correction after a delayed device sync.',
            ])
            ->assertRedirect(route('attendance.timesheets', ['month' => '2026-03']));

        $attendance->refresh();

        $this->assertSame('2026-03-25 09:05:00', $attendance->clock_in_at?->format('Y-m-d H:i:s'));
        $this->assertSame('2026-03-25 17:55:00', $attendance->clock_out_at?->format('Y-m-d H:i:s'));
        $this->assertSame('2026-03-25 09:15:00', $attendance->timesheet_start_at?->format('Y-m-d H:i:s'));
        $this->assertSame('2026-03-25 18:20:00', $attendance->timesheet_end_at?->format('Y-m-d H:i:s'));
        $this->assertSame('Manual correction after a delayed device sync.', $attendance->timesheet_notes);
    }

    public function test_timesheet_summary_uses_the_effective_timesheet_values()
    {
        $user = User::factory()->create();

        Attendance::factory()->for($user)->create([
            'work_date' => '2026-03-25',
            'clock_in_at' => CarbonImmutable::parse('2026-03-25 09:05:00'),
            'clock_out_at' => CarbonImmutable::parse('2026-03-25 17:40:00'),
            'timesheet_start_at' => CarbonImmutable::parse('2026-03-25 09:15:00'),
            'timesheet_end_at' => CarbonImmutable::parse('2026-03-25 18:30:00'),
        ]);

        $this->actingAs($user)
            ->get(route('attendance.timesheets', ['month' => '2026-03']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('attendance/Timesheets')
                ->where('entries.0.lateMinutes', 15)
                ->where('entries.0.undertimeMinutes', 0)
                ->where('entries.0.overtimeMinutes', 30)
                ->where('summary.0.value', 1)
                ->where('summary.1.value', 15)
                ->where('summary.2.value', 0)
                ->where('summary.3.value', 30),
            );
    }

    private function configureAttendance(): void
    {
        config()->set('attendance.site.name', 'Makati HQ');
        config()->set('attendance.site.latitude', 14.5547);
        config()->set('attendance.site.longitude', 121.0244);
        config()->set('attendance.site.radius_meters', 100);
        config()->set('attendance.shift.start_time', '09:00');
        config()->set('attendance.shift.end_time', '18:00');
        config()->set('attendance.photos_disk', 'public');
        config()->set('attendance.photos_directory', 'attendance/photos');
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function punchPayload(array $overrides = []): array
    {
        return array_merge([
            'captured_at' => CarbonImmutable::now()->toIso8601String(),
            'latitude' => 14.5547,
            'longitude' => 121.0244,
            'photo' => UploadedFile::fake()->image('face.jpg'),
        ], $overrides);
    }
}
