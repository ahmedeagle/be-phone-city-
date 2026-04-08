<?php<?php








































































































































































































}    }        VipTierService::clearCache();    {    public static function afterSave($record): void     */     * Clear tier cache after any mutation.    /**    }        ];            'index' => Pages\ManageVipTiers::route('/'),        return [    {    public static function getPages(): array    }            ]);                ]),                    DeleteBulkAction::make(),                BulkActionGroup::make([            ->toolbarActions([            ])                CreateAction::make(),            ->headerActions([            ])                    ->requiresConfirmation(),                DeleteAction::make()                EditAction::make(),            ->recordActions([            ])                    ->sortable(),                    ->label('الترتيب')                TextColumn::make('sort_order')                    ->boolean(),                    ->label('مفعّل')                IconColumn::make('is_active')                    ->color('primary'),                    ->badge()                    })                        return \App\Models\User::where('vip_tier', $record->key)->count();                    ->getStateUsing(function ($record) {                    ->label('عدد العملاء')                TextColumn::make('users_count')                    ->sortable(),                    ->color('danger')                    ->money('SAR')                    ->label('أعلى خصم')                TextColumn::make('max_discount')                    ->sortable(),                    ->weight('bold')                    ->color('success')                    ->suffix('%')                    ->label('نسبة الخصم')                TextColumn::make('discount_percentage')                    ->sortable(),                    ->money('SAR')                    ->label('إجمالي المشتريات')                TextColumn::make('min_total')                    ->sortable(),                    ->suffix(' طلب')                    ->label('عدد الطلبات')                TextColumn::make('min_orders')                    ->searchable(),                    ->label('English')                TextColumn::make('name_en')                    ->searchable(),                    })                        default => 'primary',                        'platinum' => 'info',                        'gold' => 'warning',                        'silver' => 'gray',                        'bronze' => 'warning',                    ->color(fn ($record) => match ($record->key) {                    ->badge()                    ->weight('bold')                    ->label('المستوى')                TextColumn::make('name_ar')            ->columns([            ->defaultSort('sort_order', 'asc')        return $table    {    public static function table(Table $table): Table    }            ]);                    ->default(true),                    ->label('مفعّل')                Toggle::make('is_active')                    ->helperText('ترتيب تصاعدي — الأعلى رقم = أعلى مستوى'),                    ->default(0)                    ->numeric()                    ->required()                    ->label('الترتيب')                TextInput::make('sort_order')                    ->helperText('الحد الأعلى لمبلغ الخصم في الطلب الواحد'),                    ->minValue(0)                    ->numeric()                    ->required()                    ->label('أعلى مبلغ خصم (ر.س)')                TextInput::make('max_discount')                    ->maxValue(100),                    ->minValue(0)                    ->numeric()                    ->required()                    ->label('نسبة الخصم %')                TextInput::make('discount_percentage')                    ->helperText('إجمالي المبلغ المطلوب'),                    ->minValue(0)                    ->numeric()                    ->required()                    ->label('الحد الأدنى إجمالي المشتريات (ر.س)')                TextInput::make('min_total')                    ->helperText('عدد الطلبات المكتملة المطلوبة'),                    ->minValue(1)                    ->numeric()                    ->required()                    ->label('الحد الأدنى عدد الطلبات')                TextInput::make('min_orders')                    ->maxLength(50),                    ->required()                    ->label('الاسم بالإنجليزية')                TextInput::make('name_en')                    ->maxLength(50),                    ->required()                    ->label('الاسم بالعربية')                TextInput::make('name_ar')                    ->helperText('مثال: bronze, silver, gold, platinum'),                    ->regex('/^[a-z_]+$/')                    ->maxLength(20)                    ->unique(ignoreRecord: true)                    ->required()                    ->label('المفتاح (بالإنجليزية)')                TextInput::make('key')            ->components([        return $schema    {    public static function form(Schema $schema): Schema    }        return auth()->user()->can('users.delete');    {    public static function canDelete($record): bool    }        return auth()->user()->can('users.update');    {    public static function canEdit($record): bool    }        return auth()->user()->can('users.create');    {    public static function canCreate(): bool    }        return auth()->user()->can('users.show');    {    public static function canViewAny(): bool    }        return 'العملاء';    {    public static function getNavigationGroup(): ?string    protected static ?int $navigationSort = 2;    protected static ?string $recordTitleAttribute = 'name_ar';    protected static ?string $label = 'مستوى VIP';    protected static ?string $pluralLabel = 'مستويات VIP';    protected static ?string $navigationLabel = 'مستويات VIP';    protected static string|BackedEnum|null $navigationIcon = Heroicon::Star;    protected static ?string $model = VipTier::class;{class VipTierResource extends Resourceuse Filament\Tables\Table;use Filament\Tables\Columns\TextColumn;use Filament\Tables\Columns\IconColumn;use Filament\Support\Icons\Heroicon;use Filament\Schemas\Schema;use Filament\Resources\Resource;use Filament\Forms\Components\Toggle;use Filament\Forms\Components\TextInput;use Filament\Actions\BulkActionGroup;use Filament\Actions\EditAction;use Filament\Actions\DeleteBulkAction;use Filament\Actions\DeleteAction;use Filament\Actions\CreateAction;use BackedEnum;use App\Services\VipTierService;use App\Models\VipTier;namespace App\Filament\Admin\Resources\VipTiers;
namespace App\Filament\Admin\Resources\VipTiers;

use App\Models\VipTier;
use App\Services\VipTierService;
use BackedEnum;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VipTierResource extends Resource
{
    protected static ?string $model = VipTier::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Star;

    protected static ?string $navigationLabel = 'مستويات VIP';

    protected static ?string $pluralLabel = 'مستويات VIP';

    protected static ?string $label = 'مستوى VIP';

    protected static ?string $recordTitleAttribute = 'name_ar';

    protected static ?int $navigationSort = 2;

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
                TextInput::make('key')
                    ->label('المفتاح (بالإنجليزية)')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(20)
                    ->regex('/^[a-z_]+$/')
                    ->helperText('مثال: bronze, silver, gold, platinum'),
                TextInput::make('name_ar')
                    ->label('الاسم بالعربية')
                    ->required()
                    ->maxLength(50),
                TextInput::make('name_en')
                    ->label('الاسم بالإنجليزية')
                    ->required()
                    ->maxLength(50),
                TextInput::make('min_orders')
                    ->label('الحد الأدنى عدد الطلبات')
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->helperText('عدد الطلبات المكتملة المطلوبة'),
                TextInput::make('min_total')
                    ->label('الحد الأدنى إجمالي المشتريات (ر.س)')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->helperText('إجمالي المبلغ المطلوب'),
                TextInput::make('discount_percentage')
                    ->label('نسبة الخصم %')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100),
                TextInput::make('max_discount')
                    ->label('أعلى مبلغ خصم (ر.س)')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->helperText('الحد الأعلى لمبلغ الخصم في الطلب الواحد'),
                TextInput::make('sort_order')
                    ->label('الترتيب')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->helperText('ترتيب تصاعدي — الأعلى رقم = أعلى مستوى'),
                Toggle::make('is_active')
                    ->label('مفعّل')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order', 'asc')
            ->columns([
                TextColumn::make('name_ar')
                    ->label('المستوى')
                    ->weight('bold')
                    ->badge()
                    ->color(fn ($record) => match ($record->key) {
                        'bronze' => 'warning',
                        'silver' => 'gray',
                        'gold' => 'warning',
                        'platinum' => 'info',
                        default => 'primary',
                    })
                    ->searchable(),
                TextColumn::make('name_en')
                    ->label('English')
                    ->searchable(),
                TextColumn::make('min_orders')
                    ->label('عدد الطلبات')
                    ->suffix(' طلب')
                    ->sortable(),
                TextColumn::make('min_total')
                    ->label('إجمالي المشتريات')
                    ->money('SAR')
                    ->sortable(),
                TextColumn::make('discount_percentage')
                    ->label('نسبة الخصم')
                    ->suffix('%')
                    ->color('success')
                    ->weight('bold')
                    ->sortable(),
                TextColumn::make('max_discount')
                    ->label('أعلى خصم')
                    ->money('SAR')
                    ->color('danger')
                    ->sortable(),
                TextColumn::make('users_count')
                    ->label('عدد العملاء')
                    ->getStateUsing(function ($record) {
                        return \App\Models\User::where('vip_tier', $record->key)->count();
                    })
                    ->badge()
                    ->color('primary'),
                IconColumn::make('is_active')
                    ->label('مفعّل')
                    ->boolean(),
                TextColumn::make('sort_order')
                    ->label('الترتيب')
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->requiresConfirmation(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageVipTiers::route('/'),
        ];
    }

    /**
     * Clear tier cache after any mutation.
     */
    public static function afterSave($record): void
    {
        VipTierService::clearCache();
    }
}
