<?php

namespace App\Filament\Admin\Resources\Products\Schemas;

use App\Models\PaymentMethod;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Filament\Tables\Columns\Layout\Split;

class ProductInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                // Main Product Information
                Group::make()
                    ->schema([
                        Section::make('معلومات المنتج الأساسية')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                Grid::make(3)
                                    ->schema([
                                        // الصورة الرئيسية
                                        ImageEntry::make('main_image')
                                            ->label('الصورة الرئيسية')
                                            ->size(220)
                                            ->square()
                                            ->defaultImageUrl(url('/images/placeholder.png'))
                                            ->extraImgAttributes(['class' => 'rounded-xl shadow-lg border-2 border-gray-200']),

                                        // معلومات النص بجانب الصورة
                                        Group::make()
                                            ->schema([
                                                TextEntry::make('name_ar')
                                                    ->label('الاسم بالعربية')
                                                    ->size(TextSize::Large)
                                                    ->weight(FontWeight::Bold)
                                                    ->color('primary'),

                                                TextEntry::make('name_en')
                                                    ->label('الاسم بالإنجليزية')
                                                    ->size(TextSize::Medium)
                                                    ->weight(FontWeight::SemiBold)
                                                    ->color('gray'),

                                                TextEntry::make('slug')
                                                    ->label('الرابط (Slug)')
                                                    ->badge()
                                                    ->color('info')
                                                    ->copyable()
                                                    ->copyMessage('تم نسخ الرابط')
                                                    ->copyMessageDuration(1500),

                                                Grid::make(1)
                                                    ->schema([
                                                        TextEntry::make('categories.name_ar')
                                                            ->label('التصنيفات')
                                                            ->badge()
                                                            ->separator(',')
                                                            ->color('success'),
                                                    ]),

                                                Grid::make(5)
                                                    ->schema([
                                                        TextEntry::make('capacity')
                                                            ->label('السعة')
                                                            ->icon('heroicon-o-cube')
                                                            ->default('غير محدد'),

                                                        TextEntry::make('points')
                                                            ->label('نقاط المكافآت')
                                                            ->icon('heroicon-o-star')
                                                            ->suffix(' نقطة')
                                                            ->color('warning'),

                                                        TextEntry::make('is_new')
                                                            ->label('منتج جديد؟')
                                                            ->badge()
                                                            ->formatStateUsing(fn ($state) => $state ? 'نعم، جديد' : 'قديم')
                                                            ->color(fn ($state) => $state ? 'success' : 'gray'),

                                                        TextEntry::make('is_new_arrival')
                                                            ->label('جديد الوصول')
                                                            ->badge()
                                                            ->formatStateUsing(fn ($state) => $state ? 'نعم' : 'لا')
                                                            ->color(fn ($state) => $state ? 'success' : 'gray')
                                                            ->icon(fn ($state) => $state ? 'heroicon-o-sparkles' : null),

                                                        TextEntry::make('is_featured')
                                                            ->label('مميز')
                                                            ->badge()
                                                            ->formatStateUsing(fn ($state) => $state ? 'نعم' : 'لا')
                                                            ->color(fn ($state) => $state ? 'warning' : 'gray')
                                                            ->icon(fn ($state) => $state ? 'heroicon-o-star' : null),
                                                    ]),
                                            ])
                                            ->columnSpan(2),
                                    ]),
                            ])
                            ->columnSpan(2),

                        Section::make('معرض الصور')
                            ->icon('heroicon-o-photo')
                            ->description('جميع صور المنتج')
                            ->collapsible()
                            ->schema([
                                RepeatableEntry::make('images')
                                    ->label('الصور')
                                    ->schema([
                                        ImageEntry::make('path')
                                            ->label('الصورة')
                                            ->size(150)
                                            ->square(),
                                    ])
                                    ->columns(4)
                                    ->grid(4)
                                    ->contained(false),
                            ])
                            ->hidden(fn ($record) => $record->images->isEmpty()),
                    ])
                    ->columnSpan(2),

                // Price and Stock Sidebar
                Group::make()
                    ->schema([
                        Section::make('السعر والمخزون')
                            ->icon('heroicon-o-currency-dollar')
                            ->schema([
                                TextEntry::make('main_price')
                                    ->label('السعر الأساسي (الأصلي)')
                                    ->state(fn ($record) => $record->main_price . ' ر.س')
                                    ->size(TextSize::Large)
                                    ->weight(FontWeight::Bold)
                                    ->color('gray'),

                                TextEntry::make('discounted_price')
                                    ->label('السعر المخفض')
                                    ->state(fn ($record) => $record->discounted_price ? $record->discounted_price . ' ر.س' : 'لا يوجد')
                                    ->size(TextSize::Large)
                                    ->weight(FontWeight::Bold)
                                    ->color('warning'),

                                TextEntry::make('final_price')
                                    ->label('السعر النهائي (بعد العروض)')
                                    ->state(fn ($record) => $record->getFinalPrice() . ' ر.س')
                                    ->size(TextSize::Large)
                                    ->weight(FontWeight::Bold)
                                    ->color('success'),

                                TextEntry::make('purchase_price')
                                    ->label('سعر التكلفة (للمسؤول)')
                                    ->state(fn ($record) => $record->purchase_price . ' ر.س')
                                    ->size(TextSize::Medium)
                                    ->weight(FontWeight::SemiBold)
                                    ->color('danger'),

                                TextEntry::make('quantity')
                                    ->label('الكمية المتاحة')
                                    ->badge()
                                    ->color(fn ($record) => match($record->stock_status) {
                                        'out_of_stock' => 'danger',
                                        'limited' => 'warning',
                                        default => 'success',
                                    })
                                    ->formatStateUsing(fn ($record) => match($record->stock_status) {
                                        'out_of_stock' => 'نفذت الكمية',
                                        'limited' => $record->quantity . ' (كمية محدودة)',
                                        default => $record->quantity . ' متوفر',
                                    }),
                            ]),

                        // Best Offer Section
                        Section::make('العرض المتاح')
                            ->icon('heroicon-o-gift')
                            ->schema([
                                ViewEntry::make('best_offer')
                                    ->label('')
                                    ->view('filament.infolists.components.offer-card')
                                    ->state(fn ($record) => $record->getBestOffer()),
                            ])
                            ->visible(fn ($record) => $record->getBestOffer() !== null),
                    ])
                    ->columnSpan(1),

                // Description and Details
                Section::make('الوصف والتفاصيل')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('description_ar')
                                    ->label('الوصف بالعربية')
                                    ->markdown()
                                    ->columnSpan(1),

                                TextEntry::make('description_en')
                                    ->label('الوصف بالإنجليزية')
                                    ->markdown()
                                    ->columnSpan(1),
                            ]),
                    ])
                    ->collapsible()
                    ->columnSpan(3),

                Section::make('تفاصيل إضافية')
                    ->icon('heroicon-o-document-duplicate')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                RepeatableEntry::make('details_ar')
                                    ->label('التفاصيل بالعربية')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextEntry::make('key')
                                                    ->label('المعيار')
                                                    ->weight(\Filament\Support\Enums\FontWeight::Bold)
                                                    ->color('primary')
                                                    ->columnSpan(1),

                                                TextEntry::make('value')
                                                    ->label('القيمة')
                                                    ->columnSpan(1),
                                            ]),
                                    ])
                                    ->columns(1)
                                    ->columnSpan(1),

                                RepeatableEntry::make('details_en')
                                    ->label('التفاصيل بالإنجليزية')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextEntry::make('key')
                                                    ->label('Specification')
                                                    ->weight(\Filament\Support\Enums\FontWeight::Bold)
                                                    ->color('primary')
                                                    ->columnSpan(1),

                                                TextEntry::make('value')
                                                    ->label('Value')
                                                    ->columnSpan(1),
                                            ]),
                                    ])
                                    ->columns(1)
                                    ->columnSpan(1),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->columnSpan(3)
                    ->hidden(fn ($record) => (empty($record->details_ar) || !is_array($record->details_ar) || count($record->details_ar) === 0) && (empty($record->details_en) || !is_array($record->details_en) || count($record->details_en) === 0)),

                Section::make('حول المنتج')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('about_ar')
                                    ->label('حول المنتج بالعربية')
                                    ->html()
                                    ->columnSpan(1),

                                TextEntry::make('about_en')
                                    ->label('حول المنتج بالإنجليزية')
                                    ->html()
                                    ->columnSpan(1),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->columnSpan(3)
                    ->hidden(fn ($record) => empty($record->about_ar) && empty($record->about_en)),

                // Order Statistics Section
                Section::make('إحصائيات المبيعات')
                    ->icon('heroicon-o-chart-bar')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('sold_quantity')
                                    ->label('الكمية المباعة')
                                    ->state(function ($record) {
                                        return $record->orderItems()
                                            ->whereHas('order', function ($query) {
                                                $query->where('status', \App\Models\Order::STATUS_COMPLETED);
                                            })
                                            ->sum('quantity') ?: 0;
                                    })
                                    ->suffix(' قطعة')
                                    ->size(TextSize::Large)
                                    ->weight(FontWeight::Bold)
                                    ->color('primary')
                                    ->icon('heroicon-o-shopping-cart'),

                                TextEntry::make('total_revenue')
                                    ->label('إجمالي الإيرادات')
                                    ->state(function ($record) {
                                        $total = $record->orderItems()
                                            ->whereHas('order', function ($query) {
                                                $query->where('status', \App\Models\Order::STATUS_COMPLETED);
                                            })
                                            ->sum('total') ?: 0;
                                        return number_format($total, 2) . ' ر.س';
                                    })
                                    ->size(TextSize::Large)
                                    ->weight(FontWeight::Bold)
                                    ->color('success')
                                    ->icon('heroicon-o-currency-dollar'),

                                TextEntry::make('average_order_value')
                                    ->label('متوسط قيمة الطلب')
                                    ->state(function ($record) {
                                        $soldQty = $record->orderItems()
                                            ->whereHas('order', function ($query) {
                                                $query->where('status', \App\Models\Order::STATUS_COMPLETED);
                                            })
                                            ->sum('quantity') ?: 0;

                                        if ($soldQty == 0) return '0.00 ر.س';

                                        $total = $record->orderItems()
                                            ->whereHas('order', function ($query) {
                                                $query->where('status', \App\Models\Order::STATUS_COMPLETED);
                                            })
                                            ->sum('total') ?: 0;

                                        return number_format($total / $soldQty, 2) . ' ر.س';
                                    })
                                    ->size(TextSize::Medium)
                                    ->weight(FontWeight::SemiBold)
                                    ->color('info')
                                    ->icon('heroicon-o-calculator'),
                            ]),
                    ])
                    ->columnSpan(3)
                    ->collapsible(),

                // Reviews Section
                Section::make('التقييمات والمراجعات')
                    ->icon('heroicon-o-star')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('reviews_summary')
                                    ->label('ملخص التقييمات')
                                    ->state(function ($record) {
                                        $reviewsCount = $record->reviews()->count();
                                        $avgRating = $record->reviews()->avg('rating');

                                        if ($reviewsCount == 0) {
                                            return 'لا توجد تقييمات بعد';
                                        }

                                        return "{$reviewsCount} تقييم | متوسط التقييم: " . number_format($avgRating, 1) . " / 5.0";
                                    })
                                    ->size(TextSize::Large)
                                    ->weight(FontWeight::Bold)
                                    ->color('primary')
                                    ->icon('heroicon-o-star'),

                                TextEntry::make('rating_distribution')
                                    ->label('توزيع التقييمات')
                                    ->state(function ($record) {
                                        $distribution = [];
                                        for ($i = 5; $i >= 1; $i--) {
                                            $count = $record->reviews()->where('rating', $i)->count();
                                            $distribution[] = "{$i} نجوم: {$count}";
                                        }
                                        return implode(' | ', $distribution);
                                    })
                                    ->size(TextSize::Small)
                                    ->color('gray'),
                            ]),

                        RepeatableEntry::make('reviews')
                            ->label('المراجعات')
                            ->schema([
                                Grid::make(4)
                                    ->schema([
                                        TextEntry::make('user.name')
                                            ->label('المستخدم')
                                            ->weight(FontWeight::Bold)
                                            ->color('primary'),

                                        TextEntry::make('rating')
                                            ->label('التقييم')
                                            ->badge()
                                            ->formatStateUsing(fn ($state) => $state . ' / 5')
                                            ->color(fn ($state) => match (true) {
                                                $state >= 4 => 'success',
                                                $state >= 3 => 'warning',
                                                default => 'danger',
                                            })
                                            ->icon('heroicon-o-star'),

                                        TextEntry::make('comment')
                                            ->label('التعليق')
                                            ->limit(100)
                                            ->placeholder('لا يوجد تعليق'),

                                        TextEntry::make('created_at')
                                            ->label('تاريخ التقييم')
                                            ->dateTime('Y-m-d H:i')
                                            ->color('gray')
                                            ->size(TextSize::Small),
                                    ]),
                            ])
                            ->columns(1)
                            ->contained(true),
                    ])
                    ->columnSpan(3)
                    ->collapsible()
                    ->collapsed(),

                // Product Options with Payment Methods
                Section::make('خيارات المنتج')
                ->icon('heroicon-o-swatch')
                ->schema([
                    RepeatableEntry::make('options')
                        ->label('خيارات المنتج')
                        ->schema([
                            Section::make()
                                ->schema([
                                    Grid::make(4)
                                        ->schema([
                                            TextEntry::make('type')
                                                ->label('نوع الخيار')
                                                ->badge()
                                                ->color('primary')
                                                ->formatStateUsing(fn (string $state): string =>
                                                    $state === 'color' ? 'لون' : 'مقاس'
                                                ),

                                            TextEntry::make('value_ar')
                                                ->label('القيمة')
                                                ->html()
                                                ->formatStateUsing(function ($record): string {
                                                    if ($record->type === 'color') {
                                                        $color = $record->value_ar;
                                                        $colorMap = [
                                                            '#FF0000' => 'أحمر',
                                                            '#00FF00' => 'أخضر',
                                                            '#0000FF' => 'أزرق',
                                                            '#FFFF00' => 'أصفر',
                                                            '#FFA500' => 'برتقالي',
                                                            '#800080' => 'بنفسجي',
                                                            '#FFC0CB' => 'وردي',
                                                            '#000000' => 'أسود',
                                                            '#FFFFFF' => 'أبيض',
                                                            '#808080' => 'رمادي',
                                                            '#A52A2A' => 'بني',
                                                            '#F5F5DC' => 'بيج',
                                                            '#FFD700' => 'ذهبي',
                                                            '#C0C0C0' => 'فضي',
                                                            '#00FFFF' => 'سماوي',
                                                        ];
                                                        $colorName = $colorMap[$color] ?? $color;
                                                        return "
                                                            <div style='display: flex; align-items: center; gap: 8px;'>
                                                                <span style='
                                                                    width: 20px;
                                                                    height: 20px;
                                                                    border-radius: 50%;
                                                                    background: {$color};
                                                                    border: 2px solid #ccc;
                                                                    display: inline-block;
                                                                '></span>
                                                                <span style='font-weight: 600;'>{$colorName}</span>
                                                            </div>
                                                        ";
                                                    }
                                                    return $record->value_ar;
                                                }),

                                            TextEntry::make('price')
                                                ->label('السعر الأساسي')
                                                // ->money('SAR', locale: 'ar-SA')
                                                ->state(fn ($record) => ($record->price ?: $record->product->main_price ). ' ر.س')
                                                ->weight(FontWeight::Bold)
                                                ->color('success'),

                                            TextEntry::make('sku')
                                                ->label('SKU')
                                                ->badge()
                                                ->color('gray')
                                                ->default('غير محدد'),
                                        ]),

                                    // Option Images
                                    RepeatableEntry::make('images')
                                        ->label('صور الخيار')
                                        ->schema([
                                            ImageEntry::make('path')
                                                ->label('الصورة')
                                                ->imageSize(100)
                                                // ->width(150)
                                                ->extraImgAttributes(['class' => 'rounded-lg object-cover shadow-md']),
                                        ])
                                        ->columns(6)
                                        ->grid(6)
                                        ->contained(false)
                                        ->visible(fn ($record) => $record->images->isNotEmpty()),

                                    // Applied Offers for this Option
                                    Grid::make(1)
                                        ->schema([
                                            TextEntry::make('applied_offer')
                                                ->label('العرض المطبق')
                                                ->badge()
                                                ->color('danger')
                                                ->state(function ($record) {
                                                    $bestOffer = $record->getBestOffer();
                                                    if (!$bestOffer) return 'لا يوجد عرض';

                                                    $offerValue = $bestOffer->type === 'percentage'
                                                        ? $bestOffer->value . '%'
                                                        : number_format($bestOffer->value, 2) . ' ر.س';

                                                    return $bestOffer->name_ar . ' - خصم ' . $offerValue;
                                                })
                                                ->visible(fn ($record) => $record->getBestOffer() !== null),
                                        ])
                                        ->visible(fn ($record) => $record->getBestOffer() !== null),

                                    // Price Breakdown
                                    Grid::make(3)
                                        ->schema([
                                            TextEntry::make('base_price')
                                                ->label('السعر الأساسي')
                                                ->state(fn ($record) => number_format($record->getOriginalPrice(), 2) . ' ر.س')
                                                ->weight(FontWeight::Bold)
                                                ->color('gray'),

                                            TextEntry::make('discount_value')
                                                ->label('قيمة الخصم')
                                                ->state(function ($record) {
                                                    $bestOffer = $record->getBestOffer();
                                                    if (!$bestOffer) return '0.00 ر.س';

                                                    $price = $record->getOriginalPrice();
                                                    $discount = $bestOffer->type === 'percentage'
                                                        ? $price * ($bestOffer->value / 100)
                                                        : $bestOffer->value;

                                                    return number_format($discount, 2) . ' ر.س';
                                                })
                                                ->badge()
                                                ->color('danger')
                                                ->visible(fn ($record) => $record->getBestOffer() !== null),

                                            TextEntry::make('final_price_before_fee')
                                                ->label('السعر النهائي')
                                                ->state(fn ($record) => number_format($record->getFinalPrice(), 2) . ' ر.س')
                                                ->weight(FontWeight::Bold)
                                                ->color('success')
                                                ->size(TextSize::Large),
                                        ]),
                                ])
                                ->columnSpan(1),
                        ])
                        ->contained(true),
                ])
                ->columnSpan(3)
                ->visible(fn ($record) => $record->options()->count() > 0)
                ->collapsible()
                ->collapsed(),
            ]);
    }
}
