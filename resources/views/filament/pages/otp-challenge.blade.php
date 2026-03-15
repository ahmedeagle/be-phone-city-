<x-filament-panels::page.simple>
    <div class="text-center mb-6">
        <div class="text-6xl mb-4">🔐</div>
        <p class="text-sm text-gray-500 dark:text-gray-400">
            تم إرسال رمز التحقق إلى بريدك الإلكتروني
        </p>
    </div>

    <x-filament-panels::form wire:submit="verify">
        {{ $this->form }}

        <x-filament::button type="submit" class="w-full">
            تحقق
        </x-filament::button>
    </x-filament-panels::form>

    <div class="text-center mt-4">
        <x-filament::button
            color="gray"
            size="sm"
            wire:click="resend"
        >
            إعادة إرسال الرمز
        </x-filament::button>
    </div>
</x-filament-panels::page.simple>
