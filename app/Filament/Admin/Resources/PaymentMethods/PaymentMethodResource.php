<?php

namespace App\Filament\Admin\Resources\PaymentMethods;

use App\Filament\Admin\Resources\PaymentMethods\Pages\CreatePaymentMethod;
use App\Filament\Admin\Resources\PaymentMethods\Pages\EditPaymentMethod;
use App\Filament\Admin\Resources\PaymentMethods\Pages\ListPaymentMethods;
use App\Filament\Admin\Resources\PaymentMethods\Pages\ViewPaymentMethod;
use App\Filament\Admin\Resources\PaymentMethods\Schemas\PaymentMethodForm;
use App\Filament\Admin\Resources\PaymentMethods\Schemas\PaymentMethodInfolist;
use App\Filament\Admin\Resources\PaymentMethods\Tables\PaymentMethodsTable;
use App\Models\PaymentMethod;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PaymentMethodResource extends Resource
{
    protected static ?string $model = PaymentMethod::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::CreditCard;

    protected static ?string $navigationLabel = 'طرق الدفع';

    protected static ?string $pluralLabel = 'طرق الدفع';

    protected static ?string $label = 'طريقة دفع';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return 'الإعدادات والنظام';
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('payment_methods.show');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->can('payment_methods.create');
    }

    public static function canEdit($record): bool
    {
        return auth()->user()->can('payment_methods.update');
    }

    public static function canDelete($record): bool
    {
        return auth()->user()->can('payment_methods.delete');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name_ar')
                    ->required()
                    ->label('الاسم بالعربية'),

                TextInput::make('name_en')
                    ->required()
                    ->label('الاسم بالإنجليزية'),

                Textarea::make('description_ar')
                    ->nullable()
                    ->rows(3)
                    ->label('الوصف بالعربية'),

                Textarea::make('description_en')
                    ->nullable()
                    ->rows(3)
                    ->label('الوصف بالإنجليزية'),

                FileUpload::make('image')
                    ->image()
                    ->imageEditor()
                    ->nullable()
                    ->label('الصورة'),

                Select::make('status')
                    ->required()
                    ->options([
                        'active' => 'نشط',
                        'inactive' => 'غير نشط',
                    ])
                    ->default('active')
                    ->label('الحالة'),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                ImageEntry::make('image')
                    ->label('الصورة'),

                TextEntry::make('name_ar')
                    ->label('الاسم بالعربية'),

                TextEntry::make('name_en')
                    ->label('الاسم بالإنجليزية'),

                TextEntry::make('description_ar')
                    ->placeholder('-')
                    ->label('الوصف بالعربية'),

                TextEntry::make('description_en')
                    ->placeholder('-')
                    ->label('الوصف بالإنجليزية'),

                TextEntry::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'danger',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => 'نشط',
                        'inactive' => 'غير نشط',
                    })
                    ->label('الحالة'),

                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-')
                    ->label('تاريخ الإنشاء'),

                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-')
                    ->label('تاريخ التحديث'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image')
                    ->label('الصورة'),

                TextColumn::make('name_ar')
                    ->searchable()
                    ->sortable()
                    ->label('الاسم بالعربية'),

                TextColumn::make('name_en')
                    ->searchable()
                    ->sortable()
                    ->label('الاسم بالإنجليزية'),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'danger',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => 'نشط',
                        'inactive' => 'غير نشط',
                    })
                    ->sortable()
                    ->label('الحالة'),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label('تاريخ الإنشاء')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make()
                    ->visible(fn () => auth()->user()->can('payment_methods.show')),
                EditAction::make()
                    ->visible(fn () => auth()->user()->can('payment_methods.update')),
                DeleteAction::make()
                    ->visible(fn () => auth()->user()->can('payment_methods.delete'))
                    ->requiresConfirmation(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()->can('payment_methods.delete')),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPaymentMethods::route('/'),
            'create' => CreatePaymentMethod::route('/create'),
            'edit' => EditPaymentMethod::route('/{record}/edit'),
            'view' => ViewPaymentMethod::route('/{record}'),
        ];
    }
}
