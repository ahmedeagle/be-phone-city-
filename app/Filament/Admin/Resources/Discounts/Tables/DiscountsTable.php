<?php

namespace App\Filament\Admin\Resources\Discounts\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use App\Models\Discount;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DiscountsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('كود الخصم')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('تم نسخ كود الخصم'),
                IconColumn::make('status')
                    ->label('الحالة')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('type')
                    ->label('النوع')
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
                    })
                    ->sortable(),
                TextColumn::make('value')
                    ->label('قيمة الخصم')
                    ->formatStateUsing(fn ($state, $record) => $state . ($record->type === 'percentage' ? '%' : ' ر.س'))
                    ->sortable(),
                TextColumn::make('condition')
                    ->label('الشرط')
                    ->formatStateUsing(function ($state) {
                        if (!$state || !is_array($state) || !isset($state['type'])) {
                            return '-';
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
                    })
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('start')
                    ->label('تاريخ البداية')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('end')
                    ->label('تاريخ النهاية')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('description')
                    ->label('الوصف')
                    ->limit(30)
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        true => 'نشط',
                        false => 'غير نشط',
                    ]),
                SelectFilter::make('type')
                    ->label('نوع الخصم')
                    ->options([
                        'percentage' => 'نسبة مئوية',
                        'fixed' => 'مبلغ ثابت',
                    ]),
                Filter::make('start')
                    ->label('تاريخ البداية')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('start_from')
                            ->label('من تاريخ'),
                        \Filament\Forms\Components\DatePicker::make('start_until')
                            ->label('إلى تاريخ'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when(
                                $data['start_from'],
                                fn ($query, $date) => $query->whereDate('start', '>=', $date),
                            )
                            ->when(
                                $data['start_until'],
                                fn ($query, $date) => $query->whereDate('start', '<=', $date),
                            );
                    }),
                Filter::make('end')
                    ->label('تاريخ النهاية')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('end_from')
                            ->label('من تاريخ'),
                        \Filament\Forms\Components\DatePicker::make('end_until')
                            ->label('إلى تاريخ'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when(
                                $data['end_from'],
                                fn ($query, $date) => $query->whereDate('end', '>=', $date),
                            )
                            ->when(
                                $data['end_until'],
                                fn ($query, $date) => $query->whereDate('end', '<=', $date),
                            );
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ViewAction::make()
                    ->visible(fn () => auth()->user()->can('discounts.show')),
                EditAction::make()
                    ->visible(fn () => auth()->user()->can('discounts.update')),
                DeleteAction::make()
                    ->visible(fn () => auth()->user()->can('discounts.delete'))
                    ->requiresConfirmation(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()->can('discounts.delete')),
                ]),
            ]);
    }
}
