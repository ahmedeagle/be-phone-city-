{{-- resources/views/filament/infolists/components/payment-methods-pricing.blade.php --}}
@php
    $data = $getState();
    $product = $data['product'];
    $paymentMethods = $data['payment_methods'];
    $basePrice = $product->getFinalPrice(); // Price after offers
    $originalPrice = $product->main_price;
    $hasOffer = $product->getBestOffer() !== null;
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    @foreach ($paymentMethods as $method)
        <div
            class="rounded-lg border-2 border-gray-200 dark:border-gray-700 hover:border-primary-500 dark:hover:border-primary-600 transition-all duration-200 overflow-hidden bg-white dark:bg-gray-800 shadow-sm hover:shadow-md">
            {{-- Payment Method Header --}}
            <div class="bg-gradient-to-r from-primary-500 to-primary-600 dark:from-primary-600 dark:to-primary-700 p-3">
                <div class="flex items-center gap-3">
                    @if ($method->image)
                        <img src="{{ Storage::url($method->image) }}" alt="{{ $method->name_ar }}"
                            class="w-12 h-12 rounded-lg object-cover bg-white p-1">
                    @else
                        <div class="w-12 h-12 rounded-lg bg-white/20 flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z">
                                </path>
                            </svg>
                        </div>
                    @endif

                    <div class="flex-1">
                        <h4 class="font-bold text-white">{{ $method->name_ar }}</h4>
                        <p class="text-xs text-white/80">{{ $method->name_en }}</p>
                    </div>
                </div>
            </div>

            {{-- Pricing Details --}}
            <div class="p-4 space-y-3">
                {{-- Original Price (if there's an offer) --}}
                @if ($hasOffer)
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">السعر الأصلي:</span>
                        <span
                            class="text-gray-500 dark:text-gray-400 line-through">{{ number_format($originalPrice, 2) }}
                            ر.س</span>
                    </div>
                @endif

                {{-- Total Price --}}
                <div class="flex items-center justify-between pt-2 border-t-2 border-gray-200 dark:border-gray-700">
                    <span class="font-bold text-gray-900 dark:text-white">الإجمالي:</span>
                    <span
                        class="text-2xl font-bold text-primary-600 dark:text-primary-400">{{ number_format($basePrice, 2) }}
                        ر.س</span>
                </div>

                {{-- Savings Badge (if offer exists) --}}
                @if ($hasOffer)
                    @php
                        $savings = $originalPrice - $basePrice;
                    @endphp
                    <div
                        class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-2 text-center">
                        <p class="text-sm font-medium text-green-700 dark:text-green-300">
                            🎉 توفير {{ number_format($savings, 2) }} ر.س
                        </p>
                    </div>
                @endif
            </div>
        </div>
    @endforeach
</div>

@if ($paymentMethods->isEmpty())
    <div class="text-center text-gray-500 dark:text-gray-400 py-8">
        لا توجد طرق دفع نشطة حالياً
    </div>
@endif
