<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Keep seeding minimal for import flows to avoid duplicate sample content.
        $this->call([
            AdminUserSeeder::class,
            DefaultPagesSeeder::class,
        ]);
    }
}
