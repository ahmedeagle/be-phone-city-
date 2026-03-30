<?php

namespace App\Filament\Admin\Resources\ContactRequests;

use App\Filament\Admin\Resources\ContactRequests\Pages\ListContactRequests;
use App\Filament\Admin\Resources\ContactRequests\Pages\ViewContactRequest;
use App\Models\ContactRequest;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section as InfolistSection;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;

class ContactRequestResource extends Resource
{
    protected static ?string $model = ContactRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Envelope;

    protected static ?string $navigationLabel = 'طلبات الاتصال';

    protected static ?string $pluralLabel = 'طلبات الاتصال';

    protected static ?string $label = 'طلب اتصال';

    protected static ?int $navigationSort = 3;

    protected static bool $shouldRegisterNavigation = false;

    public static function getNavigationGroup(): ?string
    {
        return 'الدعم الفني';
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('contact_requests.show');
    }

    public static function canDelete($record): bool
    {
        return auth()->user()->can('contact_requests.delete');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                InfolistSection::make('معلومات الاتصال')
                    ->schema([
                        TextEntry::make('name')
                            ->label('الاسم'),
                        TextEntry::make('email')
                            ->label('البريد الإلكتروني')
                            ->copyable()
                            ->copyMessage('تم نسخ البريد الإلكتروني'),
                        TextEntry::make('phone')
                            ->label('رقم الهاتف')
                            ->placeholder('-')
                            ->copyable()
                            ->copyMessage('تم نسخ رقم الهاتف'),
                    ])
                    ->columns(3),
                InfolistSection::make('الرسالة')
                    ->schema([
                        TextEntry::make('message')
                            ->label('الرسالة')
                            ->wrap(),
                    ]),
                InfolistSection::make('معلومات إضافية')
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('تاريخ الإرسال')
                            ->dateTime('d/m/Y H:i')
                            ->icon('heroicon-o-calendar'),
                        TextEntry::make('updated_at')
                            ->label('آخر تحديث')
                            ->dateTime('d/m/Y H:i')
                            ->icon('heroicon-o-clock'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('الاسم')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('البريد الإلكتروني')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('تم نسخ البريد الإلكتروني'),
                TextColumn::make('phone')
                    ->label('رقم الهاتف')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('message')
                    ->label('الرسالة')
                    ->limit(50)
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('تاريخ الإرسال')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Filter::make('created_at')
                    ->label('تاريخ الإرسال')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('created_from')
                            ->label('من تاريخ'),
                        \Filament\Forms\Components\DatePicker::make('created_until')
                            ->label('إلى تاريخ'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn ($query, $date) => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn ($query, $date) => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ViewAction::make()
                    ->visible(fn () => auth()->user()->can('contact_requests.show')),
                DeleteAction::make()
                    ->visible(fn () => auth()->user()->can('contact_requests.delete'))
                    ->requiresConfirmation(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()->can('contact_requests.delete')),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContactRequests::route('/'),
            'view' => ViewContactRequest::route('/{record}'),
        ];
    }
}

