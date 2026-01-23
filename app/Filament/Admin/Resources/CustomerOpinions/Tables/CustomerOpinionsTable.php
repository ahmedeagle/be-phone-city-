<?php

namespace App\Filament\Admin\Resources\CustomerOpinions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CustomerOpinionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image')
                    ->label('الصورة')
                    ->circular()
                    ->size(50),
                TextColumn::make('name')
                    ->label('الاسم')
                    ->searchable(['name_en', 'name_ar'])
                    ->sortable(),
                TextColumn::make('name_en')
                    ->label('الاسم بالإنجليزية')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('name_ar')
                    ->label('الاسم بالعربية')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('description')
                    ->label('الوصف')
                    ->limit(50)
                    ->wrap()
                    ->toggleable(),
                TextColumn::make('rate')
                    ->label('التقييم')
                    ->formatStateUsing(fn (int $state): string => str_repeat('⭐', $state) . " ({$state}/5)")
                    ->color(fn (int $state): string => match (true) {
                        $state >= 5 => 'success',
                        $state >= 4 => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('تاريخ التحديث')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('rate')
                    ->label('التقييم')
                    ->options([
                        1 => '⭐ (1/5)',
                        2 => '⭐⭐ (2/5)',
                        3 => '⭐⭐⭐ (3/5)',
                        4 => '⭐⭐⭐⭐ (4/5)',
                        5 => '⭐⭐⭐⭐⭐ (5/5)',
                    ])
                    ->native(false),
            ])
            ->recordActions([
                ViewAction::make()
                    ->visible(fn () => auth()->user()->can('customer_opinions.show')),
                EditAction::make()
                    ->visible(fn () => auth()->user()->can('customer_opinions.update')),
                DeleteAction::make()
                    ->visible(fn () => auth()->user()->can('customer_opinions.delete'))
                    ->requiresConfirmation(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()->can('customer_opinions.delete')),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}

