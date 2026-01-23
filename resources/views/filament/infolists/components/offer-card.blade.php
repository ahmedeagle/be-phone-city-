{{-- resources/views/filament/infolists/components/offer-card.blade.php --}}
@if ($getState())
    @php
        $offer = $getState();
    @endphp
    <div
        class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 p-4">
        <div class="flex items-start gap-4">
            @if ($offer->image)
                <img src="{{ Storage::url($offer->image) }}" alt="{{ $offer->name_ar }}"
                    class="w-20 h-20 rounded-lg object-cover shadow-md">
            @else
                <div
                    class="w-20 h-20 rounded-lg bg-gradient-to-br from-green-400 to-emerald-500 flex items-center justify-center shadow-md">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7">
                        </path>
                    </svg>
                </div>
            @endif

            <div class="flex-1">
                <h4 class="text-lg font-bold text-gray-900 dark:text-white mb-1">{{ $offer->name_ar }}</h4>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">{{ $offer->name_en }}</p>

                <div class="flex items-center gap-2">
                    <span
                        class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 dark:bg-green-900/50 text-green-800 dark:text-green-200">
                        @if ($offer->type === 'percentage')
                            خصم {{ $offer->value }}%
                        @else
                            خصم {{ number_format($offer->value, 2) }} ر.س
                        @endif
                    </span>

                    @if ($offer->start_at || $offer->end_at)
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            @if ($offer->start_at && $offer->end_at)
                                من {{ $offer->start_at->format('Y-m-d') }} إلى {{ $offer->end_at->format('Y-m-d') }}
                            @elseif($offer->end_at)
                                حتى {{ $offer->end_at->format('Y-m-d') }}
                            @elseif($offer->start_at)
                                من {{ $offer->start_at->format('Y-m-d') }}
                            @endif
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </div>
@else
    <div class="text-center text-gray-500 dark:text-gray-400 py-4">
        لا يوجد عروض متاحة حالياً
    </div>
@endif
