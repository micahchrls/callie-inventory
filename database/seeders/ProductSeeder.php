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
        // Get category and subcategory IDs
        $earringsCategory = DB::table('product_categories')->where('name', 'Earrings')->first();
        $necklacesCategory = DB::table('product_categories')->where('name', 'Necklaces')->first();
        $ringsCategory = DB::table('product_categories')->where('name', 'Rings')->first();
        $braceletsCategory = DB::table('product_categories')->where('name', 'Bracelets')->first();
        $pendantsCategory = DB::table('product_categories')->where('name', 'Pendants')->first();
        $watchesCategory = DB::table('product_categories')->where('name', 'Watches')->first();

        // Get subcategory IDs
        $studEarrings = DB::table('product_sub_categories')->where('name', 'Stud Earrings')->first();
        $hoopEarrings = DB::table('product_sub_categories')->where('name', 'Hoop Earrings')->first();
        $dropEarrings = DB::table('product_sub_categories')->where('name', 'Drop Earrings')->first();

        $chainNecklaces = DB::table('product_sub_categories')->where('name', 'Chain Necklaces')->first();
        $chokers = DB::table('product_sub_categories')->where('name', 'Chokers')->first();
        $statementNecklaces = DB::table('product_sub_categories')->where('name', 'Statement Necklaces')->first();

        $engagementRings = DB::table('product_sub_categories')->where('name', 'Engagement Rings')->first();
        $weddingBands = DB::table('product_sub_categories')->where('name', 'Wedding Bands')->first();
        $fashionRings = DB::table('product_sub_categories')->where('name', 'Fashion Rings')->first();

        $tennisBracelets = DB::table('product_sub_categories')->where('name', 'Tennis Bracelets')->first();
        $bangles = DB::table('product_sub_categories')->where('name', 'Bangles')->first();
        $charmBracelets = DB::table('product_sub_categories')->where('name', 'Charm Bracelets')->first();

        $gemstonePendants = DB::table('product_sub_categories')->where('name', 'Gemstone Pendants')->first();
        $symbolPendants = DB::table('product_sub_categories')->where('name', 'Symbol Pendants')->first();

        $luxuryWatches = DB::table('product_sub_categories')->where('name', 'Luxury Watches')->first();
        $fashionWatches = DB::table('product_sub_categories')->where('name', 'Fashion Watches')->first();

        $products = [
            // Earrings
            [
                'name' => 'Diamond Solitaire Studs',
                'description' => 'Classic 1ct diamond solitaire stud earrings in 14k white gold setting',
                'product_category_id' => $earringsCategory->id,
                'product_sub_category_id' => $studEarrings->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Pearl Stud Earrings',
                'description' => 'Elegant 8mm freshwater pearl stud earrings with sterling silver posts',
                'product_category_id' => $earringsCategory->id,
                'product_sub_category_id' => $studEarrings->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Gold Hoop Earrings',
                'description' => '30mm 14k yellow gold classic hoop earrings with hinged closure',
                'product_category_id' => $earringsCategory->id,
                'product_sub_category_id' => $hoopEarrings->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Sapphire Drop Earrings',
                'description' => 'Blue sapphire and diamond drop earrings in 18k white gold',
                'product_category_id' => $earringsCategory->id,
                'product_sub_category_id' => $dropEarrings->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Necklaces
            [
                'name' => 'Gold Chain Necklace',
                'description' => '18 inch 14k yellow gold rope chain necklace, 3mm wide',
                'product_category_id' => $necklacesCategory->id,
                'product_sub_category_id' => $chainNecklaces->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Diamond Choker',
                'description' => 'Delicate diamond choker necklace with 0.5ct total weight diamonds',
                'product_category_id' => $necklacesCategory->id,
                'product_sub_category_id' => $chokers->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Ruby Statement Necklace',
                'description' => 'Bold ruby and diamond statement necklace in 18k yellow gold',
                'product_category_id' => $necklacesCategory->id,
                'product_sub_category_id' => $statementNecklaces->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Rings
            [
                'name' => 'Classic Solitaire Engagement Ring',
                'description' => '1.5ct round diamond solitaire engagement ring in platinum setting',
                'product_category_id' => $ringsCategory->id,
                'product_sub_category_id' => $engagementRings->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Vintage Halo Engagement Ring',
                'description' => 'Vintage-inspired halo engagement ring with 1ct center diamond',
                'product_category_id' => $ringsCategory->id,
                'product_sub_category_id' => $engagementRings->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Classic Wedding Band',
                'description' => '3mm 14k white gold classic wedding band with comfort fit',
                'product_category_id' => $ringsCategory->id,
                'product_sub_category_id' => $weddingBands->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Emerald Fashion Ring',
                'description' => 'Emerald cut emerald fashion ring with diamond accents in 14k gold',
                'product_category_id' => $ringsCategory->id,
                'product_sub_category_id' => $fashionRings->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Bracelets
            [
                'name' => 'Diamond Tennis Bracelet',
                'description' => '2ct diamond tennis bracelet in 14k white gold, 7 inch length',
                'product_category_id' => $braceletsCategory->id,
                'product_sub_category_id' => $tennisBracelets->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Gold Bangle Set',
                'description' => 'Set of three 14k yellow gold bangles with textured finish',
                'product_category_id' => $braceletsCategory->id,
                'product_sub_category_id' => $bangles->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Sterling Silver Charm Bracelet',
                'description' => 'Sterling silver charm bracelet with five initial charms included',
                'product_category_id' => $braceletsCategory->id,
                'product_sub_category_id' => $charmBracelets->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Pendants
            [
                'name' => 'Amethyst Pendant',
                'description' => 'Large oval amethyst pendant in sterling silver setting with chain',
                'product_category_id' => $pendantsCategory->id,
                'product_sub_category_id' => $gemstonePendants->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Cross Pendant',
                'description' => '14k gold cross pendant with diamond accents and 18 inch chain',
                'product_category_id' => $pendantsCategory->id,
                'product_sub_category_id' => $symbolPendants->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Watches
            [
                'name' => 'Swiss Automatic Watch',
                'description' => 'Luxury Swiss automatic watch with leather strap and sapphire crystal',
                'product_category_id' => $watchesCategory->id,
                'product_sub_category_id' => $luxuryWatches->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Rose Gold Fashion Watch',
                'description' => 'Trendy rose gold fashion watch with mesh bracelet and crystal markers',
                'product_category_id' => $watchesCategory->id,
                'product_sub_category_id' => $fashionWatches->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('products')->insert($products);
    }
}
