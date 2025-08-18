<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSubCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get category IDs
        $earringsId = DB::table('product_categories')->where('name', 'Earrings')->first()->id;
        $necklacesId = DB::table('product_categories')->where('name', 'Necklaces')->first()->id;
        $ringsId = DB::table('product_categories')->where('name', 'Rings')->first()->id;
        $braceletsId = DB::table('product_categories')->where('name', 'Bracelets')->first()->id;
        $pendantsId = DB::table('product_categories')->where('name', 'Pendants')->first()->id;
        $watchesId = DB::table('product_categories')->where('name', 'Watches')->first()->id;

        $subCategories = [
            // Earrings subcategories
            [
                'product_category_id' => $earringsId,
                'name' => 'Stud Earrings',
                'description' => 'Classic stud earrings with various gemstones and metals',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'product_category_id' => $earringsId,
                'name' => 'Hoop Earrings',
                'description' => 'Circular hoop earrings in different sizes and styles',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'product_category_id' => $earringsId,
                'name' => 'Drop Earrings',
                'description' => 'Elegant drop and dangle earrings',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'product_category_id' => $earringsId,
                'name' => 'Chandelier Earrings',
                'description' => 'Statement chandelier and dramatic earrings',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Necklaces subcategories
            [
                'product_category_id' => $necklacesId,
                'name' => 'Chain Necklaces',
                'description' => 'Various chain styles including rope, box, and curb chains',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'product_category_id' => $necklacesId,
                'name' => 'Chokers',
                'description' => 'Short necklaces that sit close to the neck',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'product_category_id' => $necklacesId,
                'name' => 'Statement Necklaces',
                'description' => 'Bold and eye-catching statement pieces',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'product_category_id' => $necklacesId,
                'name' => 'Layering Necklaces',
                'description' => 'Delicate necklaces perfect for layering',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Rings subcategories
            [
                'product_category_id' => $ringsId,
                'name' => 'Engagement Rings',
                'description' => 'Diamond and gemstone engagement rings',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'product_category_id' => $ringsId,
                'name' => 'Wedding Bands',
                'description' => 'Classic and modern wedding bands',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'product_category_id' => $ringsId,
                'name' => 'Fashion Rings',
                'description' => 'Trendy and casual fashion rings',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'product_category_id' => $ringsId,
                'name' => 'Cocktail Rings',
                'description' => 'Large statement cocktail and dinner rings',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Bracelets subcategories
            [
                'product_category_id' => $braceletsId,
                'name' => 'Tennis Bracelets',
                'description' => 'Classic tennis bracelets with continuous gemstones',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'product_category_id' => $braceletsId,
                'name' => 'Bangles',
                'description' => 'Rigid circular bracelets and bangles',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'product_category_id' => $braceletsId,
                'name' => 'Charm Bracelets',
                'description' => 'Bracelets designed to hold charms and pendants',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'product_category_id' => $braceletsId,
                'name' => 'Cuff Bracelets',
                'description' => 'Open-ended cuff style bracelets',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Pendants subcategories
            [
                'product_category_id' => $pendantsId,
                'name' => 'Gemstone Pendants',
                'description' => 'Pendants featuring precious and semi-precious stones',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'product_category_id' => $pendantsId,
                'name' => 'Symbol Pendants',
                'description' => 'Pendants with religious, spiritual, or meaningful symbols',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'product_category_id' => $pendantsId,
                'name' => 'Initial Pendants',
                'description' => 'Personalized letter and initial pendants',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Watches subcategories
            [
                'product_category_id' => $watchesId,
                'name' => 'Luxury Watches',
                'description' => 'High-end luxury timepieces and designer watches',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'product_category_id' => $watchesId,
                'name' => 'Fashion Watches',
                'description' => 'Trendy and affordable fashion watches',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'product_category_id' => $watchesId,
                'name' => 'Sport Watches',
                'description' => 'Durable watches designed for active lifestyles',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('product_sub_categories')->insert($subCategories);
    }
}
