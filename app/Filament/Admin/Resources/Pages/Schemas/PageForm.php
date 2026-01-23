<?php

namespace App\Filament\Admin\Resources\Pages\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('banner'),
                TextInput::make('slug')
                    ->required(),
                TextInput::make('title_en'),
                TextInput::make('title_ar'),
                Textarea::make('short_description_en')
                    ->columnSpanFull(),
                Textarea::make('short_description_ar')
                    ->columnSpanFull(),
                Textarea::make('description_en')
                    ->columnSpanFull(),
                Textarea::make('description_ar')
                    ->columnSpanFull(),
                TextInput::make('order')
                    ->required()
                    ->numeric()
                    ->default(0),
                Textarea::make('meta_description_en')
                    ->columnSpanFull(),
                Textarea::make('meta_description_ar')
                    ->columnSpanFull(),
                Textarea::make('meta_keywords_en')
                    ->columnSpanFull(),
                Textarea::make('meta_keywords_ar')
                    ->columnSpanFull(),
                Toggle::make('is_active')
                    ->required(),
                Toggle::make('can_delete')
                    ->required(),
            ]);
    }
}
