<?php

return [
    'admin' => [
        'name' => env('ADMIN_NAME', 'Admin User'),
        'email' => env('ADMIN_EMAIL', 'admin@admin.com'),
        'password' => env('ADMIN_PASSWORD', 'password123'),
        'role' => env('ADMIN_ROLE', 'admin'),
        'status' => env('ADMIN_STATUS', 'active'),
    ],
];
