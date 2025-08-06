<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get category and subcategory IDs - using Earrings category and Stud Earrings subcategory for both products
        $earringsCategory = DB::table('product_categories')->where('name', 'Earrings')->first();
        $studEarrings = DB::table('product_sub_categories')->where('name', 'Stud Earrings')->first();

        $products = [
            [
                'name' => 'Diamond Solitaire Studs',
                'description' => 'Classic diamond solitaire stud earrings in 14k white gold setting',
                'product_category_id' => $earringsCategory->id,
                'product_sub_category_id' => $studEarrings->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Gold Pearl Studs',
                'description' => 'Elegant freshwater pearl stud earrings in 14k yellow gold setting',
                'product_category_id' => $earringsCategory->id,
                'product_sub_category_id' => $studEarrings->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('products')->insert($products);
    }
}
