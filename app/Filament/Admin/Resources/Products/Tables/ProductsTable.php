<?php

namespace App\Filament\Admin\Resources\Products\Tables;

use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('main_image')
                    ->circular()
                    ->label('الصورة'),

                TextColumn::make('name_ar')
                    ->searchable()
                    ->sortable()
                    ->label('الاسم بالعربية'),

                TextColumn::make('categories.name_ar')
                    ->badge()
                    ->separator(',')
                    ->searchable()
                    ->label('التصنيفات'),

                TextColumn::make('main_price')
                    ->money('SAR')
                    ->sortable()
                    ->label('السعر الأساسي'),

                TextColumn::make('discounted_price')
                    ->money('SAR')
                    ->sortable()
                    ->label('السعر المخفض'),

                TextColumn::make('purchase_price')
                    ->money('SAR')
                    ->sortable()
                    ->label('سعر التكلفة')
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('is_new')
                    ->boolean()
                    ->getStateUsing(fn ($record) => $record->is_new)
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-mark')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->label('هل هو جديد؟'),

                IconColumn::make('is_new_arrival')
                    ->boolean()
                    ->getStateUsing(fn ($record) => $record->is_new_arrival)
                    ->trueIcon('heroicon-o-sparkles')
                    ->falseIcon('heroicon-o-x-mark')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->label('جديد الوصول'),

                IconColumn::make('is_featured')
                    ->boolean()
                    ->getStateUsing(fn ($record) => $record->is_featured)
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon('heroicon-o-x-mark')
                    ->trueColor('warning')
                    ->falseColor('gray')
                    ->label('مميز'),

                TextColumn::make('final_price')
                    ->money('SAR')
                    ->getStateUsing(fn ($record) => $record->getFinalPrice())
                    ->label('السعر النهائي')
                    ->color(fn ($record) => $record->getBestOffer() ? 'success' : 'gray'),

                TextColumn::make('stock_status')
                    ->badge()
                    ->formatStateUsing(fn ($record): string => match ($record->stock_status) {
                        'in_stock' => 'متوفر',
                        'out_of_stock' => 'غير متوفر',
                        'limited' => 'كمية محدودة',
                        default => $record->stock_status,
                    })
                    ->color(fn ($record): string => match ($record->stock_status) {
                        'in_stock' => 'success',
                        'out_of_stock' => 'danger',
                        'limited' => 'warning',
                        default => 'gray',
                    })
                    ->label('حالة المخزون'),

                TextColumn::make('quantity')
                    ->sortable()
                    ->label('الكمية'),

                TextColumn::make('options_count')
                    ->counts('options')
                    ->label('عدد الخيارات'),

                TextColumn::make('reviews_count')
                    ->counts('reviews')
                    ->label('عدد التقييمات')
                    ->sortable(),

                TextColumn::make('average_rating')
                    ->label('متوسط التقييم')
                    ->getStateUsing(function ($record) {
                        $avgRating = $record->reviews_avg_rating ?? 0;
                        return $avgRating ? number_format($avgRating, 1) : '0.0';
                    })
                    ->badge()
                    ->color(function ($record) {
                        $avgRating = $record->reviews_avg_rating ?? 0;
                        if (!$avgRating) return 'gray';
                        return match (true) {
                            $avgRating >= 4 => 'success',
                            $avgRating >= 3 => 'warning',
                            default => 'danger',
                        };
                    })
                    ->icon(function ($record) {
                        $avgRating = $record->reviews_avg_rating ?? 0;
                        return $avgRating && $avgRating >= 4 ? 'heroicon-o-star' : null;
                    })
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('reviews_avg_rating', $direction);
                    }),

                TextColumn::make('sold_quantity')
                    ->label('الكمية المباعة')
                    ->getStateUsing(function ($record) {
                        // Use cached sum if available
                        return $record->sold_quantity_sum ?? 0;
                    })
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('sold_quantity_sum', $direction);
                    }),

                TextColumn::make('total_revenue')
                    ->label('إجمالي الإيرادات')
                    ->money('SAR')
                    ->getStateUsing(function ($record) {
                        return $record->total_revenue_sum ?? 0;
                    })
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('total_revenue_sum', $direction);
                    })
                    ->color('success'),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label('تاريخ الإنشاء')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('categories')
                    ->relationship('categories', 'name_ar')
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->label('التصنيفات'),

                SelectFilter::make('stock_status')
                    ->options([
                        'in_stock' => 'متوفر',
                        'out_of_stock' => 'غير متوفر',
                        'limited' => 'كمية محدودة',
                    ])
                    ->query(function (Builder $query, array $data) {
                        $value = $data['value'] ?? null;
                        if (!$value) {
                            return $query;
                        }

                        return match ($value) {
                            'in_stock' => $query->where('quantity', '>', 10),
                            'limited' => $query->whereBetween('quantity', [1, 10]),
                            'out_of_stock' => $query->where('quantity', '<=', 0),
                            default => $query,
                        };
                    })
                    ->label('حالة المخزون'),

                SelectFilter::make('is_new')
                    ->options([
                        true => 'نعم',
                        false => 'لا',
                    ])
                    ->label('هل هو جديد؟'),

                SelectFilter::make('is_new_arrival')
                    ->options([
                        true => 'نعم',
                        false => 'لا',
                    ])
                    ->label('جديد الوصول'),

                SelectFilter::make('is_featured')
                    ->options([
                        true => 'نعم',
                        false => 'لا',
                    ])
                    ->label('مميز'),

                Filter::make('has_offer')
                    ->query(fn (Builder $query): Builder => $query->whereHas('offers'))
                    ->label('يوجد عرض'),
            ])
            ->recordActions([
                ViewAction::make()
                    ->visible(fn () => auth()->user()->can('products.show')),
                EditAction::make()
                    ->visible(fn () => auth()->user()->can('products.update')),
                DeleteAction::make()
                    ->visible(fn () => auth()->user()->can('products.delete'))
                    ->requiresConfirmation(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()->can('products.delete')),
                    BulkAction::make('markAsNew')
                        ->label('تعيين كمنتج جديد')
                        ->icon('heroicon-o-sparkles')
                        ->color('success')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->update(['is_new' => 1]);
                            }
                        })
                        ->requiresConfirmation(),

                    // 👇 Bulk Action لتعيين is_new = 0
                    BulkAction::make('markAsOld')
                        ->label('إلغاء كونها جديدة')
                        ->icon('heroicon-o-x-circle')
                        ->color('gray')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->update(['is_new' => 0]);
                            }
                        })
                        ->requiresConfirmation(),

                    BulkAction::make('markAsNewArrival')
                        ->label('تعيين كمنتج جديد الوصول')
                        ->icon('heroicon-o-sparkles')
                        ->color('success')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->update(['is_new_arrival' => 1]);
                            }
                        })
                        ->requiresConfirmation(),

                    BulkAction::make('unmarkAsNewArrival')
                        ->label('إلغاء كونها جديد الوصول')
                        ->icon('heroicon-o-x-circle')
                        ->color('gray')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->update(['is_new_arrival' => 0]);
                            }
                        })
                        ->requiresConfirmation(),

                    BulkAction::make('markAsFeatured')
                        ->label('تعيين كمنتج مميز')
                        ->icon('heroicon-o-star')
                        ->color('warning')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->update(['is_featured' => 1]);
                            }
                        })
                        ->requiresConfirmation(),

                    BulkAction::make('unmarkAsFeatured')
                        ->label('إلغاء كونها مميز')
                        ->icon('heroicon-o-x-circle')
                        ->color('gray')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->update(['is_featured' => 0]);
                            }
                        })
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
