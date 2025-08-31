{{-- Calendar Header --}}
<div class="grid grid-cols-7 gap-1 bg-gray-50 dark:bg-gray-900 p-2 rounded-t-xl">
    @foreach(['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $day)
        <div class="bg-white dark:bg-gray-900 p-3 text-center rounded-lg border border-gray-200 dark:border-gray-700">
            <span class="text-md font-bold text-gray-700 dark:text-white uppercase tracking-wider">
                {{ $day }}
            </span>
        </div>
    @endforeach
</div>

{{-- Calendar Grid --}}
<div class="grid grid-cols-7 gap-1 bg-gray-50 dark:bg-gray-900 p-2 rounded-b-xl ">
    @foreach($weeks as $week)
        @foreach($week as $day)
            @php
                $intensityColors = [
                    'none' => 'bg-white dark:bg-gray-900 border-gray-200 dark:border-gray-800',
                    'low' => 'bg-gradient-to-br from-blue-50 to-sky-50 dark:from-blue-950/30 dark:to-sky-950/30 border-blue-300 dark:border-blue-800',
                    'medium' => 'bg-gradient-to-br from-amber-50 to-yellow-50 dark:from-amber-950/30 dark:to-yellow-950/30 border-amber-300 dark:border-amber-800',
                    'high' => 'bg-gradient-to-br from-orange-50 to-red-50 dark:from-orange-950/30 dark:to-red-950/30 border-orange-300 dark:border-orange-800',
                    'very_high' => 'bg-gradient-to-br from-red-100 to-rose-100 dark:from-red-950/40 dark:to-rose-950/40 border-red-400 dark:border-red-800'
                ];

                $baseClass = $intensityColors[$day['intensity']];
                $isClickable = $day['data']['has_data'] && $day['is_current_month'];

                $platformIcons = [
                    'Shopee' => '🛒',
                    'TikTok' => '🎵',
                ];

                $platformBadgeColors = [
                    'Shopee' => 'bg-orange-500 dark:bg-orange-600 text-white shadow-sm',
                    'TikTok' => 'bg-gradient-to-r from-pink-500 to-rose-500 dark:from-pink-600 dark:to-rose-600 text-white shadow-sm',
                ];
            @endphp

            <div class="relative min-h-[140px] {{ $baseClass }} border-2 border-gray-200 dark:border-gray-700 {{ !$day['is_current_month'] ? 'opacity-40' : '' }}
                        {{ $day['is_today'] ? 'ring-2 ring-offset-2 ring-primary-500 dark:ring-offset-gray-900 shadow-xl' : 'shadow-sm' }}
                        {{ $isClickable ? 'cursor-pointer hover:shadow-lg hover:scale-[1.01] hover:z-20 hover:border-primary-300 dark:hover:border-primary-700' : '' }}
                        rounded-xl transition-all duration-200 p-3 overflow-hidden"
                 @if($isClickable)
                     onclick="window.location.href=''"
                 @endif>

                {{-- Date Header --}}
                <div class="flex items-start justify-between mb-2">
                    @if($day['is_today'])
                        <span
                            class="inline-flex items-center justify-center w-8 h-8 text-sm font-bold bg-gradient-to-br from-primary-500 to-primary-600 dark:from-primary-400 dark:to-primary-500 text-white rounded-full shadow-md">
                            {{ $day['date']->day }}
                        </span>
                    @else
                        <span
                            class="text-sm font-bold {{ $day['is_current_month'] ? 'text-gray-800 dark:text-gray-100' : 'text-gray-400 dark:text-gray-600' }}">
                            {{ $day['date']->day }}
                        </span>
                    @endif

                    {{-- Stock In/Out Badges --}}
                    @if($day['is_current_month'] && $day['data']['has_data'])
                        <div class="flex flex-col items-end gap-1" >
                            {{-- Stock In Badge --}}
                            @if(($day['data']['total_stock_in'] ?? 0) > 0)
                                @php
                                    $stockInTypes = [];
                                    if (($day['data']['platform_data']['restock_stock_in'] ?? 0) > 0) {
                                        $stockInTypes[] = 'Restock';
                                    }
                                    if (($day['data']['platform_data']['others_stock_in'] ?? 0) > 0) {
                                        $stockInTypes[] = 'Return';
                                    }
                                    $inTypeText = count($stockInTypes) > 1 ? 'Multi' : ($stockInTypes[0] ?? 'Stock In');
                                @endphp
                                <a href="{{ route('filament.admin.resources.stock-ins.reports', ['date' => $day['date']->format('Y-m-d')]) }}"
                                   class="group block relative z-10"
                                   onclick="event.stopPropagation()"
                                   wire:navigate>
                                    <span class="inline-flex items-center justify-between min-w-[60px] px-1.5 py-0.5 text-xs font-semibold rounded shadow-sm hover:shadow-md transition-all duration-200 hover:scale-105" style="background-color: #10b981; color: white;">
                                       <span style="font-size: 8px">Total Stock In: </span>
                                        <span class="font-bold" style="font-size: 8px">{{ $day['data']['total_stock_in'] }}</span>
                                    </span>
                                </a>
                            @endif

                            {{-- Stock Out Badge --}}
                            @if(($day['data']['total_stock_out'] ?? 0) > 0)
                                @php
                                    $stockOutPlatforms = [];
                                    if (($day['data']['platform_data']['tiktok_stock_out'] ?? 0) > 0) {
                                        $stockOutPlatforms[] = 'TikTok';
                                    }
                                    if (($day['data']['platform_data']['shopee_stock_out'] ?? 0) > 0) {
                                        $stockOutPlatforms[] = 'Shopee';
                                    }
                                    if (($day['data']['platform_data']['bazar_stock_out'] ?? 0) > 0) {
                                        $stockOutPlatforms[] = 'Bazar';
                                    }
                                    if (($day['data']['platform_data']['others_stock_out'] ?? 0) > 0) {
                                        $stockOutPlatforms[] = 'Others';
                                    }
                                    $outPlatformText = count($stockOutPlatforms) > 1 ? 'Multi' : ($stockOutPlatforms[0] ?? 'Out');
                                @endphp
                                <a href="{{ route('filament.admin.resources.stock-outs.reports', ['date' => $day['date']->format('Y-m-d')]) }}"
                                   class="group block relative z-10"
                                   onclick="event.stopPropagation()"
                                   wire:navigate>
                                    <span class="inline-flex items-center justify-between min-w-[60px] px-1.5 py-0.5 text-xs font-semibold rounded shadow-sm hover:shadow-md transition-all duration-200 hover:scale-105" style="background-color: #ef4444; color: white;">
                                        <span style="font-size: 8px">Total Stock Out: </span>
                                        <span class="font-bold mr-2" style="font-size: 8px">{{ $day['data']['total_stock_out'] }}</span>
                                    </span>
                                </a>
                            @endif

                            {{-- Platform-specific small badges below --}}
                            <div class="flex flex-wrap justify-end gap-1 mt-1">
                                @if(($day['data']['platform_data']['tiktok_stock_out'] ?? 0) > 0)
                                    <span class="inline-flex items-center px-1 py-0.5 font-medium rounded" style="background-color: rgba(236, 72, 153, 0.1); color: #be185d; font-size: 10px;">
                                        Tiktok: {{ $day['data']['platform_data']['tiktok_stock_out'] }}
                                    </span>
                                @endif
                                @if(($day['data']['platform_data']['shopee_stock_out'] ?? 0) > 0)
                                    <span class="inline-flex items-center px-1 py-0.5 text-[8px] font-medium rounded" style="background-color: rgba(249, 115, 22, 0.1); color: #ea580c; font-size: 10px;">
                                        Shopee: {{ $day['data']['platform_data']['shopee_stock_out'] }}
                                    </span>
                                @endif
                                @if(($day['data']['platform_data']['bazar_stock_out'] ?? 0) > 0)
                                    <span class="inline-flex items-center px-1 py-0.5 text-[10px] font-medium rounded" style="background-color: rgba(59, 130, 246, 0.1); color: #2563eb; font-size: 10px;">
                                        Bazar: {{ $day['data']['platform_data']['bazar_stock_out'] }}
                                    </span>
                                @endif
                                @if(($day['data']['platform_data']['restock_stock_in'] ?? 0) > 0)
                                    <span class="inline-flex items-center px-1 py-0.5 text-[10px] font-medium rounded" style="background-color: rgba(34, 197, 94, 0.1); color: #16a34a; font-size: 10px;">
                                        Others: {{ $day['data']['platform_data']['restock_stock_in'] }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>

            </div>
        @endforeach
    @endforeach
</div>
