<!-- Week Header -->
<div class="grid grid-cols-7 gap-px bg-gradient-to-r from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-600 p-1 rounded-t-2xl">
    @foreach(['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'] as $day)
        <div class="bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-700 px-3 py-4 text-center text-sm font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wide rounded-lg">
            {{ $day }}
        </div>
    @endforeach
</div>

<!-- Week Grid -->
<div class="grid grid-cols-7 gap-2 p-2 bg-gray-50 dark:bg-gray-800 min-h-[500px]">
    @foreach($weekData as $day)
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

        <div class="relative {{ $baseClass }} {{ $primaryClass }} {{ $day['is_today'] ? 'ring-2 ring-blue-500 ring-inset shadow-lg' : '' }} rounded-xl transition-all duration-200 hover:shadow-md border border-gray-200 dark:border-gray-600">
            <!-- Date Header -->
            <div class="p-4 border-b border-gray-200 dark:border-gray-600">
                @if($day['is_today'])
                    <div class="flex items-center justify-center w-10 h-10 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-full text-lg font-bold shadow-lg mx-auto">
                        {{ $day['date']->day }}
                    </div>
                @else
                    <div class="text-center">
                        <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $day['date']->day }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">{{ $day['date']->format('M') }}</div>
                    </div>
                @endif

                @if($day['data']['total_movements'] > 0)
                    <div class="mt-2 flex justify-center">
                        <span class="inline-flex items-center px-3 py-1 text-sm font-bold text-white bg-gradient-to-r from-indigo-500 to-purple-600 rounded-full shadow-lg">
                            {{ $day['data']['total_movements'] }} movements
                        </span>
                    </div>
                @endif
            </div>

            <!-- Movement List -->
            <div class="p-3 space-y-2 max-h-96 overflow-y-auto">
                @if($day['data']['total_movements'] > 0)
                    @foreach($day['data']['movements']->take(8) as $movement)
                        @php
                            $typeConfig = [
                                'restock' => ['icon' => '📦', 'bg' => 'bg-emerald-100 dark:bg-emerald-900', 'text' => 'text-emerald-700 dark:text-emerald-300'],
                                'adjustment' => ['icon' => '⚖️', 'bg' => 'bg-amber-100 dark:bg-amber-900', 'text' => 'text-amber-700 dark:text-amber-300'],
                                'damage' => ['icon' => '⚠️', 'bg' => 'bg-red-100 dark:bg-red-900', 'text' => 'text-red-700 dark:text-red-300'],
                            ];
                            $config = $typeConfig[$movement->movement_type] ?? ['icon' => '📝', 'bg' => 'bg-gray-100 dark:bg-gray-700', 'text' => 'text-gray-700 dark:text-gray-300'];
                        @endphp

                        <div class="p-3 {{ $config['bg'] }} rounded-lg shadow-sm border border-gray-200 dark:border-gray-600">
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center space-x-2">
                                    <span class="text-lg">{{ $config['icon'] }}</span>
                                    <span class="text-sm font-semibold {{ $config['text'] }} capitalize">
                                        {{ $movement->movement_type }}
                                    </span>
                                </div>
                                <div class="text-sm font-bold {{ $movement->quantity_change > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                    {{ $movement->quantity_change > 0 ? '+' : '' }}{{ $movement->quantity_change }}
                                </div>
                            </div>
                            <div class="text-xs text-gray-600 dark:text-gray-400">
                                <div class="font-medium">{{ $movement->productVariant?->product?->name }}</div>
                                <div class="text-gray-500 dark:text-gray-500">SKU: {{ $movement->productVariant?->sku }}</div>
                                <div class="text-gray-500 dark:text-gray-500">{{ $movement->created_at->format('g:i A') }}</div>
                            </div>
                        </div>
                    @endforeach

                    @if($day['data']['movements']->count() > 8)
                        <div class="text-center py-2">
                            <span class="inline-flex items-center px-3 py-1 text-xs bg-gray-200 dark:bg-gray-600 text-gray-600 dark:text-gray-400 rounded-full">
                                +{{ $day['data']['movements']->count() - 8 }} more movements
                            </span>
                        </div>
                    @endif
                @else
                    <div class="text-center py-8 text-gray-400 dark:text-gray-500">
                        <div class="text-3xl mb-2">📭</div>
                        <div class="text-sm">No movements</div>
                    </div>
                @endif
            </div>
        </div>
    @endforeach
</div>
