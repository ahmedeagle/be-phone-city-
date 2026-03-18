<?php

namespace App\Filament\Admin\Resources\Offers;

use App\Filament\Admin\Resources\Offers\Pages\ManageOffers;
use App\Models\Category;
use App\Models\Offer;
use App\Models\Product;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;

class OfferResource extends Resource
{
    protected static ?string $model = Offer::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Tag;

    protected static ?string $navigationLabel = 'العروض';

    protected static ?string $pluralLabel = 'العروض';

    protected static ?string $label = 'عرض';

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): ?string
    {
        return 'إدارة المتجر';
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('offers.show');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->can('offers.create');
    }

    public static function canEdit($record): bool
    {
        return auth()->user()->can('offers.update');
    }

    public static function canDelete($record): bool
    {
        return auth()->user()->can('offers.delete');
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
                TextInput::make('value')
                    ->required()
                    ->numeric()
                    ->minValue(0.01)
                    ->maxValue(fn ($get) => $get('type') === 'percentage' ? 100 : null)
                    ->suffix(fn ($get) => $get('type') === 'percentage' ? '%' : 'ر.س')
                    ->helperText(fn ($get) => $get('type') === 'percentage' ? 'نسبة الخصم (1 - 100%)' : 'قيمة الخصم بالريال')
                    ->reactive()
                    ->label('القيمة'),
                Select::make('type')
                    ->required()
                    ->options([
                        'percentage' => 'نسبة مئوية (%)',
                        'amount' => 'مبلغ ثابت',
                    ])
                    ->native(false)
                    ->label('نوع العرض'),
                Select::make('status')
                    ->required()
                    ->options([
                        'active' => 'نشط',
                        'inactive' => 'غير نشط',
                    ])
                    ->default('active')
                    ->native(false)
                    ->label('الحالة'),
                Select::make('apply_to')
                    ->required()
                    ->options([
                        'all' => 'جميع المنتجات',
                        'product' => 'منتجات محددة',
                        'category' => 'تصنيفات محددة',
                    ])
                    ->native(false)
                    ->reactive()
                    ->afterStateUpdated(function ($set, $state) {
                        if ($state === 'product') {
                            $set('categories', []);
                        } elseif ($state === 'category') {
                            $set('products', []);
                        } elseif ($state === 'all') {
                            $set('products', []);
                            $set('categories', []);
                        }
                    })
                    ->label('تطبيق على'),
                Select::make('products')
                    ->multiple()
                    ->relationship('products', 'name_ar')
                    ->searchable()
                    ->preload()
                    ->visible(fn ($get) => $get('apply_to') === 'product')
                    ->label('المنتجات'),
                Select::make('categories')
                    ->multiple()
                    ->relationship('categories', 'name_ar')
                    ->searchable()
                    ->preload()
                    ->visible(fn ($get) => $get('apply_to') === 'category')
                    ->label('التصنيفات'),
                DateTimePicker::make('start_at')
                    ->nullable()
                    ->label('تاريخ البداية'),
                DateTimePicker::make('end_at')
                    ->nullable()
                    ->after('start_at')
                    ->label('تاريخ النهاية'),
                FileUpload::make('image')
                    ->image()
                    ->directory('offers')
                    ->label('صورة العرض')
                    ->nullable(),

                Toggle::make('show_in_home')
                    ->label('عرض في الصفحة الرئيسية')
                    ->helperText('يتم عرض أول منتج أو تصنيف مرتبط بالعرض،
                    ويعمل فقط عند اختيار "منتجات محددة" أو "تصنيفات محددة".')
                    ->visible(fn (Get $get) =>
                        in_array($get('apply_to'), ['product', 'category'])
                    ),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name_ar')
                    ->label('الاسم بالعربية'),
                TextEntry::make('name_en')
                    ->label('الاسم بالإنجليزية'),
                TextEntry::make('value')
                    ->label('القيمة'),
                ImageEntry::make('image')
                    ->label('الصورة'),
                TextEntry::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'percentage' => 'success',
                        'amount' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'percentage' => 'نسبة مئوية (%)',
                        'amount' => 'مبلغ ثابت',
                        default => $state,
                    })
                    ->label('نوع العرض'),
                TextEntry::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => 'نشط',
                        'inactive' => 'غير نشط',
                        default => $state,
                    })
                    ->label('الحالة'),
                TextEntry::make('apply_to')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'all' => 'جميع المنتجات',
                        'product' => 'منتجات محددة',
                        'category' => 'تصنيفات محددة',
                        default => $state,
                    })
                    ->label('تطبيق على'),
                TextEntry::make('products.name_ar')
                    ->listWithLineBreaks()
                    ->placeholder('-')
                    ->visible(fn ($record) => $record->apply_to === 'product')
                    ->label('المنتجات'),
                TextEntry::make('categories.name_ar')
                    ->listWithLineBreaks()
                    ->placeholder('-')
                    ->visible(fn ($record) => $record->apply_to === 'category')
                    ->label('التصنيفات'),
                TextEntry::make('start_at')
                    ->dateTime()
                    ->placeholder('-')
                    ->label('تاريخ البداية'),
                TextEntry::make('first_related')
                    ->label('أول عنصر مرتبط')
                    ->getStateUsing(function ($record) {
                        if ($record->apply_to === 'product') {
                            return $record->products()->first()?->name_ar ?? '-';
                        }

                        if ($record->apply_to === 'category') {
                            return $record->categories()->first()?->name_ar ?? '-';
                        }

                        return '-';
                    }),
                TextEntry::make('end_at')
                    ->dateTime()
                    ->placeholder('-')
                    ->label('تاريخ النهاية'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->label('تاريخ الإنشاء'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name_ar')
                    ->searchable()
                    ->sortable()
                    ->label('الاسم بالعربية'),
                TextColumn::make('name_en')
                    ->searchable()
                    ->sortable()
                    ->label('الاسم بالإنجليزية'),
                ImageColumn::make('image')
                    ->circular()
                    ->label('الصورة'),
                TextColumn::make('value')
                    ->sortable()
                    ->label('القيمة'),
                TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'percentage' => 'success',
                        'amount' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'percentage' => '%',
                        'amount' => 'ثابت',
                        default => $state,
                    })
                    ->label('النوع'),
                IconColumn::make('status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->getStateUsing(fn ($record) => $record->status === 'active')
                    ->label('نشط'),
                TextColumn::make('apply_to')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'all' => 'الكل',
                        'product' => 'منتجات',
                        'category' => 'تصنيفات',
                        default => $state,
                    })
                    ->label('تطبيق على'),
                IconColumn::make('show_in_home')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->getStateUsing(fn ($record) => $record->show_in_home === true)
                    ->label('عرض في الرئيسية'),
                TextColumn::make('start_at')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('-')
                    ->label('البداية'),
                TextColumn::make('end_at')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('-')
                    ->label('النهاية'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label('تاريخ الإنشاء')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'active' => 'نشط',
                        'inactive' => 'غير نشط',
                    ])
                    ->label('الحالة'),
                SelectFilter::make('type')
                    ->options([
                        'percentage' => 'نسبة مئوية',
                        'amount' => 'مبلغ ثابت',
                    ])
                    ->label('النوع'),
                SelectFilter::make('apply_to')
                    ->options([
                        'all' => 'الكل',
                        'product' => 'منتجات',
                        'category' => 'تصنيفات',
                    ])
                    ->label('تطبيق على'),
            ])
            ->recordActions([
                ViewAction::make()
                    ->visible(fn () => auth()->user()->can('offers.show')),
                EditAction::make()
                    ->visible(fn () => auth()->user()->can('offers.update')),
                DeleteAction::make()
                    ->visible(fn () => auth()->user()->can('offers.delete'))
                    ->requiresConfirmation(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()->can('offers.delete')),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageOffers::route('/'),
        ];
    }
}
