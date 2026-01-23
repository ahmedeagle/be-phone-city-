{{-- resources/views/filament/infolists/components/color-display.blade.php --}}
@php
    $color = $getState();
@endphp

<div class="flex items-center gap-2">
    <div class="w-10 h-10 rounded-lg border-2 border-gray-300 dark:border-gray-600 shadow-sm"
        style="background-color: {{ $color }};"></div>
    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $color }}</span>
</div>
