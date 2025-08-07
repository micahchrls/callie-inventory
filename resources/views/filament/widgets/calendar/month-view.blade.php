{{-- Calendar Header --}}
<div class="grid grid-cols-7 gap-1 bg-gray-50 dark:bg-gray-900 p-2 rounded-t-xl">
    @foreach(['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $day)
        <div class="bg-white dark:bg-gray-900 p-3 text-center rounded-lg border border-gray-100 dark:border-gray-800">
            <span class="text-md font-bold text-gray-700 dark:text-gray-100 uppercase tracking-wider">
                {{ $day }}
            </span>
        </div>
    @endforeach
</div>

{{-- Calendar Grid --}}
<div class="grid grid-cols-7 gap-1 bg-gray-50 dark:bg-gray-900 p-2 rounded-b-xl">
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

            <div class="relative min-h-[140px] {{ $baseClass }} border-2 {{ !$day['is_current_month'] ? 'opacity-40' : '' }} 
                        {{ $day['is_today'] ? 'ring-2 ring-offset-2 ring-primary-500 dark:ring-offset-gray-900 shadow-xl' : 'shadow-sm' }} 
                        {{ $isClickable ? 'cursor-pointer hover:shadow-lg hover:scale-[1.01] hover:z-20 hover:border-primary-300 dark:hover:border-primary-700' : '' }} 
                        rounded-xl transition-all duration-200 p-3 overflow-hidden"
                 @if($isClickable)
                     wire:click="$dispatch('openUrl', { url: '{{ route('filament.admin.pages.stockout-details', ['date' => $day['dateKey']]) }}' })"
                 @endif>
                
                {{-- Date Header --}}
                <div class="flex items-start justify-between mb-2">
                    @if($day['is_today'])
                        <span class="inline-flex items-center justify-center w-8 h-8 text-sm font-bold bg-gradient-to-br from-primary-500 to-primary-600 dark:from-primary-400 dark:to-primary-500 text-white rounded-full shadow-md">
                            {{ $day['date']->day }}
                        </span>
                    @else
                        <span class="text-sm font-bold {{ $day['is_current_month'] ? 'text-gray-800 dark:text-gray-100' : 'text-gray-400 dark:text-gray-600' }}">
                            {{ $day['date']->day }}
                        </span>
                    @endif
                    
                    @if($day['data']['total_quantity'] > 0 && $day['is_current_month'])
                        <span class="inline-flex items-center px-2.5 py-1 text-xs font-bold rounded-full shadow-sm
                            {{ $day['data']['total_quantity'] > 300 ? 'bg-gradient-to-r from-red-500 to-rose-500 dark:from-red-600 dark:to-rose-600 text-white animate-pulse' : 
                               ($day['data']['total_quantity'] > 150 ? 'bg-gradient-to-r from-orange-500 to-amber-500 dark:from-orange-600 dark:to-amber-600 text-white' : 
                               ($day['data']['total_quantity'] > 50 ? 'bg-gradient-to-r from-yellow-400 to-amber-400 dark:from-yellow-500 dark:to-amber-500 text-white' : 
                               'bg-gradient-to-r from-green-500 to-emerald-500 dark:from-green-600 dark:to-emerald-600 text-white')) }}">
                            {{ $day['data']['total_quantity'] }}
                        </span>
                    @endif
                </div>

                {{-- Platform Data --}}
                @if($day['data']['has_data'] && $day['is_current_month'])
                    <div class="space-y-1.5">
                        @if($day['data']['total_stock_outs'] > 0)
                            {{-- Platform Breakdown with improved styling --}}
                            @foreach($day['data']['platform_data'] as $platform => $data)
                                @php
                                    $icon = $platformIcons[$platform] ?? $platformIcons['Unknown'];
                                    $badgeColor = $platformBadgeColors[$platform] ?? $platformBadgeColors['Unknown'];
                                @endphp
                                
                                <a href="{{ route('filament.admin.pages.stockout-details', ['date' => $day['dateKey'], 'platform' => $platform]) }}"
                                   class="group block"
                                   wire:navigate>
                                    <div class="flex items-center justify-between bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-lg px-2.5 py-2 
                                                border border-gray-200/70 dark:border-gray-700/70 hover:border-primary-400 dark:hover:border-primary-600 
                                                hover:bg-white dark:hover:bg-gray-800 hover:shadow-md transition-all duration-150">
                                        <div class="flex items-center space-x-2">
                                            <span class="text-base">{{ $icon }}</span>
                                            <span class="text-xs font-semibold text-gray-700 dark:text-gray-200 group-hover:text-primary-600 dark:group-hover:text-primary-400">
                                                {{ $platform }}
                                            </span>
                                        </div>
                                        <span class="inline-flex items-center justify-center min-w-[28px] h-6 px-2 text-xs font-bold rounded-full {{ $badgeColor }}">
                                            {{ $data['quantity'] }}
                                        </span>
                                    </div>
                                </a>
                            @endforeach
                            
                            {{-- Quick Stats --}}
                            @if(count($day['data']['platform_data']) > 1)
                                <div class="mt-2 pt-2 border-t border-gray-200/50 dark:border-gray-700/50">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-medium text-gray-600 dark:text-gray-400">Total</span>
                                        <span class="text-xs font-bold text-gray-800 dark:text-gray-100">
                                            {{ $day['data']['total_quantity'] }} units
                                        </span>
                                    </div>
                                </div>
                            @endif
                        @endif
                    </div>
                @elseif(!$day['is_current_month'])
                    {{-- Empty state for non-current month days --}}
                    <div class="absolute inset-0 flex items-center justify-center">
                        <div class="w-full h-full bg-gradient-to-br from-gray-100/50 to-gray-200/50 dark:from-gray-800/50 dark:to-gray-900/50"></div>
                    </div>
                @endif
            </div>
        @endforeach
    @endforeach
</div>

{{-- Legend and Instructions --}}
<div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
    {{-- Intensity Legend --}}
    {{-- <div class="p-4 bg-white dark:bg-gray-900 rounded-xl border-2 border-gray-200 dark:border-gray-800 shadow-sm">
        <h4 class="text-sm font-bold text-gray-800 dark:text-gray-100 mb-4 flex items-center">
            <span class="w-2 h-2 bg-gradient-to-r from-primary-400 to-primary-600 rounded-full mr-2"></span>
            Activity Levels
        </h4>
        <div class="grid grid-cols-2 gap-3">
            <div class="flex items-center space-x-2">
                <div class="w-5 h-5 bg-gradient-to-br from-blue-50 to-sky-50 dark:from-blue-950/30 dark:to-sky-950/30 rounded-md border-2 border-blue-300 dark:border-blue-800 shadow-sm"></div>
                <span class="text-xs font-medium text-gray-700 dark:text-gray-300">Low (1-50)</span>
            </div>
            <div class="flex items-center space-x-2">
                <div class="w-5 h-5 bg-gradient-to-br from-amber-50 to-yellow-50 dark:from-amber-950/30 dark:to-yellow-950/30 rounded-md border-2 border-amber-300 dark:border-amber-800 shadow-sm"></div>
                <span class="text-xs font-medium text-gray-700 dark:text-gray-300">Medium (51-150)</span>
            </div>
            <div class="flex items-center space-x-2">
                <div class="w-5 h-5 bg-gradient-to-br from-orange-50 to-red-50 dark:from-orange-950/30 dark:to-red-950/30 rounded-md border-2 border-orange-300 dark:border-orange-800 shadow-sm"></div>
                <span class="text-xs font-medium text-gray-700 dark:text-gray-300">High (151-300)</span>
            </div>
            <div class="flex items-center space-x-2">
                <div class="w-5 h-5 bg-gradient-to-br from-red-100 to-rose-100 dark:from-red-950/40 dark:to-rose-950/40 rounded-md border-2 border-red-400 dark:border-red-800 shadow-sm"></div>
                <span class="text-xs font-medium text-gray-700 dark:text-gray-300">Critical (300+)</span>
            </div>
        </div>
    </div> --}}
    
    {{-- Platform Legend --}}
    {{-- <div class="p-4 bg-white dark:bg-gray-900 rounded-xl border-2 border-gray-200 dark:border-gray-800 shadow-sm">
        <h4 class="text-sm font-bold text-gray-800 dark:text-gray-100 mb-4 flex items-center">
            <span class="w-2 h-2 bg-gradient-to-r from-primary-400 to-primary-600 rounded-full mr-2"></span>
            Platforms
        </h4>
        <div class="flex flex-wrap gap-3">
            <div class="flex items-center space-x-2 bg-orange-50 dark:bg-orange-950/20 px-3 py-1.5 rounded-lg border border-orange-200 dark:border-orange-800">
                <span class="text-base">🛒</span>
                <span class="text-xs font-semibold text-orange-700 dark:text-orange-300">Shopee</span>
            </div>
            <div class="flex items-center space-x-2 bg-pink-50 dark:bg-pink-950/20 px-3 py-1.5 rounded-lg border border-pink-200 dark:border-pink-800">
                <span class="text-base">🎵</span>
                <span class="text-xs font-semibold text-pink-700 dark:text-pink-300">TikTok</span>
            </div>
            <div class="flex items-center space-x-2 bg-blue-50 dark:bg-blue-950/20 px-3 py-1.5 rounded-lg border border-blue-200 dark:border-blue-800">
                <span class="text-base">🏪</span>
                <span class="text-xs font-semibold text-blue-700 dark:text-blue-300">Lazada</span>
            </div>
        </div>
        <p class="mt-3 text-xs text-gray-600 dark:text-gray-400 italic flex items-center">
            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
            </svg>
            Click any date to view detailed stock movements
        </p>
    </div> --}}
</div>
