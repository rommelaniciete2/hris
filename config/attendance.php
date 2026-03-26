<?php

return [
    'site' => [
        'name' => env('ATTENDANCE_SITE_NAME', 'Makati HQ'),
        'latitude' => (float) env('ATTENDANCE_SITE_LATITUDE', 14.5547),
        'longitude' => (float) env('ATTENDANCE_SITE_LONGITUDE', 121.0244),
        'radius_meters' => (int) env('ATTENDANCE_SITE_RADIUS_METERS', 100000),
    ],
    'shift' => [
        'start_time' => env('ATTENDANCE_SHIFT_START', '09:00'),
        'end_time' => env('ATTENDANCE_SHIFT_END', '18:00'),
    ],
    'photos_disk' => env('ATTENDANCE_PHOTOS_DISK', 'public'),
    'photos_directory' => env('ATTENDANCE_PHOTOS_DIRECTORY', 'attendance/photos'),
];
