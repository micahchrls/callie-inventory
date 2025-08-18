<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Earrings',
                'description' => 'All types of earrings including studs, hoops, drops, and dangles',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Necklaces',
                'description' => 'Chains, chokers, pendants, and statement necklaces',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Rings',
                'description' => 'Engagement rings, wedding bands, fashion rings, and statement pieces',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Bracelets',
                'description' => 'Tennis bracelets, bangles, charm bracelets, and cuffs',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Pendants',
                'description' => 'Standalone pendants and charms for necklaces',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Watches',
                'description' => 'Luxury watches, fashion watches, and timepieces',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('product_categories')->insert($categories);
    }
}
