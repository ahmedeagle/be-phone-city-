<?php

namespace App\Filament\Admin\Resources\Sliders;

use App\Filament\Admin\Resources\Sliders\Pages\CreateSlider;
use App\Filament\Admin\Resources\Sliders\Pages\EditSlider;
use App\Filament\Admin\Resources\Sliders\Pages\ListSliders;
use App\Filament\Admin\Resources\Sliders\Pages\ViewSlider;
use App\Filament\Admin\Resources\Sliders\Schemas\SliderForm;
use App\Filament\Admin\Resources\Sliders\Tables\SlidersTable;
use App\Models\Slider;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SliderResource extends Resource
{
    protected static ?string $model = Slider::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Photo;

    protected static ?string $navigationLabel = 'الشرائح';

    protected static ?string $pluralLabel = 'الشرائح';

    protected static ?string $label = 'شريحة';

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return 'المحتوى التسويقي';
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('sliders.show');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->can('sliders.create');
    }

    public static function canEdit($record): bool
    {
        return auth()->user()->can('sliders.update');
    }

    public static function canDelete($record): bool
    {
        return auth()->user()->can('sliders.delete');
    }

    public static function form(Schema $schema): Schema
    {
        return SliderForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SlidersTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Schemas\Components\Section::make('معلومات الشريحة')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('title')
                            ->label('العنوان'),
                        \Filament\Infolists\Components\TextEntry::make('title_en')
                            ->label('العنوان بالإنجليزية'),
                        \Filament\Infolists\Components\TextEntry::make('title_ar')
                            ->label('العنوان بالعربية'),
                    ])
                    ->columns(3),
                \Filament\Schemas\Components\Section::make('الوصف')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('description')
                            ->label('الوصف')
                            ->wrap(),
                        \Filament\Infolists\Components\TextEntry::make('description_en')
                            ->label('الوصف بالإنجليزية')
                            ->wrap(),
                        \Filament\Infolists\Components\TextEntry::make('description_ar')
                            ->label('الوصف بالعربية')
                            ->wrap(),
                    ])
                    ->columns(3),
                \Filament\Schemas\Components\Section::make('الصورة')
                    ->schema([
                        \Filament\Infolists\Components\ImageEntry::make('image')
                            ->label('الصورة')
                            ->height(200),
                    ]),
                \Filament\Schemas\Components\Section::make('زر الإجراء')
                    ->schema([
                        \Filament\Infolists\Components\IconEntry::make('have_button')
                            ->label('إظهار زر')
                            ->boolean(),
                        \Filament\Infolists\Components\TextEntry::make('type')
                            ->label('نوع الصفحة')
                            ->formatStateUsing(fn ($state) => match ($state) {
                                'page' => 'صفحة',
                                'offer' => 'عرض',
                                'product' => 'منتج',
                                'category' => 'تصنيف',
                                default => '-',
                            })
                            ->visible(fn ($record) => $record->have_button),
                        \Filament\Infolists\Components\TextEntry::make('url_slug')
                            ->label('الرابط (Slug)')
                            ->visible(fn ($record) => $record->have_button),
                        \Filament\Infolists\Components\TextEntry::make('button_text')
                            ->label('نص الزر')
                            ->visible(fn ($record) => $record->have_button),
                        \Filament\Infolists\Components\TextEntry::make('button_text_en')
                            ->label('نص الزر بالإنجليزية')
                            ->visible(fn ($record) => $record->have_button),
                        \Filament\Infolists\Components\TextEntry::make('button_text_ar')
                            ->label('نص الزر بالعربية')
                            ->visible(fn ($record) => $record->have_button),
                    ])
                    ->columns(2)
                    ->visible(fn ($record) => $record->have_button)
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSliders::route('/'),
            'create' => CreateSlider::route('/create'),
            'edit' => EditSlider::route('/{record}/edit'),
            'view' => ViewSlider::route('/{record}'),
        ];
    }
}
