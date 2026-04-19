<?php

namespace App\Filament\Admin\Resources\Products\Schemas;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
        ->columns(1)
        ->components([
            // Basic Information Section
            Section::make('المعلومات الأساسية')
                ->description('معلومات المنتج الأساسية والتصنيف')
                ->icon('heroicon-o-information-circle')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            TextInput::make('name_ar')
                                ->required()
                                ->maxLength(255)
                                ->label('اسم المنتج بالعربية')
                                ->placeholder('أدخل اسم المنتج بالعربية'),

                            TextInput::make('name_en')
                                ->required()
                                ->maxLength(255)
                                ->label('اسم المنتج بالإنجليزية')
                                ->placeholder('Enter product name in English'),


                        ]),

                    Grid::make(4)
                        ->schema([
                            Select::make('categories')
                                ->relationship('categories', 'name_ar')
                                ->multiple()
                                ->searchable()
                                ->preload()
                                ->required()
                                ->label('التصنيفات')
                                ->placeholder('اختر التصنيفات'),

                            Toggle::make('is_new')
                                ->label('هل هو جديد؟')
                                ->default(false),

                            Toggle::make('is_new_arrival')
                                ->label('منتج جديد الوصول')
                                ->default(false)
                                ->helperText('يعرض في قسم المنتجات الجديدة'),

                            Toggle::make('is_featured')
                                ->label('منتج مميز')
                                ->default(false)
                                ->helperText('يعرض في قسم المنتجات المميزة'),

                            Toggle::make('is_installment')
                                ->label('متاح بالتقسيط')
                                ->default(false)
                                ->helperText('يمكن شراء هذا المنتج بالتقسيط'),
                        ]),
                ])
                ->columnSpan(1),

            // Images Section
            Section::make('الصور')
                ->description('الصورة الرئيسية والصور الإضافية للمنتج')
                ->icon('heroicon-o-photo')
                ->schema([
                    FileUpload::make('main_image')
                        ->image()
                        ->imageEditor()
                        ->imageCropAspectRatio('1:1')
                        ->imageResizeTargetWidth(800)
                        ->imageResizeTargetHeight(800)
                        ->maxSize(5120)
                        ->nullable()
                        ->label('الصورة الرئيسية')
                        ->helperText('يفضل أن تكون الصورة بحجم 800x800 بكسل'),

                    Repeater::make('images')
                        ->relationship()
                        ->schema([
                            FileUpload::make('path')
                                ->image()
                                ->imageEditor()
                                ->maxSize(5120)
                                ->required()
                                ->label('الصورة'),

                            TextInput::make('sort_order')
                                ->numeric()
                                ->default(0)
                                ->label('الترتيب')
                                ->helperText('رقم الترتيب للصورة'),
                        ])
                        ->columns(2)
                        ->defaultItems(0)
                        ->collapsible()
                        ->itemLabel(fn (array $state): ?string => 'صورة ' . ($state['sort_order'] ?? 'جديدة'))
                        ->label('صور إضافية')
                        ->addActionLabel('إضافة صورة')
                        ->reorderable('sort_order')
                        ->orderColumn('sort_order'),
                ])
                ->collapsible()
                ->collapsed()
                ->columnSpan(1),

            // Description Section
            Section::make('الوصف والتفاصيل')
                ->description('وصف المنتج وتفاصيله الكاملة')
                ->icon('heroicon-o-document-text')
                ->schema([
                    Grid::make(1)
                        ->schema([
                            Textarea::make('description_ar')
                                ->rows(3)
                                ->required()
                                ->label('الوصف بالعربية')
                                ->placeholder('وصف مختصر للمنتج بالعربية'),

                            Textarea::make('description_en')
                                ->rows(3)
                                ->required()
                                ->label('الوصف بالإنجليزية')
                                ->placeholder('Short description in English'),
                        ]),

                    Grid::make(2)
                        ->schema([
                            Repeater::make('details_ar')
                                ->label('التفاصيل بالعربية')
                                ->schema([
                                    Grid::make(2)
                                        ->schema([
                                            TextInput::make('key')
                                                ->label('المفتاح (المعيار)')
                                                ->required()
                                                ->placeholder('مثال: العلامة التجارية')
                                                ->columnSpan(1),

                                            TextInput::make('value')
                                                ->label('القيمة')
                                                ->required()
                                                ->placeholder('مثال: HP')
                                                ->columnSpan(1),
                                        ]),
                                ])
                                ->defaultItems(0)
                                ->collapsible()
                                ->itemLabel(fn (array $state): ?string =>
                                    isset($state['key']) ? $state['key'] : 'تفصيل جديد'
                                )
                                ->addActionLabel('إضافة تفصيل')
                                ->reorderable()
                                ->columnSpan(1),

                            Repeater::make('details_en')
                                ->label('التفاصيل بالإنجليزية')
                                ->schema([
                                    Grid::make(2)
                                        ->schema([
                                            TextInput::make('key')
                                                ->label('Key (Specification)')
                                                ->required()
                                                ->placeholder('e.g., Brand')
                                                ->columnSpan(1),

                                            TextInput::make('value')
                                                ->label('Value')
                                                ->required()
                                                ->placeholder('e.g., HP')
                                                ->columnSpan(1),
                                        ]),
                                ])
                                ->defaultItems(0)
                                ->collapsible()
                                ->itemLabel(fn (array $state): ?string =>
                                    isset($state['key']) ? $state['key'] : 'New Detail'
                                )
                                ->addActionLabel('Add Detail')
                                ->reorderable()
                                ->columnSpan(1),
                        ]),

                    Grid::make(1)
                        ->schema([
                            RichEditor::make('about_ar')
                                ->label('حول المنتج بالعربية')
                                ->toolbarButtons([
                                    'bold',
                                    'italic',
                                    'underline',
                                    'bulletList',
                                    'orderedList',
                                ]),

                            RichEditor::make('about_en')
                                ->label('حول المنتج بالإنجليزية')
                                ->toolbarButtons([
                                    'bold',
                                    'italic',
                                    'underline',
                                    'bulletList',
                                    'orderedList',
                                ]),
                        ]),
                ])
                ->collapsible()
                ->collapsed()
                ->columnSpan(1),

            // Price and Stock Section
            Section::make('السعر والمخزون')
                ->description('معلومات السعر والكمية المتوفرة')
                ->icon('heroicon-o-currency-dollar')
                ->schema([
                    Grid::make(3)
                        ->schema([
                            TextInput::make('main_price')
                                ->required()
                                ->numeric()
                                ->minValue(0)
                                ->prefix('ر.س')
                                ->label('السعر الأساسي')
                                ->placeholder('0.00')
                                ->reactive(),

                            TextInput::make('discounted_price')
                                ->numeric()
                                ->minValue(0)
                                ->prefix('ر.س')
                                ->label('السعر بعد الخصم (اختياري)')
                                ->placeholder('0.00')
                                ->helperText('يجب أن يكون أقل من السعر الأساسي')
                                ->reactive()
                                ->rules([fn ($get) => function ($attribute, $value, $fail) use ($get) {
                                    if ($value !== null && $value !== '' && $get('main_price') !== null && $get('main_price') !== '' && floatval($value) >= floatval($get('main_price'))) {
                                        $fail('السعر بعد الخصم يجب أن يكون أقل من السعر الأساسي.');
                                    }
                                }]),

                            TextInput::make('purchase_price')
                                ->numeric()
                                ->minValue(0)
                                ->prefix('ر.س')
                                ->label('سعر التكلفة (للمسؤول فقط)')
                                ->placeholder('0.00')
                                ->helperText('يجب أن يكون أقل من السعر الأساسي')
                                ->rules([fn ($get) => function ($attribute, $value, $fail) use ($get) {
                                    if ($value !== null && $value !== '' && $get('main_price') !== null && $get('main_price') !== '') {
                                        $discounted = $get('discounted_price');
                                        $comparePrice = ($discounted !== null && $discounted !== '') ? floatval($discounted) : floatval($get('main_price'));
                                        if (floatval($value) >= $comparePrice) {
                                            $fail('سعر التكلفة يجب أن يكون أقل من السعر بعد الخصم (أو الأساسي إن لم يكن هناك خصم).');
                                        }
                                    }
                                }]),
                        ]),

                    Grid::make(2)
                        ->schema([
                            TextInput::make('quantity')
                                ->required()
                                ->numeric()
                                ->minValue(0)
                                ->default(0)
                                ->label('الكمية')
                                ->placeholder('0'),

                            TextInput::make('capacity')
                                ->maxLength(255)
                                ->label('السعة')
                                ->placeholder('مثال: 500 مل، 1 كجم'),
                        ]),
                ])
                ->collapsible()
                ->columnSpan(1),

            // Colors Section
            Section::make('الألوان المتاحة')
                ->description('أضف الألوان المتوفرة للمنتج')
                ->icon('heroicon-o-swatch')
                ->schema([
                    Repeater::make('colors')
                        ->relationship('options', modifyQueryUsing: fn($query) => $query->where('type', 'color'))
                        ->schema([
                            Grid::make(3)
                                ->schema([
                                    ColorPicker::make('value_ar')
                                        ->required()
                                        ->label('اللون')
                                        ->afterStateUpdated(function ($state, $set) {
                                            $set('value_en', $state);
                                        }),

                                    TextInput::make('sku')
                                        ->maxLength(255)
                                        ->label('SKU')
                                        ->placeholder('مثال: PROD-RED-001'),

                                    TextInput::make('quantity')
                                        ->numeric()
                                        ->minValue(0)
                                        ->default(0)
                                        ->label('الكمية')
                                        ->placeholder('0'),
                                ]),

                            Grid::make(3)
                                ->schema([
                                    TextInput::make('price')
                                        ->numeric()
                                        ->minValue(0)
                                        ->prefix('ر.س')
                                        ->label('السعر الإضافي')
                                        ->placeholder('0.00')
                                        ->helperText('اتركه فارغاً إذا كان بنفس السعر الأساسي')
                                        ->reactive(),

                                    TextInput::make('discounted_price')
                                        ->numeric()
                                        ->minValue(0)
                                        ->prefix('ر.س')
                                        ->label('السعر بعد الخصم')
                                        ->placeholder('0.00')
                                        ->helperText('يجب أن يكون أقل من السعر الإضافي')
                                        ->rules([fn ($get) => function ($attribute, $value, $fail) use ($get) {
                                            if ($value !== null && $value !== '' && $get('price') !== null && $get('price') !== '' && floatval($value) >= floatval($get('price'))) {
                                                $fail('السعر بعد الخصم يجب أن يكون أقل من السعر الإضافي.');
                                            }
                                        }])
                                        ->reactive(),

                                    TextInput::make('purchase_price')
                                        ->numeric()
                                        ->minValue(0)
                                        ->prefix('ر.س')
                                        ->label('سعر التكلفة')
                                        ->placeholder('0.00')
                                        ->helperText('يجب أن يكون أقل من السعر بعد الخصم أو الإضافي')
                                        ->rules([fn ($get) => function ($attribute, $value, $fail) use ($get) {
                                            if ($value !== null && $value !== '') {
                                                $discounted = $get('discounted_price');
                                                $base = $get('price');
                                                if ($discounted !== null && $discounted !== '') {
                                                    $comparePrice = floatval($discounted);
                                                } elseif ($base !== null && $base !== '') {
                                                    $comparePrice = floatval($base);
                                                } else {
                                                    return;
                                                }
                                                if (floatval($value) >= $comparePrice) {
                                                    $fail('سعر التكلفة يجب أن يكون أقل من سعر البيع.');
                                                }
                                            }
                                        }]),
                                ]),

                            Repeater::make('images')
                                ->relationship()
                                ->schema([
                                    Grid::make(2)
                                        ->schema([
                                            FileUpload::make('path')
                                                ->image()
                                                ->imageEditor()
                                                ->maxSize(5120)
                                                ->required()
                                                ->label('الصورة')
                                                ->columnSpan(1),

                                            TextInput::make('sort_order')
                                                ->numeric()
                                                ->default(0)
                                                ->label('الترتيب')
                                                ->columnSpan(1),
                                        ]),
                                ])
                                ->defaultItems(0)
                                ->collapsed()
                                ->itemLabel(fn (array $state): ?string => 'صورة ' . ($state['sort_order'] ?? 'جديدة'))
                                ->label('صور اللون')
                                ->addActionLabel('إضافة صورة')
                                ->reorderable('sort_order')
                                ->orderColumn('sort_order'),
                        ])
                        ->mutateRelationshipDataBeforeCreateUsing(function (array $data): array {
                            $data['type'] = 'color';
                            $data['value_en'] = $data['value_ar'];
                            return $data;
                        })
                        ->mutateRelationshipDataBeforeSaveUsing(function (array $data): array {
                            $data['type'] = 'color';
                            $data['value_en'] = $data['value_ar'];
                            return $data;
                        })
                        ->collapsible()
                        ->collapsed()
                        ->itemLabel(fn (array $state): ?string =>
                            isset($state['value_ar']) ? 'لون: ' . $state['value_ar'] : 'لون جديد'
                        )
                        ->defaultItems(0)
                        ->label('الألوان')
                        ->addActionLabel('إضافة لون'),
                ])
                ->collapsible()
                ->collapsed()
                ->columnSpan(1),

            // Sizes Section
            Section::make('المقاسات المتاحة')
                ->description('أضف المقاسات المتوفرة للمنتج')
                ->icon('heroicon-o-adjustments-horizontal')
                ->schema([
                    Repeater::make('sizes')
                        ->relationship('options', modifyQueryUsing: fn($query) => $query->where('type', 'size'))
                        ->schema([
                            Grid::make(3)
                                ->schema([
                                    TextInput::make('value_ar')
                                        ->required()
                                        ->label('المقاس بالعربية')
                                        ->placeholder('مثال: كبير، وسط، صغير'),

                                    TextInput::make('value_en')
                                        ->required()
                                        ->label('المقاس بالإنجليزية')
                                        ->placeholder('e.g., Large, Medium, Small'),

                                    TextInput::make('sku')
                                        ->maxLength(255)
                                        ->label('SKU')
                                        ->placeholder('مثال: PROD-L-001'),

                                    TextInput::make('quantity')
                                        ->numeric()
                                        ->minValue(0)
                                        ->default(0)
                                        ->label('الكمية')
                                        ->placeholder('0'),
                                ]),

                            Grid::make(3)
                                ->schema([
                                    TextInput::make('price')
                                        ->numeric()
                                        ->minValue(0)
                                        ->prefix('ر.س')
                                        ->label('السعر الإضافي')
                                        ->placeholder('0.00')
                                        ->helperText('اتركه فارغاً إذا كان بنفس السعر الأساسي')
                                        ->reactive(),

                                    TextInput::make('discounted_price')
                                        ->numeric()
                                        ->minValue(0)
                                        ->prefix('ر.س')
                                        ->label('السعر بعد الخصم')
                                        ->placeholder('0.00')
                                        ->helperText('يجب أن يكون أقل من السعر الإضافي')
                                        ->rules([fn ($get) => function ($attribute, $value, $fail) use ($get) {
                                            if ($value !== null && $value !== '' && $get('price') !== null && $get('price') !== '' && floatval($value) >= floatval($get('price'))) {
                                                $fail('السعر بعد الخصم يجب أن يكون أقل من السعر الإضافي.');
                                            }
                                        }])
                                        ->reactive(),

                                    TextInput::make('purchase_price')
                                        ->numeric()
                                        ->minValue(0)
                                        ->prefix('ر.س')
                                        ->label('سعر التكلفة')
                                        ->placeholder('0.00')
                                        ->helperText('يجب أن يكون أقل من السعر بعد الخصم أو الإضافي')
                                        ->rules([fn ($get) => function ($attribute, $value, $fail) use ($get) {
                                            if ($value !== null && $value !== '') {
                                                $discounted = $get('discounted_price');
                                                $base = $get('price');
                                                if ($discounted !== null && $discounted !== '') {
                                                    $comparePrice = floatval($discounted);
                                                } elseif ($base !== null && $base !== '') {
                                                    $comparePrice = floatval($base);
                                                } else {
                                                    return;
                                                }
                                                if (floatval($value) >= $comparePrice) {
                                                    $fail('سعر التكلفة يجب أن يكون أقل من سعر البيع.');
                                                }
                                            }
                                        }]),
                                ]),

                            Repeater::make('images')
                                ->relationship()
                                ->schema([
                                    Grid::make(2)
                                        ->schema([
                                            FileUpload::make('path')
                                                ->image()
                                                ->imageEditor()
                                                ->maxSize(5120)
                                                ->required()
                                                ->label('الصورة')
                                                ->columnSpan(1),

                                            TextInput::make('sort_order')
                                                ->numeric()
                                                ->default(0)
                                                ->label('الترتيب')
                                                ->columnSpan(1),
                                        ]),
                                ])
                                ->defaultItems(0)
                                ->collapsed()
                                ->itemLabel(fn (array $state): ?string => 'صورة ' . ($state['sort_order'] ?? 'جديدة'))
                                ->label('صور المقاس')
                                ->addActionLabel('إضافة صورة')
                                ->reorderable('sort_order')
                                ->orderColumn('sort_order'),
                        ])
                        ->mutateRelationshipDataBeforeCreateUsing(function (array $data): array {
                            $data['type'] = 'size';
                            return $data;
                        })
                        ->mutateRelationshipDataBeforeSaveUsing(function (array $data): array {
                            $data['type'] = 'size';
                            return $data;
                        })
                        ->collapsible()
                        ->collapsed()
                        ->itemLabel(fn (array $state): ?string =>
                            isset($state['value_ar']) ? 'مقاس: ' . $state['value_ar'] : 'مقاس جديد'
                        )
                        ->defaultItems(0)
                        ->label('المقاسات')
                        ->addActionLabel('إضافة مقاس'),
                ])
                ->collapsible()
                ->collapsed()
                ->columnSpan(1),
        ]);
    }
}
