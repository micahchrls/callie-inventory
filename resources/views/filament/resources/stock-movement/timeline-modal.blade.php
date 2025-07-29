<div class="max-h-[80vh] overflow-y-auto">
    @if($movementsByDate->isEmpty())
        <div class="text-center py-12">
            <div class="text-gray-500 dark:text-gray-400">
                <x-heroicon-o-clock class="w-16 h-16 mx-auto mb-4 opacity-50" />
                <h3 class="text-lg font-medium mb-2">No Stock Movements</h3>
                <p>No stock movements have been recorded yet.</p>
            </div>
        </div>
    @else
        <div class="space-y-8">
            @foreach($movementsByDate as $date => $movements)
                <div class="relative">
                    <!-- Date header -->
                    <div class="flex items-center mb-6">
                        <div class="flex-shrink-0">
                            <div class="flex items-center justify-center w-12 h-12 rounded-full bg-gray-100 dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
                                <x-heroicon-o-calendar class="w-6 h-6 text-gray-600 dark:text-gray-400" />
                            </div>
                        </div>
                        <div class="ml-4 flex-1">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                {{ \Carbon\Carbon::parse($date)->format('F j, Y') }}
                            </h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ $movements->count() }} {{ Str::plural('movement', $movements->count()) }}
                                @if($date === today()->format('Y-m-d'))
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 ml-2">
                                        Today
                                    </span>
                                @elseif($date === yesterday()->format('Y-m-d'))
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200 ml-2">
                                        Yesterday
                                    </span>
                                @endif
                            </p>
                        </div>
                        <div class="flex-shrink-0">
                            <button
                                x-data="{ expanded: true }"
                                @click="expanded = !expanded"
                                class="inline-flex items-center px-3 py-1 border border-gray-300 dark:border-gray-600 rounded-md text-xs font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700"
                            >
                                <span x-show="expanded">Collapse</span>
                                <span x-show="!expanded">Expand</span>
                                <x-heroicon-m-chevron-up class="ml-1 w-3 h-3" x-show="expanded" />
                                <x-heroicon-m-chevron-down class="ml-1 w-3 h-3" x-show="!expanded" />
                            </button>
                        </div>
                    </div>

                    <!-- Timeline for this date -->
                    <div x-data="{ expanded: true }" x-show="expanded" x-transition class="relative ml-6">
                        <!-- Vertical line -->
                        <div class="absolute left-6 top-0 bottom-0 w-0.5 bg-gray-200 dark:bg-gray-700"></div>

                        <div class="space-y-6">
                            @foreach($movements->sortByDesc('created_at') as $movement)
                                <div class="relative flex items-start group">
                                    <!-- Timeline dot with movement type icon -->
                                    <div class="absolute left-0 mt-2 w-12 h-12 rounded-full border-4 border-white dark:border-gray-900
                                        @switch($movement->movement_type)
                                            @case('restock')
                                                bg-green-100 dark:bg-green-900
                                                @break
                                            @case('sale')
                                                bg-blue-100 dark:bg-blue-900
                                                @break
                                            @case('adjustment')
                                                bg-yellow-100 dark:bg-yellow-900
                                                @break
                                            @case('damage')
                                            @case('loss')
                                                bg-red-100 dark:bg-red-900
                                                @break
                                            @case('return')
                                                bg-purple-100 dark:bg-purple-900
                                                @break
                                            @case('transfer')
                                                bg-gray-100 dark:bg-gray-800
                                                @break
                                            @case('initial_stock')
                                                bg-gray-100 dark:bg-gray-800
                                                @break
                                            @default
                                                bg-yellow-100 dark:bg-yellow-900
                                        @endswitch
                                        flex items-center justify-center shadow-md">
                                        @switch($movement->movement_type)
                                            @case('restock')
                                                <x-heroicon-s-arrow-up class="w-5 h-5 text-green-600" />
                                                @break
                                            @case('sale')
                                                <x-heroicon-s-arrow-down class="w-5 h-5 text-blue-600" />
                                                @break
                                            @case('adjustment')
                                                <x-heroicon-s-cog class="w-5 h-5 text-yellow-600" />
                                                @break
                                            @case('damage')
                                            @case('loss')
                                                <x-heroicon-s-x-mark class="w-5 h-5 text-red-600" />
                                                @break
                                            @case('return')
                                                <x-heroicon-s-arrow-left class="w-5 h-5 text-purple-600" />
                                                @break
                                            @case('transfer')
                                                <x-heroicon-s-arrow-right class="w-5 h-5 text-gray-600" />
                                                @break
                                            @case('initial_stock')
                                                <x-heroicon-s-plus class="w-5 h-5 text-gray-600" />
                                                @break
                                            @default
                                                <x-heroicon-s-pencil class="w-5 h-5 text-gray-600" />
                                        @endswitch
                                    </div>

                                    <!-- Movement details card -->
                                    <div class="ml-16 flex-1 min-w-0">
                                        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-5 group-hover:shadow-lg transition-all duration-200">
                                            <!-- Header -->
                                            <div class="flex items-start justify-between">
                                                <div class="flex-1 min-w-0">
                                                    <div class="flex items-center space-x-3">
                                                        <!-- Product info -->
                                                        <div class="flex-1 min-w-0">
                                                            <h4 class="text-sm font-semibold text-gray-900 dark:text-white truncate">
                                                                {{ $movement->productVariant->product->name }}
                                                            </h4>
                                                            <div class="flex items-center space-x-2 mt-1">
                                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                                                                    {{ $movement->productVariant->sku }}
                                                                </span>
                                                                @if($movement->productVariant->variation_name)
                                                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                                                        {{ $movement->productVariant->variation_name }}
                                                                    </span>
                                                                @endif
                                                            </div>
                                                        </div>

                                                        <!-- Movement type badge -->
                                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium
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
                                                    </div>
                                                </div>

                                                <!-- Time -->
                                                <div class="flex-shrink-0 ml-4">
                                                    <time class="text-sm text-gray-500 dark:text-gray-400">
                                                        {{ $movement->created_at->format('H:i') }}
                                                    </time>
                                                </div>
                                            </div>

                                            <!-- Quantity changes -->
                                            <div class="mt-4 grid grid-cols-3 gap-4">
                                                <div class="text-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Before</div>
                                                    <div class="text-lg font-semibold text-gray-900 dark:text-white">
                                                        {{ number_format($movement->quantity_before) }}
                                                    </div>
                                                </div>
                                                <div class="text-center p-3 rounded-lg {{ $movement->quantity_change > 0 ? 'bg-green-50 dark:bg-green-900/20' : 'bg-red-50 dark:bg-red-900/20' }}">
                                                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Change</div>
                                                    <div class="text-lg font-bold {{ $movement->quantity_change > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                                        {{ $movement->quantity_change > 0 ? '+' : '' }}{{ number_format($movement->quantity_change) }}
                                                    </div>
                                                </div>
                                                <div class="text-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">After</div>
                                                    <div class="text-lg font-semibold text-gray-900 dark:text-white">
                                                        {{ number_format($movement->quantity_after) }}
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Additional details -->
                                            @if($movement->reason || $movement->user || $movement->platform)
                                                <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                                                    <div class="space-y-2">
                                                        @if($movement->reason)
                                                            <div class="flex items-start">
                                                                <x-heroicon-m-chat-bubble-left class="w-4 h-4 text-gray-400 mt-0.5 mr-2 flex-shrink-0" />
                                                                <p class="text-sm text-gray-600 dark:text-gray-300">{{ $movement->reason }}</p>
                                                            </div>
                                                        @endif

                                                        <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                                                            @if($movement->user)
                                                                <div class="flex items-center">
                                                                    <x-heroicon-m-user class="w-3 h-3 mr-1" />
                                                                    {{ $movement->user->name }}
                                                                </div>
                                                            @else
                                                                <div class="flex items-center">
                                                                    <x-heroicon-m-cog class="w-3 h-3 mr-1" />
                                                                    System
                                                                </div>
                                                            @endif

                                                            @if($movement->platform)
                                                                <div class="flex items-center">
                                                                    <x-heroicon-m-device-phone-mobile class="w-3 h-3 mr-1" />
                                                                    {{ $movement->platform }}
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Summary stats -->
        <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                @php
                    $totalMovements = $movementsByDate->flatten();
                    $totalIncrease = $totalMovements->where('quantity_change', '>', 0)->sum('quantity_change');
                    $totalDecrease = $totalMovements->where('quantity_change', '<', 0)->sum('quantity_change');
                    $netChange = $totalIncrease + $totalDecrease;
                @endphp

                <div class="text-center p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Total Movements</div>
                    <div class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ number_format($totalMovements->count()) }}
                    </div>
                </div>

                <div class="text-center p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Total Added</div>
                    <div class="text-lg font-semibold text-green-600 dark:text-green-400">
                        +{{ number_format($totalIncrease) }}
                    </div>
                </div>

                <div class="text-center p-3 bg-red-50 dark:bg-red-900/20 rounded-lg">
                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Total Removed</div>
                    <div class="text-lg font-semibold text-red-600 dark:text-red-400">
                        {{ number_format($totalDecrease) }}
                    </div>
                </div>

                <div class="text-center p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Net Change</div>
                    <div class="text-lg font-semibold {{ $netChange >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                        {{ $netChange > 0 ? '+' : '' }}{{ number_format($netChange) }}
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
