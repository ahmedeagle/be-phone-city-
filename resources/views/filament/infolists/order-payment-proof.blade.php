<div class="space-y-2">
    @php
        $order = $getRecord();
        $transaction = $order->currentPaymentTransaction;
        $proofPath = $transaction?->payment_proof_path;
        $extension = $proofPath ? pathinfo($proofPath, PATHINFO_EXTENSION) : '';
        $isPdf = strtolower($extension) === 'pdf';
        $isImage = in_array(strtolower($extension), ['jpg', 'jpeg', 'png', 'gif', 'webp']);
        $proofUrl = $transaction ? route('admin.payment-transactions.proof', ['transaction' => $transaction->id]) : '#';
    @endphp

    @if ($transaction && $proofPath)
        @if ($isPdf)
            <div class="rounded-lg border border-gray-300 dark:border-gray-600 p-4 bg-gray-50 dark:bg-gray-800">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        ملف PDF - إثبات الدفع
                    </span>
                    <a href="{{ $proofUrl }}" target="_blank"
                        class="text-primary-600 hover:text-primary-700 text-sm font-medium">
                        عرض الملف ↗
                    </a>
                </div>
                <object data="{{ $proofUrl }}" type="application/pdf" class="w-full" style="height: 500px;">
                    <p class="text-center py-4">
                        <a href="{{ $proofUrl }}" target="_blank" class="text-primary-600 hover:text-primary-700">
                            انقر هنا لعرض ملف PDF
                        </a>
                    </p>
                </object>
            </div>
        @elseif($isImage)
            <div class="rounded-lg border border-gray-300 dark:border-gray-600 overflow-hidden">
                <a href="{{ $proofUrl }}" target="_blank">
                    <img src="{{ $proofUrl }}" alt="إثبات الدفع"
                        class="w-full h-auto max-h-[500px] object-contain bg-gray-50 dark:bg-gray-800" />
                </a>
            </div>
            <div class="flex justify-end">
                <a href="{{ $proofUrl }}" target="_blank" class="text-sm text-primary-600 hover:text-primary-700">
                    عرض بالحجم الكامل ↗
                </a>
            </div>
        @else
            <div class="rounded-lg border border-gray-300 dark:border-gray-600 p-4 bg-gray-50 dark:bg-gray-800 text-center">
                <a href="{{ $proofUrl }}" target="_blank" class="text-primary-600 hover:text-primary-700 text-sm font-medium">
                    تنزيل الملف ↗
                </a>
            </div>
        @endif
    @endif
</div>
