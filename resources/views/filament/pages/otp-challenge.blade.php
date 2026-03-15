<div class="min-h-screen flex items-center justify-center bg-gray-100 dark:bg-gray-900 p-4">
    <div class="w-full max-w-md bg-white dark:bg-gray-800 rounded-xl shadow-lg p-8">
        <div class="text-center mb-6">
            <div class="text-6xl mb-4">🔐</div>
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-2">التحقق بالرمز</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                تم إرسال رمز التحقق إلى بريدك الإلكتروني
            </p>
        </div>

        <form wire:submit.prevent="verify" class="space-y-4">
            {{ $this->form }}

            <button type="submit"
                class="w-full px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                تحقق
            </button>
        </form>

        <div class="text-center mt-4">
            <button wire:click="resend"
                class="text-sm text-gray-500 hover:text-blue-600 dark:text-gray-400 dark:hover:text-blue-400 transition-colors">
                إعادة إرسال الرمز
            </button>
        </div>

        @if (session()->has('message'))
            <div class="mt-4 text-center text-sm text-green-600">{{ session('message') }}</div>
        @endif
    </div>
</div>
