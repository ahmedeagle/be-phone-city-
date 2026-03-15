<?php

namespace App\Filament\Admin\Resources\Reviews;

use App\Filament\Admin\Resources\Reviews\Pages\ListReviews;
use App\Filament\Admin\Resources\Reviews\Pages\ViewReview;
use App\Models\Review;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

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

    /**
     * Show pending badge count on nav item.
     */
    public static function getNavigationBadge(): ?string
    {
        $count = Review::pending()->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
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
                                default     => 'danger',
                            }),

                        TextEntry::make('status')
                            ->label('الحالة')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                Review::STATUS_APPROVED => 'success',
                                Review::STATUS_REJECTED => 'danger',
                                default                 => 'warning',
                            })
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                Review::STATUS_APPROVED => 'مقبول',
                                Review::STATUS_REJECTED => 'مرفوض',
                                default                 => 'قيد المراجعة',
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
                        default     => 'danger',
                    }),

                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->sortable()
                    ->color(fn (string $state): string => match ($state) {
                        Review::STATUS_APPROVED => 'success',
                        Review::STATUS_REJECTED => 'danger',
                        default                 => 'warning',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        Review::STATUS_APPROVED => 'مقبول',
                        Review::STATUS_REJECTED => 'مرفوض',
                        default                 => 'قيد المراجعة',
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
                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        Review::STATUS_PENDING  => 'قيد المراجعة',
                        Review::STATUS_APPROVED => 'مقبول',
                        Review::STATUS_REJECTED => 'مرفوض',
                    ])
                    ->default(Review::STATUS_PENDING),

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

                Action::make('approve')
                    ->label('قبول')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('قبول التقييم')
                    ->modalDescription('هل تريد قبول هذا التقييم ونشره للعامة؟')
                    ->modalSubmitActionLabel('قبول')
                    ->visible(fn (Review $record): bool => ! $record->isApproved())
                    ->action(function (Review $record): void {
                        $record->approve();
                        Notification::make()
                            ->title('تم قبول التقييم ونشره')
                            ->success()
                            ->send();
                    }),

                Action::make('reject')
                    ->label('رفض')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('رفض التقييم')
                    ->modalDescription('هل تريد رفض هذا التقييم وعدم نشره؟')
                    ->modalSubmitActionLabel('رفض')
                    ->visible(fn (Review $record): bool => ! $record->isRejected())
                    ->action(function (Review $record): void {
                        $record->reject();
                        Notification::make()
                            ->title('تم رفض التقييم')
                            ->danger()
                            ->send();
                    }),

                DeleteAction::make()
                    ->requiresConfirmation(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('approve_selected')
                        ->label('قبول المحدد')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('قبول التقييمات المحددة')
                        ->modalDescription('سيتم نشر جميع التقييمات المحددة للعامة.')
                        ->modalSubmitActionLabel('قبول الكل')
                        ->action(function (Collection $records): void {
                            $records->each->approve();
                            Notification::make()
                                ->title('تم قبول ' . $records->count() . ' تقييم')
                                ->success()
                                ->send();
                        }),

                    BulkAction::make('reject_selected')
                        ->label('رفض المحدد')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('رفض التقييمات المحددة')
                        ->modalDescription('سيتم رفض جميع التقييمات المحددة وعدم نشرها.')
                        ->modalSubmitActionLabel('رفض الكل')
                        ->action(function (Collection $records): void {
                            $records->each->reject();
                            Notification::make()
                                ->title('تم رفض ' . $records->count() . ' تقييم')
                                ->danger()
                                ->send();
                        }),

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
            'view'  => ViewReview::route('/{record}'),
        ];
    }
}