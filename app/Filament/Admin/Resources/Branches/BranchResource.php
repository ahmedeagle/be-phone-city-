<?php

namespace App\Filament\Admin\Resources\Branches;

use App\Filament\Admin\Resources\Branches\Pages\CreateBranch;
use App\Filament\Admin\Resources\Branches\Pages\EditBranch;
use App\Filament\Admin\Resources\Branches\Pages\ListBranches;
use App\Filament\Admin\Resources\Branches\Pages\ViewBranch;
use App\Filament\Admin\Resources\Branches\Schemas\BranchForm;
use App\Filament\Admin\Resources\Branches\Schemas\BranchInfolist;
use App\Filament\Admin\Resources\Branches\Tables\BranchesTable;
use App\Models\Branch;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BranchResource extends Resource
{
    protected static ?string $model = Branch::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::BuildingStorefront;

    protected static ?string $navigationLabel = 'الفروع';

    protected static ?string $pluralLabel = 'الفروع';

    protected static ?string $label = 'فرع';

    protected static ?string $recordTitleAttribute = 'name_ar';

    protected static ?int $navigationSort = 6;

    public static function getNavigationGroup(): ?string
    {
        return 'الإعدادات والنظام';
    }

    public static function canViewAny(): bool
    {
        return true;
    }

    public static function canCreate(): bool
    {
        return true;
    }

    public static function canEdit($record): bool
    {
        return true;
    }

    public static function canDelete($record): bool
    {
        return true;
    }

    public static function form(Schema $schema): Schema
    {
        return BranchForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return BranchInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BranchesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBranches::route('/'),
            'create' => CreateBranch::route('/create'),
            'edit' => EditBranch::route('/{record}/edit'),
            'view' => ViewBranch::route('/{record}'),
        ];
    }
}
