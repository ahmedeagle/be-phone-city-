<?php

namespace App\Filament\Admin\Resources\Branches\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BranchesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name_ar')
                    ->searchable()
                    ->sortable()
                    ->label('الاسم بالعربية'),

                TextColumn::make('name_en')
                    ->searchable()
                    ->sortable()
                    ->label('الاسم بالإنجليزية'),

                TextColumn::make('city_ar')
                    ->label('المدينة')
                    ->searchable()
                    ->placeholder('-'),

                TextColumn::make('phone')
                    ->label('الهاتف')
                    ->copyable()
                    ->placeholder('-'),

                IconColumn::make('is_active')
                    ->boolean()
                    ->label('نشط')
                    ->sortable(),

                TextColumn::make('sort_order')
                    ->label('الترتيب')
                    ->sortable(),

                TextColumn::make('orders_count')
                    ->counts('orders')
                    ->label('عدد الطلبات')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order')
            ->filters([
                SelectFilter::make('is_active')
                    ->options([
                        true => 'نشط',
                        false => 'غير نشط',
                    ])
                    ->label('الحالة'),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
