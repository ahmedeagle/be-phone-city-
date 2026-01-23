<?php

namespace App\Filament\Admin\Resources\CustomerOpinions;

use App\Filament\Admin\Resources\CustomerOpinions\Pages\CreateCustomerOpinion;
use App\Filament\Admin\Resources\CustomerOpinions\Pages\EditCustomerOpinion;
use App\Filament\Admin\Resources\CustomerOpinions\Pages\ListCustomerOpinions;
use App\Filament\Admin\Resources\CustomerOpinions\Pages\ViewCustomerOpinion;
use App\Filament\Admin\Resources\CustomerOpinions\Schemas\CustomerOpinionForm;
use App\Filament\Admin\Resources\CustomerOpinions\Tables\CustomerOpinionsTable;
use App\Models\CustomerOpinion;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CustomerOpinionResource extends Resource
{
    protected static ?string $model = CustomerOpinion::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ChatBubbleLeftRight;

    protected static ?string $navigationLabel = 'آراء العملاء';

    protected static ?string $pluralLabel = 'آراء العملاء';

    protected static ?string $label = 'رأي العميل';

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return 'المحتوى التسويقي';
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('customer_opinions.show');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->can('customer_opinions.create');
    }

    public static function canEdit($record): bool
    {
        return auth()->user()->can('customer_opinions.update');
    }

    public static function canDelete($record): bool
    {
        return auth()->user()->can('customer_opinions.delete');
    }

    public static function form(Schema $schema): Schema
    {
        return CustomerOpinionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CustomerOpinionsTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Schemas\Components\Section::make('معلومات العميل')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('name')
                            ->label('الاسم'),
                        \Filament\Infolists\Components\TextEntry::make('name_en')
                            ->label('الاسم بالإنجليزية'),
                        \Filament\Infolists\Components\TextEntry::make('name_ar')
                            ->label('الاسم بالعربية'),
                        \Filament\Infolists\Components\TextEntry::make('rate')
                            ->label('التقييم')
                            ->formatStateUsing(fn (int $state): string => str_repeat('⭐', $state) . " ({$state}/5)")
                            ->color(fn (int $state): string => match (true) {
                                $state >= 5 => 'success',
                                $state >= 4 => 'warning',
                                default => 'gray',
                            }),
                    ])
                    ->columns(2),
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
                            ->label('صورة العميل')
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
            'index' => ListCustomerOpinions::route('/'),
            'create' => CreateCustomerOpinion::route('/create'),
            'edit' => EditCustomerOpinion::route('/{record}/edit'),
            'view' => ViewCustomerOpinion::route('/{record}'),
        ];
    }
}

