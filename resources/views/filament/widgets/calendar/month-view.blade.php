{{-- Calendar Header --}}
<div class="grid grid-cols-7 gap-px bg-gray-100 dark:bg-gray-800 p-px">
    @foreach(['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $day)
        <div class="bg-gray-50 dark:bg-gray-900 p-2 text-center">
            <span class="text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">
                {{ $day }}
            </span>
        </div>
    @endforeach
</div>

{{-- Calendar Grid --}}
<div class="grid grid-cols-7 gap-px bg-gray-100 dark:bg-gray-800 p-px">
    @foreach($weeks as $week)
        @foreach($week as $day)
            @php
                $intensityColors = [
                    'none' => 'bg-white dark:bg-gray-900',
                    'low' => 'bg-blue-50 dark:bg-blue-950/30',
                    'medium' => 'bg-amber-50 dark:bg-amber-950/30',
                    'high' => 'bg-red-50 dark:bg-red-950/30',
                    'very_high' => 'bg-purple-50 dark:bg-purple-950/30'
                ];
                
                $baseClass = $intensityColors[$day['intensity']];
                $isClickable = $day['data']['has_data'] && $day['is_current_month'];
            @endphp

            <div class="relative min-h-[120px] {{ $baseClass }} {{ !$day['is_current_month'] ? 'opacity-40' : '' }} 
                        {{ $day['is_today'] ? 'ring-2 ring-primary-500' : '' }} 
                        {{ $isClickable ? 'cursor-pointer hover:shadow-lg hover:z-10' : '' }} 
                        transition-all duration-200 p-2"
                 @if($isClickable)
                     wire:click="$dispatch('openUrl', { url: '{{ route('filament.admin.pages.stockout-details', ['date' => $day['dateKey']]) }}' })"
                 @endif>
                
                {{-- Date Header --}}
                <div class="flex items-start justify-between mb-2">
                    <span class="text-sm font-semibold {{ $day['is_current_month'] ? 'text-gray-900 dark:text-gray-100' : 'text-gray-400 dark:text-gray-500' }}">
                        {{ $day['date']->day }}
                    </span>
                    
                    @if($day['is_today'])
                        <span class="px-2 py-0.5 text-xs font-medium bg-primary-500 text-white rounded-full">
                            Today
                        </span>
                    @endif
                </div>

                {{-- Platform Data --}}
                @if($day['data']['has_data'])
                    <div class="space-y-1">
                        {{-- Total Summary if there are stock outs --}}
                        @if($day['data']['total_stock_outs'] > 0)
                            @if(count($day['data']['platform_data']) > 1)
                                <div class="text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1 border-b border-gray-200 dark:border-gray-700 pb-1">
                                    Total: {{ $day['data']['total_stock_outs'] }} stock outs
                                </div>
                            @endif
                            
                            {{-- Platform Breakdown --}}
                            @foreach($day['data']['platform_data'] as $platform => $data)
                                @php
                                    $platformColors = [
                                        'Shopee' => 'bg-orange-100 text-orange-700 dark:bg-orange-900/50 dark:text-orange-300',
                                        'TikTok' => 'bg-pink-100 text-pink-700 dark:bg-pink-900/50 dark:text-pink-300',
                                        'Lazada' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300',
                                        'Unknown' => 'bg-gray-100 text-gray-700 dark:bg-gray-900/50 dark:text-gray-300',
                                    ];
                                    $color = $platformColors[$platform] ?? $platformColors['Unknown'];
                                @endphp
                                
                                <a href="{{ route('filament.admin.pages.stockout-details', ['date' => $day['dateKey'], 'platform' => $platform]) }}"
                                   class="block group/item hover:scale-105 transition-transform"
                                   wire:navigate>
                                    <div class="flex items-center justify-between {{ $color }} rounded px-2 py-1 text-xs">
                                        <span class="font-medium truncate">{{ $platform }}</span>
                                        <span class="font-bold">{{ $data['stock_out_count'] }}</span>
                                    </div>
                                </a>
                            @endforeach
                        @endif
                        
                        {{-- View All Link --}}
                        @if($day['data']['total_stock_outs'] > 0)
                            <a href="{{ route('filament.admin.pages.stockout-details', ['date' => $day['dateKey']]) }}"
                               class="block text-center mt-2"
                               wire:navigate>
                                <span class="text-xs text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300 font-medium">
                                    View Details →
                                </span>
                            </a>
                        @endif
                    </div>
                @endif
            </div>
        @endforeach
    @endforeach
</div>

{{-- Legend --}}
<div class="mt-4 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
    <div class="flex items-center justify-between">
        <div class="flex items-center space-x-4">
            <span class="text-xs font-medium text-gray-600 dark:text-gray-400">Intensity:</span>
            <div class="flex items-center space-x-2">
                <div class="flex items-center space-x-1">
                    <div class="w-4 h-4 bg-blue-50 dark:bg-blue-950/30 rounded"></div>
                    <span class="text-xs text-gray-600 dark:text-gray-400">Low (≤5)</span>
                </div>
                <div class="flex items-center space-x-1">
                    <div class="w-4 h-4 bg-amber-50 dark:bg-amber-950/30 rounded"></div>
                    <span class="text-xs text-gray-600 dark:text-gray-400">Medium (≤15)</span>
                </div>
                <div class="flex items-center space-x-1">
                    <div class="w-4 h-4 bg-red-50 dark:bg-red-950/30 rounded"></div>
                    <span class="text-xs text-gray-600 dark:text-gray-400">High (≤30)</span>
                </div>
                <div class="flex items-center space-x-1">
                    <div class="w-4 h-4 bg-purple-50 dark:bg-purple-950/30 rounded"></div>
                    <span class="text-xs text-gray-600 dark:text-gray-400">Very High (>30)</span>
                </div>
            </div>
        </div>
        
        <div class="text-xs text-gray-500 dark:text-gray-400">
            Click on any date with data to view details
        </div>
    </div>
</div>
