<?php

namespace App\Filament\Admin\Resources\CustomerOpinions\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CustomerOpinionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('معلومات العميل')
                    ->schema([
                        TextInput::make('name_en')
                            ->label('الاسم بالإنجليزية')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('name_ar')
                            ->label('الاسم بالعربية')
                            ->required()
                            ->maxLength(255),
                        Select::make('rate')
                            ->label('التقييم')
                            ->options([
                                1 => '⭐ (1/5)',
                                2 => '⭐⭐ (2/5)',
                                3 => '⭐⭐⭐ (3/5)',
                                4 => '⭐⭐⭐⭐ (4/5)',
                                5 => '⭐⭐⭐⭐⭐ (5/5)',
                            ])
                            ->required()
                            ->default(5)
                            ->native(false),
                    ])
                    ->columns(3),
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
                            ->label('صورة العميل')
                            ->image()
                            ->directory('customer-opinions')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}

