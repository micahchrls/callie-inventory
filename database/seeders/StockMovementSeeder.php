<?php

namespace Database\Seeders;

use App\Models\Product\ProductVariant;
use App\Models\StockMovement;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class StockMovementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Creates initial stock movements only for product variants.
     */
    public function run(): void
    {
        $this->command->info('Creating initial stock movements...');

        // Get existing data
        $productVariants = ProductVariant::with('product')->get();
        $users = User::all();

        if ($productVariants->isEmpty()) {
            $this->command->warn('No product variants found. Please run ProductVariantSeeder first.');
            return;
        }

        if ($users->isEmpty()) {
            $this->command->warn('No users found. Please run UserSeeder first.');
            return;
        }

        $movements = [];
        $totalMovements = 0;

        foreach ($productVariants as $variant) {
            // Create initial stock entry for each variant
            $initialStock = rand(50, 150);
            $user = $users->random();

            $movements[] = $this->createMovement(
                $variant->id,
                $user->id,
                'initial_stock',
                0,
                $initialStock,
                $initialStock,
                'system_setup',
                'INIT-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT),
                'Initial inventory setup',
                'System-generated initial inventory for ' . $variant->product->name . ' - ' . $variant->variation_name,
                rand(15, 50), // unit cost
                Carbon::now()->subDays(rand(30, 60)) // Initial stock from 1-2 months ago
            );

            // Update the variant with the initial stock quantity
            $variant->update(['quantity_in_stock' => $initialStock]);
            $totalMovements++;
            
            // Add some stock out movements for demo purposes
            if (rand(0, 100) > 30) { // 70% chance of having stock outs
                $stockOutCount = rand(1, 5);
                $currentStock = $initialStock;
                
                for ($i = 0; $i < $stockOutCount; $i++) {
                    $stockOutQuantity = rand(1, min(20, $currentStock));
                    $currentStock -= $stockOutQuantity;
                    
                    $movements[] = $this->createMovement(
                        $variant->id,
                        $user->id,
                        'stock_out',
                        $currentStock + $stockOutQuantity,
                        -$stockOutQuantity,
                        $currentStock,
                        'sale',
                        'SO-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT),
                        'sold',
                        'Product sold through platform',
                        null,
                        Carbon::now()->subDays(rand(1, 29)) // Stock outs in the last month
                    );
                    $totalMovements++;
                    
                    if ($currentStock <= 0) break;
                }
                
                // Update variant with current stock after stock outs
                $variant->update(['quantity_in_stock' => $currentStock]);
            }
        }

        // Insert all movements
        StockMovement::insert($movements);

        $this->command->info("✅ Created {$totalMovements} initial stock movements for " . $productVariants->count() . " product variants");
    }

    /**
     * Create a stock movement record array
     */
    private function createMovement(
        int $variantId,
        int $userId,
        string $movementType,
        int $quantityBefore,
        int $quantityChange,
        int $quantityAfter,
        string $referenceType,
        string $referenceId,
        string $reason,
        string $notes,
        ?float $unitCost,
        Carbon $createdAt
    ): array {
        $totalCost = $unitCost && $quantityChange > 0 ? $unitCost * abs($quantityChange) : null;

        return [
            'product_variant_id' => $variantId,
            'user_id' => $userId,
            'movement_type' => $movementType,
            'quantity_before' => $quantityBefore,
            'quantity_change' => $quantityChange,
            'quantity_after' => $quantityAfter,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'reason' => $reason,
            'notes' => $notes,
            'unit_cost' => $unitCost,
            'total_cost' => $totalCost,
            'ip_address' => $this->generateRandomIP(),
            'user_agent' => $this->generateRandomUserAgent(),
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ];
    }

    /**
     * Generate a random IP address for audit purposes
     */
    private function generateRandomIP(): string
    {
        return rand(1, 255) . '.' . rand(1, 255) . '.' . rand(1, 255) . '.' . rand(1, 255);
    }

    /**
     * Generate a random user agent for audit purposes
     */
    private function generateRandomUserAgent(): string
    {
        $userAgents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36',
            'PostmanRuntime/7.29.2',
            'Internal System',
        ];

        return $userAgents[array_rand($userAgents)];
    }
}
