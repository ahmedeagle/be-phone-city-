<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>لوحة التحكم - @yield('title', 'لوحة التحكم')</title>
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        .sidebar-transition {
            transition: all 0.3s ease;
        }

        
        @media (max-width: 768px) {
            #sidebar {
                position: fixed !important;
                top: 0;
                right: 0;
                width: 280px;
                height: 100vh;
                background: white;
                z-index: 50;
                transform: translateX(100%);
                transition: transform 0.35s ease-in-out;
                box-shadow: -8px 0 25px rgba(0,0,0,0.15);
                overflow-y: auto;
            }

            #sidebar.show {
                transform: translateX(0);
            }

            
            .sidebar-overlay {
                position: fixed;
                inset: 0;
                background: rgba(0,0,0,0.6);
                opacity: 0;
                visibility: hidden;
                transition: all 0.35s ease;
                z-index: 40;
            }
            .sidebar-overlay.show {
                opacity: 1;
                visibility: visible;
            }

            
            #sidebar.show ~ .flex-1 > * {
                filter: blur(8px);
                transition: filter 0.35s ease;
            }
        }

        
        body { direction: rtl; text-align: right; font-family: 'Roboto', sans-serif; }
        .space-x-2 > :not([hidden]) ~ :not([hidden]) { --tw-space-x-reverse: 1; margin-left: calc(0.5rem * var(--tw-space-x-reverse)); margin-right: calc(0.5rem * calc(1 - var(--tw-space-x-reverse))); }
        .space-x-3 > :not([hidden]) ~ :not([hidden]) { --tw-space-x-reverse: 1; margin-left: calc(0.75rem * var(--tw-space-x-reverse)); margin-right: calc(0.75rem * calc(1 - var(--tw-space-x-reverse))); }
        .space-x-4 > :not([hidden]) ~ :not([hidden]) { --tw-space-x-reverse: 1; margin-left: calc(1rem * var(--tw-space-x-reverse)); margin-right: calc(1rem * calc(1 - var(--tw-space-x-reverse))); }
        .mr-2 { margin-left: 0.5rem; margin-right: 0; }
        .ml-0 { margin-right: 0; margin-left: auto; }
        .mx-4 { margin-left: 1rem; margin-right: 1rem; }
        .left-3 { right: 0.75rem; left: auto; }
        .right-0 { left: 0; right: auto; }
        .text-left { text-align: right; }
        .border-r { border-left-width: 1px; border-right-width: 0; }
        .pl-10 { padding-right: 2.5rem; padding-left: 1rem; }
        .pr-4 { padding-left: 1rem; padding-right: 1rem; }

        .sidebar-active {
            position: relative;
            background-color: rgba(42, 160, 220, 0.2) !important;
            color: #211C4D !important;
        }
        .sidebar-active::before {
            content: '';
            position: absolute;
            right: -18px;
            top: 0;
            width: 10px;
            height: 100%;
            border-radius: 8px 0 0 8px;
            background-color: #211C4D;
        }

        
        .dropdown-hidden {
            display: none !important;
        }
        .dropdown-visible {
            display: block !important;
        }

        
        .group:hover .group-hover\:scale-110 {
            transform: scale(1.1);
        }

        .group:hover .group-hover\:text-\[\#2AA0DC\] {
            color: #2AA0DC;
        }

        .transition-all {
            transition: all 0.3s ease;
        }

        .ring-\[\#2AA0DC\] {
            --tw-ring-color: #2AA0DC;
        }

        
        #notificationsDropdown:not(.hidden),
        #messagesDropdown:not(.hidden),
        #userDropdown:not(.hidden) {
            transform: scale(1);
            opacity: 1;
            transition: all 0.3s ease;
        }

        
        #sidebarToggle:hover {
            transform: scale(1.05);
        }

        
        @media (max-width: 768px) {
            #notificationsDropdown,
            #messagesDropdown,
            #userDropdown {
                position: fixed !important;
                top: 80px !important;
                right: 1rem !important;
                left: 1rem !important;
                width: auto !important;
                max-width: calc(100vw - 2rem) !important;
                margin: 0 !important;
                transform-origin: top center !important;
            }

            .flex.items-center.space-x-4 {
                position: static;
            }
        }

        
        @media (min-width: 769px) {
            #notificationsDropdown,
            #messagesDropdown,
            #userDropdown {
                position: absolute !important;
                top: 100% !important;
                left: 0 !important;
                right: auto !important;
                width: 320px !important;
                max-width: none !important;
                margin-top: 0.5rem !important;
                transform-origin: top left !important;
            }
        }

    </style>
    
    @stack('styles')
