<?php

namespace App\Filament\Admin\Resources\Services\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ServicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image')
                    ->label('الصورة'), // Arabic label
                TextColumn::make('name_en')
                    ->label('الاسم بالإنجليزية') // Arabic label
                    ->searchable(),
                TextColumn::make('name_ar')
                    ->label('الاسم بالعربية') // Arabic label
                    ->searchable(),
                IconColumn::make('is_active')
                    ->label('نشط') // Arabic label
                    ->boolean(),
                TextColumn::make('order')
                    ->label('الترتيب') // Arabic label
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء') // Arabic label
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('تاريخ التحديث') // Arabic label
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn () => auth()->user()->can('services.update')),
                DeleteAction::make()
                    ->visible(fn () => auth()->user()->can('services.delete'))
                    ->requiresConfirmation(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()->can('services.delete')),
                ]),
            ]);
    }
}
