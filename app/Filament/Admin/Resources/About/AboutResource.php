<?php

namespace App\Filament\Admin\Resources\About;

use App\Filament\Admin\Resources\About\Pages\CreateAbout;
use App\Filament\Admin\Resources\About\Pages\EditAbout;
use App\Filament\Admin\Resources\About\Pages\ManageAbout;
use App\Filament\Admin\Resources\About\Pages\ViewAbout;
use App\Models\About;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
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

class AboutResource extends Resource
{
    protected static ?string $model = About::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::InformationCircle;

    protected static ?string $navigationLabel = 'معلومات عنا';

    protected static ?string $pluralLabel = 'معلومات عنا';

    protected static ?string $label = 'معلومات عنا';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return 'المحتوى التسويقي';
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('about.show');
    }

    public static function canEdit($record): bool
    {
        return auth()->user()->can('about.update');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('معلومات الموقع')
                    ->description('معلومات عامة عن الموقع')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        RichEditor::make('about_website_en')
                            ->label('وصف الموقع بالإنجليزية')
                            ->required(),
                        RichEditor::make('about_website_ar')
                            ->label('وصف الموقع بالعربية')
                            ->required(),
                    ]),
                Section::make('معلومات عنا')
                    ->description('معلومات عن الشركة')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        RichEditor::make('about_us_en')
                            ->label('معلومات عنا بالإنجليزية')
                            ->required(),
                        RichEditor::make('about_us_ar')
                            ->label('معلومات عنا بالعربية')
                            ->required(),
                    ]),
                Section::make('الصورة')
                    ->description('صورة الشركة أو الشعار')
                    ->schema([
                        FileUpload::make('image')
                            ->image()
                            ->imageEditor()
                            ->directory('abouts')
                            ->label('الصورة')
                            ->nullable(),
                    ]),
                Section::make('معلومات الاتصال')
                    ->description('معلومات الاتصال والعنوان')
                    ->schema([
                        Textarea::make('address_en')
                            ->label('العنوان بالإنجليزية')
                            ->rows(3)
                            ->required(),
                        Textarea::make('address_ar')
                            ->label('العنوان بالعربية')
                            ->rows(3)
                            ->required(),
                        TextInput::make('maps')
                            ->label('رابط خرائط جوجل')
                            ->url()
                            ->placeholder('https://www.google.com/maps/embed?pb=...')
                            ->nullable(),
                        TextInput::make('email')
                            ->label('البريد الإلكتروني')
                            ->email()
                            ->required(),
                        TextInput::make('phone')
                            ->label('رقم الهاتف')
                            ->tel()
                            ->required(),
                    ])
                    ->columns(2),
                Section::make('روابط التواصل الاجتماعي')
                    ->description('إضافة روابط التواصل الاجتماعي')
                    ->schema([
                        Repeater::make('social_links')
                            ->label('روابط التواصل')
                            ->schema([
                                TextInput::make('name')
                                    ->label('الاسم')
                                    ->required(),
                                TextInput::make('icon')
                                    ->label('الأيقونة')
                                    ->placeholder('fa-brands fa-facebook')
                                    ->helperText('استخدم Font Awesome أو أي مكتبة أيقونات')
                                    ->required(),
                                TextInput::make('url')
                                    ->label('الرابط')
                                    ->url()
                                    ->required(),
                            ])
                            ->columns(3)
                            ->defaultItems(0)
                            ->collapsible(),
                    ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                InfolistSection::make('معلومات الموقع')
                    ->schema([
                        TextEntry::make('about_website_en')
                            ->label('وصف الموقع بالإنجليزية'),
                        TextEntry::make('about_website_ar')
                            ->label('وصف الموقع بالعربية'),
                    ])
                    ->columns(2),
                InfolistSection::make('معلومات عنا')
                    ->schema([
                        TextEntry::make('about_us_en')
                            ->label('معلومات عنا بالإنجليزية'),
                        TextEntry::make('about_us_ar')
                            ->label('معلومات عنا بالعربية'),
                    ])
                    ->columns(2),
                InfolistSection::make('الصورة')
                    ->schema([
                        ImageEntry::make('image')
                            ->label('الصورة'),
                    ]),
                InfolistSection::make('معلومات الاتصال')
                    ->schema([
                        TextEntry::make('address_en')
                            ->label('العنوان بالإنجليزية'),
                        TextEntry::make('address_ar')
                            ->label('العنوان بالعربية'),
                        TextEntry::make('maps')
                            ->label('رابط خرائط جوجل')
                            ->placeholder('-'),
                        TextEntry::make('email')
                            ->label('البريد الإلكتروني'),
                        TextEntry::make('phone')
                            ->label('رقم الهاتف'),
                    ])
                    ->columns(2),
                InfolistSection::make('روابط التواصل الاجتماعي')
                    ->schema([
                        RepeatableEntry::make('social_links')
                            ->label('روابط التواصل')
                            ->schema([
                                TextEntry::make('name')
                                    ->label('الاسم'),
                                TextEntry::make('icon')
                                    ->label('الأيقونة'),
                                TextEntry::make('url')
                                    ->label('الرابط'),
                            ])
                            ->columns(3),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image')
                    ->circular()
                    ->label('الصورة'),
                TextColumn::make('about_website_en')
                    ->label('وصف الموقع')
                    ->limit(50)
                    ->wrap(),
                TextColumn::make('email')
                    ->label('البريد الإلكتروني')
                    ->searchable(),
                TextColumn::make('phone')
                    ->label('رقم الهاتف')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label('تاريخ الإنشاء')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                // No DeleteAction - deletion is not allowed
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageAbout::route('/'),
            'create' => CreateAbout::route('/create'),
            'edit' => EditAbout::route('/{record}/edit'),
        ];
    }
}
