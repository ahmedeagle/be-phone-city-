<?php

namespace App\Filament\Admin\Resources\Subscribers;

use App\Models\Subscriber;
use BackedEnum;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Response;
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
                BulkAction::make('export_csv')
                    ->label('تصدير CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(function (Collection $records) {
                        $csv = "رقم الجوال,المصدر,فعال,تاريخ الاشتراك\n";
                        foreach ($records as $subscriber) {
                            $source = match($subscriber->source) {
                                'popup' => 'نافذة منبثقة',
                                'footer' => 'الفوتر',
                                'checkout' => 'الدفع',
                                default => $subscriber->source,
                            };
                            $csv .= "{$subscriber->phone},{$source}," .
                                ($subscriber->is_active ? 'نعم' : 'لا') . "," .
                                $subscriber->created_at->format('Y-m-d H:i') . "\n";
                        }

                        return Response::streamDownload(function () use ($csv) {
                            echo "\xEF\xBB\xBF" . $csv; // UTF-8 BOM for Arabic support
                        }, 'subscribers_' . now()->format('Y-m-d') . '.csv', [
                            'Content-Type' => 'text/csv; charset=UTF-8',
                        ]);
                    })
                    ->deselectRecordsAfterCompletion(),
                BulkAction::make('export_numbers_txt')
                    ->label('تصدير الأرقام فقط')
                    ->icon('heroicon-o-clipboard-document')
                    ->action(function (Collection $records) {
                        $numbers = $records->pluck('phone')->implode("\n");

                        return Response::streamDownload(function () use ($numbers) {
                            echo $numbers;
                        }, 'phone_numbers_' . now()->format('Y-m-d') . '.txt', [
                            'Content-Type' => 'text/plain; charset=UTF-8',
                        ]);
                    })
                    ->deselectRecordsAfterCompletion(),
                DeleteBulkAction::make(),
            ])
            ->headerActions([
                \Filament\Tables\Actions\Action::make('export_all_active')
                    ->label('تصدير جميع الأرقام النشطة')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->action(function () {
                        $subscribers = Subscriber::where('is_active', true)->get();
                        $numbers = $subscribers->pluck('phone')->implode("\n");

                        return Response::streamDownload(function () use ($numbers) {
                            echo $numbers;
                        }, 'all_active_numbers_' . now()->format('Y-m-d') . '.txt', [
                            'Content-Type' => 'text/plain; charset=UTF-8',
                        ]);
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSubscribers::route('/'),
        ];
    }
}
