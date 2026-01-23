<?php

namespace App\Filament\Admin\Resources\Cities\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CitiesTable
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

                TextColumn::make('slug')
                    ->searchable()
                    ->label('الرابط')
                    ->copyable()
                    ->copyMessage('تم نسخ الرابط'),

                TextColumn::make('shipping_fee')
                    ->money('SAR')
                    ->sortable()
                    ->label('رسوم الشحن'),

                TextColumn::make('order')
                    ->numeric()
                    ->sortable()
                    ->label('الترتيب'),

                IconColumn::make('status')
                    ->boolean()
                    ->sortable()
                    ->label('الحالة'),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label('تاريخ الإنشاء')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->label('آخر تحديث')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        true => 'نشط',
                        false => 'غير نشط',
                    ])
                    ->label('الحالة'),
            ])
            ->defaultSort('order')
            ->recordActions([
                ViewAction::make()
                    ->visible(fn () => auth()->user()->can('cities.show')),
                EditAction::make()
                    ->visible(fn () => auth()->user()->can('cities.update')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()->can('cities.delete')),
                ]),
            ])
            ->paginated([ 25, 50, 100, 150, 200]);
    }
}
