<?php

namespace App\Filament\Admin\Resources\Subscribers;

use App\Models\Subscriber;
use BackedEnum;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use App\Filament\Admin\Resources\Subscribers\Pages\ListSubscribers;

class SubscriberResource extends Resource
{
    protected static ?string $model = Subscriber::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::DevicePhoneMobile;

    protected static ?string $navigationLabel = 'المشتركين';

    protected static ?string $pluralLabel = 'المشتركين';

    protected static ?string $label = 'مشترك';

    protected static ?int $navigationSort = 4;

    public static function getNavigationGroup(): ?string
    {
        return 'التسويق';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->sortable(),
                TextColumn::make('phone')
                    ->label('رقم الجوال')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('تم نسخ الرقم'),
                TextColumn::make('source')
                    ->label('المصدر')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'popup' => 'نافذة منبثقة',
                        'footer' => 'الفوتر',
                        'checkout' => 'الدفع',
                        default => $state,
                    }),
                IconColumn::make('is_active')
                    ->label('فعال')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('تاريخ الاشتراك')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->bulkActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSubscribers::route('/'),
        ];
    }
}
