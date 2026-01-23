<?php

namespace App\Filament\Admin\Resources\Cities\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CityForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('معلومات المدينة')
                    ->description('المعلومات الأساسية للمدينة')
                    ->icon('heroicon-o-map-pin')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name_ar')
                                    ->required()
                                    ->maxLength(255)
                                    ->label('اسم المدينة بالعربية')
                                    ->placeholder('أدخل اسم المدينة بالعربية'),

                                TextInput::make('name_en')
                                    ->required()
                                    ->maxLength(255)
                                    ->label('اسم المدينة بالإنجليزية')
                                    ->placeholder('Enter city name in English'),
                            ]),
                    ])
                    ->columnSpan(1),

                Section::make('إعدادات الشحن')
                    ->description('معلومات الشحن والرسوم')
                    ->icon('heroicon-o-truck')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('shipping_fee')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->prefix('ر.س')
                                    ->default(0)
                                    ->label('رسوم الشحن')
                                    ->placeholder('0.00')
                                    ->helperText('رسوم الشحن للمدينة'),

                                TextInput::make('order')
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(0)
                                    ->label('الترتيب')
                                    ->placeholder('0')
                                    ->helperText('ترتيب العرض في القائمة'),
                            ]),

                        Toggle::make('status')
                            ->label('الحالة')
                            ->helperText('تفعيل/إلغاء تفعيل المدينة')
                            ->default(true),
                    ])
                    ->columnSpan(1),
            ]);
    }
}
