<?php

namespace App\Filament\Admin\Resources\StoreFeatures\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class StoreFeatureForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('معلومات الميزة')
                    ->schema([
                        TextInput::make('name_en')
                            ->label('الاسم بالإنجليزية')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('name_ar')
                            ->label('الاسم بالعربية')
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
                            ->directory('store-features')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}

