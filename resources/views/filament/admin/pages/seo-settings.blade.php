<x-filament-panels::page>
    <div class="mb-4 rounded-xl border p-4"><strong>النتيجة الفنية الحالية: {{ $this->technicalScore }}/100</strong><p class="text-sm text-gray-500">تعتمد على عنوان ووصف وكلمات وصورة المشاركة وبيانات النشاط.</p></div><div class="flex gap-3 mb-4">
        <x-filament::button wire:click="generate" icon="heroicon-o-sparkles">إنشاء بالذكاء الاصطناعي</x-filament::button>
        <x-filament::button wire:click="save" color="success" icon="heroicon-o-check">حفظ الإعدادات</x-filament::button>
    </div>
    {{ $this->form }}
</x-filament-panels::page>