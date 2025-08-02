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
     * Generates comprehensive stock movement history for jewelry inventory
     * with various movement types and realistic business scenarios.
     */
    public function run(): void
    {
        $this->command->info('Creating stock movements...');

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

        // Define realistic platforms for jewelry business
        $platforms = [
            'tiktok_shop' => 'TikTok Shop',
            'shopee' => 'Shopee',
            'website' => 'Company Website',
            'physical_store' => 'Physical Store',
            'wholesale' => 'Wholesale',
        ];

        // Define movement type scenarios
        $movementScenarios = [
            'initial_stock' => [
                'weight' => 1,
                'reasons' => ['Initial inventory setup', 'Opening stock entry', 'System migration data'],
                'platforms' => [null],
                'reference_types' => ['system_setup'],
            ],
            'restock' => [
                'weight' => 3,
                'reasons' => [
                    'Purchase from supplier', 'Manufacturing completion', 'Return from repair',
                    'Wholesale purchase', 'Import shipment received', 'Production batch completed'
                ],
                'platforms' => [null],
                'reference_types' => ['purchase_order', 'production_batch', 'supplier_delivery'],
            ],
            'sale' => [
                'weight' => 5,
                'reasons' => [
                    'Online order', 'Walk-in customer', 'Phone order', 'Bulk sale',
                    'VIP customer purchase', 'Wedding order', 'Corporate gift order'
                ],
                'platforms' => array_keys($platforms),
                'reference_types' => ['order', 'invoice', 'receipt'],
            ],
            'adjustment' => [
                'weight' => 2,
                'reasons' => [
                    'Stock count correction', 'System error fix', 'Found missing items',
                    'Cycle count adjustment', 'Physical count variance', 'Database correction'
                ],
                'platforms' => [null],
                'reference_types' => ['stock_audit', 'cycle_count', 'adjustment_form'],
            ],
            'damage' => [
                'weight' => 1,
                'reasons' => [
                    'Shipping damage', 'Display damage', 'Manufacturing defect',
                    'Customer return - damaged', 'Storage damage', 'Handling accident'
                ],
                'platforms' => [null],
                'reference_types' => ['damage_report', 'insurance_claim', 'quality_control'],
            ],
            'loss' => [
                'weight' => 1,
                'reasons' => [
                    'Theft', 'Misplaced inventory', 'Lost in transit',
                    'Unexplained shortage', 'Security incident', 'Missing from display'
                ],
                'platforms' => [null],
                'reference_types' => ['loss_report', 'security_incident', 'investigation'],
            ],
            'return' => [
                'weight' => 2,
                'reasons' => [
                    'Customer return', 'Size exchange', 'Color exchange',
                    'Defective return', 'Changed mind', 'Gift return', 'Warranty return'
                ],
                'platforms' => array_keys($platforms),
                'reference_types' => ['return_authorization', 'exchange_order', 'warranty_claim'],
            ],
            'transfer' => [
                'weight' => 1,
                'reasons' => [
                    'Store transfer', 'Warehouse relocation', 'Display movement',
                    'Consignment placement', 'Exhibition loan', 'Repair center transfer'
                ],
                'platforms' => [null],
                'reference_types' => ['transfer_order', 'consignment_agreement', 'loan_agreement'],
            ],
        ];

        foreach ($productVariants as $variant) {
            $currentStock = 0;
            $movementCount = rand(5, 25); // Each variant gets 5-25 movements

            // Start with initial stock for some variants
            if (rand(1, 100) <= 80) { // 80% chance of initial stock
                $initialStock = rand(10, 100);
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
                    null,
                    'Initial stock entry for ' . $variant->product->name,
                    'System-generated initial inventory',
                    rand(15, 50), // unit cost
                    Carbon::now()->subDays(rand(30, 180))
                );

                $currentStock = $initialStock;
                $totalMovements++;
            }

            // Generate random movements over time
            for ($i = 0; $i < $movementCount; $i++) {
                // Select movement type based on weights
                $movementType = $this->selectWeightedMovementType($movementScenarios);
                $scenario = $movementScenarios[$movementType];

                // Skip initial_stock after the first one
                if ($movementType === 'initial_stock' && $currentStock > 0) {
                    continue;
                }

                $quantityChange = $this->generateQuantityChange($movementType, $currentStock);
                $quantityBefore = $currentStock;
                $quantityAfter = max(0, $currentStock + $quantityChange);

                $user = $users->random();
                $reason = $scenario['reasons'][array_rand($scenario['reasons'])];
                $platform = $scenario['platforms'][array_rand($scenario['platforms'])];
                $referenceType = $scenario['reference_types'][array_rand($scenario['reference_types'])];
                $referenceId = $this->generateReferenceId($referenceType);

                $unitCost = in_array($movementType, ['restock', 'initial_stock']) ? rand(10, 80) : null;
                $notes = $this->generateNotes($movementType, $variant, $quantityChange);

                $createdAt = Carbon::now()->subDays(rand(1, 90))->subHours(rand(0, 23));

                $movements[] = $this->createMovement(
                    $variant->id,
                    $user->id,
                    $movementType,
                    $quantityBefore,
                    $quantityChange,
                    $quantityAfter,
                    $referenceType,
                    $referenceId,
                    $platform,
                    $reason,
                    $notes,
                    $unitCost,
                    $createdAt
                );

                $currentStock = $quantityAfter;
                $totalMovements++;
            }

            // Update the final stock in the product variant
            $variant->update(['quantity_in_stock' => $currentStock]);
        }

        // Batch insert all movements for better performance
        $chunks = array_chunk($movements, 500);
        foreach ($chunks as $chunk) {
            StockMovement::insert($chunk);
        }

        $this->command->info("✅ Created {$totalMovements} stock movements for " . $productVariants->count() . " product variants");

        // Show summary statistics
        $this->showSummaryStats();
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
        ?string $platform,
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
            'platform' => $platform,
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
     * Select movement type based on weighted probabilities
     */
    private function selectWeightedMovementType(array $scenarios): string
    {
        $totalWeight = array_sum(array_column($scenarios, 'weight'));
        $random = rand(1, $totalWeight);

        $currentWeight = 0;
        foreach ($scenarios as $type => $scenario) {
            $currentWeight += $scenario['weight'];
            if ($random <= $currentWeight) {
                return $type;
            }
        }

        return 'adjustment'; // fallback
    }

    /**
     * Generate appropriate quantity change based on movement type
     */
    private function generateQuantityChange(string $movementType, int $currentStock): int
    {
        return match ($movementType) {
            'initial_stock', 'restock' => rand(5, 50),
            'sale' => -rand(1, min(10, max(1, $currentStock))),
            'return' => rand(1, 5),
            'adjustment' => rand(-5, 5),
            'damage', 'loss' => -rand(1, min(3, max(1, $currentStock))),
            'transfer' => rand(-10, 10),
            'manual_edit' => rand(-20, 20),
            default => 0,
        };
    }

    /**
     * Generate reference ID based on type
     */
    private function generateReferenceId(string $referenceType): string
    {
        return match ($referenceType) {
            'order' => 'ORD-' . date('Y') . '-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT),
            'invoice' => 'INV-' . date('Ym') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT),
            'purchase_order' => 'PO-' . date('Y') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT),
            'return_authorization' => 'RMA-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT),
            'stock_audit' => 'AUDIT-' . date('Ymd') . '-' . str_pad(rand(1, 99), 2, '0', STR_PAD_LEFT),
            'damage_report' => 'DMG-' . date('Ymd') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT),
            'transfer_order' => 'TRF-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT),
            default => strtoupper($referenceType) . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT),
        };
    }

    /**
     * Generate contextual notes for the movement
     */
    private function generateNotes(string $movementType, ProductVariant $variant, int $quantityChange): string
    {
        $productName = $variant->product->name;
        $variationName = $variant->variation_name ?: 'Standard';

        $notes = match ($movementType) {
            'initial_stock' => "Initial inventory setup for {$productName} ({$variationName})",
            'restock' => "Restocked {$productName} - {$variationName}. Added " . abs($quantityChange) . " units",
            'sale' => "Sold " . abs($quantityChange) . " unit(s) of {$productName} ({$variationName})",
            'adjustment' => "Stock adjustment for {$productName}. Change: {$quantityChange} units",
            'damage' => "Damaged stock removed: {$productName} ({$variationName}). Quantity: " . abs($quantityChange),
            'loss' => "Stock loss recorded for {$productName} ({$variationName}). Missing: " . abs($quantityChange),
            'return' => "Customer return: {$productName} ({$variationName}). Returned: " . abs($quantityChange) . " units",
            'transfer' => "Stock transfer for {$productName} ({$variationName}). Net change: {$quantityChange}",
            default => "Stock movement for {$productName} ({$variationName}): {$quantityChange} units",
        };

        // Add additional context for some movements
        if (in_array($movementType, ['damage', 'loss']) && rand(1, 100) <= 30) {
            $additionalInfo = [
                'Investigation pending',
                'Insurance claim filed',
                'Supplier notified',
                'Quality control review',
                'Security review initiated'
            ];
            $notes .= '. ' . $additionalInfo[array_rand($additionalInfo)];
        }

        return $notes;
    }

    /**
     * Generate random IP address for audit trail
     */
    private function generateRandomIP(): string
    {
        return rand(192, 203) . '.' . rand(0, 255) . '.' . rand(0, 255) . '.' . rand(1, 254);
    }

    /**
     * Generate random user agent for audit trail
     */
    private function generateRandomUserAgent(): string
    {
        $userAgents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:89.0) Gecko/20100101 Firefox/89.0',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.1.1 Safari/605.1.15',
            'Callie-Inventory-System/1.0 (Internal)',
        ];

        return $userAgents[array_rand($userAgents)];
    }

    /**
     * Display summary statistics after seeding
     */
    private function showSummaryStats(): void
    {
        $stats = StockMovement::selectRaw('
            movement_type,
            COUNT(*) as count,
            SUM(CASE WHEN quantity_change > 0 THEN quantity_change ELSE 0 END) as total_in,
            SUM(CASE WHEN quantity_change < 0 THEN ABS(quantity_change) ELSE 0 END) as total_out
        ')
        ->groupBy('movement_type')
        ->orderBy('count', 'desc')
        ->get();

        $this->command->info("\n📊 Stock Movement Summary:");
        $this->command->table(
            ['Movement Type', 'Count', 'Total In', 'Total Out'],
            $stats->map(fn($stat) => [
                ucwords(str_replace('_', ' ', $stat->movement_type)),
                $stat->count,
                number_format($stat->total_in),
                number_format($stat->total_out),
            ])->toArray()
        );
    }
}
