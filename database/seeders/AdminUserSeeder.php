<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $email = (string) config('seeding.admin.email', 'admin@admin.com');
        $name = (string) config('seeding.admin.name', 'Admin User');
        $password = (string) config('seeding.admin.password', 'password123');
        $role = (string) config('seeding.admin.role', 'admin');
        $status = (string) config('seeding.admin.status', 'active');

        User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
                'role' => $role,
                'status' => $status,
            ]
        );
    }
}
