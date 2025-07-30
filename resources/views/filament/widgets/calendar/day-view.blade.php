<!-- Day View Header -->
<div class="bg-gradient-to-r from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-600 p-4 rounded-t-2xl">
    <div class="text-center">
        <div class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-2">
            {{ $dayData['date']->format('j') }}
        </div>
        <div class="text-lg font-semibold text-gray-700 dark:text-gray-300">
            {{ $dayData['date']->format('l, F Y') }}
        </div>
        @if($dayData['total_movements'] > 0)
            <div class="mt-3">
                <span class="inline-flex items-center px-4 py-2 text-lg font-bold text-white bg-gradient-to-r from-indigo-500 to-purple-600 rounded-full shadow-lg">
                    {{ $dayData['total_movements'] }} Total Movements
                </span>
            </div>
        @endif
    </div>
</div>

<!-- Day Content -->
<div class="p-6 bg-gray-50 dark:bg-gray-800 min-h-[600px]">
    @if($dayData['total_movements'] > 0)
        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            @if($dayData['restock_count'] > 0)
                <div class="bg-gradient-to-br from-emerald-100 to-green-100 dark:from-emerald-900 dark:to-green-900 rounded-xl p-4 border border-emerald-200 dark:border-emerald-700 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <div class="text-3xl">📦</div>
                            <div>
                                <div class="text-lg font-bold text-emerald-800 dark:text-emerald-200">Restocks</div>
                                <div class="text-sm text-emerald-600 dark:text-emerald-400">Stock replenishments</div>
                            </div>
                        </div>
                        <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">
                            {{ $dayData['restock_count'] }}
                        </div>
                    </div>
                </div>
            @endif

            @if($dayData['adjustment_count'] > 0)
                <div class="bg-gradient-to-br from-amber-100 to-yellow-100 dark:from-amber-900 dark:to-yellow-900 rounded-xl p-4 border border-amber-200 dark:border-amber-700 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <div class="text-3xl">⚖️</div>
                            <div>
                                <div class="text-lg font-bold text-amber-800 dark:text-amber-200">Adjustments</div>
                                <div class="text-sm text-amber-600 dark:text-amber-400">Inventory corrections</div>
                            </div>
                        </div>
                        <div class="text-2xl font-bold text-amber-600 dark:text-amber-400">
                            {{ $dayData['adjustment_count'] }}
                        </div>
                    </div>
                </div>
            @endif

            @if($dayData['damage_count'] > 0)
                <div class="bg-gradient-to-br from-red-100 to-rose-100 dark:from-red-900 dark:to-rose-900 rounded-xl p-4 border border-red-200 dark:border-red-700 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <div class="text-3xl">⚠️</div>
                            <div>
                                <div class="text-lg font-bold text-red-800 dark:text-red-200">Damages</div>
                                <div class="text-sm text-red-600 dark:text-red-400">Damaged inventory</div>
                            </div>
                        </div>
                        <div class="text-2xl font-bold text-red-600 dark:text-red-400">
                            {{ $dayData['damage_count'] }}
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Detailed Movement List -->
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-600">
                <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">Movement Details</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">All stock movements for this day</p>
            </div>

            <div class="divide-y divide-gray-200 dark:divide-gray-600 max-h-96 overflow-y-auto">
                @foreach($dayData['movements']->sortByDesc('created_at') as $movement)
                    @php
                        $typeConfig = [
                            'restock' => [
                                'icon' => '📦',
                                'bg' => 'bg-emerald-50 dark:bg-emerald-950',
                                'border' => 'border-l-4 border-emerald-500',
                                'text' => 'text-emerald-700 dark:text-emerald-300',
                                'badge' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-800 dark:text-emerald-200'
                            ],
                            'adjustment' => [
                                'icon' => '⚖️',
                                'bg' => 'bg-amber-50 dark:bg-amber-950',
                                'border' => 'border-l-4 border-amber-500',
                                'text' => 'text-amber-700 dark:text-amber-300',
                                'badge' => 'bg-amber-100 text-amber-800 dark:bg-amber-800 dark:text-amber-200'
                            ],
                            'damage' => [
                                'icon' => '⚠️',
                                'bg' => 'bg-red-50 dark:bg-red-950',
                                'border' => 'border-l-4 border-red-500',
                                'text' => 'text-red-700 dark:text-red-300',
                                'badge' => 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-200'
                            ]
                        ];
                        $config = $typeConfig[$movement->movement_type] ?? [
                            'icon' => '📝',
                            'bg' => 'bg-gray-50 dark:bg-gray-950',
                            'border' => 'border-l-4 border-gray-500',
                            'text' => 'text-gray-700 dark:text-gray-300',
                            'badge' => 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200'
                        ];
                    @endphp

                    <div class="p-6 {{ $config['bg'] }} {{ $config['border'] }} hover:shadow-md transition-all duration-200">
                        <div class="flex items-start justify-between">
                            <div class="flex items-start space-x-4 flex-1">
                                <div class="text-2xl mt-1">{{ $config['icon'] }}</div>
                                <div class="flex-1">
                                    <div class="flex items-center space-x-3 mb-2">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $config['badge'] }}">
                                            {{ ucfirst($movement->movement_type) }}
                                        </span>
                                        <span class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ $movement->created_at->format('g:i A') }}
                                        </span>
                                    </div>

                                    <div class="mb-3">
                                        <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-1">
                                            {{ $movement->productVariant?->product?->name }}
                                        </h4>
                                        <div class="flex items-center space-x-4 text-sm text-gray-600 dark:text-gray-400">
                                            <span><strong>SKU:</strong> {{ $movement->productVariant?->sku }}</span>
                                            @if($movement->productVariant?->size)
                                                <span><strong>Size:</strong> {{ $movement->productVariant?->size }}</span>
                                            @endif
                                            @if($movement->productVariant?->color)
                                                <span><strong>Color:</strong> {{ $movement->productVariant?->color }}</span>
                                            @endif
                                        </div>
                                    </div>

                                    @if($movement->notes)
                                        <div class="text-sm text-gray-600 dark:text-gray-400 bg-gray-100 dark:bg-gray-800 rounded-lg p-3 italic">
                                            <strong>Notes:</strong> {{ $movement->notes }}
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <div class="text-right ml-4">
                                <div class="text-2xl font-bold {{ $movement->quantity_change > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                    {{ $movement->quantity_change > 0 ? '+' : '' }}{{ $movement->quantity_change }}
                                </div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ abs($movement->quantity_change) }} {{ abs($movement->quantity_change) === 1 ? 'unit' : 'units' }}
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @else
        <!-- Empty State -->
        <div class="text-center py-16">
            <div class="text-8xl mb-4">📭</div>
            <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-2">
                No Stock Movements
            </h3>
            <p class="text-gray-500 dark:text-gray-400 max-w-md mx-auto">
                There were no stock movements recorded for {{ $dayData['date']->format('F j, Y') }}.
                This could mean it was a quiet day for inventory changes.
            </p>
        </div>
    @endif
</div>
