<x-filament-widgets::widget>
    <x-filament::section>
        {{-- Header Section --}}
        <div slot="headerEnd" class="flex items-center justify-between w-full mb-4">
            <div class="flex items-center space-x-4">
                <div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                        Stock Movement Calendar
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ $this->currentPeriodTitle }}
                    </p>
                </div>
            </div>

            <div class="flex items-center space-x-4">
                {{-- Navigation Controls --}}
                <x-filament::button
                    wire:click="previousMonth"
                    size="sm"
                    color="gray"
                    icon="heroicon-m-chevron-left"
                />

                <x-filament::button
                    wire:click="goToToday"
                    size="sm"
                >
                    Today
                </x-filament::button>

                <x-filament::button
                    wire:click="nextMonth"
                    size="sm"
                    color="gray"
                    icon="heroicon-m-chevron-right"
                />
            </div>
        </div>

        {{-- Calendar Grid --}}
        <div class="overflow-hidden bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700">
            @include('filament.widgets.calendar.month-view', ['weeks' => $this->calendarWeeks])
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
