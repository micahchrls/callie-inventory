<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Grid View --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
            @forelse($records as $record)
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 hover:shadow-lg transition-all duration-200">
                    {{-- Status Badge with Shopee branding --}}
                    <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-r from-orange-50 to-orange-50/50 dark:from-orange-900/10 dark:to-orange-900/5">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-mono text-gray-500 dark:text-gray-400">{{ $record->sku }}</span>
                            @if($record->quantity_in_stock <= 0)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">
                                    Out of Stock
                                </span>
                            @elseif($record->quantity_in_stock <= $record->reorder_level)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400">
                                    Low Stock
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                    In Stock
                                </span>
                            @endif
                        </div>
                    </div>
                    
                    {{-- Product Details --}}
                    <div class="p-4 space-y-4">
                        {{-- Product Name --}}
                        <div>
                            <h3 class="font-semibold text-sm text-gray-900 dark:text-gray-100 leading-tight">
                                {{ $record->product->name }}
                            </h3>
                            @if($record->variation_name && $record->variation_name !== 'Standard')
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    {{ $record->variation_name }}
                                </p>
                            @endif
                        </div>
                        
                        {{-- Categories & Shopee Badge --}}
                        <div class="flex flex-wrap gap-1.5">
                            @if($record->product->productCategory)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                    {{ $record->product->productCategory->name }}
                                </span>
                            @endif
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400">
                                Shopee
                            </span>
                        </div>
                        
                        {{-- Stock Info --}}
                        <div class="grid grid-cols-2 gap-3 pt-3 border-t border-gray-100 dark:border-gray-700">
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Stock</p>
                                <p class="text-2xl font-bold {{ $record->quantity_in_stock <= 0 ? 'text-red-600 dark:text-red-400' : ($record->quantity_in_stock <= $record->reorder_level ? 'text-amber-600 dark:text-amber-400' : 'text-gray-900 dark:text-gray-100') }}">
                                    {{ number_format($record->quantity_in_stock) }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Reorder at</p>
                                <p class="text-2xl font-semibold text-gray-600 dark:text-gray-300">
                                    {{ number_format($record->reorder_level) }}
                                </p>
                            </div>
                        </div>
                        
                        {{-- Actions --}}
                        <div class="flex gap-2 pt-3">
                            <a href="{{ \App\Filament\Resources\ShopeeInventoryResource::getUrl('view', ['record' => $record]) }}" 
                               class="flex-1 text-center px-3 py-2 text-xs font-medium text-gray-600 bg-gray-50 border border-gray-200 rounded-lg hover:bg-gray-100 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600 transition-colors">
                                View
                            </a>
                            <a href="{{ \App\Filament\Resources\ShopeeInventoryResource::getUrl('edit', ['record' => $record]) }}" 
                               class="flex-1 text-center px-3 py-2 text-xs font-medium text-white bg-orange-600 rounded-lg hover:bg-orange-700 dark:bg-orange-600 dark:hover:bg-orange-500 transition-colors">
                                Edit
                            </a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full">
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">No Shopee inventory items</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Get started by adding a new inventory item.</p>
                    </div>
                </div>
            @endforelse
        </div>
        
        {{-- Pagination --}}
        @if($records->hasPages())
            <div class="mt-6">
                {{ $records->links() }}
            </div>
        @endif
    </div>
</x-filament-panels::page>
