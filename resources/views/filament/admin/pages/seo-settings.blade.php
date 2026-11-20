<x-filament-panels::page>
    <div class="flex gap-3 mb-4">
        <x-filament::button wire:click="generate" icon="heroicon-o-sparkles">إنشاء بالذكاء الاصطناعي</x-filament::button>
        <x-filament::button wire:click="save" color="success" icon="heroicon-o-check">حفظ الإعدادات</x-filament::button>
    </div>
    {{ $this->form }}
</x-filament-panels::page>