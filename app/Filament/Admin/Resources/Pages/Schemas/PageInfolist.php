<?php

namespace App\Filament\Admin\Resources\Pages\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class PageInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('banner')
                    ->placeholder('-'),
                TextEntry::make('slug'),
                TextEntry::make('title_en')
                    ->placeholder('-'),
                TextEntry::make('title_ar')
                    ->placeholder('-'),
                TextEntry::make('short_description_en')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('short_description_ar')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('description_en')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('description_ar')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('order')
                    ->numeric(),
                TextEntry::make('meta_description_en')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('meta_description_ar')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('meta_keywords_en')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('meta_keywords_ar')
                    ->placeholder('-')
                    ->columnSpanFull(),
                IconEntry::make('is_active')
                    ->boolean(),
                IconEntry::make('can_delete')
                    ->boolean(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
