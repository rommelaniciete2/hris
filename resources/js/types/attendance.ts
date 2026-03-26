export type AttendanceSiteConfig = {
    name: string;
    latitude: number;
    longitude: number;
    radiusMeters: number;
};

export type AttendanceShiftConfig = {
    startTime: string;
    endTime: string;
    label: string;
};

export type AttendanceSummaryStat = {
    label: string;
    value: number;
    description: string;
};

export type AttendanceClockState = {
    status: string;
    title: string;
    description: string;
    canClockIn: boolean;
    canClockOut: boolean;
    hasOpenAttendance: boolean;
    lastActionLabel: string | null;
    lastActionAt: string | null;
};

export type AttendanceTodayRecord = {
    id: number;
    workDate: string | null;
    workDateLabel: string | null;
    status: string;
    rawClockIn: string | null;
    rawClockOut: string | null;
    effectiveClockIn: string | null;
    effectiveClockOut: string | null;
    clockInDistance: string | null;
    clockOutDistance: string | null;
    clockInWithinGeofence: boolean | null;
    clockOutWithinGeofence: boolean | null;
    clockInPhotoCaptured: boolean;
    clockOutPhotoCaptured: boolean;
    timesheetNotes: string | null;
    lateMinutes: number;
    undertimeMinutes: number;
    overtimeMinutes: number;
    hasTimesheetOverrides: boolean;
};

export type AttendanceHistoryItem = {
    id: number;
    workDate: string | null;
    workDateLabel: string | null;
    status: string;
    rawClockIn: string | null;
    rawClockOut: string | null;
    lateMinutes: number;
    undertimeMinutes: number;
    overtimeMinutes: number;
    hasTimesheetOverrides: boolean;
};

export type AttendanceTimesheetEntry = AttendanceHistoryItem & {
    effectiveClockIn: string | null;
    effectiveClockOut: string | null;
    editableStartAt: string;
    editableEndAt: string;
    timesheetNotes: string | null;
};

export type AttendanceTimesheetsFilters = {
    month: string;
    monthLabel: string;
};

export type AttendanceIndexPageProps = {
    site: AttendanceSiteConfig;
    shift: AttendanceShiftConfig;
    today: AttendanceTodayRecord | null;
    clockState: AttendanceClockState;
    recentAttendances: AttendanceHistoryItem[];
    summary: AttendanceSummaryStat[];
};

export type AttendanceTimesheetsPageProps = {
    site: AttendanceSiteConfig;
    shift: AttendanceShiftConfig;
    filters: AttendanceTimesheetsFilters;
    entries: AttendanceTimesheetEntry[];
    summary: AttendanceSummaryStat[];
};
