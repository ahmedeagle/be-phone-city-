<?php

namespace App\Filament\Admin\Resources\Users;

use App\Filament\Admin\Resources\Orders\OrderResource;
use App\Filament\Admin\Resources\Users\Pages\ManageUsers;
use App\Filament\Admin\Resources\Users\Pages\ViewUser;
use App\Models\Order;
use App\Models\User;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Users;

    protected static ?string $navigationLabel = 'المستخدمين';

    protected static ?string $pluralLabel = 'المستخدمين';

    protected static ?string $label = 'مستخدم';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return 'العملاء';
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('users.show');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->can('users.create');
    }

    public static function canEdit($record): bool
    {
        return auth()->user()->can('users.update');
    }

    public static function canDelete($record): bool
    {
        return auth()->user()->can('users.delete');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->label('الاسم'),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->unique()
                    ->label('البريد الالكتروني')
                    ->required(),
                TextInput::make('phone')
                    ->tel()
                    ->label('رقم الهاتف')
                    ->required(),
                TextInput::make('password')
                    ->password()
                    ->label('كلمة المرور')
                    ->required(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                // User Information Section
                Section::make('معلومات المستخدم')
                    ->icon('heroicon-o-user')
                    ->schema([
                        Group::make()
                            ->schema([
                                TextEntry::make('name')
                                    ->label('الاسم')
                                    ->weight(FontWeight::Bold)
                                    ->size(\Filament\Support\Enums\TextSize::Large),
                                TextEntry::make('email')
                                    ->label('البريد الالكتروني')
                                    ->copyable(),
                                TextEntry::make('phone')
                                    ->label('رقم الهاتف')
                                    ->copyable(),
                                TextEntry::make('email_verified_at')
                                    ->dateTime()
                                    ->placeholder('لم يتم التحقق')
                                    ->label('تم التحقق من البريد الالكتروني')
                                    ->badge()
                                    ->color(fn ($state) => $state ? 'success' : 'gray')
                                    ->formatStateUsing(fn ($state) => $state ? 'تم التحقق' : 'لم يتم التحقق'),
                                TextEntry::make('created_at')
                                    ->dateTime()
                                    ->label('تم التسجيل'),
                            ])
                            ->columnSpan(1),
                    ])
                    ->columnSpan(1),

                // Statistics Section
                Section::make('الإحصائيات')
                    ->icon('heroicon-o-chart-bar')
                    ->schema([
                        Group::make()
                            ->schema([
                                TextEntry::make('orders_count')
                                    ->label('عدد الطلبات')
                                    ->getStateUsing(function ($record) {
                                        return $record->orders()->count();
                                    })
                                    ->suffix(' طلب')
                                    ->weight(FontWeight::Bold)
                                    ->size(\Filament\Support\Enums\TextSize::Large)
                                    ->color('primary')
                                    ->icon('heroicon-o-shopping-cart'),
                                TextEntry::make('orders_total')
                                    ->label('إجمالي الطلبات')
                                    ->getStateUsing(function ($record) {
                                        $total = $record->orders()->sum('total') ?? 0;
                                        return number_format($total, 2) . ' ر.س';
                                    })
                                    ->weight(FontWeight::Bold)
                                    ->size(\Filament\Support\Enums\TextSize::Large)
                                    ->color('success')
                                    ->icon('heroicon-o-currency-dollar'),
                                TextEntry::make('reviews_count')
                                    ->label('عدد التقييمات')
                                    ->getStateUsing(function ($record) {
                                        return $record->reviews()->count();
                                    })
                                    ->suffix(' تقييم')
                                    ->weight(FontWeight::Bold)
                                    ->size(\Filament\Support\Enums\TextSize::Medium)
                                    ->color('info')
                                    ->icon('heroicon-o-star'),
                            ])
                            ->columnSpan(1),
                    ])
                    ->columnSpan(1),

                // Orders Section
                Section::make('الطلبات')
                    ->icon('heroicon-o-shopping-cart')
                    ->description('آخر 20 طلب (لعرض جميع الطلبات، استخدم رابط "عرض الطلب" لكل طلب)')
                    ->schema([
                        RepeatableEntry::make('orders')
                            ->label('الطلبات')
                            ->schema([
                                Group::make()
                                    ->schema([
                                        TextEntry::make('order_number')
                                            ->label('رقم الطلب')
                                            ->weight(FontWeight::Bold)
                                            ->color('primary')
                                            ->copyable(),
                                        TextEntry::make('total')
                                            ->label('الإجمالي')
                                            ->getStateUsing(fn ($record) => number_format($record->total, 2) . ' ر.س')
                                            ->weight(FontWeight::Bold)
                                            ->color('success'),
                                        TextEntry::make('status')
                                            ->label('الحالة')
                                            ->badge()
                                            ->color(fn (string $state): string => match ($state) {
                                                Order::STATUS_PENDING => 'gray',
                                                Order::STATUS_CONFIRMED => 'info',
                                                Order::STATUS_PROCESSING => 'primary',
                                                Order::STATUS_SHIPPED => 'warning',
                                                Order::STATUS_IN_PROGRESS => 'warning',
                                                Order::STATUS_DELIVERED => 'success',
                                                Order::STATUS_COMPLETED => 'success',
                                                Order::STATUS_CANCELLED => 'danger',
                                                default => 'gray',
                                            })
                                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                                Order::STATUS_PENDING => 'قيد الانتظار',
                                                Order::STATUS_CONFIRMED => 'مؤكد',
                                                Order::STATUS_PROCESSING => 'قيد المعالجة',
                                                Order::STATUS_SHIPPED => 'تم الشحن',
                                                Order::STATUS_IN_PROGRESS => 'قيد التوصيل',
                                                Order::STATUS_DELIVERED => 'تم التسليم',
                                                Order::STATUS_COMPLETED => 'مكتمل',
                                                Order::STATUS_CANCELLED => 'ملغي',
                                                default => $state,
                                            }),
                                        TextEntry::make('created_at')
                                            ->label('تاريخ الطلب')
                                            ->dateTime('Y-m-d H:i')
                                            ->color('gray'),
                                        TextEntry::make('view_order')
                                            ->label('')
                                            ->state('عرض الطلب')
                                            ->url(fn ($record) => OrderResource::getUrl('view', ['record' => $record]))
                                            ->icon('heroicon-o-arrow-left')
                                            ->color('primary'),
                                    ])
                                    ->columns(5),
                            ])
                            ->columns(1)
                            ->contained(true),
                    ])
                    ->columnSpan(2)
                    ->collapsible()
                    ->collapsed(),

                // Reviews Section
                Section::make('التقييمات')
                    ->icon('heroicon-o-star')
                    ->description('آخر 20 تقييم')
                    ->schema([
                        RepeatableEntry::make('reviews')
                            ->label('التقييمات')
                            ->schema([
                                Group::make()
                                    ->schema([
                                        TextEntry::make('product.name_ar')
                                            ->label('المنتج')
                                            ->weight(FontWeight::Bold)
                                            ->color('primary'),
                                        TextEntry::make('rating')
                                            ->label('التقييم')
                                            ->badge()
                                            ->formatStateUsing(fn ($state) => $state . ' / 5')
                                            ->color(fn ($state) => match (true) {
                                                $state >= 4 => 'success',
                                                $state >= 3 => 'warning',
                                                default => 'danger',
                                            })
                                            ->icon('heroicon-o-star'),
                                        TextEntry::make('comment')
                                            ->label('التعليق')
                                            ->limit(100)
                                            ->placeholder('لا يوجد تعليق'),
                                        TextEntry::make('created_at')
                                            ->label('تاريخ التقييم')
                                            ->dateTime('Y-m-d H:i')
                                            ->color('gray'),
                                    ])
                                    ->columns(4),
                            ])
                            ->columns(1)
                            ->contained(true),
                    ])
                    ->columnSpan(2)
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                return $query->withCount('orders')
                    ->withSum('orders', 'total');
            })
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->label('الاسم'),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable()
                    ->sortable()
                    ->label('البريد الالكتروني'),
                TextColumn::make('phone')
                    ->searchable()
                    ->label('رقم الهاتف'),
                TextColumn::make('orders_count')
                    ->counts('orders')
                    ->label('عدد الطلبات')
                    ->sortable(),
                TextColumn::make('orders_sum_total')
                    ->label('إجمالي الطلبات')
                    ->money('SAR')
                    ->getStateUsing(function ($record) {
                        return $record->orders_sum_total ?? 0;
                    })
                    ->sortable()
                    ->color('success'),
                TextColumn::make('email_verified_at')
                    ->dateTime()
                    ->sortable()
                    ->label('تم التحقق من البريد الالكتروني'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label('تم التسجيل')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make()
                    ->visible(fn () => auth()->user()->can('users.show')),
                EditAction::make()
                    ->visible(fn () => auth()->user()->can('users.update')),
                DeleteAction::make()
                    ->visible(fn () => auth()->user()->can('users.delete'))
                    ->requiresConfirmation(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()->can('users.delete')),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageUsers::route('/'),
            'view' => ViewUser::route('/{record}'),
        ];
    }
}
