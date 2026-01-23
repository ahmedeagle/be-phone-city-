{{-- resources/views/filament/infolists/components/payment-methods-option-pricing.blade.php --}}
@php
    $data = $getState();
    $option = $data['option'];
    $basePrice = $data['base_price'];
    $paymentMethods = $data['payment_methods'];
    $product = $option->product;
    $bestOffer = $product->getBestOffer();

    // Calculate price after offer if exists
    if ($bestOffer) {
        $discount = $bestOffer->type === 'percentage' ? $basePrice * ($bestOffer->value / 100) : $bestOffer->value;
        $finalBasePrice = max(0, $basePrice - $discount);
    } else {
        $finalBasePrice = $basePrice;
    }
@endphp

<div class="mt-3 pt-3 border-t dark:border-gray-700">
    <h5 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">الأسعار حسب طرق الدفع:</h5>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
        @foreach ($paymentMethods as $method)
            <div
                class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-3 hover:shadow-md transition-shadow">
                <div class="flex items-center gap-2 mb-2">
                    @if ($method->image)
                        <img src="{{ Storage::url($method->image) }}" alt="{{ $method->name_ar }}"
                            class="w-8 h-8 rounded object-cover">
                    @else
                        <div class="w-8 h-8 rounded bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z">
                                </path>
                            </svg>
                        </div>
                    @endif
                    <span class="text-xs font-medium text-gray-700 dark:text-gray-300">{{ $method->name_ar }}</span>
                </div>

                <div class="space-y-1 text-xs">
                    @if ($bestOffer)
                        <div class="flex justify-between text-gray-500 dark:text-gray-400">
                            <span>قبل الخصم:</span>
                            <span class="line-through">{{ number_format($basePrice, 2) }}</span>
                        </div>
                    @endif

                    <div
                        class="flex justify-between pt-1 border-t dark:border-gray-700 font-bold text-primary-600 dark:text-primary-400">
                        <span>الإجمالي:</span>
                        <span>{{ number_format($finalBasePrice, 2) }} ر.س</span>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
