<?php

namespace App\Filament\Admin\Resources\Discounts\Schemas;

use App\Models\Discount;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DiscountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('معلومات أساسية')
                    ->schema([
                        TextInput::make('code')
                            ->label('كود الخصم')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->helperText('كود فريد للخصم'),
                        Toggle::make('status')
                            ->label('الحالة')
                            ->default(true)
                            ->helperText('تفعيل أو تعطيل الخصم'),
                        Select::make('type')
                            ->label('نوع الخصم')
                            ->options([
                                'percentage' => 'نسبة مئوية (%)',
                                'fixed' => 'مبلغ ثابت',
                            ])
                            ->default('percentage')
                            ->required()
                            ->native(false),
                        TextInput::make('value')
                            ->label('قيمة الخصم')
                            ->required()
                            ->numeric()
                            ->suffix(fn ($get) => $get('type') === 'percentage' ? '%' : 'ر.س')
                            ->helperText(fn ($get) => $get('type') === 'percentage'
                                ? 'قيمة الخصم كنسبة مئوية (0-100)'
                                : 'قيمة الخصم كمبلغ ثابت')
                            ->minValue(0.01)
                            ->maxValue(fn ($get) => $get('type') === 'percentage' ? 100 : null)
                            ->reactive(),
                    ])
                    ->columns(2),
                Section::make('التواريخ')
                    ->schema([
                        DateTimePicker::make('start')
                            ->label('تاريخ البداية')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y H:i')
                            ->minDate(now()),
                        DateTimePicker::make('end')
                            ->label('تاريخ النهاية')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y H:i')
                            ->after('start')
                            ->minDate(now()),
                    ])
                    ->columns(2),
                Section::make('الوصف')
                    ->schema([
                        Textarea::make('description_en')
                            ->label('الوصف بالإنجليزية')
                            ->rows(3)
                            ->nullable(),
                        Textarea::make('description_ar')
                            ->label('الوصف بالعربية')
                            ->rows(3)
                            ->nullable(),
                    ])
                    ->columns(2),
                Section::make('الشروط')
                    ->description('شروط تطبيق الخصم (اختياري - اتركه فارغاً إذا لم يكن هناك شرط)')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Select::make('condition_type')
                            ->label('نوع الشرط')
                            ->options(Discount::getConditionTypes())
                            ->default(null)
                            ->nullable()
                            ->placeholder('اختر نوع الشرط (اختياري)')
                            ->native(false)
                            ->reactive()
                            ->afterStateUpdated(function ($set, $state) {
                                // Clear condition value when type changes
                                if ($state === null || !in_array($state, [Discount::CONDITION_MIN_AMOUNT, Discount::CONDITION_MIN_QUANTITY])) {
                                    $set('condition_value', null);
                                }
                            }),
                        TextInput::make('condition_value')
                            ->label('قيمة الشرط')
                            ->numeric()
                            ->minValue(0)
                            ->visible(fn ($get) => in_array($get('condition_type'), [Discount::CONDITION_MIN_AMOUNT, Discount::CONDITION_MIN_QUANTITY]))
                            ->required(fn ($get) => in_array($get('condition_type'), [Discount::CONDITION_MIN_AMOUNT, Discount::CONDITION_MIN_QUANTITY]))
                            ->helperText(fn ($get) => $get('condition_type') === Discount::CONDITION_MIN_AMOUNT
                                ? 'الحد الأدنى للمبلغ المطلوب (مثال: 100)'
                                : ($get('condition_type') === Discount::CONDITION_MIN_QUANTITY
                                    ? 'الحد الأدنى لعدد العناصر (مثال: 3)'
                                    : ''))
                            ->reactive(),
                    ]),
            ]);
    }
}
