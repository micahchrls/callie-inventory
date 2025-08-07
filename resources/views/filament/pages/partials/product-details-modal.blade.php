<div class="p-4 space-y-6">
    <!-- Product Information -->
    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Product Information</h3>
        <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Product Name</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $product->name }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Category</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                    {{ $product->productCategory->name ?? 'No Category' }}
                </dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Brand</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $product->brand ?? 'N/A' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</dt>
                <dd class="mt-1">
                    @if($product->is_active)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                            Active
                        </span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                            Inactive
                        </span>
                    @endif
                </dd>
            </div>
        </dl>
    </div>

    <!-- Variant Information -->
    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Variant Details</h3>
        <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">SKU</dt>
                <dd class="mt-1 text-sm font-mono text-gray-900 dark:text-gray-100">{{ $variant->sku }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Variant Name</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                    {{ $variant->variation_name ?? 'Standard' }}
                </dd>
            </div>
            @if($variant->size)
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Size</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $variant->size }}</dd>
            </div>
            @endif
            @if($variant->color)
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Color</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $variant->color }}</dd>
            </div>
            @endif
            @if($variant->material)
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Material</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $variant->material }}</dd>
            </div>
            @endif
            @if($variant->weight)
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Weight</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $variant->weight }} kg</dd>
            </div>
            @endif
        </dl>
    </div>

    <!-- Stock Information -->
    <div class="bg-amber-50 dark:bg-amber-900/20 rounded-lg p-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Stock Status</h3>
        <dl class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Current Stock</dt>
                <dd class="mt-1 text-2xl font-bold {{ $variant->isOutOfStock() ? 'text-red-600' : ($variant->isLowStock() ? 'text-amber-600' : 'text-green-600') }}">
                    {{ $variant->quantity_in_stock }}
                </dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Reorder Level</dt>
                <dd class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-100">
                    {{ $variant->reorder_level }}
                </dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Stock Status</dt>
                <dd class="mt-1">
                    @if($variant->isOutOfStock())
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                            Out of Stock
                        </span>
                    @elseif($variant->isLowStock())
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200">
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                            Low Stock
                        </span>
                    @else
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            In Stock
                        </span>
                    @endif
                </dd>
            </div>
        </dl>
    </div>

    <!-- Platform Information -->
    @if($variant->platform)
    <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Platform</h3>
        <div class="flex items-center">
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">
                {{ $variant->platform->name }}
            </span>
        </div>
    </div>
    @endif

    <!-- Additional Information -->
    @if($variant->notes)
    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">Notes</h3>
        <p class="text-sm text-gray-600 dark:text-gray-400">{{ $variant->notes }}</p>
    </div>
    @endif

    <!-- Last Updated -->
    <div class="text-center text-sm text-gray-500 dark:text-gray-400">
        Last updated: {{ $variant->updated_at->format('M d, Y g:i A') }}
    </div>
</div>
