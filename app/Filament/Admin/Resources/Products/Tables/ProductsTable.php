<?php

namespace App\Filament\Admin\Resources\Products\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
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
                    ->label('التصنيفات')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('main_price')
                    ->money('SAR')
                    ->sortable()
                    ->label('السعر الأساسي'),

                TextColumn::make('discounted_price')
                    ->money('SAR')
                    ->sortable()
                    ->label('السعر المخفض')
                    ->toggleable(isToggledHiddenByDefault: true),

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
                    ->label('هل هو جديد؟')
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('is_new_arrival')
                    ->boolean()
                    ->getStateUsing(fn ($record) => $record->is_new_arrival)
                    ->trueIcon('heroicon-o-sparkles')
                    ->falseIcon('heroicon-o-x-mark')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->label('جديد الوصول')
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('is_featured')
                    ->boolean()
                    ->getStateUsing(fn ($record) => $record->is_featured)
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon('heroicon-o-x-mark')
                    ->trueColor('warning')
                    ->falseColor('gray')
                    ->label('مميز')
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('is_installment')
                    ->boolean()
                    ->getStateUsing(fn ($record) => $record->is_installment)
                    ->trueIcon('heroicon-o-credit-card')
                    ->falseIcon('heroicon-o-x-mark')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->label('تقسيط')
                    ->toggleable(isToggledHiddenByDefault: false),

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
                    ->label('عدد الخيارات')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('reviews_count')
                    ->counts('reviews')
                    ->label('عدد التقييمات')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

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
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('sold_quantity')
                    ->label('الكمية المباعة')
                    ->getStateUsing(function ($record) {
                        // Use cached sum if available
                        return $record->sold_quantity_sum ?? 0;
                    })
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('sold_quantity_sum', $direction);
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('total_revenue')
                    ->label('إجمالي الإيرادات')
                    ->money('SAR')
                    ->getStateUsing(function ($record) {
                        return $record->total_revenue_sum ?? 0;
                    })
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('total_revenue_sum', $direction);
                    })
                    ->color('success')
                    ->toggleable(isToggledHiddenByDefault: true),

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
                Action::make('duplicate')
                    ->label('نسخ')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('نسخ المنتج')
                    ->modalDescription('سيتم إنشاء نسخة من المنتج وفتحها للتعديل قبل الحفظ.')
                    ->modalSubmitActionLabel('نسخ وفتح للتعديل')
                    ->visible(fn () => auth()->user()->can('products.create'))
                    ->action(function ($record) {
                        $newProduct = \Illuminate\Support\Facades\DB::transaction(function () use ($record) {
                            // Reload a fresh copy from DB to drop any aggregate columns
                            // (reviews_count, reviews_avg_rating, sold_quantity_sum, total_revenue_sum)
                            // injected by the table query (withCount/withSum).
                            $fresh = $record->newQueryWithoutScopes()->find($record->getKey());

                            // Replicate base product attributes
                            $copy = $fresh->replicate(['slug']);

                            // Suffix names to avoid duplicates
                            if (! empty($copy->name_ar)) {
                                $copy->name_ar = $copy->name_ar . ' (نسخة)';
                            }
                            if (! empty($copy->name_en)) {
                                $copy->name_en = $copy->name_en . ' (Copy)';
                            }

                            // Regenerate unique slug if column exists
                            if (\Illuminate\Support\Facades\Schema::hasColumn($copy->getTable(), 'slug')) {
                                $base = \Illuminate\Support\Str::slug($copy->name_en ?? $copy->name_ar ?? 'product') . '-' . \Illuminate\Support\Str::random(6);
                                $copy->slug = $base;
                            }

                            // Strip any aggregate / non-column attributes that may have been
                            // hydrated onto the model via withCount/withSum/withAvg.
                            $tableColumns = \Illuminate\Support\Facades\Schema::getColumnListing($copy->getTable());
                            foreach (array_keys($copy->getAttributes()) as $attr) {
                                if (! in_array($attr, $tableColumns, true)) {
                                    unset($copy->{$attr});
                                }
                            }

                            // Reset stats / unique fields where present
                            foreach (['views_count', 'sold_quantity'] as $col) {
                                if (in_array($col, $tableColumns, true)) {
                                    $copy->{$col} = 0;
                                }
                            }

                            $copy->save();

                            // Reload fresh source for relations (avoid carrying aggregates)
                            $record = $fresh;

                            // Copy categories (many-to-many)
                            if (method_exists($record, 'categories')) {
                                $copy->categories()->sync($record->categories()->pluck('categories.id')->all());
                            }

                            // Copy product options
                            if (method_exists($record, 'options')) {
                                foreach ($record->options as $option) {
                                    $newOption = $option->replicate();
                                    $newOption->product_id = $copy->id;
                                    $newOption->save();
                                }
                            }

                            // Copy images (polymorphic)
                            if (method_exists($record, 'images')) {
                                foreach ($record->images as $image) {
                                    $newImage = $image->replicate();
                                    $newImage->imageable_id = $copy->id;
                                    $newImage->imageable_type = get_class($copy);
                                    $newImage->save();
                                }
                            }

                            return $copy;
                        });

                        Notification::make()
                            ->title('تم نسخ المنتج بنجاح')
                            ->body('يمكنك الآن تعديل البيانات قبل الحفظ.')
                            ->success()
                            ->send();

                        // Use a relative path to avoid issues with APP_URL containing /public
                        return redirect('/dashboard/products/'.$newProduct->id.'/edit');
                    }),
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
