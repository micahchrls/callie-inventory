<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Back Navigation -->
        <div class="flex items-center space-x-2">
            <a href="{{ url()->previous() }}" 
               class="flex items-center text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to Calendar
            </a>
        </div>

        <!-- Data Table -->
        <div class="bg-white dark:bg-gray-900 rounded-lg shadow">
            {{ $this->table }}
        </div>
    </div>
</x-filament-panels::page>
