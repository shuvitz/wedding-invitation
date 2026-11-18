<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Media Disk
    |--------------------------------------------------------------------------
    |
    | The filesystem disk new uploads are written to. V1 targets shared
    | hosting (e.g. Hostinger) via Laravel's local "public" disk, served
    | through the standard storage:link symlink. Every Media row records
    | its own `disk`, so existing files keep resolving correctly even if
    | this value changes later (e.g. to an S3-backed disk) — only new
    | uploads are affected.
    |
    */

    'disk' => env('MEDIA_DISK', 'public'),

];
