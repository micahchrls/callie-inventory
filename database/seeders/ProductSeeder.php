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
                'sku' => 'DSS-001',
                'quantity_in_stock' => 15,
                'reorder_level' => 5,
                'location' => 'Vault A-1',
                'status' => 'in_stock',
                'notes' => 'Premium diamond quality, certified stones',
                'is_active' => true,
                'last_restocked_at' => now()->subDays(10),
                'product_category_id' => $earringsCategory->id,
                'product_sub_category_id' => $studEarrings->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Pearl Stud Earrings',
                'description' => 'Elegant 8mm freshwater pearl stud earrings with sterling silver posts',
                'sku' => 'PSE-002',
                'quantity_in_stock' => 25,
                'reorder_level' => 10,
                'location' => 'Display B-2',
                'status' => 'in_stock',
                'notes' => 'AAA quality freshwater pearls',
                'is_active' => true,
                'last_restocked_at' => now()->subDays(5),
                'product_category_id' => $earringsCategory->id,
                'product_sub_category_id' => $studEarrings->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Gold Hoop Earrings',
                'description' => '30mm 14k yellow gold classic hoop earrings with hinged closure',
                'sku' => 'GHE-003',
                'quantity_in_stock' => 8,
                'reorder_level' => 5,
                'location' => 'Display A-3',
                'status' => 'in_stock',
                'notes' => 'Popular everyday style',
                'is_active' => true,
                'last_restocked_at' => now()->subDays(15),
                'product_category_id' => $earringsCategory->id,
                'product_sub_category_id' => $hoopEarrings->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Sapphire Drop Earrings',
                'description' => 'Blue sapphire and diamond drop earrings in 18k white gold',
                'sku' => 'SDE-004',
                'quantity_in_stock' => 3,
                'reorder_level' => 3,
                'location' => 'Vault A-2',
                'status' => 'low_stock',
                'notes' => 'Ceylon sapphires, special order available',
                'is_active' => true,
                'last_restocked_at' => now()->subDays(30),
                'product_category_id' => $earringsCategory->id,
                'product_sub_category_id' => $dropEarrings->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Necklaces
            [
                'name' => 'Gold Chain Necklace',
                'description' => '18 inch 14k yellow gold rope chain necklace, 3mm wide',
                'sku' => 'GCN-005',
                'quantity_in_stock' => 12,
                'reorder_level' => 6,
                'location' => 'Display C-1',
                'status' => 'in_stock',
                'notes' => 'Available in 16", 18", and 20" lengths',
                'is_active' => true,
                'last_restocked_at' => now()->subDays(8),
                'product_category_id' => $necklacesCategory->id,
                'product_sub_category_id' => $chainNecklaces->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Diamond Choker',
                'description' => 'Delicate diamond choker necklace with 0.5ct total weight diamonds',
                'sku' => 'DCH-006',
                'quantity_in_stock' => 6,
                'reorder_level' => 4,
                'location' => 'Vault B-1',
                'status' => 'in_stock',
                'notes' => 'Adjustable length 14-16 inches',
                'is_active' => true,
                'last_restocked_at' => now()->subDays(12),
                'product_category_id' => $necklacesCategory->id,
                'product_sub_category_id' => $chokers->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Ruby Statement Necklace',
                'description' => 'Bold ruby and diamond statement necklace in 18k yellow gold',
                'sku' => 'RSN-007',
                'quantity_in_stock' => 2,
                'reorder_level' => 2,
                'location' => 'Vault A-3',
                'status' => 'low_stock',
                'notes' => 'Designer piece, limited availability',
                'is_active' => true,
                'last_restocked_at' => now()->subDays(45),
                'product_category_id' => $necklacesCategory->id,
                'product_sub_category_id' => $statementNecklaces->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Rings
            [
                'name' => 'Classic Solitaire Engagement Ring',
                'description' => '1.5ct round diamond solitaire engagement ring in platinum setting',
                'sku' => 'CSE-008',
                'quantity_in_stock' => 0,
                'reorder_level' => 2,
                'location' => 'Vault A-4',
                'status' => 'out_of_stock',
                'notes' => 'Made to order, 2-3 week delivery',
                'is_active' => true,
                'last_restocked_at' => now()->subDays(60),
                'product_category_id' => $ringsCategory->id,
                'product_sub_category_id' => $engagementRings->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Vintage Halo Engagement Ring',
                'description' => 'Vintage-inspired halo engagement ring with 1ct center diamond',
                'sku' => 'VHE-009',
                'quantity_in_stock' => 4,
                'reorder_level' => 3,
                'location' => 'Vault B-2',
                'status' => 'in_stock',
                'notes' => 'Art deco inspired design',
                'is_active' => true,
                'last_restocked_at' => now()->subDays(20),
                'product_category_id' => $ringsCategory->id,
                'product_sub_category_id' => $engagementRings->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Classic Wedding Band',
                'description' => '3mm 14k white gold classic wedding band with comfort fit',
                'sku' => 'CWB-010',
                'quantity_in_stock' => 18,
                'reorder_level' => 8,
                'location' => 'Display B-3',
                'status' => 'in_stock',
                'notes' => 'Available in sizes 4-13',
                'is_active' => true,
                'last_restocked_at' => now()->subDays(7),
                'product_category_id' => $ringsCategory->id,
                'product_sub_category_id' => $weddingBands->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Emerald Fashion Ring',
                'description' => 'Emerald cut emerald fashion ring with diamond accents in 14k gold',
                'sku' => 'EFR-011',
                'quantity_in_stock' => 5,
                'reorder_level' => 4,
                'location' => 'Display C-2',
                'status' => 'in_stock',
                'notes' => 'Natural Colombian emerald',
                'is_active' => true,
                'last_restocked_at' => now()->subDays(25),
                'product_category_id' => $ringsCategory->id,
                'product_sub_category_id' => $fashionRings->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Bracelets
            [
                'name' => 'Diamond Tennis Bracelet',
                'description' => '2ct diamond tennis bracelet in 14k white gold, 7 inch length',
                'sku' => 'DTB-012',
                'quantity_in_stock' => 7,
                'reorder_level' => 4,
                'location' => 'Vault B-3',
                'status' => 'in_stock',
                'notes' => 'Safety clasp included',
                'is_active' => true,
                'last_restocked_at' => now()->subDays(18),
                'product_category_id' => $braceletsCategory->id,
                'product_sub_category_id' => $tennisBracelets->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Gold Bangle Set',
                'description' => 'Set of three 14k yellow gold bangles with textured finish',
                'sku' => 'GBS-013',
                'quantity_in_stock' => 9,
                'reorder_level' => 5,
                'location' => 'Display C-3',
                'status' => 'in_stock',
                'notes' => 'Stackable design, sold as set',
                'is_active' => true,
                'last_restocked_at' => now()->subDays(14),
                'product_category_id' => $braceletsCategory->id,
                'product_sub_category_id' => $bangles->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Sterling Silver Charm Bracelet',
                'description' => 'Sterling silver charm bracelet with five initial charms included',
                'sku' => 'SCB-014',
                'quantity_in_stock' => 14,
                'reorder_level' => 8,
                'location' => 'Display D-1',
                'status' => 'in_stock',
                'notes' => 'Additional charms available separately',
                'is_active' => true,
                'last_restocked_at' => now()->subDays(6),
                'product_category_id' => $braceletsCategory->id,
                'product_sub_category_id' => $charmBracelets->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Pendants
            [
                'name' => 'Amethyst Pendant',
                'description' => 'Large oval amethyst pendant in sterling silver setting with chain',
                'sku' => 'AMP-015',
                'quantity_in_stock' => 11,
                'reorder_level' => 6,
                'location' => 'Display D-2',
                'status' => 'in_stock',
                'notes' => 'Includes 18" silver chain',
                'is_active' => true,
                'last_restocked_at' => now()->subDays(22),
                'product_category_id' => $pendantsCategory->id,
                'product_sub_category_id' => $gemstonePendants->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Cross Pendant',
                'description' => '14k gold cross pendant with diamond accents and 18 inch chain',
                'sku' => 'CRP-016',
                'quantity_in_stock' => 6,
                'reorder_level' => 4,
                'location' => 'Display D-3',
                'status' => 'in_stock',
                'notes' => 'Religious jewelry collection',
                'is_active' => true,
                'last_restocked_at' => now()->subDays(16),
                'product_category_id' => $pendantsCategory->id,
                'product_sub_category_id' => $symbolPendants->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Watches
            [
                'name' => 'Swiss Automatic Watch',
                'description' => 'Luxury Swiss automatic watch with leather strap and sapphire crystal',
                'sku' => 'SAW-017',
                'quantity_in_stock' => 3,
                'reorder_level' => 2,
                'location' => 'Vault C-1',
                'status' => 'low_stock',
                'notes' => 'Swiss movement, 2 year warranty',
                'is_active' => true,
                'last_restocked_at' => now()->subDays(35),
                'product_category_id' => $watchesCategory->id,
                'product_sub_category_id' => $luxuryWatches->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Rose Gold Fashion Watch',
                'description' => 'Trendy rose gold fashion watch with mesh bracelet and crystal markers',
                'sku' => 'RGW-018',
                'quantity_in_stock' => 20,
                'reorder_level' => 10,
                'location' => 'Display E-1',
                'status' => 'in_stock',
                'notes' => 'Popular gift item',
                'is_active' => true,
                'last_restocked_at' => now()->subDays(4),
                'product_category_id' => $watchesCategory->id,
                'product_sub_category_id' => $fashionWatches->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('products')->insert($products);
    }
}
