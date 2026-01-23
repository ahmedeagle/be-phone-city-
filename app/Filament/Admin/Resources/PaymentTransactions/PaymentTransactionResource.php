<?php

namespace App\Filament\Admin\Resources\PaymentTransactions;

use App\Filament\Admin\Resources\PaymentTransactions\Pages\ListPaymentTransactions;
use App\Filament\Admin\Resources\PaymentTransactions\Pages\ViewPaymentTransaction;
use App\Models\PaymentTransaction;
use App\Services\PaymentService;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Radio;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class PaymentTransactionResource extends Resource
{
    protected static ?string $model = PaymentTransaction::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::CreditCard;

    protected static ?string $navigationLabel = 'معاملات الدفع';

    protected static ?string $pluralLabel = 'معاملات الدفع';

    protected static ?string $label = 'معاملة دفع';

    protected static ?string $recordTitleAttribute = 'transaction_id';

    protected static ?int $navigationSort = 6;

    public static function getNavigationGroup(): ?string
    {
        return 'المبيعات والمدفوعات';
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('payment_transactions.show');
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::awaitingReview()->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('transaction_id')
                    ->label('رقم المعاملة')
                    ->searchable()
                    ->copyable()
                    ->placeholder('-'),

                TextColumn::make('order.order_number')
                    ->label('رقم الطلب')
                    ->searchable()
                    ->url(fn (PaymentTransaction $record): string =>
                        route('filament.admin.resources.orders.view', ['record' => $record->order_id])
                    ),

                TextColumn::make('gateway')
                    ->label('طريقة الدفع')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'cash' => 'كاش',
                        'bank_transfer' => 'تحويل بنكي',
                        'tamara' => 'تمارا',
                        'tabby' => 'تابي',
                        'amwal' => 'أموال',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'cash' => 'success',
                        'bank_transfer' => 'warning',
                        'tamara', 'tabby', 'amwal' => 'info',
                        default => 'gray',
                    }),

                TextColumn::make('amount')
                    ->label('المبلغ')
                    ->money('SAR')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'قيد الانتظار',
                        'processing' => 'قيد المعالجة',
                        'success' => 'ناجح',
                        'failed' => 'فشل',
                        'expired' => 'منتهي',
                        'cancelled' => 'ملغي',
                        'refunded' => 'مسترجع',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'pending', 'processing' => 'warning',
                        'success' => 'success',
                        'failed', 'cancelled' => 'danger',
                        'expired' => 'gray',
                        'refunded' => 'info',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('payment_proof_path')
                    ->label('إثبات الدفع')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? 'تم الرفع' : 'لا يوجد')
                    ->color(fn ($state) => $state ? 'success' : 'gray')
                    ->toggleable(),

                TextColumn::make('reviewed_at')
                    ->label('تاريخ المراجعة')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('لم تتم المراجعة')
                    ->toggleable()
                    ->toggledHiddenByDefault(),

                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('gateway')
                    ->label('طريقة الدفع')
                    ->options([
                        'cash' => 'كاش',
                        'bank_transfer' => 'تحويل بنكي',
                        'tamara' => 'تمارا',
                        'tabby' => 'تابي',
                        'amwal' => 'أموال',
                    ]),

                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'pending' => 'قيد الانتظار',
                        'processing' => 'قيد المعالجة',
                        'success' => 'ناجح',
                        'failed' => 'فشل',
                        'expired' => 'منتهي',
                        'cancelled' => 'ملغي',
                        'refunded' => 'مسترجع',
                    ])
                    ->multiple(),

                Filter::make('awaiting_review')
                    ->label('بانتظار المراجعة')
                    ->query(fn (Builder $query): Builder => $query->awaitingReview())
                    ->toggle(),

                Filter::make('has_proof')
                    ->label('يحتوي على إثبات دفع')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('payment_proof_path'))
                    ->toggle(),
            ])
            ->actions([
                ViewAction::make()
                    ->visible(fn () => auth()->user()->can('payment_transactions.show')),

                Action::make('review_payment')
                    ->label('مراجعة')
                    ->icon('heroicon-o-check-circle')
                    ->color('warning')
                    ->visible(fn (PaymentTransaction $record) => $record->requiresReview() && auth()->user()->can('payment_transactions.review'))
                    ->form([
                        Radio::make('decision')
                            ->label('القرار')
                            ->options([
                                'approve' => 'قبول',
                                'reject' => 'رفض',
                            ])
                            ->required()
                            ->inline(),

                        Textarea::make('notes')
                            ->label('ملاحظات')
                            ->rows(3)
                            ->placeholder('سبب الرفض أو ملاحظات إضافية'),
                    ])
                    ->action(function (PaymentTransaction $record, array $data) {
                        try {
                            $approve = $data['decision'] === 'approve';
                            $paymentService = app(PaymentService::class);

                            $paymentService->reviewPaymentProof(
                                $record->order,
                                $approve,
                                $data['notes'] ?? null
                            );

                            Notification::make()
                                ->success()
                                ->title($approve ? 'تم قبول الدفع' : 'تم رفض الدفع')
                                ->body($approve ? 'تم تحديث حالة الطلب بنجاح' : 'تم إخطار العميل بالرفض')
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title('فشل في مراجعة الدفع')
                                ->body($e->getMessage())
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                // No bulk actions for payment transactions (for security)
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('معلومات المعاملة')
                    ->schema([
                        TextEntry::make('transaction_id')
                            ->label('رقم المعاملة')
                            ->copyable()
                            ->placeholder('لا يوجد'),

                        TextEntry::make('order.order_number')
                            ->label('رقم الطلب')
                            ->url(fn (PaymentTransaction $record): string =>
                                route('filament.admin.resources.orders.view', ['record' => $record->order_id])
                            ),

                        TextEntry::make('gateway')
                            ->label('طريقة الدفع')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'cash' => 'كاش',
                                'bank_transfer' => 'تحويل بنكي',
                                'tamara' => 'تمارا',
                                'tabby' => 'تابي',
                                'amwal' => 'أموال',
                                default => $state,
                            }),

                        TextEntry::make('amount')
                            ->label('المبلغ')
                            ->money('SAR'),

                        TextEntry::make('currency')
                            ->label('العملة'),

                        TextEntry::make('status')
                            ->label('الحالة')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'pending' => 'قيد الانتظار',
                                'processing' => 'قيد المعالجة',
                                'success' => 'ناجح',
                                'failed' => 'فشل',
                                'expired' => 'منتهي',
                                'cancelled' => 'ملغي',
                                'refunded' => 'مسترجع',
                                default => $state,
                            })
                            ->color(fn (string $state): string => match ($state) {
                                'success' => 'success',
                                'failed' => 'danger',
                                'pending' => 'warning',
                                default => 'gray',
                            }),

                        TextEntry::make('created_at')
                            ->label('تاريخ الإنشاء')
                            ->dateTime('d/m/Y H:i'),

                        TextEntry::make('expires_at')
                            ->label('تاريخ الانتهاء')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('لا ينتهي'),
                    ])
                    ->columns(3),

                Section::make('إثبات الدفع')
                    ->schema([
                        ViewEntry::make('payment_proof_path')
                            ->label('')
                            ->view('filament.infolists.payment-proof-viewer')
                            ->visible(fn (PaymentTransaction $record) => $record->hasPaymentProof()),
                    ])
                    ->visible(fn (PaymentTransaction $record) => $record->hasPaymentProof()),

                Section::make('معلومات المراجعة')
                    ->schema([
                        TextEntry::make('reviewer.name')
                            ->label('تمت المراجعة بواسطة')
                            ->placeholder('لم تتم المراجعة'),

                        TextEntry::make('reviewed_at')
                            ->label('تاريخ المراجعة')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('لم تتم المراجعة'),

                        TextEntry::make('review_notes')
                            ->label('ملاحظات المراجعة')
                            ->placeholder('لا توجد ملاحظات')
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->visible(fn (PaymentTransaction $record) => $record->gateway === 'bank_transfer'),

                Section::make('رسالة الخطأ')
                    ->schema([
                        TextEntry::make('error_message')
                            ->label('')
                            ->color('danger')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn (PaymentTransaction $record) => !empty($record->error_message)),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPaymentTransactions::route('/'),
            'view' => ViewPaymentTransaction::route('/{record}'),
        ];
    }
}
