<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Product Information using Infolist -->
        <div class="filament-infolist">
            {{ $this->infolist }}
        </div>

        <!-- Warning Banner with enhanced styling -->
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-m-exclamation-triangle class="h-5 w-5 text-warning-500" />
                    <span class="text-warning-600 dark:text-warning-400 font-semibold">
                        Stock Removal Operation
                    </span>
                </div>
            </x-slot>

            <div class="rounded-lg bg-warning-50 p-4 border border-warning-200 dark:bg-warning-900/10 dark:border-warning-800/50">
                <div class="text-sm text-warning-800 dark:text-warning-200">
                    <div class="font-medium mb-2">Important Notice:</div>
                    <ul class="list-disc list-inside space-y-1 text-warning-700 dark:text-warning-300">
                        <li>This action will permanently remove stock from inventory</li>
                        <li>A stock movement record will be created for audit purposes</li>
                        <li>This operation cannot be undone once confirmed</li>
                        <li>Use <kbd class="px-2 py-1 text-xs font-semibold bg-white rounded border border-warning-300 dark:bg-gray-800 dark:border-warning-600">Ctrl+S</kbd> to process or <kbd class="px-2 py-1 text-xs font-semibold bg-white rounded border border-warning-300 dark:bg-gray-800 dark:border-warning-600">Esc</kbd> to cancel</li>
                    </ul>
                </div>
            </div>
        </x-filament::section>

        <!-- Main Form with enhanced styling -->
        <div class="filament-form">
            {{ $this->form }}
        </div>

        <!-- Action Buttons with enhanced layout -->
        <x-filament::section>
            <div class="flex flex-col sm:flex-row justify-end gap-3 pt-4">
                @foreach ($this->getCachedFormActions() as $action)
                    {{ $action }}
                @endforeach
            </div>
        </x-filament::section>

        <!-- Enhanced Stock Movement History -->
        @if($this->record->stockMovements()->exists())
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-m-clock class="h-5 w-5" />
                    Recent Stock Movements
                </div>
            </x-slot>

            <x-slot name="description">
                Last 5 stock movements for this product variant.
                <a href="/admin/stock-movements?tableFilters[product_variant_id][value]={{ $this->record->id }}"
                   class="text-primary-600 hover:text-primary-500 dark:text-primary-400 font-medium"
                   target="_blank">
                    View all movements →
                </a>
            </x-slot>

            <div class="grid gap-3">
                @foreach($this->record->stockMovements()->with('user')->orderBy('created_at', 'desc')->limit(5)->get() as $movement)
                    <div class="flex justify-between items-center p-4 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-750 transition-colors">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 font-medium">
                                <x-filament::badge
                                    :color="$movement->quantity_change > 0 ? 'success' : 'danger'"
                                    size="sm">
                                    {{ ucwords(str_replace('_', ' ', $movement->movement_type)) }}
                                </x-filament::badge>

                                @if($movement->quantity_change > 0)
                                    <x-heroicon-m-arrow-trending-up class="h-4 w-4 text-success-500" />
                                @else
                                    <x-heroicon-m-arrow-trending-down class="h-4 w-4 text-danger-500" />
                                @endif
                            </div>

                            <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                <time datetime="{{ $movement->created_at->toISOString() }}">
                                    {{ $movement->created_at->format('M d, Y H:i') }}
                                </time>
                                by {{ $movement->user ? $movement->user->name : 'System' }}
                            </div>

                            @if($movement->reason)
                                <div class="text-sm text-gray-500 dark:text-gray-500 mt-1 italic">
                                    "{{ $movement->reason }}"
                                </div>
                            @endif
                        </div>

                        <div class="text-right ml-4">
                            <div class="font-bold text-lg {{ $movement->quantity_change > 0 ? 'text-success-600 dark:text-success-400' : 'text-danger-600 dark:text-danger-400' }}">
                                {{ $movement->quantity_change > 0 ? '+' : '' }}{{ number_format($movement->quantity_change) }}
                            </div>
                            <div class="text-sm text-gray-500 dark:text-gray-400 flex items-center gap-1">
                                <span>{{ number_format($movement->quantity_before) }}</span>
                                <x-heroicon-m-arrow-right class="h-3 w-3" />
                                <span class="font-medium">{{ number_format($movement->quantity_after) }}</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            @if($this->record->stockMovements()->count() > 5)
                <div class="text-center mt-4">
                    <x-filament::button
                        tag="a"
                        href="/admin/stock-movements?tableFilters[product_variant_id][value]={{ $this->record->id }}"
                        target="_blank"
                        color="gray"
                        outlined
                        size="sm">
                        <x-heroicon-m-eye class="h-4 w-4 mr-1" />
                        View All {{ $this->record->stockMovements()->count() }} Movements
                    </x-filament::button>
                </div>
            @endif
        </x-filament::section>
        @endif

        <!-- Enhanced Quick Stats Section -->
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-m-chart-bar class="h-5 w-5" />
                    Quick Stats
                </div>
            </x-slot>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="text-center p-4 rounded-lg bg-gray-50 dark:bg-gray-800">
                    <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                        {{ number_format($this->record->quantity_in_stock) }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Current Stock</div>
                </div>

                <div class="text-center p-4 rounded-lg bg-gray-50 dark:bg-gray-800">
                    <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                        {{ number_format($this->record->reorder_level) }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Reorder Level</div>
                </div>

                <div class="text-center p-4 rounded-lg bg-gray-50 dark:bg-gray-800">
                    <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                        {{ $this->record->stockMovements()->count() }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Total Movements</div>
                </div>

                <div class="text-center p-4 rounded-lg bg-gray-50 dark:bg-gray-800">
                    <div class="text-sm font-medium {{ $this->record->is_active ? 'text-success-600 dark:text-success-400' : 'text-danger-600 dark:text-danger-400' }}">
                        @if($this->record->is_active)
                            <x-heroicon-m-check-circle class="h-5 w-5 inline mr-1" />
                            Active
                        @else
                            <x-heroicon-m-x-circle class="h-5 w-5 inline mr-1" />
                            Inactive
                        @endif
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Status</div>
                </div>
            </div>
        </x-filament::section>

        <!-- Keyboard Shortcuts Help -->
        <x-filament::section collapsible collapsed>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-m-computer-desktop class="h-5 w-5" />
                    Keyboard Shortcuts
                </div>
            </x-slot>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                <div class="flex justify-between items-center">
                    <span>Process Stock Out</span>
                    <kbd class="px-2 py-1 font-mono text-xs bg-gray-100 rounded border dark:bg-gray-800">Ctrl+S</kbd>
                </div>
                <div class="flex justify-between items-center">
                    <span>Cancel Operation</span>
                    <kbd class="px-2 py-1 font-mono text-xs bg-gray-100 rounded border dark:bg-gray-800">Esc</kbd>
                </div>
                <div class="flex justify-between items-center">
                    <span>View Details</span>
                    <kbd class="px-2 py-1 font-mono text-xs bg-gray-100 rounded border dark:bg-gray-800">Ctrl+V</kbd>
                </div>
            </div>
        </x-filament::section>
    </div>

    @push('scripts')
    <script>
        // Enhanced keyboard navigation
        document.addEventListener('keydown', function(event) {
            // Prevent default actions if form is being used
            if (event.target.tagName === 'INPUT' || event.target.tagName === 'TEXTAREA' || event.target.tagName === 'SELECT') {
                return;
            }

            // Focus on quantity input when pressing 'Q'
            if (event.key === 'q' || event.key === 'Q') {
                event.preventDefault();
                const quantityInput = document.querySelector('input[name="data.quantity_out"]');
                if (quantityInput) {
                    quantityInput.focus();
                }
            }

            // Focus on reason select when pressing 'R'
            if (event.key === 'r' || event.key === 'R') {
                event.preventDefault();
                const reasonSelect = document.querySelector('select[name="data.reason_type"]');
                if (reasonSelect) {
                    reasonSelect.focus();
                }
            }
        });

        // Auto-calculate and preview stock levels
        const quantityInput = document.querySelector('input[name="data.quantity_out"]');
        const newStockInput = document.querySelector('input[name="data.new_stock"]');
        const currentStock = {{ $this->record->quantity_in_stock }};

        if (quantityInput && newStockInput) {
            quantityInput.addEventListener('input', function() {
                const quantity = parseInt(this.value) || 0;
                const newStock = Math.max(0, currentStock - quantity);
                newStockInput.value = newStock;

                // Update visual feedback
                if (quantity > currentStock) {
                    this.classList.add('border-danger-300', 'ring-danger-500');
                    this.classList.remove('border-gray-300', 'ring-primary-500');
                } else {
                    this.classList.remove('border-danger-300', 'ring-danger-500');
                    this.classList.add('border-gray-300');
                }
            });
        }
    </script>
    @endpush

    @push('styles')
    <style>
        /* Enhanced visual feedback for form validation */
        .fi-input-wrp input:invalid {
            @apply border-danger-300 ring-danger-500/20;
        }

        .fi-input-wrp input:valid {
            @apply border-success-300 ring-success-500/20;
        }

        /* Better focus states */
        .fi-input-wrp input:focus,
        .fi-select select:focus,
        .fi-textarea textarea:focus {
            @apply ring-2 ring-primary-500/20 border-primary-400;
        }

        /* Smooth transitions */
        .fi-section,
        .fi-input-wrp,
        .fi-btn {
            @apply transition-all duration-200;
        }

        /* Enhanced badge styling */
        .fi-badge {
            @apply shadow-sm;
        }
    </style>
    @endpush
</x-filament-panels::page>
