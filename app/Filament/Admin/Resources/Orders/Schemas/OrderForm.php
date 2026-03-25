<?php

namespace App\Filament\Admin\Resources\Orders\Schemas;

use App\Models\Order;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\HtmlString;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('معلومات الطلب')
                    ->schema([
                        TextInput::make('order_number')
                            ->label('رقم الطلب')
                            ->disabled()
                            ->dehydrated(false),
                        Select::make('status')
                            ->label('حالة الطلب')
                            ->options([
                                Order::STATUS_PENDING => 'بانتظار الدفع',
                                Order::STATUS_CONFIRMED => 'تم تأكيد الطلب',
                                Order::STATUS_PROCESSING => 'جاري تجهيز الطلب',
                                Order::STATUS_SHIPPED => 'تم الشحن',
                                Order::STATUS_IN_PROGRESS => 'جاري التوصيل',
                                Order::STATUS_DELIVERED => 'تم التوصيل',
                                Order::STATUS_COMPLETED => 'مكتمل',
                                Order::STATUS_CANCELLED => 'ملغي',
                            ])
                            ->required()
                            ->native(false),
                        Textarea::make('notes')
                            ->label('ملاحظات')
                            ->rows(3)
                            ->maxLength(1000),
                    ])
                    ->columns(2),

                Section::make('معلومات الشحن')
                    ->schema([
                        TextInput::make('oto_order_id')
                            ->label('رقم طلب OTO')
                            ->helperText('يتم تعبئته تلقائياً عند إنشاء الشحنة')
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn ($record) => !empty($record?->oto_order_id)),
                        TextInput::make('tracking_number')
                            ->label('رقم التتبع')
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn ($record) => !empty($record?->tracking_number)),
                    ])
                    ->collapsed()
                    ->collapsible()
                    ->visible(fn ($record) => !empty($record?->oto_order_id) || !empty($record?->tracking_number)),
            ]);
    }
}