</head>
<body class="bg-white">
    <div class="flex h-screen relative">
        <!-- Sidebar -->
        @include('admin.layouts.partials.sidebar')

        <!-- Overlay للموبايل فقط -->
        <div id="sidebarOverlay" class="sidebar-overlay"></div>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col">
            <!-- Top Header -->
            @include('admin.layouts.partials.header')

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto p-6">
                @yield('breadcrumb')
                @yield('content')
            </main>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const sidebar = document.getElementById('sidebar');
            const toggleBtn = document.getElementById('sidebarToggle');
            const overlay = document.getElementById('sidebarOverlay');
            
            
            const notificationsButton = document.getElementById('notificationsButton');
            const messagesButton = document.getElementById('messagesButton');
            const userMenuButton = document.getElementById('userMenuButton');
            
            const notificationsDropdown = document.getElementById('notificationsDropdown');
            const messagesDropdown = document.getElementById('messagesDropdown');
            const userDropdown = document.getElementById('userDropdown');

            
            function showDropdown(dropdown) {
                if (!dropdown) return;
                
                dropdown.classList.remove('hidden');
                
                dropdown.style.removeProperty('transform');
                dropdown.style.removeProperty('opacity');
                
                
                requestAnimationFrame(() => {
                    dropdown.style.transform = 'scale(1)';
                    dropdown.style.opacity = '1';
                });
            }

        function hideDropdown(dropdown) {
            if (!dropdown) return;
            
            
            dropdown.style.transform = 'scale(0.95)';
            dropdown.style.opacity = '0';
            
            setTimeout(() => {
                if (dropdown.style.transform === 'scale(0.95)' && dropdown.style.opacity === '0') {
                    dropdown.classList.add('hidden');
                }
            }, 200);
        }

        
        function closeAllDropdowns() {
            const dropdowns = [notificationsDropdown, messagesDropdown, userDropdown];
            dropdowns.forEach(dropdown => {
                if (dropdown && !dropdown.classList.contains('hidden')) {
                    hideDropdown(dropdown);
                }
            });
        }

        
        if (toggleBtn) {
            toggleBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                const isShowing = sidebar.classList.contains('show');
                
                if (isShowing) {
                    sidebar.classList.remove('show');
                    overlay.classList.remove('show');
                } else {
                    sidebar.classList.add('show');
                    overlay.classList.add('show');
                }
                
                closeAllDropdowns();
            });
        }

        
        if (overlay) {
            overlay.addEventListener('click', function () {
                sidebar.classList.remove('show');
                overlay.classList.remove('show');
                closeAllDropdowns();
            });
        }

        
        document.addEventListener('click', function (e) {
            if (window.innerWidth < 768 && sidebar && sidebar.classList.contains('show')) {
                if (!sidebar.contains(e.target) && (!toggleBtn || !toggleBtn.contains(e.target))) {
                    sidebar.classList.remove('show');
                    if (overlay) overlay.classList.remove('show');
                    closeAllDropdowns();
                }
            }
        });

        if (notificationsButton && notificationsDropdown) {
            notificationsButton.addEventListener('click', function (e) {
                e.stopPropagation();
                const isHidden = notificationsDropdown.classList.contains('hidden');
                closeAllDropdowns();
                if (isHidden) {
                    showDropdown(notificationsDropdown);
                }
            });
        }

        
        if (messagesButton && messagesDropdown) {
            messagesButton.addEventListener('click', function (e) {
                e.stopPropagation();
                const isHidden = messagesDropdown.classList.contains('hidden');
                closeAllDropdowns();
                if (isHidden) {
                    showDropdown(messagesDropdown);
                }
            });
        }

        
        if (userMenuButton && userDropdown) {
            userMenuButton.addEventListener('click', function (e) {
                e.stopPropagation();
                const isHidden = userDropdown.classList.contains('hidden');
                closeAllDropdowns();
                if (isHidden) {
                    showDropdown(userDropdown);
                }
            });
        }

        
        document.addEventListener('click', function (e) {
            
            const isClickInsideDropdown = 
                (notificationsDropdown && notificationsDropdown.contains(e.target)) ||
                (messagesDropdown && messagesDropdown.contains(e.target)) ||
                (userDropdown && userDropdown.contains(e.target)) ||
                (notificationsButton && notificationsButton.contains(e.target)) ||
                (messagesButton && messagesButton.contains(e.target)) ||
                (userMenuButton && userMenuButton.contains(e.target));
            
            if (!isClickInsideDropdown) {
                closeAllDropdowns();
            }
        });

        
        const dropdowns = [notificationsDropdown, messagesDropdown, userDropdown];
        dropdowns.forEach(dropdown => {
            if (dropdown) {
                dropdown.addEventListener('click', function (e) {
                    e.stopPropagation();
                });
            }
        });

        
        window.addEventListener('resize', function () {
            if (window.innerWidth >= 768) {
                sidebar.classList.remove('show');
                if (overlay) overlay.classList.remove('show');
            }
            
            closeAllDropdowns();
        });

    
        window.addEventListener('scroll', function () {
            closeAllDropdowns();
        });

        
            const style = document.createElement('style');
            style.textContent = `
                #notificationsDropdown,
                #messagesDropdown,
                #userDropdown {
                    transform: scale(0.95);
                    opacity: 0;
                    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                }
                
                #notificationsDropdown:not(.hidden),
                #messagesDropdown:not(.hidden),
                #userDropdown:not(.hidden) {
                    transform: scale(1);
                    opacity: 1;
                }
                
                /* تحسينات للـ hover */
                #notificationsButton:hover .fa-bell,
                #messagesButton:hover .fa-envelope,
                #userMenuButton:hover .fa-user {
                    transform: scale(1.1);
                    transition: transform 0.3s ease;
                }
            `;
            document.head.appendChild(style);
        });
    </script>

    @stack('scripts')
</body>
</html>