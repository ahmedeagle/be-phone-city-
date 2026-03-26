<?php

namespace App\Filament\Admin\Resources\Branches\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BranchInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Section::make('معلومات الفرع')
                    ->icon('heroicon-o-building-storefront')
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

                                TextEntry::make('address_ar')
                                    ->label('العنوان بالعربية')
                                    ->wrap(),

                                TextEntry::make('address_en')
                                    ->label('العنوان بالإنجليزية')
                                    ->wrap(),

                                TextEntry::make('city_ar')
                                    ->label('المدينة بالعربية')
                                    ->placeholder('-'),

                                TextEntry::make('city_en')
                                    ->label('المدينة بالإنجليزية')
                                    ->placeholder('-'),

                                TextEntry::make('is_active')
                                    ->label('الحالة')
                                    ->badge()
                                    ->formatStateUsing(fn ($state) => $state ? 'نشط' : 'غير نشط')
                                    ->color(fn ($state) => $state ? 'success' : 'danger'),

                                TextEntry::make('sort_order')
                                    ->label('الترتيب')
                                    ->badge()
                                    ->color('gray'),
                            ]),
                    ])
                    ->columnSpan(1),

                Section::make('الموقع والتواصل')
                    ->icon('heroicon-o-map-pin')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('latitude')
                                    ->label('خط العرض')
                                    ->placeholder('-'),

                                TextEntry::make('longitude')
                                    ->label('خط الطول')
                                    ->placeholder('-'),
                            ]),

                        TextEntry::make('google_maps_url')
                            ->label('رابط خرائط جوجل')
                            ->url(fn ($record) => $record->google_maps_url, shouldOpenInNewTab: true)
                            ->color('primary')
                            ->icon('heroicon-o-arrow-top-right-on-square')
                            ->placeholder('-'),

                        Grid::make(3)
                            ->schema([
                                TextEntry::make('phone')
                                    ->label('الهاتف الأول')
                                    ->copyable()
                                    ->placeholder('-'),

                                TextEntry::make('phone2')
                                    ->label('الهاتف الثاني')
                                    ->copyable()
                                    ->placeholder('-'),

                                TextEntry::make('whatsapp')
                                    ->label('الواتساب')
                                    ->copyable()
                                    ->placeholder('-'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextEntry::make('working_hours_ar')
                                    ->label('أوقات العمل بالعربية')
                                    ->placeholder('-'),

                                TextEntry::make('working_hours_en')
                                    ->label('أوقات العمل بالإنجليزية')
                                    ->placeholder('-'),
                            ]),

                        Grid::make(2)
                            ->schema([
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
