<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    | Supported drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        /*
         * Task 2.9: task attachment files (App\Services\Execution\TaskService::addAttachmentFile())
         * write here — never web-accessible directly. Served only through
         * App\Http\Controllers\Eksekusi\AttachmentDownloadController, which
         * checks auth + project membership before streaming (same rule as
         * App\Livewire\Eksekusi\Tasks\Show::mount()).
         */
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => rtrim(env('APP_URL', 'http://localhost'), '/').'/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        /*
         * LEGACY — no longer written to. Briefly used (task 2.8) for task
         * attachment files, publicly servable with no `storage:link` symlink
         * needed (production host disables symlink() entirely). Superseded
         * by task 2.9: attachments now write to the private 'local' disk
         * above and are only ever served through
         * App\Http\Controllers\Eksekusi\AttachmentDownloadController (auth +
         * project-membership checked), because this disk had zero access
         * control — anyone with a file's URL could view it. Kept configured
         * only so AttachmentDownloadController's fallback can still read any
         * attachment that was uploaded during the brief 2.8-to-2.9 window
         * (none exist as of 2.9, confirmed live).
         */
        'storage_files' => [
            'driver' => 'local',
            'root' => public_path('storage_files'),
            'url' => rtrim(env('APP_URL', 'http://localhost'), '/').'/storage_files',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
            'report' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
