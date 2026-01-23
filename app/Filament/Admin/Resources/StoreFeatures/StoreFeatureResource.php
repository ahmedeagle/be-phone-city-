<?php

namespace App\Filament\Admin\Resources\StoreFeatures;

use App\Filament\Admin\Resources\StoreFeatures\Pages\CreateStoreFeature;
use App\Filament\Admin\Resources\StoreFeatures\Pages\EditStoreFeature;
use App\Filament\Admin\Resources\StoreFeatures\Pages\ListStoreFeatures;
use App\Filament\Admin\Resources\StoreFeatures\Pages\ViewStoreFeature;
use App\Filament\Admin\Resources\StoreFeatures\Schemas\StoreFeatureForm;
use App\Filament\Admin\Resources\StoreFeatures\Tables\StoreFeaturesTable;
use App\Models\StoreFeature;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class StoreFeatureResource extends Resource
{
    protected static ?string $model = StoreFeature::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Sparkles;

    protected static ?string $navigationLabel = 'مميزات المتجر';

    protected static ?string $pluralLabel = 'مميزات المتجر';

    protected static ?string $label = 'ميزة المتجر';

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): ?string
    {
        return 'المحتوى التسويقي';
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('store_features.show');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->can('store_features.create');
    }

    public static function canEdit($record): bool
    {
        return auth()->user()->can('store_features.update');
    }

    public static function canDelete($record): bool
    {
        return auth()->user()->can('store_features.delete');
    }

    public static function form(Schema $schema): Schema
    {
        return StoreFeatureForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StoreFeaturesTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Schemas\Components\Section::make('معلومات الميزة')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('name')
                            ->label('الاسم'),
                        \Filament\Infolists\Components\TextEntry::make('name_en')
                            ->label('الاسم بالإنجليزية'),
                        \Filament\Infolists\Components\TextEntry::make('name_ar')
                            ->label('الاسم بالعربية'),
                    ])
                    ->columns(3),
                \Filament\Schemas\Components\Section::make('الوصف')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('description')
                            ->label('الوصف')
                            ->wrap(),
                        \Filament\Infolists\Components\TextEntry::make('description_en')
                            ->label('الوصف بالإنجليزية')
                            ->wrap(),
                        \Filament\Infolists\Components\TextEntry::make('description_ar')
                            ->label('الوصف بالعربية')
                            ->wrap(),
                    ])
                    ->columns(3),
                \Filament\Schemas\Components\Section::make('الصورة')
                    ->schema([
                        \Filament\Infolists\Components\ImageEntry::make('image')
                            ->label('الصورة')
                            ->height(200),
                    ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStoreFeatures::route('/'),
            'create' => CreateStoreFeature::route('/create'),
            'edit' => EditStoreFeature::route('/{record}/edit'),
            'view' => ViewStoreFeature::route('/{record}'),
        ];
    }
}

