<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttendancePunchRequest;
use App\Http\Requests\UpdateAttendanceTimesheetRequest;
use App\Models\Attendance;
use App\Models\User;
use App\Services\AttendanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceController extends Controller
{
    public function __construct(
        private AttendanceService $attendanceService,
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('attendance/Index', $this->attendanceService->dashboard(
            $this->authenticatedUser($request),
        ));
    }

    public function clockIn(AttendancePunchRequest $request): RedirectResponse
    {
        $this->attendanceService->clockIn(
            $this->authenticatedUser($request),
            $request->validated(),
        );

        return to_route('attendance.index');
    }

    public function clockOut(AttendancePunchRequest $request): RedirectResponse
    {
        $this->attendanceService->clockOut(
            $this->authenticatedUser($request),
            $request->validated(),
        );

        return to_route('attendance.index');
    }

    public function timesheets(Request $request): Response
    {
        return Inertia::render('attendance/Timesheets', $this->attendanceService->timesheets(
            $this->authenticatedUser($request),
            trim((string) $request->query('month', '')),
        ));
    }

    public function updateTimesheet(UpdateAttendanceTimesheetRequest $request, Attendance $attendance): RedirectResponse
    {
        abort_if($attendance->user_id !== $this->authenticatedUser($request)->id, 403);

        $this->attendanceService->updateTimesheet($attendance, $request->validated());

        return to_route('attendance.timesheets', [
            'month' => $attendance->work_date?->format('Y-m'),
        ]);
    }

    private function authenticatedUser(Request $request): User
    {
        $user = $request->user();

        abort_unless($user instanceof User, 403);

        return $user;
    }
}
