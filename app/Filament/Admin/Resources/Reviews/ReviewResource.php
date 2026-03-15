<?php

namespace App\Filament\Admin\Resources\Reviews;

use App\Filament\Admin\Resources\Reviews\Pages\ListReviews;
use App\Filament\Admin\Resources\Reviews\Pages\ViewReview;
use App\Models\Review;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ReviewResource extends Resource
{
    protected static ?string $model = Review::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Star;

    protected static ?string $navigationLabel = 'التقييمات';

    protected static ?string $pluralLabel = 'التقييمات';

    protected static ?string $label = 'تقييم';

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): ?string
    {
        return 'إدارة المتجر';
    }

    public static function canViewAny(): bool
    {
        return auth('admin')->check();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return auth('admin')->check();
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('تفاصيل التقييم')
                    ->icon('heroicon-o-star')
                    ->schema([
                        TextEntry::make('user.name')
                            ->label('العميل'),

                        TextEntry::make('product.name_ar')
                            ->label('المنتج'),

                        TextEntry::make('rating')
                            ->label('التقييم')
                            ->badge()
                            ->color(fn ($state) => match (true) {
                                $state >= 4 => 'success',
                                $state >= 3 => 'warning',
                                default => 'danger',
                            }),

                        TextEntry::make('comment')
                            ->label('التعليق')
                            ->columnSpanFull(),

                        TextEntry::make('created_at')
                            ->label('تاريخ التقييم')
                            ->dateTime(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->searchable()
                    ->sortable()
                    ->label('العميل'),

                TextColumn::make('product.name_ar')
                    ->searchable()
                    ->sortable()
                    ->label('المنتج')
                    ->limit(30),

                TextColumn::make('rating')
                    ->sortable()
                    ->label('التقييم')
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state >= 4 => 'success',
                        $state >= 3 => 'warning',
                        default => 'danger',
                    }),

                TextColumn::make('comment')
                    ->label('التعليق')
                    ->limit(50)
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label('التاريخ'),
            ])
            ->filters([
                SelectFilter::make('rating')
                    ->label('التقييم')
                    ->options([
                        '1' => '1 نجمة',
                        '2' => '2 نجمة',
                        '3' => '3 نجوم',
                        '4' => '4 نجوم',
                        '5' => '5 نجوم',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                DeleteAction::make()
                    ->requiresConfirmation(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReviews::route('/'),
            'view' => ViewReview::route('/{record}'),
        ];
    }
}
