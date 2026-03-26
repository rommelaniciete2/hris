<?php

namespace App\Http\Requests;

use App\Models\Attendance;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateAttendanceTimesheetRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $attendance = $this->route('attendance');

        if ($this->user() === null) {
            return false;
        }

        if (! $attendance instanceof Attendance) {
            return true;
        }

        return $attendance->user_id === $this->user()->id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'timesheet_start_at' => ['nullable', 'date'],
            'timesheet_end_at' => ['nullable', 'date'],
            'timesheet_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $attendance = $this->route('attendance');

                if (! $attendance instanceof Attendance || $attendance->work_date === null) {
                    return;
                }

                $timesheetStartAt = $validator->errors()->has('timesheet_start_at')
                    ? null
                    : $this->parseDate($this->input('timesheet_start_at'));
                $timesheetEndAt = $validator->errors()->has('timesheet_end_at')
                    ? null
                    : $this->parseDate($this->input('timesheet_end_at'));
                $workDate = $attendance->work_date->toDateString();

                if ($timesheetStartAt !== null && $timesheetStartAt->toDateString() !== $workDate) {
                    $validator->errors()->add('timesheet_start_at', 'The edited start time must stay on the attendance work date.');
                }

                if ($timesheetEndAt !== null && $timesheetEndAt->toDateString() !== $workDate) {
                    $validator->errors()->add('timesheet_end_at', 'The edited end time must stay on the attendance work date.');
                }

                if ($timesheetStartAt !== null && $timesheetEndAt !== null && $timesheetEndAt->lessThan($timesheetStartAt)) {
                    $validator->errors()->add('timesheet_end_at', 'The edited end time must be after the edited start time.');
                }
            },
        ];
    }

    private function parseDate(?string $value): ?CarbonImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }

        return CarbonImmutable::parse($value, config('app.timezone'));
    }
}
