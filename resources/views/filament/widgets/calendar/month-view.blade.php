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
                     onclick="window.location.href='{{ route('filament.admin.pages.stock-transactions', ['date' => $day['dateKey']]) }}'"
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
                        <div class="flex flex-col items-end gap-1">
                            @if(($day['data']['stock_in'] ?? 0) > 0)
                                @php
                                    $inPlatforms = !empty($day['data']['platform_data']) ? array_keys($day['data']['platform_data']) : [];
                                    $inPlatformParam = count($inPlatforms) == 1 ? $inPlatforms[0] : null;
                                    $inPlatformText = count($inPlatforms) > 1 ? 'Multi' : ($inPlatforms[0] ?? 'Direct');
                                @endphp
                                <a href="{{ route('filament.admin.pages.stock-transactions', array_filter(['date' => $day['dateKey'], 'movement_type' => 'in', 'platform' => $inPlatformParam])) }}"
                                   class="group block relative z-10"
                                   onclick="event.stopPropagation()"
                                   wire:navigate>
                                    <span class="inline-flex items-center justify-between min-w-[60px] px-1.5 py-0.5 text-xs font-semibold rounded shadow-sm hover:shadow-md transition-all duration-200 hover:scale-105" style="background-color: #10b981; color: white;">
                                        <svg class="w-3 h-3 mr-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"></path>
                                        </svg>
                                        <span class="font-bold">{{ $day['data']['stock_in'] }}</span>
                                        <span class="ml-1 text-[10px] font-normal opacity-90">{{ Str::limit($inPlatformText, 6) }}</span>
                                    </span>
                                </a>
                            @endif
                            @if(($day['data']['stock_out'] ?? 0) > 0)
                                @php
                                    $outPlatforms = !empty($day['data']['platform_data']) ? array_keys($day['data']['platform_data']) : [];
                                    $outPlatformParam = count($outPlatforms) == 1 ? $outPlatforms[0] : null;
                                    $outPlatformText = count($outPlatforms) > 1 ? 'Multi' : ($outPlatforms[0] ?? 'Direct');
                                @endphp
                                <a href="{{ route('filament.admin.pages.stock-transactions', array_filter(['date' => $day['dateKey'], 'movement_type' => 'out', 'platform' => $outPlatformParam])) }}"
                                   class="group block relative z-10"
                                   onclick="event.stopPropagation()"
                                   wire:navigate>
                                    <span class="inline-flex items-center justify-between min-w-[60px] px-1.5 py-0.5 text-xs font-semibold rounded shadow-sm hover:shadow-md transition-all duration-200 hover:scale-105" style="background-color: #ef4444; color: white;">
                                        <svg class="w-3 h-3 mr-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6"></path>
                                        </svg>
                                        <span class="font-bold">{{ $day['data']['stock_out'] }}</span>
                                        <span class="ml-1 text-[10px] font-normal opacity-90">{{ Str::limit($outPlatformText, 6) }}</span>
                                    </span>
                                </a>
                            @endif
                        </div>
                    @endif
                </div>

            </div>
        @endforeach
    @endforeach
</div>
