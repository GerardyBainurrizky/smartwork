<?php

return [
    'late_threshold' => env('ATTENDANCE_LATE_THRESHOLD', '08:00'),
    'clock_in_start' => env('ATTENDANCE_CLOCK_IN_START', '05:00'),
    'clock_in_end' => env('ATTENDANCE_CLOCK_IN_END', '10:00'),
    'clock_out_start' => env('ATTENDANCE_CLOCK_OUT_START', '15:00'),
    'clock_out_end' => env('ATTENDANCE_CLOCK_OUT_END', '22:00'),
    'max_selfie_size' => env('ATTENDANCE_MAX_SELFIE_SIZE', 5120), // KB
    'gps_accuracy_threshold' => env('ATTENDANCE_GPS_ACCURACY', 50), // meters
];