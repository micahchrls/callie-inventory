<?php

namespace App\Observers;

use App\Models\Product\ProductVariant;

class ProductVariantObserver
{
    /**
     * Handle the ProductVariant "creating" event.
     */
    public function creating(ProductVariant $productVariant): void
    {
        $this->updateStatus($productVariant);
    }

    /**
     * Handle the ProductVariant "updating" event.
     */
    public function updating(ProductVariant $productVariant): void
    {
        // Check if quantity_in_stock or reorder_level is being changed
        if ($productVariant->isDirty(['quantity_in_stock', 'reorder_level'])) {
            $this->updateStatus($productVariant);
        }
    }

    /**
     * Handle the ProductVariant "updated" event.
     * This will catch changes made by increment/decrement methods
     */
    public function updated(ProductVariant $productVariant): void
    {
        // Check if quantity_in_stock or reorder_level was changed
        if ($productVariant->wasChanged(['quantity_in_stock', 'reorder_level'])) {
            // Get the current values and update status accordingly
            $this->updateStatusAndSave($productVariant);
        }
    }

    /**
     * Update the status based on current stock levels
     */
    private function updateStatus(ProductVariant $productVariant): void
    {
        if ($productVariant->quantity_in_stock <= 0) {
            $productVariant->status = 'out_of_stock';
        } elseif ($productVariant->quantity_in_stock <= $productVariant->reorder_level) {
            $productVariant->status = 'low_stock';
        } else {
            $productVariant->status = 'in_stock';
        }
    }

    /**
     * Update status and save the model (for post-update scenarios)
     */
    private function updateStatusAndSave(ProductVariant $productVariant): void
    {
        $oldStatus = $productVariant->status;

        if ($productVariant->quantity_in_stock <= 0) {
            $newStatus = 'out_of_stock';
        } elseif ($productVariant->quantity_in_stock <= $productVariant->reorder_level) {
            $newStatus = 'low_stock';
        } else {
            $newStatus = 'in_stock';
        }

        // Only update if status actually changed to avoid unnecessary DB queries
        if ($oldStatus !== $newStatus) {
            $productVariant->updateQuietly(['status' => $newStatus]);
        }
    }
}
