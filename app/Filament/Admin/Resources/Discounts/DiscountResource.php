<?php

namespace App\Filament\Admin\Resources\Discounts;

use App\Filament\Admin\Resources\Discounts\Pages\CreateDiscount;
use App\Filament\Admin\Resources\Discounts\Pages\EditDiscount;
use App\Filament\Admin\Resources\Discounts\Pages\ListDiscounts;
use App\Filament\Admin\Resources\Discounts\Pages\ViewDiscount;
use App\Filament\Admin\Resources\Discounts\Schemas\DiscountForm;
use App\Filament\Admin\Resources\Discounts\Tables\DiscountsTable;
use App\Models\Discount;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class DiscountResource extends Resource
{
    protected static ?string $model = Discount::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Tag;

    protected static ?string $navigationLabel = 'الخصومات';

    protected static ?string $pluralLabel = 'الخصومات';

    protected static ?string $label = 'خصم';

    protected static ?string $recordTitleAttribute = 'code';

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): ?string
    {
        return 'إدارة المتجر';
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('discounts.show');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->can('discounts.create');
    }

    public static function canEdit($record): bool
    {
        return auth()->user()->can('discounts.update');
    }

    public static function canDelete($record): bool
    {
        return auth()->user()->can('discounts.delete');
    }

    public static function form(Schema $schema): Schema
    {
        return DiscountForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DiscountsTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Schemas\Components\Section::make('معلومات الخصم')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('code')
                            ->label('كود الخصم'),
                        \Filament\Infolists\Components\IconEntry::make('status')
                            ->label('الحالة')
                            ->boolean(),
                        \Filament\Infolists\Components\TextEntry::make('type')
                            ->label('نوع الخصم')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'percentage' => 'success',
                                'fixed' => 'info',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'percentage' => 'نسبة مئوية',
                                'fixed' => 'مبلغ ثابت',
                                default => $state,
                            }),
                        \Filament\Infolists\Components\TextEntry::make('value')
                            ->label('قيمة الخصم')
                            ->formatStateUsing(fn ($state, $record) => $state . ($record->type === 'percentage' ? '%' : ' ر.س')),
                        \Filament\Infolists\Components\TextEntry::make('start')
                            ->label('تاريخ البداية')
                            ->dateTime('d/m/Y H:i'),
                        \Filament\Infolists\Components\TextEntry::make('end')
                            ->label('تاريخ النهاية')
                            ->dateTime('d/m/Y H:i'),
                    ])
                    ->columns(3),
                \Filament\Schemas\Components\Section::make('الوصف')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('description_en')
                            ->label('الوصف بالإنجليزية')
                            ->wrap(),
                        \Filament\Infolists\Components\TextEntry::make('description_ar')
                            ->label('الوصف بالعربية')
                            ->wrap(),
                    ])
                    ->columns(2),
                \Filament\Schemas\Components\Section::make('الشروط')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('condition')
                            ->label('الشرط')
                            ->formatStateUsing(function ($state) {
                                if (!$state || !is_array($state) || !isset($state['type'])) {
                                    return 'بدون شرط';
                                }

                                $type = $state['type'];
                                $typeLabel = Discount::getConditionTypes()[$type] ?? $type;

                                if (isset($state['value'])) {
                                    if ($type === Discount::CONDITION_MIN_AMOUNT) {
                                        return $typeLabel . ': ' . $state['value'] . ' ر.س';
                                    } elseif ($type === Discount::CONDITION_MIN_QUANTITY) {
                                        return $typeLabel . ': ' . $state['value'];
                                    }
                                }

                                return $typeLabel;
                            }),
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
            'index' => ListDiscounts::route('/'),
            'create' => CreateDiscount::route('/create'),
            'edit' => EditDiscount::route('/{record}/edit'),
            'view' => ViewDiscount::route('/{record}'),
        ];
    }
}
