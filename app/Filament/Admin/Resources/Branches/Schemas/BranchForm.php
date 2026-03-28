<?php

namespace App\Filament\Admin\Resources\Branches\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BranchForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('معلومات الفرع')
                    ->description('الاسم والعنوان بالعربية والإنجليزية')
                    ->icon('heroicon-o-building-storefront')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name_ar')
                                    ->required()
                                    ->maxLength(255)
                                    ->label('اسم الفرع بالعربية')
                                    ->placeholder('مثال: فرع الرياض الرئيسي'),

                                TextInput::make('name_en')
                                    ->required()
                                    ->maxLength(255)
                                    ->label('اسم الفرع بالإنجليزية')
                                    ->placeholder('e.g. Main Riyadh Branch'),
                            ]),
                        Grid::make(2)
                            ->schema([
                                Textarea::make('address_ar')
                                    ->required()
                                    ->rows(2)
                                    ->label('العنوان بالعربية')
                                    ->placeholder('العنوان الكامل بالعربية'),

                                Textarea::make('address_en')
                                    ->required()
                                    ->rows(2)
                                    ->label('العنوان بالإنجليزية')
                                    ->placeholder('Full address in English'),
                            ]),
                        Grid::make(2)
                            ->schema([
                                TextInput::make('city_ar')
                                    ->maxLength(255)
                                    ->label('المدينة بالعربية')
                                    ->placeholder('الرياض'),

                                TextInput::make('city_en')
                                    ->maxLength(255)
                                    ->label('المدينة بالإنجليزية')
                                    ->placeholder('Riyadh'),
                            ]),
                    ]),

                Section::make('الموقع على الخريطة')
                    ->description('إحداثيات الموقع ورابط خرائط جوجل')
                    ->icon('heroicon-o-map-pin')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('latitude')
                                    ->numeric()
                                    ->label('خط العرض (Latitude)')
                                    ->placeholder('24.7136')
                                    ->helperText('يمكنك الحصول عليه من خرائط جوجل'),

                                TextInput::make('longitude')
                                    ->numeric()
                                    ->label('خط الطول (Longitude)')
                                    ->placeholder('46.6753')
                                    ->helperText('يمكنك الحصول عليه من خرائط جوجل'),
                            ]),
                        TextInput::make('google_maps_url')
                            ->url()
                            ->maxLength(500)
                            ->label('رابط خرائط جوجل')
                            ->placeholder('https://maps.google.com/...')
                            ->helperText('الرابط المباشر لموقع الفرع على خرائط جوجل')
                            ->columnSpanFull(),
                    ]),

                Section::make('أرقام التواصل')
                    ->description('أرقام الهاتف والواتساب')
                    ->icon('heroicon-o-phone')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('phone')
                                    ->tel()
                                    ->maxLength(20)
                                    ->label('رقم الهاتف الأول')
                                    ->placeholder('+966XXXXXXXXX'),

                                TextInput::make('phone2')
                                    ->tel()
                                    ->maxLength(20)
                                    ->label('رقم الهاتف الثاني')
                                    ->placeholder('+966XXXXXXXXX'),

                                TextInput::make('whatsapp')
                                    ->tel()
                                    ->maxLength(20)
                                    ->label('رقم الواتساب')
                                    ->placeholder('+966XXXXXXXXX'),
                            ]),
                    ]),

                Section::make('ربط OTO للشحن')
                    ->description('معرّف المستودع في نظام OTO لإنشاء الشحنات من هذا الفرع')
                    ->icon('heroicon-o-truck')
                    ->schema([
                        TextInput::make('oto_warehouse_id')
                            ->maxLength(255)
                            ->label('معرّف مستودع OTO')
                            ->placeholder('مثال: WH-001')
                            ->helperText('أدخل معرّف المستودع من حساب OTO. الفروع التي لها معرّف فقط ستظهر عند إنشاء الشحنات.')
                            ->columnSpanFull(),
                    ]),

                Section::make('أوقات العمل والإعدادات')
                    ->description('ساعات العمل وترتيب العرض')
                    ->icon('heroicon-o-clock')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('working_hours_ar')
                                    ->maxLength(255)
                                    ->label('أوقات العمل بالعربية')
                                    ->placeholder('السبت - الخميس: 9 ص - 11 م'),

                                TextInput::make('working_hours_en')
                                    ->maxLength(255)
                                    ->label('أوقات العمل بالإنجليزية')
                                    ->placeholder('Sat - Thu: 9 AM - 11 PM'),
                            ]),
                        Grid::make(2)
                            ->schema([
                                TextInput::make('sort_order')
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(0)
                                    ->label('ترتيب العرض')
                                    ->helperText('الأقل يظهر أولاً'),

                                Toggle::make('is_active')
                                    ->label('نشط')
                                    ->helperText('تفعيل/تعطيل الفرع')
                                    ->default(true),
                            ]),
                    ]),
            ]);
    }
}
