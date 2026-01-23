<?php

namespace App\Filament\Admin\Resources\Services\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ServiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                FileUpload::make('image')
                    ->label('الصورة')
                    ->image(),

                TextInput::make('name_en')
                    ->label('الاسم بالإنجليزية')
                    ->required(),

                TextInput::make('name_ar')
                    ->label('الاسم بالعربية')
                    ->required(),

                Textarea::make('description_en')
                    ->label('الوصف بالإنجليزية')
                    ->columnSpanFull(),

                Textarea::make('description_ar')
                    ->label('الوصف بالعربية')
                    ->columnSpanFull(),

                Toggle::make('is_active')
                    ->label('نشط')
                    ->required(),

                TextInput::make('order')
                    ->label('الترتيب')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }
}
