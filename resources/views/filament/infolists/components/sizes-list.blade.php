{{-- resources/views/filament/infolists/components/sizes-list.blade.php --}}
@php
    $data = $getState();
    $sizes = $data['sizes'];
    $product = $data['product'];
    $paymentMethods = \App\Models\PaymentMethod::active()->get();
@endphp

<div class="space-y-6">
    @foreach ($sizes as $size)
        @php
            $basePrice = $size->price ?? $product->main_price;
            $bestOffer = $product->getBestOffer();

            // Calculate price after offer
            if ($bestOffer) {
                $discount =
                    $bestOffer->type === 'percentage' ? $basePrice * ($bestOffer->value / 100) : $bestOffer->value;
                $finalBasePrice = max(0, $basePrice - $discount);
            } else {
                $finalBasePrice = $basePrice;
            }
        @endphp

        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 bg-white dark:bg-gray-800">
            {{-- Size Header --}}
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                {{-- Size Display --}}
                <div>
                    <label class="text-xs font-medium text-gray-500 dark:text-gray-400 block mb-2">المقاس</label>
                    <div class="flex flex-col gap-1">
                        <span
                            class="inline-flex items-center px-3 py-2 rounded-lg text-base font-bold bg-primary-100 dark:bg-primary-900/50 text-primary-800 dark:text-primary-200 w-fit">
                            {{ $size->value_ar }}
                        </span>
                        <span class="text-xs text-gray-600 dark:text-gray-400">{{ $size->value_en }}</span>
                    </div>
                </div>

                {{-- Price --}}
                <div>
                    <label class="text-xs font-medium text-gray-500 dark:text-gray-400 block mb-2">سعر إضافي</label>
                    @if ($size->price)
                        <span
                            class="inline-flex items-center px-3 py-2 rounded-lg text-sm font-medium bg-blue-100 dark:bg-blue-900/50 text-blue-800 dark:text-blue-200">
                            {{ number_format($size->price, 2) }} ر.س
                        </span>
                    @else
                        <span
                            class="inline-flex items-center px-3 py-2 rounded-lg text-sm font-medium bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                            نفس السعر الأساسي
                        </span>
                    @endif
                </div>

                {{-- SKU --}}
                <div>
                    <label class="text-xs font-medium text-gray-500 dark:text-gray-400 block mb-2">SKU</label>
                    @if ($size->sku)
                        <div class="flex items-center gap-2">
                            <span
                                class="inline-flex items-center px-3 py-2 rounded-lg text-sm font-mono bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                                {{ $size->sku }}
                            </span>
                            <button onclick="navigator.clipboard.writeText('{{ $size->sku }}')"
                                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200" title="نسخ">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z">
                                    </path>
                                </svg>
                            </button>
                        </div>
                    @else
                        <span
                            class="inline-flex items-center px-3 py-2 rounded-lg text-sm bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400">
                            غير محدد
                        </span>
                    @endif
                </div>
            </div>

            {{-- Size Images --}}
            @if ($size->images->isNotEmpty())
                <div class="mb-4">
                    <label class="text-xs font-medium text-gray-500 dark:text-gray-400 block mb-2">الصور</label>
                    <div class="grid grid-cols-3 md:grid-cols-5 lg:grid-cols-7 gap-2">
                        @foreach ($size->images as $image)
                            <img src="{{ \Storage::url($image->path) }}" alt="Size image"
                                class="w-full h-24 object-cover rounded-lg border border-gray-200 dark:border-gray-700 hover:scale-105 transition-transform cursor-pointer">
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Payment Methods Pricing --}}
            <div class="pt-4 border-t dark:border-gray-700">
                <h5 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">الأسعار حسب طرق الدفع:</h5>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    @foreach ($paymentMethods as $method)
                        <div
                            class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 p-3 hover:shadow-md transition-shadow">
                            <div class="flex items-center gap-2 mb-2">
                                @if ($method->image)
                                    <img src="{{ \Storage::url($method->image) }}" alt="{{ $method->name_ar }}"
                                        class="w-8 h-8 rounded object-cover">
                                @else
                                    <div
                                        class="w-8 h-8 rounded bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z">
                                            </path>
                                        </svg>
                                    </div>
                                @endif
                                <span
                                    class="text-xs font-medium text-gray-700 dark:text-gray-300">{{ $method->name_ar }}</span>
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
        </div>
    @endforeach
</div>

@if ($sizes->isEmpty())
    <div class="text-center text-gray-500 dark:text-gray-400 py-8">
        لا توجد مقاسات متاحة
    </div>
@endif
