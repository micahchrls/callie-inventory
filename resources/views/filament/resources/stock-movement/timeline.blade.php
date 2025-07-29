@php
    use App\Models\Product\StockMovement;

    $movements = StockMovement::where('product_variant_id', $getRecord()->product_variant_id)
        ->with(['productVariant.product', 'user'])
        ->orderBy('created_at', 'desc')
        ->limit(10)
        ->get();
@endphp

<div class="space-y-4">
    <div class="text-sm font-medium text-gray-900 dark:text-white mb-4">
        Recent Stock Movements for {{ $getRecord()->productVariant->sku }}
    </div>

    @if($movements->isEmpty())
        <div class="text-center py-8">
            <div class="text-gray-500 dark:text-gray-400">
                <x-heroicon-o-clock class="w-12 h-12 mx-auto mb-4 opacity-50" />
                <p>No stock movements found for this variant.</p>
            </div>
        </div>
    @else
        <div class="relative">
            <!-- Timeline line -->
            <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-gray-200 dark:bg-gray-700"></div>

            <div class="space-y-6">
                @foreach($movements as $index => $movement)
                    <div class="relative flex items-start group">
                        <!-- Timeline dot -->
                        <div class="absolute left-0 mt-1.5 w-8 h-8 rounded-full border-4 {{ $movement->id === $getRecord()->id ? 'border-blue-500 bg-blue-100 dark:bg-blue-900' : 'border-gray-300 bg-white dark:border-gray-600 dark:bg-gray-800' }} flex items-center justify-center">
                            @switch($movement->movement_type)
                                @case('restock')
                                    <x-heroicon-o-arrow-up class="w-3 h-3 text-green-600" />
                                    @break
                                @case('sale')
                                    <x-heroicon-o-arrow-down class="w-3 h-3 text-blue-600" />
                                    @break
                                @case('adjustment')
                                    <x-heroicon-o-cog class="w-3 h-3 text-yellow-600" />
                                    @break
                                @case('damage')
                                @case('loss')
                                    <x-heroicon-o-x-mark class="w-3 h-3 text-red-600" />
                                    @break
                                @case('return')
                                    <x-heroicon-o-arrow-left class="w-3 h-3 text-purple-600" />
                                    @break
                                @case('transfer')
                                    <x-heroicon-o-arrow-right class="w-3 h-3 text-gray-600" />
                                    @break
                                @case('initial_stock')
                                    <x-heroicon-o-plus class="w-3 h-3 text-gray-600" />
                                    @break
                                @default
                                    <x-heroicon-o-pencil class="w-3 h-3 text-gray-600" />
                            @endswitch
                        </div>

                        <!-- Content -->
                        <div class="ml-12 flex-1 min-w-0">
                            <div class="bg-white dark:bg-gray-800 rounded-lg border {{ $movement->id === $getRecord()->id ? 'border-blue-300 ring-2 ring-blue-100 dark:ring-blue-900' : 'border-gray-200 dark:border-gray-700' }} p-4 group-hover:shadow-md transition-shadow">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-2">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            @switch($movement->movement_type)
                                                @case('restock')
                                                    bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                                    @break
                                                @case('sale')
                                                    bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                                    @break
                                                @case('adjustment')
                                                    bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                                    @break
                                                @case('damage')
                                                @case('loss')
                                                    bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                                    @break
                                                @case('return')
                                                    bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200
                                                    @break
                                                @case('transfer')
                                                    bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200
                                                    @break
                                                @case('initial_stock')
                                                    bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200
                                                    @break
                                                @default
                                                    bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                            @endswitch
                                        ">
                                            {{ ucwords(str_replace('_', ' ', $movement->movement_type)) }}
                                        </span>

                                        @if($movement->id === $getRecord()->id)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                                Current
                                            </span>
                                        @endif
                                    </div>

                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $movement->created_at->format('M d, Y H:i') }}
                                    </div>
                                </div>

                                <div class="mt-3 grid grid-cols-3 gap-4 text-sm">
                                    <div>
                                        <span class="text-gray-500 dark:text-gray-400">Before:</span>
                                        <span class="font-medium ml-1">{{ number_format($movement->quantity_before) }}</span>
                                    </div>
                                    <div>
                                        <span class="text-gray-500 dark:text-gray-400">Change:</span>
                                        <span class="font-medium ml-1 {{ $movement->quantity_change > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                            {{ $movement->quantity_change > 0 ? '+' : '' }}{{ number_format($movement->quantity_change) }}
                                        </span>
                                    </div>
                                    <div>
                                        <span class="text-gray-500 dark:text-gray-400">After:</span>
                                        <span class="font-medium ml-1">{{ number_format($movement->quantity_after) }}</span>
                                    </div>
                                </div>

                                @if($movement->reason || $movement->user)
                                    <div class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700">
                                        @if($movement->reason)
                                            <p class="text-sm text-gray-600 dark:text-gray-300 mb-2">
                                                <span class="font-medium">Reason:</span> {{ $movement->reason }}
                                            </p>
                                        @endif

                                        @if($movement->user)
                                            <div class="flex items-center text-xs text-gray-500 dark:text-gray-400">
                                                <x-heroicon-o-user class="w-3 h-3 mr-1" />
                                                {{ $movement->user->name }}
                                                @if($movement->platform)
                                                    <span class="mx-1">•</span>
                                                    {{ $movement->platform }}
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        @if($movements->count() >= 10)
            <div class="text-center pt-4">
                <span class="text-sm text-gray-500 dark:text-gray-400">
                    Showing latest 10 movements. Use the main timeline view to see all movements.
                </span>
            </div>
        @endif
    @endif
</div>
