<?php

namespace App\Filament\Admin\Resources\Cities\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CityInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Section::make('معلومات المدينة')
                    ->icon('heroicon-o-map-pin')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('name_ar')
                                    ->label('الاسم بالعربية')
                                    ->size('lg')
                                    ->weight('bold'),

                                TextEntry::make('name_en')
                                    ->label('الاسم بالإنجليزية')
                                    ->size('lg')
                                    ->weight('bold'),

                                TextEntry::make('slug')
                                    ->label('الرابط (Slug)')
                                    ->badge()
                                    ->color('info')
                                    ->copyable()
                                    ->copyMessage('تم نسخ الرابط'),

                                TextEntry::make('status')
                                    ->label('الحالة')
                                    ->badge()
                                    ->formatStateUsing(fn ($state) => $state ? 'نشط' : 'غير نشط')
                                    ->color(fn ($state) => $state ? 'success' : 'danger'),
                            ]),
                    ])
                    ->columnSpan(1),

                Section::make('إعدادات الشحن')
                    ->icon('heroicon-o-truck')
                    ->schema([
                        Grid::make(1)
                            ->schema([
                                TextEntry::make('shipping_fee')
                                    ->label('رسوم الشحن')
                                    ->state(fn ($record) => number_format($record->shipping_fee, 2) . 'ر.س')
                                    ->size('lg')
                                    ->weight('bold')
                                    ->color('success'),

                                TextEntry::make('order')
                                    ->label('الترتيب')
                                    ->badge()
                                    ->color('gray'),

                                TextEntry::make('created_at')
                                    ->label('تاريخ الإنشاء')
                                    ->dateTime('Y-m-d H:i')
                                    ->color('gray'),

                                TextEntry::make('updated_at')
                                    ->label('آخر تحديث')
                                    ->dateTime('Y-m-d H:i')
                                    ->color('gray'),
                            ]),
                    ])
                    ->columnSpan(1),
            ]);
    }
}
