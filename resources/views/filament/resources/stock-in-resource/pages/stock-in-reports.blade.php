<x-filament-panels::page>
    <div class="space-y-6">

        @if($totalItems > 0)
            {{-- Stock In Grouped by Reason --}}
            @foreach($groupedStockIns as $group)
                <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <div class="fi-section-header flex items-center gap-x-3 overflow-hidden px-6 py-4">
                        <div class="flex items-center justify-between w-full">
                            <h3 class="fi-section-header-heading text-base font-semibold leading-6 text-gray-950 dark:text-white capitalize">
                                {{ str_replace('_', ' ', $group['reason']) }}
                            </h3>
                            <div class="flex items-center space-x-4">
                                <span class="fi-badge fi-color-success inline-flex items-center justify-center gap-x-1 rounded-md text-xs font-medium ring-1 ring-inset px-2 py-1 fi-color-custom bg-custom-50 text-custom-600 ring-custom-600/10 dark:bg-custom-400/10 dark:text-custom-400 dark:ring-custom-400/30" style="--c-50:var(--success-50);--c-400:var(--success-400);--c-600:var(--success-600);">
                                    {{ $group['count'] }} {{ Str::plural('item', $group['count']) }}
                                </span>
                                <span class="fi-badge fi-color-info inline-flex items-center justify-center gap-x-1 rounded-md text-xs font-medium ring-1 ring-inset px-2 py-1 fi-color-custom bg-custom-50 text-custom-600 ring-custom-600/10 dark:bg-custom-400/10 dark:text-custom-400 dark:ring-custom-400/30" style="--c-50:var(--info-50);--c-400:var(--info-400);--c-600:var(--info-600);">
                                    {{ number_format($group['total_quantity']) }} units
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="fi-section-content p-6">
                        <div class="fi-ta overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                            <div class="fi-ta-content relative divide-y divide-gray-200 overflow-x-auto dark:divide-white/10">
                                <div class="fi-ta-header-row divide-x divide-gray-200 dark:divide-white/5">
                                    <div class="fi-ta-header-cell px-3 py-3.5 sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                                        <span class="fi-ta-header-cell-label text-sm font-semibold text-gray-950 dark:text-white">Product</span>
                                    </div>
                                    <div class="fi-ta-header-cell px-3 py-3.5 sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                                        <span class="fi-ta-header-cell-label text-sm font-semibold text-gray-950 dark:text-white">Variant SKU</span>
                                    </div>
                                    <div class="fi-ta-header-cell px-3 py-3.5 sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                                        <span class="fi-ta-header-cell-label text-sm font-semibold text-gray-950 dark:text-white">Quantity</span>
                                    </div>
                                    <div class="fi-ta-header-cell px-3 py-3.5 sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                                        <span class="fi-ta-header-cell-label text-sm font-semibold text-gray-950 dark:text-white">Time</span>
                                    </div>
                                    <div class="fi-ta-header-cell px-3 py-3.5 sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                                        <span class="fi-ta-header-cell-label text-sm font-semibold text-gray-950 dark:text-white">User</span>
                                    </div>
                                </div>

                                @foreach($group['items'] as $stockIn)
                                    <div class="fi-ta-row [@media(hover:hover)]:transition [@media(hover:hover)]:duration-75 hover:bg-gray-50 dark:hover:bg-white/5 divide-x divide-gray-200 dark:divide-white/5">
                                        <div class="fi-ta-cell p-0 first-of-type:ps-1 last-of-type:pe-1 sm:first-of-type:ps-3 sm:last-of-type:pe-3">
                                            <div class="fi-ta-col-wrp px-3 py-4">
                                                <div class="fi-ta-text text-sm leading-6 text-gray-950 dark:text-white">
                                                    {{ $stockIn->product->name ?? 'N/A' }}
                                                </div>
                                            </div>
                                        </div>
                                        <div class="fi-ta-cell p-0 first-of-type:ps-1 last-of-type:pe-1 sm:first-of-type:ps-3 sm:last-of-type:pe-3">
                                            <div class="fi-ta-col-wrp px-3 py-4">
                                                <div class="fi-ta-text text-sm leading-6 text-gray-950 dark:text-white">
                                                    {{ $stockIn->productVariant->sku ?? 'N/A' }}
                                                </div>
                                            </div>
                                        </div>
                                        <div class="fi-ta-cell p-0 first-of-type:ps-1 last-of-type:pe-1 sm:first-of-type:ps-3 sm:last-of-type:pe-3">
                                            <div class="fi-ta-col-wrp px-3 py-4">
                                                <span class="fi-badge fi-color-success inline-flex items-center justify-center gap-x-1 rounded-md text-xs font-medium ring-1 ring-inset px-2 py-1 fi-color-custom bg-custom-50 text-custom-600 ring-custom-600/10 dark:bg-custom-400/10 dark:text-custom-400 dark:ring-custom-400/30" style="--c-50:var(--success-50);--c-400:var(--success-400);--c-600:var(--success-600);">
                                                    +{{ number_format($stockIn->total_quantity) }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="fi-ta-cell p-0 first-of-type:ps-1 last-of-type:pe-1 sm:first-of-type:ps-3 sm:last-of-type:pe-3">
                                            <div class="fi-ta-col-wrp px-3 py-4">
                                                <div class="fi-ta-text text-sm leading-6 text-gray-500 dark:text-gray-400">
                                                    {{ $stockIn->created_at->format('h:i A') }}
                                                </div>
                                            </div>
                                        </div>
                                        <div class="fi-ta-cell p-0 first-of-type:ps-1 last-of-type:pe-1 sm:first-of-type:ps-3 sm:last-of-type:pe-3">
                                            <div class="fi-ta-col-wrp px-3 py-4">
                                                <div class="fi-ta-text text-sm leading-6 text-gray-500 dark:text-gray-400">
                                                    {{ $stockIn->user->name ?? 'System' }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        @else
            {{-- Empty State --}}
            <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="fi-section-content p-12">
                    <div class="fi-empty-state mx-auto max-w-lg text-center">
                        <div class="fi-empty-state-icon-ctn mb-4 flex justify-center">
                            <div class="fi-empty-state-icon flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-800">
                                <svg class="fi-empty-state-icon-svg h-6 w-6 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                                </svg>
                            </div>
                        </div>
                        <h3 class="fi-empty-state-heading text-base font-semibold leading-6 text-gray-950 dark:text-white">No Stock In Records</h3>
                        <p class="fi-empty-state-description mt-1 text-sm text-gray-500 dark:text-gray-400">
                            No stock in transactions were found for {{ \Carbon\Carbon::parse($date)->format('F j, Y') }}.
                        </p>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
