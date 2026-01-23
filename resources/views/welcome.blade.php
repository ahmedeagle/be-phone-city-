<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>City Phone | لوحة التحكم</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600&display=swap" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
            <style>
        body { font-family: 'Inter', sans-serif; }
            </style>
    </head>
<body class="antialiased bg-gray-50 flex items-center justify-center min-h-screen">
    <div class="max-w-md w-full p-8 bg-white rounded-2xl shadow-xl text-center border border-gray-100 mx-4">
        <div class="mb-8 flex justify-center">
            @if(file_exists(public_path('assets/images/logo.svg')))
                <img src="{{ asset('assets/images/logo.svg') }}" alt="City Phone" class="h-20 w-auto">
                    @else
                <div class="h-20 w-20 bg-blue-100 rounded-full flex items-center justify-center">
                    <span class="text-blue-600 text-2xl font-bold">CP</span>
                </div>
            @endif
        </div>
        
        <h1 class="text-2xl font-bold text-gray-900 mb-2">City Phone</h1>
        <p class="text-gray-500 mb-8 leading-relaxed">نظام إدارة المتجر والطلبات المتكامل</p>
        
        <div class="space-y-4">
            <a href="{{ url('/dashboard') }}" 
               class="flex items-center justify-center w-full py-3 px-4 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl transition duration-200 shadow-lg shadow-blue-200">
                <span>الدخول إلى لوحة التحكم</span>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                                    </svg>
                                </a>
            
            <p class="text-sm text-gray-400">سجل الدخول للمتابعة إلى لوحة القيادة</p>
        </div>

        <div class="mt-12 pt-6 border-t border-gray-50 text-[10px] uppercase tracking-widest text-gray-400">
            &copy; {{ date('Y') }} City Phone Dashboard
        </div>
    </div>
    </body>
</html>
