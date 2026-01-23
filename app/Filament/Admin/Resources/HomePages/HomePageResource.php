<?php

namespace App\Filament\Admin\Resources\HomePages;

use App\Filament\Admin\Resources\HomePages\Pages\CreateHomePage;
use App\Filament\Admin\Resources\HomePages\Pages\EditHomePage;
use App\Filament\Admin\Resources\HomePages\Pages\ManageHomePage;
use App\Filament\Admin\Resources\HomePages\Pages\ViewHomePage;
use App\Models\HomePage;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section as InfolistSection;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class HomePageResource extends Resource
{
    protected static ?string $model = HomePage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Home;

    protected static ?string $navigationLabel = 'الصفحة الرئيسية';

    protected static ?string $pluralLabel = 'الصفحة الرئيسية';

    protected static ?string $label = 'الصفحة الرئيسية';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return 'المحتوى التسويقي';
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('home_pages.show');
    }

    public static function canEdit($record): bool
    {
        return auth()->user()->can('home_pages.update');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('نص العرض')
                    ->description('نص العرض الخاص')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Textarea::make('offer_text_en')
                            ->label('نص العرض بالإنجليزية')
                            ->rows(3)
                            ->nullable(),
                        Textarea::make('offer_text_ar')
                            ->label('نص العرض بالعربية')
                            ->rows(3)
                            ->nullable(),
                    ])
                    ->columns(2),
                Section::make('صور العرض')
                    ->description('صور العرض الخاص')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Repeater::make('offer_images')
                            ->label('صور العرض')
                            ->schema([
                                FileUpload::make('image')
                                    ->label('الصورة')
                                    ->image()
                                    ->directory('home-pages/offers')
                                    ->required(),
                            ])
                            ->defaultItems(0)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['image'] ?? null),
                    ]),
                Section::make('معلومات التطبيق')
                    ->description('معلومات التطبيق المحمول')
                    ->schema([
                        TextInput::make('app_title_en')
                            ->label('عنوان التطبيق بالإنجليزية')
                            ->maxLength(255)
                            ->nullable(),
                        TextInput::make('app_title_ar')
                            ->label('عنوان التطبيق بالعربية')
                            ->maxLength(255)
                            ->nullable(),
                        Textarea::make('app_description_en')
                            ->label('وصف التطبيق بالإنجليزية')
                            ->rows(3)
                            ->nullable(),
                        Textarea::make('app_description_ar')
                            ->label('وصف التطبيق بالعربية')
                            ->rows(3)
                            ->nullable(),
                    ])
                    ->columns(2),
                Section::make('صور التطبيق')
                    ->description('صور التطبيق المحمول')
                    ->schema([
                        FileUpload::make('app_main_image')
                            ->label('الصورة الرئيسية للتطبيق')
                            ->image()
                            ->imageEditor()
                            ->directory('home-pages/app')
                            ->nullable(),
                        Repeater::make('app_images')
                            ->label('صور التطبيق')
                            ->schema([
                                FileUpload::make('image')
                                    ->label('الصورة')
                                    ->image()
                                    ->directory('home-pages/app')
                                    ->required(),
                            ])
                            ->defaultItems(0)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['image'] ?? null),
                    ])->collapsible()->collapsed(),
                Section::make('الصور الرئيسية')
                    ->description('الصور الرئيسية للصفحة')
                    ->schema([
                        Repeater::make('main_images')
                            ->label('الصور الرئيسية')
                            ->schema([
                                FileUpload::make('image')
                                    ->label('الصورة')
                                    ->image()
                                    ->directory('home-pages/main')
                                    ->required(),
                            ])
                            ->defaultItems(0)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['image'] ?? null),
                    ])->collapsible()->collapsed(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                InfolistSection::make('نص العرض')
                    ->schema([
                        TextEntry::make('offer_text_en')
                            ->label('نص العرض بالإنجليزية')
                            ->wrap(),
                        TextEntry::make('offer_text_ar')
                            ->label('نص العرض بالعربية')
                            ->wrap(),
                    ])
                    ->columns(2),
                InfolistSection::make('صور العرض')
                    ->schema([
                        \Filament\Infolists\Components\RepeatableEntry::make('offer_images_for_display')
                            ->label('صور العرض')
                            ->schema([
                                \Filament\Infolists\Components\ImageEntry::make('path')
                                    ->label('الصورة')
                                    ->disk('public')
                                    ->size(150)
                                    ->square()
                                    ->extraImgAttributes(['class' => 'rounded-lg object-cover shadow-md']),
                            ])
                            ->columns(3)
                            ->grid(3)
                            ->contained(false)
                            ->hidden(fn ($record) => empty($record->offer_images_for_display)),
                    ]),
                InfolistSection::make('معلومات التطبيق')
                    ->schema([
                        TextEntry::make('app_title_en')
                            ->label('عنوان التطبيق بالإنجليزية'),
                        TextEntry::make('app_title_ar')
                            ->label('عنوان التطبيق بالعربية'),
                        TextEntry::make('app_description_en')
                            ->label('وصف التطبيق بالإنجليزية')
                            ->wrap(),
                        TextEntry::make('app_description_ar')
                            ->label('وصف التطبيق بالعربية')
                            ->wrap(),
                    ])
                    ->columns(2),
                InfolistSection::make('صور التطبيق')
                    ->schema([
                        ImageEntry::make('app_main_image')
                            ->label('الصورة الرئيسية للتطبيق')
                            ->height(200),
                        \Filament\Infolists\Components\RepeatableEntry::make('app_images_for_display')
                            ->label('صور التطبيق')
                            ->schema([
                                \Filament\Infolists\Components\ImageEntry::make('path')
                                    ->label('الصورة')
                                    ->disk('public')
                                    ->size(150)
                                    ->square()
                                    ->extraImgAttributes(['class' => 'rounded-lg object-cover shadow-md']),
                            ])
                            ->columns(3)
                            ->grid(3)
                            ->contained(false)
                            ->hidden(fn ($record) => empty($record->app_images_for_display)),
                    ]),
                InfolistSection::make('الصور الرئيسية')
                    ->schema([
                        \Filament\Infolists\Components\RepeatableEntry::make('main_images_for_display')
                            ->label('الصور الرئيسية')
                            ->schema([
                                \Filament\Infolists\Components\ImageEntry::make('path')
                                    ->label('الصورة')
                                    ->disk('public')
                                    ->size(150)
                                    ->square()
                                    ->extraImgAttributes(['class' => 'rounded-lg object-cover shadow-md']),
                            ])
                            ->columns(3)
                            ->grid(3)
                            ->contained(false)
                            ->hidden(fn ($record) => empty($record->main_images_for_display)),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('app_main_image')
                    ->circular()
                    ->label('الصورة الرئيسية'),
                TextColumn::make('app_title')
                    ->label('عنوان التطبيق')
                    ->limit(50)
                    ->wrap(),
                TextColumn::make('offer_text')
                    ->label('نص العرض')
                    ->limit(50)
                    ->wrap()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label('تاريخ الإنشاء')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                // No DeleteAction - deletion is not allowed for single record
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageHomePage::route('/'),
            'create' => CreateHomePage::route('/create'),
            'edit' => EditHomePage::route('/{record}/edit'),
        ];
    }
}
