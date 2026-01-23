<?php

namespace App\Filament\Admin\Resources\Sliders\Schemas;

use App\Models\Category;
use App\Models\Offer;
use App\Models\Page;
use App\Models\Product;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SliderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('معلومات الشريحة')
                    ->schema([
                        TextInput::make('title_en')
                            ->label('العنوان بالإنجليزية')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('title_ar')
                            ->label('العنوان بالعربية')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->columns(2),
                Section::make('الوصف')
                    ->schema([
                        Textarea::make('description_en')
                            ->label('الوصف بالإنجليزية')
                            ->rows(4)
                            ->columnSpanFull(),
                        Textarea::make('description_ar')
                            ->label('الوصف بالعربية')
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),
                Section::make('الصورة')
                    ->schema([
                        FileUpload::make('image')
                            ->label('الصورة')
                            ->image()
                            ->imageEditor()
                            ->directory('sliders')
                            ->columnSpanFull(),
                    ]),
                Section::make('زر الإجراء')
                    ->schema([
                        Toggle::make('have_button')
                            ->label('إظهار زر')
                            ->default(false)
                            ->reactive()
                            ->helperText('تفعيل زر في الشريحة'),
                        Select::make('type')
                            ->label('نوع الصفحة')
                            ->options([
                                'page' => 'صفحة',
                                'offer' => 'عرض',
                                'product' => 'منتج',
                                'category' => 'تصنيف',
                            ])
                            ->nullable()
                            ->visible(fn ($get) => $get('have_button') === true)
                            ->reactive()
                            ->native(false)
                            ->required(fn ($get) => $get('have_button') === true)
                            ->afterStateUpdated(fn ($set) => $set('url_slug', null)),
                        Select::make('url_slug')
                            ->label('اختر العنصر')
                            ->options(function ($get) {
                                $type = $get('type');

                                if (!$type) {
                                    return [];
                                }

                                return match ($type) {
                                    'page' => Page::query()
                                        ->orderBy('name_en')
                                        ->get()
                                        ->mapWithKeys(fn ($page) => [$page->slug => $page->name]),
                                    'offer' => Offer::query()
                                        ->orderBy('name_en')
                                        ->get()
                                        ->mapWithKeys(fn ($offer) => [$offer->slug => $offer->name]),
                                    'product' => Product::query()
                                        ->orderBy('name_en')
                                        ->get()
                                        ->mapWithKeys(fn ($product) => [$product->slug => $product->name]),
                                    'category' => Category::query()
                                        ->orderBy('name_en')
                                        ->get()
                                        ->mapWithKeys(fn ($category) => [$category->slug => $category->name]),
                                    default => [],
                                };
                            })
                            ->nullable()
                            ->visible(fn ($get) => $get('have_button') === true && !empty($get('type')))
                            ->required(fn ($get) => $get('have_button') === true && !empty($get('type')))
                            ->searchable()
                            ->preload(),
                        TextInput::make('button_text_en')
                            ->label('نص الزر بالإنجليزية')
                            ->maxLength(255)
                            ->visible(fn ($get) => $get('have_button') === true)
                            ->required(fn ($get) => $get('have_button') === true),
                        TextInput::make('button_text_ar')
                            ->label('نص الزر بالعربية')
                            ->maxLength(255)
                            ->visible(fn ($get) => $get('have_button') === true)
                            ->required(fn ($get) => $get('have_button') === true),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}
