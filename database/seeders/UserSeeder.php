<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\User::create([
            'name' => 'Admin User',
            'email' => 'admin@technewshub.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'status' => 'active',
            'bio' => 'Chief Editor at TechNewsHub. Passionate about technology and innovation.',
            'email_verified_at' => now(),
        ]);

        \App\Models\User::create([
            'name' => 'John Editor',
            'email' => 'editor@technewshub.com',
            'password' => bcrypt('password'),
            'role' => 'editor',
            'status' => 'active',
            'bio' => 'Senior Editor with 10+ years of experience in tech journalism.',
            'email_verified_at' => now(),
        ]);

        \App\Models\User::create([
            'name' => 'Jane Author',
            'email' => 'author@technewshub.com',
            'password' => bcrypt('password'),
            'role' => 'author',
            'status' => 'active',
            'bio' => 'Technology writer and programmer focusing on web development.',
            'email_verified_at' => now(),
        ]);
    }
}
