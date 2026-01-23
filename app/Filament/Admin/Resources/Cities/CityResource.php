<?php

namespace App\Filament\Admin\Resources\Cities;

use App\Filament\Admin\Resources\Cities\Pages\CreateCity;
use App\Filament\Admin\Resources\Cities\Pages\EditCity;
use App\Filament\Admin\Resources\Cities\Pages\ListCities;
use App\Filament\Admin\Resources\Cities\Pages\ViewCity;
use App\Filament\Admin\Resources\Cities\Schemas\CityForm;
use App\Filament\Admin\Resources\Cities\Schemas\CityInfolist;
use App\Filament\Admin\Resources\Cities\Tables\CitiesTable;
use App\Models\City;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CityResource extends Resource
{
    protected static ?string $model = City::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::MapPin;

    protected static ?string $navigationLabel = 'المدن';

    protected static ?string $pluralLabel = 'المدن';

    protected static ?string $label = 'مدينة';

    protected static ?int $navigationSort = 5;

    public static function getNavigationGroup(): ?string
    {
        return 'الإعدادات والنظام';
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('cities.show');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->can('cities.create');
    }

    public static function canEdit($record): bool
    {
        return auth()->user()->can('cities.update');
    }

    public static function canDelete($record): bool
    {
        return auth()->user()->can('cities.delete');
    }

    public static function form(Schema $schema): Schema
    {
        return CityForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CityInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CitiesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCities::route('/'),
            'create' => CreateCity::route('/create'),
            'edit' => EditCity::route('/{record}/edit'),
            'view' => ViewCity::route('/{record}'),
        ];
    }
}
