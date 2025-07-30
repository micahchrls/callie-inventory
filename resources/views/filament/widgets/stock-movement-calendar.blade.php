<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center justify-between w-full">
                <div class="flex items-center space-x-4">
                    <div class="flex items-center space-x-2">
                        <div class="text-2xl">📊</div>
                        <div>
                            <h3 class="text-lg font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
                                Stock Movement Calendar
                            </h3>
                            <div class="text-sm text-gray-500 dark:text-gray-400 font-medium">
                                {{ $this->getCurrentPeriodTitle() }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center space-x-3">
                    <!-- View Mode Selector -->
                    <div class="flex items-center bg-gray-100 dark:bg-gray-700 rounded-xl p-1">
                        @foreach($this->getViewModes() as $mode => $config)
                            <button
                                type="button"
                                wire:click="setViewMode('{{ $mode }}')"
                                class="flex items-center space-x-1 px-3 py-1.5 text-xs font-semibold rounded-lg transition-all duration-200 {{ $this->viewMode === $mode ? 'bg-white dark:bg-gray-600 text-blue-600 dark:text-blue-400 shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200' }}"
                            >
                                <span>{{ $config['icon'] }}</span>
                                <span>{{ $config['label'] }}</span>
                            </button>
                        @endforeach
                    </div>

                    <!-- Navigation Controls -->
                    <div class="flex items-center space-x-2">
                        <button
                            type="button"
                            wire:click="previousPeriod"
                            class="inline-flex items-center justify-center w-10 h-10 text-gray-600 bg-gradient-to-r from-gray-50 to-gray-100 border border-gray-200 rounded-xl hover:from-blue-50 hover:to-blue-100 hover:text-blue-600 hover:border-blue-300 dark:from-gray-800 dark:to-gray-700 dark:border-gray-600 dark:text-gray-400 dark:hover:from-blue-900 dark:hover:to-blue-800 transition-all duration-200 shadow-sm hover:shadow-md"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                            </svg>
                        </button>

                        <button
                            type="button"
                            wire:click="goToToday"
                            class="px-4 py-2 text-sm font-semibold text-white bg-gradient-to-r from-blue-500 to-purple-600 rounded-xl hover:from-blue-600 hover:to-purple-700 transition-all duration-200 shadow-sm hover:shadow-md transform hover:scale-105"
                        >
                            Today
                        </button>

                        <button
                            type="button"
                            wire:click="nextPeriod"
                            class="inline-flex items-center justify-center w-10 h-10 text-gray-600 bg-gradient-to-r from-gray-50 to-gray-100 border border-gray-200 rounded-xl hover:from-blue-50 hover:to-blue-100 hover:text-blue-600 hover:border-blue-300 dark:from-gray-800 dark:to-gray-700 dark:border-gray-600 dark:text-gray-400 dark:hover:from-blue-900 dark:hover:to-blue-800 transition-all duration-200 shadow-sm hover:shadow-md"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </x-slot>

        @php $calendarData = $this->getCalendarWeeks(); @endphp

        <div class="overflow-hidden bg-white dark:bg-gray-900 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700">
            @if($calendarData['mode'] === 'day')
                <!-- Day View -->
                @include('filament.widgets.calendar.day-view', ['dayData' => $calendarData['data']])
            @elseif($calendarData['mode'] === 'week')
                <!-- Week View -->
                @include('filament.widgets.calendar.week-view', ['weekData' => $calendarData['data']])
            @else
                <!-- Month View -->
                @include('filament.widgets.calendar.month-view', ['monthData' => $calendarData['data']])
            @endif
        </div>

        <!-- Enhanced Legend with Statistics -->
        <div class="mt-6 bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-700 rounded-2xl p-4 border border-gray-200 dark:border-gray-600">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex flex-wrap items-center gap-6">
                    @foreach($this->getMovementTypeConfig() as $type => $config)
                        <div class="flex items-center space-x-2">
                            <div class="flex items-center space-x-1">
                                <span class="text-sm">{{ $config['icon'] }}</span>
                                <span class="w-4 h-4 bg-{{ $config['color'] }}-500 rounded-full shadow-sm"></span>
                            </div>
                            <div>
                                <div class="text-sm font-semibold text-gray-700 dark:text-gray-300">{{ $config['label'] }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $config['description'] }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="flex items-center space-x-3 text-sm">
                    <div class="flex items-center space-x-1">
                        <div class="w-3 h-3 bg-gradient-to-r from-blue-300 to-indigo-300 rounded opacity-50"></div>
                        <span class="text-gray-600 dark:text-gray-400">Low Activity</span>
                    </div>
                    <div class="flex items-center space-x-1">
                        <div class="w-3 h-3 bg-gradient-to-r from-blue-500 to-indigo-500 rounded"></div>
                        <span class="text-gray-600 dark:text-gray-400">High Activity</span>
                    </div>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
