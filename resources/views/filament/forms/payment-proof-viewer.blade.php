@php
    // Variables are passed from the form component
    $proofPath = $proofPath ?? $record->payment_proof_path ?? null;
    $extension = $proofPath ? pathinfo($proofPath, PATHINFO_EXTENSION) : '';
    $isPdf = $isPdf ?? (strtolower($extension) === 'pdf');
    $isImage = $isImage ?? in_array(strtolower($extension), ['jpg', 'jpeg', 'png', 'gif']);
    $proofUrl = $proofUrl ?? route('admin.payment-transactions.proof', ['transaction' => $record->id]);
@endphp

@if ($isPdf)
    <div class="rounded-lg border border-gray-300 dark:border-gray-600 p-4 bg-gray-50 dark:bg-gray-800">
        <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                ملف PDF - إثبات الدفع
            </span>
            <a href="{{ $proofUrl }}" target="_blank"
                class="text-primary-600 hover:text-primary-700 text-sm font-medium">
                عرض الملف
            </a>
        </div>
        <object data="{{ $proofUrl }}" type="application/pdf" class="w-full" style="height: 400px;">
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
                class="w-full h-auto max-h-[400px] object-contain bg-gray-50 dark:bg-gray-800" />
        </a>
    </div>
    <div class="flex justify-end mt-2">
        <a href="{{ $proofUrl }}" target="_blank" class="text-sm text-primary-600 hover:text-primary-700">
            عرض بالحجم الكامل
        </a>
    </div>
@else
    <div class="rounded-lg border border-gray-300 dark:border-gray-600 p-4 bg-gray-50 dark:bg-gray-800">
        <p class="text-sm text-gray-700 dark:text-gray-300 text-center">
            نوع الملف غير مدعوم للعرض
        </p>
        <div class="flex justify-center mt-2">
            <a href="{{ $proofUrl }}" target="_blank"
                class="text-primary-600 hover:text-primary-700 text-sm font-medium">
                تنزيل الملف
            </a>
        </div>
    </div>
@endif
