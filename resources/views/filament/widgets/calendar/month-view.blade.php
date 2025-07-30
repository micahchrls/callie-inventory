<!-- Calendar Header -->
<div class="grid grid-cols-7 gap-px bg-gradient-to-r from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-600 p-1 rounded-t-2xl">
    @foreach(['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'] as $day)
        <div class="bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-700 px-3 py-3 text-center text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wide rounded-lg">
            <div class="hidden sm:block">{{ $day }}</div>
            <div class="sm:hidden">{{ substr($day, 0, 3) }}</div>
        </div>
    @endforeach
</div>

<!-- Calendar Grid -->
<div class="grid grid-cols-7 gap-1 p-1 bg-gray-50 dark:bg-gray-800">
    @foreach($monthData as $week)
        @foreach($week as $day)
            @php
                $intensityClasses = [
                    'none' => 'bg-white dark:bg-gray-900',
                    'low' => 'bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-950 dark:to-indigo-950',
                    'medium' => 'bg-gradient-to-br from-blue-100 to-indigo-100 dark:from-blue-900 dark:to-indigo-900',
                    'high' => 'bg-gradient-to-br from-blue-200 to-indigo-200 dark:from-blue-800 dark:to-indigo-800',
                    'very-high' => 'bg-gradient-to-br from-blue-300 to-indigo-300 dark:from-blue-700 dark:to-indigo-700'
                ];

                $primaryTypeClasses = [
                    'restock' => 'border-l-4 border-emerald-500',
                    'adjustment' => 'border-l-4 border-amber-500',
                    'damage' => 'border-l-4 border-red-500'
                ];

                $baseClass = $intensityClasses[$day['data']['intensity']] ?? $intensityClasses['none'];
                $primaryClass = $day['data']['primary_type'] ? ($primaryTypeClasses[$day['data']['primary_type']] ?? '') : '';
            @endphp

            <div class="relative min-h-[140px] {{ $baseClass }} {{ $primaryClass }} {{ $day['is_current_month'] ? '' : 'opacity-50' }} {{ $day['is_today'] ? 'ring-2 ring-blue-500 ring-inset shadow-lg' : '' }} rounded-xl transition-all duration-200 hover:shadow-md hover:scale-105 group">
                <!-- Date Number -->
                <div class="flex items-center justify-between p-3">
                    @if($day['is_today'])
                        <div class="flex items-center justify-center w-8 h-8 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-full text-sm font-bold shadow-lg">
                            {{ $day['date']->day }}
                        </div>
                    @else
                        <span class="text-lg font-bold {{ $day['is_current_month'] ? 'text-gray-900 dark:text-gray-100' : 'text-gray-400 dark:text-gray-500' }}">
                            {{ $day['date']->day }}
                        </span>
                    @endif

                    @if($day['data']['total_movements'] > 0)
                        <div class="flex items-center space-x-1">
                            <div class="relative">
                                <span class="inline-flex items-center justify-center w-8 h-8 text-xs font-bold text-white bg-gradient-to-r from-indigo-500 to-purple-600 rounded-full shadow-lg animate-pulse">
                                    {{ $day['data']['total_movements'] }}
                                </span>
                                <div class="absolute -top-1 -right-1 w-3 h-3 bg-gradient-to-r from-green-400 to-emerald-500 rounded-full animate-ping"></div>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Movement Indicators with Enhanced Design -->
                @if($day['data']['total_movements'] > 0)
                    <div class="px-3 pb-3 space-y-1.5">
                        @if($day['data']['restock_count'] > 0)
                            <div class="flex items-center justify-between bg-gradient-to-r from-emerald-100 to-green-100 dark:from-emerald-900 dark:to-green-900 rounded-lg px-2 py-1.5 shadow-sm">
                                <div class="flex items-center text-xs font-semibold text-emerald-700 dark:text-emerald-300">
                                    <span class="text-sm mr-1">📦</span>
                                    Restocks
                                </div>
                                <span class="bg-emerald-500 text-white text-xs font-bold px-2 py-0.5 rounded-full">
                                    {{ $day['data']['restock_count'] }}
                                </span>
                            </div>
                        @endif

                        @if($day['data']['adjustment_count'] > 0)
                            <div class="flex items-center justify-between bg-gradient-to-r from-amber-100 to-yellow-100 dark:from-amber-900 dark:to-yellow-900 rounded-lg px-2 py-1.5 shadow-sm">
                                <div class="flex items-center text-xs font-semibold text-amber-700 dark:text-amber-300">
                                    <span class="text-sm mr-1">⚖️</span>
                                    Adjustments
                                </div>
                                <span class="bg-amber-500 text-white text-xs font-bold px-2 py-0.5 rounded-full">
                                    {{ $day['data']['adjustment_count'] }}
                                </span>
                            </div>
                        @endif

                        @if($day['data']['damage_count'] > 0)
                            <div class="flex items-center justify-between bg-gradient-to-r from-red-100 to-rose-100 dark:from-red-900 dark:to-rose-900 rounded-lg px-2 py-1.5 shadow-sm">
                                <div class="flex items-center text-xs font-semibold text-red-700 dark:text-red-300">
                                    <span class="text-sm mr-1">⚠️</span>
                                    Damages
                                </div>
                                <span class="bg-red-500 text-white text-xs font-bold px-2 py-0.5 rounded-full">
                                    {{ $day['data']['damage_count'] }}
                                </span>
                            </div>
                        @endif
                    </div>

                    <!-- Enhanced Hover tooltip -->
                    @if($day['data']['movements']->count() > 0)
                        <div class="absolute inset-0 cursor-pointer group/tooltip"
                             x-data="{ showTooltip: false }"
                             @mouseenter="showTooltip = true"
                             @mouseleave="showTooltip = false">

                            <div x-show="showTooltip"
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0 scale-95"
                                 x-transition:enter-end="opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-150"
                                 x-transition:leave-start="opacity-100 scale-100"
                                 x-transition:leave-end="opacity-0 scale-95"
                                 class="absolute z-20 w-80 p-4 mt-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-2xl shadow-2xl left-0 top-full backdrop-blur-sm"
                                 style="display: none;">

                                <div class="flex items-center justify-between mb-3 pb-2 border-b border-gray-200 dark:border-gray-600">
                                    <div class="font-bold text-gray-900 dark:text-gray-100">
                                        {{ $day['date']->format('M j, Y') }}
                                    </div>
                                    <div class="flex items-center space-x-1">
                                        <span class="text-sm text-gray-500 dark:text-gray-400">Total:</span>
                                        <span class="inline-flex items-center px-2 py-1 text-xs font-bold text-white bg-gradient-to-r from-indigo-500 to-purple-600 rounded-full">
                                            {{ $day['data']['total_movements'] }}
                                        </span>
                                    </div>
                                </div>

                                <div class="space-y-2 max-h-40 overflow-y-auto">
                                    @foreach($day['data']['movements']->take(5) as $movement)
                                        <div class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                            <div class="flex items-center space-x-2">
                                                @php
                                                    $typeConfig = [
                                                        'restock' => ['icon' => '📦', 'color' => 'text-emerald-600 dark:text-emerald-400'],
                                                        'adjustment' => ['icon' => '⚖️', 'color' => 'text-amber-600 dark:text-amber-400'],
                                                        'damage' => ['icon' => '⚠️', 'color' => 'text-red-600 dark:text-red-400'],
                                                    ];
                                                    $config = $typeConfig[$movement->movement_type] ?? ['icon' => '📝', 'color' => 'text-gray-600 dark:text-gray-400'];
                                                @endphp
                                                <span class="text-sm">{{ $config['icon'] }}</span>
                                                <span class="text-xs font-semibold {{ $config['color'] }} capitalize">
                                                    {{ $movement->movement_type }}
                                                </span>
                                            </div>
                                            <div class="text-right">
                                                <div class="text-xs font-bold {{ $movement->quantity_change > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                                    {{ $movement->quantity_change > 0 ? '+' : '' }}{{ $movement->quantity_change }}
                                                </div>
                                            </div>
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 truncate ml-6">
                                            {{ $movement->productVariant?->product?->name }}
                                            <span class="text-gray-400 dark:text-gray-500">({{ $movement->productVariant?->sku }})</span>
                                        </div>
                                    @endforeach

                                    @if($day['data']['movements']->count() > 5)
                                        <div class="text-xs text-gray-400 dark:text-gray-500 text-center pt-2">
                                            <span class="inline-flex items-center px-2 py-1 bg-gray-200 dark:bg-gray-600 rounded-full">
                                                +{{ $day['data']['movements']->count() - 5 }} more movements
                                            </span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif
                @endif
            </div>
        @endforeach
    @endforeach
</div>
