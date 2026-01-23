<!-- Sidebar -->
<div id="sidebar" class="sidebar-transition bg-white text-[#211C4D] shadow-lg">
    <!-- Sidebar Header -->
    <div class="flex items-center justify-center flex-col pt-2">
        <img src="/icons/PhoneCityLogo_whiteBackground 1.svg" alt="Logo" class="w-20 h-20 mb-1">
        <p class="text-[#211C4D] text-[24px]">مدينة الهواتف</p>
    </div>

    <!-- Navigation -->
    <nav class="pl-4 pt-4 pr-4 gap-0.5 flex flex-col sidebar-nav">
        <!-- لوحة التحكم -->
        <a href="{{ route('admin.dashboard') }}" 
           class="flex items-center space-x-3 p-3 rounded-lg transition-colors font-normal {{ request()->routeIs('admin.dashboard') ? 'sidebar-active' : 'text-[#211C4D] hover:text-[#2AA0DC]' }}">
            <img src="/icons/home.svg" alt="Dashboard Icon" class="w-5 h-5">
            <span>لوحة التحكم</span>
        </a>

        <!-- صفحات -->
        <a href="#" 
           class="flex items-center rounded-lg transition-colors font-normal {{ request()->routeIs('admin.pages') ? 'sidebar-active' : 'text-[#211C4D] hover:text-[#2AA0DC]' }}">
           <div class="flex justify-between w-full">
            <div class="flex items-center p-3 space-x-3">
                <img src="/icons/pages.svg" alt="pages Icon" class="w-5 h-5">
                <span>صفحات</span>
            </div>
           </div> 
           <img src="/icons/down.svg" alt="Arrow Icon" class="w-3 h-3">
        </a>

        <!-- إدارة الاقسام -->
        <a href="#" 
           class="flex items-center p-3 space-x-3 rounded-lg transition-colors font-normal {{ request()->routeIs('admin.categories') ? 'sidebar-active' : 'text-[#211C4D] hover:text-[#2AA0DC]' }}">
           <img src="/icons/Cardholder.svg" alt="pages Icon" class="w-5 h-5">
           <span>إدارة الاقسام</span>
        </a>

        <!-- إدارة المنتجات -->
        <a href="#" 
           class="flex items-center p-3 space-x-3 rounded-lg transition-colors font-normal {{ request()->routeIs('admin.products') ? 'sidebar-active' : 'text-[#211C4D] hover:text-[#2AA0DC]' }}">
           <img src="/icons/Shopping-bag.svg" alt="pages Icon" class="w-5 h-5">
           <span>إدارة المنتجات</span>
        </a>

        <!-- الطلبات -->
        <a href="#" 
           class="flex items-center p-3 space-x-3 rounded-lg transition-colors font-normal {{ request()->routeIs('admin.orders') ? 'sidebar-active' : 'text-[#211C4D] hover:text-[#2AA0DC]' }}">
           <img src="/icons/ShoppingCart.svg" alt="pages Icon" class="w-5 h-5">
           <span>الطلبات</span>
        </a>

        <!-- إدارة العملاء -->
        <a href="#" 
           class="flex items-center p-3 space-x-3 rounded-lg transition-colors font-normal {{ request()->routeIs('admin.customers') ? 'sidebar-active' : 'text-[#211C4D] hover:text-[#2AA0DC]' }}">
           <img src="/icons/UsersThree.svg" alt="pages Icon" class="w-5 h-5">
           <span>إدارة العملاء</span>
        </a>

        <!-- الشرائح -->
        <a href="#" 
           class="flex items-center p-3 space-x-3 rounded-lg transition-colors font-normal {{ request()->routeIs('admin.slides') ? 'sidebar-active' : 'text-[#211C4D] hover:text-[#2AA0DC]' }}">
           <img src="/icons/FilmStrip.svg" alt="pages Icon" class="w-5 h-5">
           <span>الشرائح</span>
        </a>

        <!-- إدارة العروض والخصومات -->
        <a href="#" 
           class="flex items-center p-3 space-x-3 rounded-lg transition-colors font-[500] text-[14px] {{ request()->routeIs('admin.offers') ? 'sidebar-active' : 'text-[#211C4D] hover:text-[#2AA0DC]' }}">
           <img src="/icons/Gift.svg" alt="pages Icon" class="w-5 h-5">
           <span>إدارة العروض والخصومات</span>
        </a>

        <!-- شركات الشحن -->
        <a href="#" 
           class="flex items-center p-3 space-x-3 rounded-lg transition-colors font-normal {{ request()->routeIs('admin.shipping') ? 'sidebar-active' : 'text-[#211C4D] hover:text-[#2AA0DC]' }}">
           <img src="/icons/Truck.svg" alt="pages Icon" class="w-5 h-5">
           <span>شركات الشحن</span>
        </a>

        <!-- شركات التقسيط -->
        <a href="#" 
           class="flex items-center p-3 space-x-3 rounded-lg transition-colors font-normal {{ request()->routeIs('admin.installment') ? 'sidebar-active' : 'text-[#211C4D] hover:text-[#2AA0DC]' }}">
           <img src="/icons/Money.svg" alt="pages Icon" class="w-5 h-5">
           <span>شركات التقسيط</span>
        </a>

        <!-- العلامات التجارية -->
        <a href="#" 
           class="flex items-center p-3 space-x-3 rounded-lg transition-colors font-normal {{ request()->routeIs('admin.brands') ? 'sidebar-active' : 'text-[#211C4D] hover:text-[#2AA0DC]' }}">
           <img src="/icons/AppleLogo.svg" alt="pages Icon" class="w-5 h-5">
           <span>العلامات التجارية</span>
        </a>

        <!-- الحساب والإعدادات -->
        <a href="#" 
           class="flex items-center rounded-lg transition-colors font-normal {{ request()->routeIs('admin.settings') ? 'sidebar-active' : 'text-[#211C4D] hover:text-[#2AA0DC]' }}">
           <div class="flex justify-between w-full">
            <div class="flex items-center p-3 space-x-3">
                <img src="/icons/User.svg" alt="pages Icon" class="w-5 h-5">
                <span>الحساب والإعدادات</span>
            </div>
           </div> 
           <img src="/icons/down.svg" alt="Arrow Icon" class="w-3 h-3">
        </a>
    </nav>
</div>


<div id="sidebarOverlay" class="sidebar-overlay"></div>