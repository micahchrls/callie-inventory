<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlatformSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('platforms')->insert([
            [
                'name' => 'TikTok',
                'description' => 'TikTok Shop - Social commerce platform for short-form video content',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Shopee',
                'description' => 'Shopee - Leading e-commerce platform in Southeast Asia',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Bazar',
                'description' => 'Bazar',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }
}
