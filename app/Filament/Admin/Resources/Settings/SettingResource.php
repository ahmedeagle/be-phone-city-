<?php

namespace App\Filament\Admin\Resources\Settings;

use App\Filament\Admin\Resources\Settings\Pages\EditSetting;
use App\Filament\Admin\Resources\Settings\Pages\ManageSetting;
use App\Models\Setting;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class SettingResource extends Resource
{
    protected static ?string $model = Setting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Cog6Tooth;

    protected static ?string $navigationLabel = 'الإعدادات';

    protected static ?string $pluralLabel = 'الإعدادات';

    protected static ?string $label = 'إعدادات';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return 'الإعدادات والنظام';
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('settings.show');
    }

    public static function canEdit($record): bool
    {
        return auth()->user()->can('settings.update');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('معلومات الموقع')
                    ->description('المعلومات الأساسية للموقع')
                    ->icon('heroicon-o-globe-alt')
                    ->schema([
                        TextInput::make('website_title_en')
                            ->label('عنوان الموقع بالإنجليزية')
                            ->maxLength(255)
                            ->placeholder('Website Title'),

                        TextInput::make('website_title_ar')
                            ->label('عنوان الموقع بالعربية')
                            ->maxLength(255)
                            ->placeholder('عنوان الموقع'),

                        FileUpload::make('logo')
                            ->label('الشعار')
                            ->image()
                            ->imageEditor()
                            ->directory('settings')
                            ->maxSize(2048)
                            ->helperText('الشعار الرئيسي للموقع')
                            ->nullable(),
                    ])
                    ->columns(1),

                Section::make('إعدادات الشحن والضرائب')
                    ->description('إعدادات الشحن المجاني والضرائب')
                    ->icon('heroicon-o-truck')
                    ->schema([
                        TextInput::make('free_shipping_threshold')
                            ->label('حد الشحن المجاني')
                            ->numeric()
                            ->minValue(0)
                            ->prefix('ر.س')
                            ->default(0)
                            ->helperText('الحد الأدنى لمبلغ الطلب للحصول على شحن مجاني')
                            ->placeholder('0.00'),

                        TextInput::make('tax_percentage')
                            ->label('نسبة الضريبة')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('%')
                            ->default(0)
                            ->helperText('نسبة الضريبة المضافة (مثال: 15 لضريبة 15%)')
                            ->placeholder('0.00'),
                    ])
                    ->columns(2),

                Section::make('إعدادات النقاط')
                    ->description('إعدادات نظام النقاط والمكافآت')
                    ->icon('heroicon-o-star')
                    ->schema([
                        TextInput::make('points_days_expired')
                            ->label('أيام انتهاء النقاط')
                            ->numeric()
                            ->minValue(1)
                            ->default(365)
                            ->helperText('عدد الأيام قبل انتهاء صلاحية النقاط')
                            ->suffix('يوم')
                            ->placeholder('365'),

                        TextInput::make('point_value')
                            ->label('قيمة النقطة')
                            ->numeric()
                            ->minValue(0)
                            ->prefix('ر.س')
                            ->default(1.00)
                            ->helperText('قيمة النقطة الواحدة بالعملة')
                            ->placeholder('1.00'),
                    ])
                    ->columns(2),

                Section::make('إعدادات المنتجات الجديدة والمنتجات المميزة')
                    ->description('إعدادات عرض قسم المنتجات الجديدة والمنتجات المميزة')
                    ->icon('heroicon-o-sparkles')
                    ->schema([
                        Toggle::make('show_new_arrivals_section')
                            ->label('إظهار قسم المنتجات الجديدة')
                            ->default(true)
                            ->helperText('تفعيل أو إلغاء عرض قسم المنتجات الجديدة في الموقع'),

                        TextInput::make('new_arrivals_count')
                            ->label('عدد المنتجات الجديدة')
                            ->numeric()
                            ->minValue(1)
                            ->default(10)
                            ->helperText('عدد المنتجات الجديدة المعروضة في القسم')
                            ->placeholder('10')
                            ->required()
                            ->visible(fn ($get) => $get('show_new_arrivals_section')),

                        Toggle::make('show_featured_section')
                            ->label('إظهار قسم المنتجات المميزة')
                            ->default(true)
                            ->helperText('تفعيل أو إلغاء عرض قسم المنتجات المميزة في الموقع'),

                        TextInput::make('featured_count')
                            ->label('عدد المنتجات المميزة')
                            ->numeric()
                            ->minValue(1)
                            ->default(10)
                            ->helperText('عدد المنتجات المميزة المعروضة في القسم')
                            ->placeholder('10')
                            ->required()
                            ->visible(fn ($get) => $get('show_featured_section')),
                    ])
                    ->columns(2),

                Section::make('معلومات الحساب البنكي')
                    ->description('معلومات الحساب البنكي لطريقة الدفع بالتحويل البنكي')
                    ->icon('heroicon-o-building-library')
                    ->schema([
                        TextInput::make('bank_name')
                            ->label('اسم البنك')
                            ->maxLength(255)
                            ->placeholder('مثال: البنك الأهلي السعودي')
                            ->helperText('اسم البنك الذي سيتم التحويل إليه'),

                        TextInput::make('account_holder')
                            ->label('اسم صاحب الحساب')
                            ->maxLength(255)
                            ->placeholder('اسم صاحب الحساب')
                            ->helperText('اسم الشخص أو الشركة صاحب الحساب'),

                        TextInput::make('account_number')
                            ->label('رقم الحساب')
                            ->maxLength(255)
                            ->placeholder('رقم الحساب البنكي')
                            ->helperText('رقم الحساب البنكي'),

                        TextInput::make('iban')
                            ->label('رقم الآيبان (IBAN)')
                            ->maxLength(255)
                            ->placeholder('SA1234567890123456789012')
                            ->helperText('رقم الآيبان الدولي للحساب'),

                        TextInput::make('swift_code')
                            ->label('رمز السويفت (SWIFT)')
                            ->maxLength(255)
                            ->placeholder('مثال: NCBKSAJE')
                            ->helperText('رمز السويفت للبنك'),

                        TextInput::make('branch')
                            ->label('الفرع')
                            ->maxLength(255)
                            ->placeholder('اسم الفرع')
                            ->helperText('اسم فرع البنك'),

                        Textarea::make('bank_instructions')
                            ->label('تعليمات التحويل البنكي')
                            ->rows(4)
                            ->maxLength(1000)
                            ->placeholder('أدخل تعليمات خاصة للتحويل البنكي')
                            ->helperText('تعليمات إضافية تظهر للمستخدم عند اختيار طريقة الدفع بالتحويل البنكي')
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),


            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('معلومات الموقع')
                    ->icon('heroicon-o-globe-alt')
                    ->schema([
                        TextEntry::make('website_title_en')
                            ->label('عنوان الموقع بالإنجليزية')
                            ->size('lg')
                            ->weight('bold'),

                        TextEntry::make('website_title_ar')
                            ->label('عنوان الموقع بالعربية')
                            ->size('lg')
                            ->weight('bold'),

                        ImageEntry::make('logo')
                            ->label('الشعار')
                            ->size(200)
                            ->extraImgAttributes(['class' => 'rounded-lg shadow-md']),
                    ])
                    ->columns(1),

                Section::make('إعدادات الشحن والضرائب')
                    ->icon('heroicon-o-truck')
                    ->schema([
                        TextEntry::make('free_shipping_threshold')
                            ->label('حد الشحن المجاني')
                            ->state(fn ($record) => number_format($record->free_shipping_threshold ?? 0, 2) . ' ر.س')
                            ->size('lg')
                            ->weight('bold')
                            ->color('success'),

                        TextEntry::make('tax_percentage')
                            ->label('نسبة الضريبة')
                            ->state(fn ($record) => number_format($record->tax_percentage ?? 0, 2) . '%')
                            ->size('lg')
                            ->weight('bold')
                            ->color('info'),
                    ])
                    ->columns(2),

                Section::make('إعدادات النقاط')
                    ->icon('heroicon-o-star')
                    ->schema([
                        TextEntry::make('points_days_expired')
                            ->label('أيام انتهاء النقاط')
                            ->state(fn ($record) => ($record->points_days_expired ?? 365) . ' يوم')
                            ->badge()
                            ->color('warning'),

                        TextEntry::make('point_value')
                            ->label('قيمة النقطة')
                            ->state(fn ($record) => number_format($record->point_value ?? 1.00, 2) . ' ر.س')
                            ->size('lg')
                            ->weight('bold')
                            ->color('success'),
                    ])
                    ->columns(2),

                Section::make('إعدادات المنتجات الجديدة والمنتجات المميزة')
                    ->icon('heroicon-o-sparkles')
                    ->schema([
                        TextEntry::make('show_new_arrivals_section')
                            ->label('إظهار قسم المنتجات الجديدة')
                            ->badge()
                            ->formatStateUsing(fn ($state) => $state ? 'مفعل' : 'معطل')
                            ->color(fn ($state) => $state ? 'success' : 'gray'),

                        TextEntry::make('new_arrivals_count')
                            ->label('عدد المنتجات الجديدة')
                            ->state(fn ($record) => ($record->new_arrivals_count ?? 10) . ' منتج')
                            ->badge()
                            ->color('info')
                            ->visible(fn ($record) => $record->show_new_arrivals_section ?? true),

                        TextEntry::make('show_featured_section')
                            ->label('إظهار قسم المنتجات المميزة')
                            ->badge()
                            ->formatStateUsing(fn ($state) => $state ? 'مفعل' : 'معطل')
                            ->color(fn ($state) => $state ? 'success' : 'gray'),

                        TextEntry::make('featured_count')
                            ->label('عدد المنتجات المميزة')
                            ->state(fn ($record) => ($record->featured_count ?? 10) . ' منتج')
                            ->badge()
                            ->color('warning')
                            ->visible(fn ($record) => $record->show_featured_section ?? true),
                    ])
                    ->columns(2),

                Section::make('معلومات الحساب البنكي')
                    ->icon('heroicon-o-building-library')
                    ->schema([
                        TextEntry::make('bank_name')
                            ->label('اسم البنك')
                            ->size('lg')
                            ->weight('bold')
                            ->color('primary')
                            ->default('غير محدد'),

                        TextEntry::make('account_holder')
                            ->label('اسم صاحب الحساب')
                            ->size('lg')
                            ->weight('bold')
                            ->default('غير محدد'),

                        TextEntry::make('account_number')
                            ->label('رقم الحساب')
                            ->badge()
                            ->color('info')
                            ->default('غير محدد'),

                        TextEntry::make('iban')
                            ->label('رقم الآيبان (IBAN)')
                            ->badge()
                            ->color('success')
                            ->default('غير محدد'),

                        TextEntry::make('swift_code')
                            ->label('رمز السويفت (SWIFT)')
                            ->badge()
                            ->color('warning')
                            ->default('غير محدد'),

                        TextEntry::make('branch')
                            ->label('الفرع')
                            ->default('غير محدد'),

                        TextEntry::make('bank_instructions')
                            ->label('تعليمات التحويل البنكي')
                            ->markdown()
                            ->columnSpanFull()
                            ->default('لا توجد تعليمات'),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),


            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageSetting::route('/'),
            'edit' => EditSetting::route('/{record}/edit'),
        ];
    }
}
