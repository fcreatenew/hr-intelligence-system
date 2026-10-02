<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Jam kerja
    |--------------------------------------------------------------------------
    |
    | Dipakai untuk menurunkan menit terlambat dan menit lembur dari jam
    | masuk/keluar hasil impor mesin absensi. Di luar rentang ini dianggap
    | lembur (setelah `work_end`) atau terlambat (setelah `work_start`).
    |
    */

    'work_start' => env('ATTENDANCE_WORK_START', '09:00'),

    'work_end' => env('ATTENDANCE_WORK_END', '17:00'),

];
