<?php

namespace App\Filament\Admin\Resources\ShippingCompanies;

use App\Models\ShippingCompany;
use BackedEnum;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use App\Filament\Admin\Resources\ShippingCompanies\Pages\ListShippingCompanies;
use App\Filament\Admin\Resources\ShippingCompanies\Pages\CreateShippingCompany;
use App\Filament\Admin\Resources\ShippingCompanies\Pages\EditShippingCompany;

class ShippingCompanyResource extends Resource
{
    protected static ?string $model = ShippingCompany::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Truck;

    protected static ?string $navigationLabel = 'شركات الشحن';

    protected static ?string $pluralLabel = 'شركات الشحن';

    protected static ?string $label = 'شركة شحن';

    protected static ?string $recordTitleAttribute = 'name_ar';

    protected static ?int $navigationSort = 7;

    public static function getNavigationGroup(): ?string
    {
        return 'الإعدادات والنظام';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name_ar')
                ->label('الاسم بالعربي')
                ->required()
                ->maxLength(255),
            TextInput::make('name_en')
                ->label('الاسم بالإنجليزي')
                ->required()
                ->maxLength(255),
            FileUpload::make('logo')
                ->label('الشعار')
                ->image()
                ->directory('shipping-companies')
                ->maxSize(1024),
            TextInput::make('cost')
                ->label('التكلفة (ر.س)')
                ->numeric()
                ->default(0)
                ->minValue(0),
            TextInput::make('estimated_days_ar')
                ->label('مدة التوصيل (عربي)')
                ->placeholder('مثال: 2-3 أيام عمل')
                ->maxLength(255),
            TextInput::make('estimated_days_en')
                ->label('مدة التوصيل (إنجليزي)')
                ->placeholder('e.g. 2-3 business days')
                ->maxLength(255),
            TextInput::make('sort_order')
                ->label('الترتيب')
                ->numeric()
                ->default(0),
            Toggle::make('is_active')
                ->label('فعال')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->sortable(),
                ImageColumn::make('logo')
                    ->label('الشعار')
                    ->circular(),
                TextColumn::make('name_ar')
                    ->label('الاسم')
                    ->searchable(),
                TextColumn::make('cost')
                    ->label('التكلفة')
                    ->money('SAR')
                    ->sortable(),
                TextColumn::make('estimated_days_ar')
                    ->label('مدة التوصيل'),
                IconColumn::make('is_active')
                    ->label('فعال')
                    ->boolean(),
                TextColumn::make('sort_order')
                    ->label('الترتيب')
                    ->sortable(),
            ])
            ->defaultSort('sort_order')
            ->bulkActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListShippingCompanies::route('/'),
            'create' => CreateShippingCompany::route('/create'),
            'edit' => EditShippingCompany::route('/{record}/edit'),
        ];
    }
}
