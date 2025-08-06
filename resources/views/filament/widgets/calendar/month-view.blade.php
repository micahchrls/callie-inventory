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
                    'medium' => 'bg-gradient-to-br from-amber-50 to-orange-50 dark:from-amber-950 dark:to-orange-950',
                    'high' => 'bg-gradient-to-br from-red-50 to-rose-50 dark:from-red-950 dark:to-rose-950',
                    'very_high' => 'bg-gradient-to-br from-purple-50 to-pink-50 dark:from-purple-950 dark:to-pink-950'
                ];

                // Determine intensity based on total stock out
                $totalStockOut = $day['data']['total_stock_out'] ?? 0;
                $intensity = 'none';
                if ($totalStockOut > 0) {
                    if ($totalStockOut <= 5) $intensity = 'low';
                    elseif ($totalStockOut <= 15) $intensity = 'medium';
                    elseif ($totalStockOut <= 30) $intensity = 'high';
                    else $intensity = 'very_high';
                }

                $baseClass = $intensityClasses[$intensity];
            @endphp

            <div class="relative min-h-[140px] {{ $baseClass }} {{ $day['is_current_month'] ? '' : 'opacity-50' }} {{ $day['is_today'] ? 'ring-2 ring-blue-500 ring-inset shadow-lg' : '' }} rounded-xl transition-all duration-200 hover:shadow-md hover:scale-105 group">
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

                    <!-- Total Stock Out Badge -->
                    @if($day['data']['total_stock_out'] > 0)
                        <div class="relative">
                            <span class="inline-flex items-center justify-center w-8 h-8 text-xs font-bold text-white bg-gradient-to-r from-red-500 to-rose-600 rounded-full shadow-lg">
                                {{ $day['data']['total_stock_out'] }}
                            </span>
                            <div class="absolute -top-1 -right-1 w-3 h-3 bg-gradient-to-r from-red-400 to-rose-500 rounded-full animate-ping"></div>
                        </div>
                    @endif
                </div>

                <!-- Platform Stock Out Data -->
                <div class="px-3 pb-3 space-y-1">
                    @if(isset($day['data']['platform_stock_outs']) && !empty($day['data']['platform_stock_outs']))
                        @foreach($day['data']['platform_stock_outs'] as $platformName => $stockOut)
                            @if($stockOut > 0)
                                @php
                                    // Define platform-specific styling
                                    $platformConfig = match($platformName) {
                                        'Shopee' => [
                                            'bg' => 'bg-gradient-to-r from-orange-100 to-amber-100 dark:from-orange-900 dark:to-amber-900',
                                            'text' => 'text-orange-700 dark:text-orange-300',
                                            'badge' => 'bg-orange-500 text-white',
                                            'icon' => '🛒'
                                        ],
                                        'TikTok' => [
                                            'bg' => 'bg-gradient-to-r from-pink-100 to-rose-100 dark:from-pink-900 dark:to-rose-900',
                                            'text' => 'text-pink-700 dark:text-pink-300',
                                            'badge' => 'bg-pink-500 text-white',
                                            'icon' => '🎵'
                                        ],
                                        'Lazada' => [
                                            'bg' => 'bg-gradient-to-r from-blue-100 to-cyan-100 dark:from-blue-900 dark:to-cyan-900',
                                            'text' => 'text-blue-700 dark:text-blue-300',
                                            'badge' => 'bg-blue-500 text-white',
                                            'icon' => '🛍️'
                                        ],
                                        'Unknown' => [
                                            'bg' => 'bg-gradient-to-r from-gray-100 to-slate-100 dark:from-gray-900 dark:to-slate-900',
                                            'text' => 'text-gray-700 dark:text-gray-300',
                                            'badge' => 'bg-gray-500 text-white',
                                            'icon' => '❓'
                                        ],
                                        default => [
                                            'bg' => 'bg-gradient-to-r from-purple-100 to-violet-100 dark:from-purple-900 dark:to-violet-900',
                                            'text' => 'text-purple-700 dark:text-purple-300',
                                            'badge' => 'bg-purple-500 text-white',
                                            'icon' => '📦'
                                        ]
                                    };
                                @endphp
                                <div class="flex items-center justify-between {{ $platformConfig['bg'] }} rounded-lg px-2 py-1.5 shadow-sm">
                                    <div class="flex items-center text-xs font-semibold {{ $platformConfig['text'] }}">
                                        <span class="text-sm mr-1">{{ $platformConfig['icon'] }}</span>
                                        {{ $platformName }}
                                    </div>
                                    <span class="{{ $platformConfig['badge'] }} text-xs font-bold px-2 py-0.5 rounded-full">
                                        {{ $stockOut }}
                                    </span>
                                </div>
                            @endif
                        @endforeach
                    @endif
                </div>

                <!-- Hover Tooltip for Platform Summary -->
                @if(isset($day['data']['platform_stock_outs']) && !empty($day['data']['platform_stock_outs']) && array_sum($day['data']['platform_stock_outs']) > 0)
                    <div class="absolute inset-0 bg-black bg-opacity-0 hover:bg-opacity-50 rounded-xl transition-all duration-200 opacity-0 group-hover:opacity-100 flex items-center justify-center">
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-2xl p-4 max-w-sm transform scale-95 group-hover:scale-100 transition-transform duration-200 border border-gray-200 dark:border-gray-600">
                            <div class="text-sm font-bold text-gray-900 dark:text-gray-100 mb-2 text-center border-b border-gray-200 dark:border-gray-600 pb-2">
                                {{ $day['date']->format('M j, Y') }}
                            </div>

                            <!-- Platform Summary -->
                            <div class="space-y-2 mb-3">
                                @foreach($day['data']['platform_stock_outs'] as $platformName => $stockOut)
                                    @if($stockOut > 0)
                                        @php
                                            $platformConfig = match($platformName) {
                                                'Shopee' => ['text' => 'text-orange-600 dark:text-orange-400', 'bg' => 'bg-orange-100 dark:bg-orange-900', 'text-bg' => 'text-orange-800 dark:text-orange-200', 'icon' => '🛒'],
                                                'TikTok' => ['text' => 'text-pink-600 dark:text-pink-400', 'bg' => 'bg-pink-100 dark:bg-pink-900', 'text-bg' => 'text-pink-800 dark:text-pink-200', 'icon' => '🎵'],
                                                'Lazada' => ['text' => 'text-blue-600 dark:text-blue-400', 'bg' => 'bg-blue-100 dark:bg-blue-900', 'text-bg' => 'text-blue-800 dark:text-blue-200', 'icon' => '🛍️'],
                                                'Unknown' => ['text' => 'text-gray-600 dark:text-gray-400', 'bg' => 'bg-gray-100 dark:bg-gray-900', 'text-bg' => 'text-gray-800 dark:text-gray-200', 'icon' => '❓'],
                                                default => ['text' => 'text-purple-600 dark:text-purple-400', 'bg' => 'bg-purple-100 dark:bg-purple-900', 'text-bg' => 'text-purple-800 dark:text-purple-200', 'icon' => '📦']
                                            };
                                        @endphp
                                        <div class="flex justify-between items-center">
                                            <span class="text-xs {{ $platformConfig['text'] }} font-medium flex items-center">
                                                <span class="mr-1">{{ $platformConfig['icon'] }}</span>
                                                Stock Out - {{ $platformName }}:
                                            </span>
                                            <span class="text-xs font-bold {{ $platformConfig['bg'] }} {{ $platformConfig['text-bg'] }} px-2 py-0.5 rounded">{{ $stockOut }}</span>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        @endforeach
    @endforeach
</div>
