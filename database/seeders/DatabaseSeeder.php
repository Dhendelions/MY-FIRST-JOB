<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     * Menjalankan seeder data demo untuk pengujian dan demonstrasi fitur.
     * Seeder bersifat idempotent — aman dijalankan berulang kali.
     */
    public function run(): void
    {
        $this->call([
            DemoSeeder::class,
        ]);
    }
}
