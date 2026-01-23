<!-- Top Header -->
<header class="header-gradient header-shadow text-white relative z-50">
    <div class="flex items-center justify-between p-4 border-b border-black/10">
        
        <!-- زر المينو في الموبايل  -->
        <button id="sidebarToggle" class="md:hidden shadow-xl text-[#211C4D] hover:bg-white/20 rounded-full p-2 transition-all duration-300">
            <i class="fas fa-bars text-2xl"></i>
        </button>

        <!-- Page Title -->
        <div class="flex-1 text-center md:text-right md:flex-none">
            <h2 class="text-[14px] font-[700] text-[#211C4DCC] md:text-[24px]">هلا , {{ Auth::user()->name ?? 'مدير' }}</h2>
            <p class="hidden md:block text-[8px] font-[400] text-[#211C4D99] md:text-[12px]">دعنا نتحقق من متجرك اليوم</p>
        </div>

        
        <div class="flex items-center space-x-2 md:space-x-4">
            <!-- Notifications -->
            <div class="relative">
                <button id="notificationsButton" class="p-2 text-[#211C4DCC] hover:bg-[#2AA0DC]/10 hover:scale-110 rounded-full transition-all duration-300 relative group">
                    <span class="absolute top-2 left-6 bg-red-500 text-white rounded-full w-2 h-2"></span>
                    <img src="/icons/bell.svg" alt="Notifications" class="w-6 h-6 group-hover:scale-110 transition-transform duration-300">
                    <div class="absolute inset-0 from-[#2AA0DC]/20 to-transparent rounded-full opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                </button>
                
                <!-- Notifications Dropdown -->
                <div id="notificationsDropdown" class="absolute top-full left-0 mt-2 w-80 bg-white rounded-lg shadow-xl border border-gray-200 hidden z-50 transform origin-top transition-all duration-300 scale-95 opacity-0 md:left-0">
                    <div class="p-4 border-b border-gray-100 from-[#2AA0DC]/5 to-white">
                        <h3 class="text-[#211C4D] font-bold text-lg">الإشعارات</h3>
                    </div>
                    <div class="max-h-64 overflow-y-auto">
                        <!-- Notification Items -->
                        <div class="p-3 border-b border-gray-100 hover:bg-[#2AA0DC]/5 cursor-pointer transition-colors duration-200 group/item">
                            <div class="flex items-start space-x-3">
                                <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center group-hover/item:bg-blue-200 transition-colors duration-200">
                                    <i class="fas fa-shopping-cart text-blue-600 text-sm"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="text-[#211C4D] text-sm group-hover/item:text-[#2AA0DC] transition-colors duration-200">طلب جديد #12345</p>
                                    <p class="text-gray-500 text-xs">منذ 5 دقائق</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="p-3 border-b border-gray-100 hover:bg-[#2AA0DC]/5 cursor-pointer transition-colors duration-200 group/item">
                            <div class="flex items-start space-x-3">
                                <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center group-hover/item:bg-green-200 transition-colors duration-200">
                                    <i class="fas fa-user text-green-600 text-sm"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="text-[#211C4D] text-sm group-hover/item:text-[#2AA0DC] transition-colors duration-200">عميل جديد مسجل</p>
                                    <p class="text-gray-500 text-xs">منذ ساعة</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="p-3 hover:bg-[#2AA0DC]/5 cursor-pointer transition-colors duration-200 group/item">
                            <div class="flex items-start space-x-3">
                                <div class="w-8 h-8 bg-yellow-100 rounded-full flex items-center justify-center group-hover/item:bg-yellow-200 transition-colors duration-200">
                                    <i class="fas fa-exclamation-triangle text-yellow-600 text-sm"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="text-[#211C4D] text-sm group-hover/item:text-[#2AA0DC] transition-colors duration-200">منتج على وشك النفاد</p>
                                    <p class="text-gray-500 text-xs">منذ 3 ساعات</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="p-3 border-t border-gray-100 text-center bg-gray-50/50">
                        <a href="#" class="text-[#2AA0DC] text-sm font-medium hover:text-[#211C4D] transition-colors duration-200 inline-flex items-center space-x-2">
                            <span>عرض جميع الإشعارات</span>
                            <i class="fas fa-arrow-left text-xs"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Messages -->
            <div class="relative">
                <button id="messagesButton" class="p-2 text-white hover:bg-[#2AA0DC]/10 hover:scale-110 rounded-full transition-all duration-300 relative group">
                    <img src="/icons/mail.svg" alt="Messages" class="w-6 h-6 group-hover:scale-110 transition-transform duration-300">
                    <span class="absolute top-2 left-6 bg-red-500 text-white rounded-full w-2 h-2"></span>
                    <div class="absolute inset-0 bg-gradient-to-r from-[#2AA0DC]/20 to-transparent rounded-full opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                </button>
                
                <!-- Messages Dropdown -->
                <div id="messagesDropdown" class="absolute top-full left-0 mt-2 w-80 bg-white rounded-lg shadow-xl border border-gray-200 hidden z-50 transform origin-top transition-all duration-300 scale-95 opacity-0 md:left-0">
                    <div class="p-4 border-b border-gray-100 bg-gradient-to-r from-[#2AA0DC]/5 to-white">
                        <h3 class="text-[#211C4D] font-bold text-lg">الرسائل</h3>
                    </div>
                    <div class="max-h-64 overflow-y-auto">
                        <!-- Message Items -->
                        <div class="p-3 border-b border-gray-100 hover:bg-[#2AA0DC]/5 cursor-pointer transition-colors duration-200 group/item">
                            <div class="flex items-start space-x-3">
                                <div class="w-10 h-10 bg-gray-200 rounded-full flex-shrink-0 overflow-hidden group-hover/item:ring-2 ring-[#2AA0DC] transition-all duration-200">
                                    <img src="/icons/Avatar.svg" alt="User" class="w-full h-full object-cover">
                                </div>
                                <div class="flex-1">
                                    <div class="flex justify-between items-start">
                                        <p class="text-[#211C4D] font-medium text-sm group-hover/item:text-[#2AA0DC] transition-colors duration-200">أحمد محمد</p>
                                        <span class="text-gray-500 text-xs">10:30 ص</span>
                                    </div>
                                    <p class="text-gray-600 text-xs truncate group-hover/item:text-[#211C4D] transition-colors duration-200">هل المنتج متوفر باللون الأسود؟</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="p-3 border-b border-gray-100 hover:bg-[#2AA0DC]/5 cursor-pointer transition-colors duration-200 group/item">
                            <div class="flex items-start space-x-3">
                                <div class="w-10 h-10 bg-gray-200 rounded-full flex-shrink-0 overflow-hidden group-hover/item:ring-2 ring-[#2AA0DC] transition-all duration-200">
                                    <img src="/icons/Avatar.svg" alt="User" class="w-full h-full object-cover">
                                </div>
                                <div class="flex-1">
                                    <div class="flex justify-between items-start">
                                        <p class="text-[#211C4D] font-medium text-sm group-hover/item:text-[#2AA0DC] transition-colors duration-200">سارة عبدالله</p>
                                        <span class="text-gray-500 text-xs">أمس</span>
                                    </div>
                                    <p class="text-gray-600 text-xs truncate group-hover/item:text-[#211C4D] transition-colors duration-200">متى سيكون المنتج متوفراً؟</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="p-3 hover:bg-[#2AA0DC]/5 cursor-pointer transition-colors duration-200 group/item">
                            <div class="flex items-start space-x-3">
                                <div class="w-10 h-10 bg-gray-200 rounded-full flex-shrink-0 overflow-hidden group-hover/item:ring-2 ring-[#2AA0DC] transition-all duration-200">
                                    <img src="/icons/Avatar.svg" alt="User" class="w-full h-full object-cover">
                                </div>
                                <div class="flex-1">
                                    <div class="flex justify-between items-start">
                                        <p class="text-[#211C4D] font-medium text-sm group-hover/item:text-[#2AA0DC] transition-colors duration-200">خالد العلي</p>
                                        <span class="text-gray-500 text-xs">٢٤/١٢</span>
                                    </div>
                                    <p class="text-gray-600 text-xs truncate group-hover/item:text-[#211C4D] transition-colors duration-200">شكراً على الخدمة الممتازة</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="p-3 border-t border-gray-100 text-center bg-gray-50/50">
                        <a href="#" class="text-[#2AA0DC] text-sm font-medium hover:text-[#211C4D] transition-colors duration-200 inline-flex items-center space-x-2">
                            <span>عرض جميع الرسائل</span>
                            <i class="fas fa-arrow-left text-xs"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- User Profile -->
            <div class="relative">
                <button id="userMenuButton" class="flex items-center space-x-2 p-2 rounded-lg group">
                    <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center border border-white/30 overflow-hidden group-hover:ring-2 ring-[#2AA0DC] transition-all duration-300">
                        <img src="/icons/Avatar.svg" alt="User" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300">
                        <div class="absolute inset-0 bg-gradient-to-r from-[#2AA0DC]/20 to-transparent rounded-full opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                    </div>
                    <span class="hidden md:block text-[#211C4DCC] group-hover:text-[#2AA0DC] transition-colors duration-300">{{ Auth::user()->name ?? 'مدير' }}</span>
                    <i class="fas fa-chevron-down text-xs text-[#211C4DCC] group-hover:text-[#2AA0DC] transition-colors duration-300"></i>
                    
                </button>
                
                <!-- User Dropdown Menu -->
                <div id="userDropdown" class="absolute top-full left-0 mt-2 w-48 bg-white rounded-lg shadow-xl border border-gray-200 hidden z-50 transform origin-top transition-all duration-300 scale-95 opacity-0 md:left-0">
                    <div class="p-2 bg-gradient-to-b from-white to-gray-50/50 rounded-lg">
                        <a href="#" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-[#2AA0DC]/10 hover:text-[#2AA0DC] transition-all duration-200 group/item">
                            <i class="fas fa-user text-gray-600 w-5 group-hover/item:text-[#2AA0DC] transition-colors duration-200"></i>
                            <span class="text-[#211C4D] group-hover/item:text-[#2AA0DC] group-hover/item:translate-x-1 transition-all duration-200">الملف الشخصي</span>
                        </a>
                        
                        <a href="#" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-[#2AA0DC]/10 hover:text-[#2AA0DC] transition-all duration-200 group/item">
                            <i class="fas fa-cog text-gray-600 w-5 group-hover/item:text-[#2AA0DC] transition-colors duration-200"></i>
                            <span class="text-[#211C4D] group-hover/item:text-[#2AA0DC] group-hover/item:translate-x-1 transition-all duration-200">الإعدادات</span>
                        </a>
                        
                        <hr class="my-1 border-gray-200">
                        
                        <form method="POST" action="#">
                            @csrf
                            <button type="submit" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-red-50 hover:text-red-600 transition-all duration-200 w-full text-right group/item">
                                <i class="fas fa-sign-out-alt text-gray-600 w-5 group-hover/item:text-red-600 group-hover/item:scale-110 transition-all duration-200"></i>
                                <span class="text-[#211C4D] group-hover/item:text-red-600 group-hover/item:translate-x-1 transition-all duration-200">تسجيل الخروج</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>