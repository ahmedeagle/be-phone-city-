<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // Products - Full CRUD
            ['name' => 'products.show', 'name_ar' => 'عرض المنتجات'],
            ['name' => 'products.create', 'name_ar' => 'إنشاء منتج'],
            ['name' => 'products.update', 'name_ar' => 'تعديل منتج'],
            ['name' => 'products.delete', 'name_ar' => 'حذف منتج'],

            // Categories - Full CRUD
            ['name' => 'categories.show', 'name_ar' => 'عرض التصنيفات'],
            ['name' => 'categories.create', 'name_ar' => 'إنشاء تصنيف'],
            ['name' => 'categories.update', 'name_ar' => 'تعديل تصنيف'],
            ['name' => 'categories.delete', 'name_ar' => 'حذف تصنيف'],

            // Offers - Full CRUD
            ['name' => 'offers.show', 'name_ar' => 'عرض العروض'],
            ['name' => 'offers.create', 'name_ar' => 'إنشاء عرض'],
            ['name' => 'offers.update', 'name_ar' => 'تعديل عرض'],
            ['name' => 'offers.delete', 'name_ar' => 'حذف عرض'],

            // Orders - Full CRUD
            ['name' => 'orders.show', 'name_ar' => 'عرض الطلبات'],
            ['name' => 'orders.create', 'name_ar' => 'إنشاء طلب'],
            ['name' => 'orders.update', 'name_ar' => 'تعديل طلب'],
            ['name' => 'orders.delete', 'name_ar' => 'حذف طلب'],

            // Payment Methods - Full CRUD
            ['name' => 'payment_methods.show', 'name_ar' => 'عرض طرق الدفع'],
            ['name' => 'payment_methods.create', 'name_ar' => 'إنشاء طريقة دفع'],
            ['name' => 'payment_methods.update', 'name_ar' => 'تعديل طريقة دفع'],
            ['name' => 'payment_methods.delete', 'name_ar' => 'حذف طريقة دفع'],

            // Payment Transactions - View only + Custom
            ['name' => 'payment_transactions.show', 'name_ar' => 'عرض معاملات الدفع'],
            ['name' => 'payment_transactions.review', 'name_ar' => 'مراجعة معاملات الدفع'],

            // Blogs - Full CRUD
            ['name' => 'blogs.show', 'name_ar' => 'عرض المدونات'],
            ['name' => 'blogs.create', 'name_ar' => 'إنشاء مدونة'],
            ['name' => 'blogs.update', 'name_ar' => 'تعديل مدونة'],
            ['name' => 'blogs.delete', 'name_ar' => 'حذف مدونة'],

            // Comments - Full CRUD
            ['name' => 'comments.show', 'name_ar' => 'عرض التعليقات'],
            ['name' => 'comments.create', 'name_ar' => 'إنشاء تعليق'],
            ['name' => 'comments.update', 'name_ar' => 'تعديل تعليق'],
            ['name' => 'comments.delete', 'name_ar' => 'حذف تعليق'],

            // Tickets - Full CRUD
            ['name' => 'tickets.show', 'name_ar' => 'عرض التذاكر'],
            ['name' => 'tickets.create', 'name_ar' => 'إنشاء تذكرة'],
            ['name' => 'tickets.update', 'name_ar' => 'تعديل تذكرة'],
            ['name' => 'tickets.delete', 'name_ar' => 'حذف تذكرة'],

            // Users - Full CRUD
            ['name' => 'users.show', 'name_ar' => 'عرض المستخدمين'],
            ['name' => 'users.create', 'name_ar' => 'إنشاء مستخدم'],
            ['name' => 'users.update', 'name_ar' => 'تعديل مستخدم'],
            ['name' => 'users.delete', 'name_ar' => 'حذف مستخدم'],

            // Sliders - Full CRUD
            ['name' => 'sliders.show', 'name_ar' => 'عرض الشرائح'],
            ['name' => 'sliders.create', 'name_ar' => 'إنشاء شريحة'],
            ['name' => 'sliders.update', 'name_ar' => 'تعديل شريحة'],
            ['name' => 'sliders.delete', 'name_ar' => 'حذف شريحة'],

            // Customer Opinions - Full CRUD
            ['name' => 'customer_opinions.show', 'name_ar' => 'عرض آراء العملاء'],
            ['name' => 'customer_opinions.create', 'name_ar' => 'إنشاء رأي عميل'],
            ['name' => 'customer_opinions.update', 'name_ar' => 'تعديل رأي عميل'],
            ['name' => 'customer_opinions.delete', 'name_ar' => 'حذف رأي عميل'],

            // Certificates - Full CRUD
            ['name' => 'certificates.show', 'name_ar' => 'عرض الشهادات'],
            ['name' => 'certificates.create', 'name_ar' => 'إنشاء شهادة'],
            ['name' => 'certificates.update', 'name_ar' => 'تعديل شهادة'],
            ['name' => 'certificates.delete', 'name_ar' => 'حذف شهادة'],

            // Store Features - Full CRUD
            ['name' => 'store_features.show', 'name_ar' => 'عرض ميزات المتجر'],
            ['name' => 'store_features.create', 'name_ar' => 'إنشاء ميزة متجر'],
            ['name' => 'store_features.update', 'name_ar' => 'تعديل ميزة متجر'],
            ['name' => 'store_features.delete', 'name_ar' => 'حذف ميزة متجر'],

            // Discounts - Full CRUD
            ['name' => 'discounts.show', 'name_ar' => 'عرض الخصومات'],
            ['name' => 'discounts.create', 'name_ar' => 'إنشاء خصم'],
            ['name' => 'discounts.update', 'name_ar' => 'تعديل خصم'],
            ['name' => 'discounts.delete', 'name_ar' => 'حذف خصم'],

            // Contact Requests - Full CRUD
            ['name' => 'contact_requests.show', 'name_ar' => 'عرض طلبات الاتصال'],
            ['name' => 'contact_requests.create', 'name_ar' => 'إنشاء طلب اتصال'],
            ['name' => 'contact_requests.update', 'name_ar' => 'تعديل طلب اتصال'],
            ['name' => 'contact_requests.delete', 'name_ar' => 'حذف طلب اتصال'],

            // Pages - Full CRUD
            ['name' => 'pages.show', 'name_ar' => 'عرض الصفحات'],
            ['name' => 'pages.create', 'name_ar' => 'إنشاء صفحة'],
            ['name' => 'pages.update', 'name_ar' => 'تعديل صفحة'],
            ['name' => 'pages.delete', 'name_ar' => 'حذف صفحة'],

            // Services - Full CRUD
            ['name' => 'services.show', 'name_ar' => 'عرض الخدمات'],
            ['name' => 'services.create', 'name_ar' => 'إنشاء خدمة'],
            ['name' => 'services.update', 'name_ar' => 'تعديل خدمة'],
            ['name' => 'services.delete', 'name_ar' => 'حذف خدمة'],

            // Cities - Full CRUD
            ['name' => 'cities.show', 'name_ar' => 'عرض المدن'],
            ['name' => 'cities.create', 'name_ar' => 'إنشاء مدينة'],
            ['name' => 'cities.update', 'name_ar' => 'تعديل مدينة'],
            ['name' => 'cities.delete', 'name_ar' => 'حذف مدينة'],

            // Settings - View and Update only
            ['name' => 'settings.show', 'name_ar' => 'عرض الإعدادات'],
            ['name' => 'settings.update', 'name_ar' => 'تعديل الإعدادات'],

            // About - View and Update only
            ['name' => 'about.show', 'name_ar' => 'عرض صفحة من نحن'],
            ['name' => 'about.update', 'name_ar' => 'تعديل صفحة من نحن'],

            // Home Pages - View and Update only
            ['name' => 'home_pages.show', 'name_ar' => 'عرض الصفحة الرئيسية'],
            ['name' => 'home_pages.update', 'name_ar' => 'تعديل الصفحة الرئيسية'],

            // Roles - Full CRUD
            ['name' => 'roles.show', 'name_ar' => 'عرض الأدوار'],
            ['name' => 'roles.create', 'name_ar' => 'إنشاء دور'],
            ['name' => 'roles.update', 'name_ar' => 'تعديل دور'],
            ['name' => 'roles.delete', 'name_ar' => 'حذف دور'],

            // Admins - Full CRUD
            ['name' => 'admins.show', 'name_ar' => 'عرض المدراء'],
            ['name' => 'admins.create', 'name_ar' => 'إنشاء مدير'],
            ['name' => 'admins.update', 'name_ar' => 'تعديل مدير'],
            ['name' => 'admins.delete', 'name_ar' => 'حذف مدير'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name'], 'guard_name' => 'admin'],
                ['name_ar' => $permission['name_ar']]
            );
        }

        // Get all created permissions from database
        $allPermissions = Permission::where('guard_name', 'admin')->get();

        // Assign all permissions to owner role
        $ownerRole = Role::where('name', 'owner')->where('guard_name', 'admin')->first();
        if ($ownerRole) {
            $ownerRole->syncPermissions($allPermissions);
        }
    }
}
