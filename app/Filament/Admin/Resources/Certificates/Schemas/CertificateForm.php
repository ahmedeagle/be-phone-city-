<?php

namespace App\Filament\Admin\Resources\Certificates\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CertificateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('معلومات الشهادة')
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
                Section::make('الصور')
                    ->schema([
                        FileUpload::make('image')
                            ->label('الصورة')
                            ->image()
                            ->directory('certificates')
                            ->columnSpanFull(),
                        FileUpload::make('main_image')
                            ->label('الصورة الرئيسية')
                            ->image()
                            ->directory('certificates')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}

