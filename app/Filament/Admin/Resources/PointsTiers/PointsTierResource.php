<?php

namespace App\Filament\Admin\Resources\PointsTiers;

use App\Models\PointsTier;
use BackedEnum;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PointsTierResource extends Resource
{
    protected static ?string $model = PointsTier::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Gift;

    protected static ?string $navigationLabel = 'شرائح النقاط';

    protected static ?string $pluralLabel = 'شرائح النقاط';

    protected static ?string $label = 'شريحة نقاط';

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): ?string
    {
        return 'الإعدادات والنظام';
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('settings.show');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->can('settings.update');
    }

    public static function canEdit($record): bool
    {
        return auth()->user()->can('settings.update');
    }

    public static function canDelete($record): bool
    {
        return auth()->user()->can('settings.update');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('min_amount')
                    ->label('الحد الأدنى للفاتورة (ر.س)')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->prefix('ر.س')
                    ->helperText('أقل مبلغ للفاتورة في هذه الشريحة')
                    ->placeholder('0.00'),
                TextInput::make('max_amount')
                    ->label('الحد الأعلى للفاتورة (ر.س)')
                    ->numeric()
                    ->minValue(0)
                    ->prefix('ر.س')
                    ->helperText('أعلى مبلغ للفاتورة. اتركه فارغ للشريحة بدون حد أعلى (مثال: أكثر من 3000)')
                    ->placeholder('فارغ = بدون حد'),
                TextInput::make('points_awarded')
                    ->label('عدد النقاط الممنوحة')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->suffix('نقطة')
                    ->helperText('عدد النقاط التي يحصل عليها العميل عند الشراء بهذه الشريحة')
                    ->placeholder('0'),
                Toggle::make('is_active')
                    ->label('مفعّل')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('min_amount', 'asc')
            ->columns([
                TextColumn::make('min_amount')
                    ->label('من (ر.س)')
                    ->money('SAR')
                    ->sortable(),
                TextColumn::make('max_amount')
                    ->label('إلى (ر.س)')
                    ->money('SAR')
                    ->placeholder('بدون حد ∞')
                    ->sortable(),
                TextColumn::make('points_awarded')
                    ->label('النقاط الممنوحة')
                    ->suffix(' نقطة')
                    ->color('success')
                    ->weight('bold')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('مفعّل')
                    ->boolean(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManagePointsTiers::route('/'),
        ];
    }
}
