<?php

namespace App\Filament\Admin\Resources\Pages;

use App\Filament\Admin\Resources\Pages\Pages\CreatePage;
use App\Filament\Admin\Resources\Pages\Pages\EditPage;
use App\Filament\Admin\Resources\Pages\Pages\ListPages;
use App\Filament\Admin\Resources\Pages\Pages\ViewPage;
use App\Models\Page;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Mockery\Matcher\Not;

class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::DocumentText;

    protected static ?string $navigationLabel = 'الصفحات';

    protected static ?string $pluralLabel = 'الصفحات';

    protected static ?string $label = 'صفحة';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return 'المحتوى التسويقي';
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('pages.show');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->can('pages.create');
    }

    public static function canEdit($record): bool
    {
        return auth()->user()->can('pages.update');
    }

    public static function canDelete($record): bool
    {
        return auth()->user()->can('pages.delete');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('المعلومات الأساسية')
                    ->schema([
                        FileUpload::make('banner')
                            ->image()
                            ->imageEditor()
                            ->nullable()
                            ->label('البانر'),
                        TextInput::make('slug')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->label('الرابط')
                            ->helperText('سيتم استخدامه في عنوان URL - يعمل تلقائياً على الاسم بالإنجليزية')
                            ->disabled() // Cannot edit slug if protected
                            ->dehydrated(fn ($state, $record) => $record?->can_delete !== false),
                        TextInput::make('order')
                            ->numeric()
                            ->default(0)
                            ->label('الترتيب'),
                        Toggle::make('is_active')
                            ->default(true)
                            ->label('مفعل'),
                    ])
                    ->columns(2)
                    ->collapsed(),

                Section::make('الاسم')
                    ->schema([
                        TextInput::make('name_ar')
                            ->required()
                            ->label('الاسم بالعربية'),
                        TextInput::make('name_en')
                            ->required()
                            ->label('الاسم بالإنجليزية')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, $set, $record) {
                                if (!$record && $state) {
                                    $set('slug', Str::slug($state));
                                }
                            }),
                    ])
                    ->columns(2)
                    ->collapsed(),

                Section::make('العنوان')
                    ->schema([
                        TextInput::make('title_ar')
                            ->required()
                            ->label('العنوان بالعربية'),
                        TextInput::make('title_en')
                            ->required()
                            ->label('العنوان بالإنجليزية'),
                    ])
                    ->columns(2)
                    ->collapsed(),

                Section::make('الوصف القصير')
                    ->schema([
                        Textarea::make('short_description_ar')
                            ->rows(3)
                            ->label('الوصف القصير بالعربية'),
                        Textarea::make('short_description_en')
                            ->rows(3)
                            ->label('الوصف القصير بالإنجليزية'),
                    ])
                    ->columns(2)
                    ->collapsed(),
                Section::make('تحسين محركات البحث (SEO)')
                    ->schema([
                        Textarea::make('meta_description_ar')
                            ->rows(3)
                            ->label('وصف الميتا بالعربية'),
                        Textarea::make('meta_description_en')
                            ->rows(3)
                            ->label('وصف الميتا بالإنجليزية'),
                        Textarea::make('meta_keywords_ar')
                            ->rows(2)
                            ->label('كلمات مفتاحية بالعربية')
                            ->helperText('افصل بين الكلمات بفاصلة'),
                        Textarea::make('meta_keywords_en')
                            ->rows(2)
                            ->label('كلمات مفتاحية بالإنجليزية')
                            ->helperText('افصل بين الكلمات بفاصلة'),
                    ])
                    ->columns(2)
                    ->collapsed(),

                Section::make(' الوصف الكامل بالعربية')
                    ->schema([
                        RichEditor::make('description_ar')

                            ->fileAttachmentsDirectory('pages/attachments')
                            ->columnSpanFull()
                            ->label('الوصف بالعربية'),
                    ])->collapsed(),

                Section::make(' الوصف الكامل بالإنجليزية')
                    ->schema([
                        RichEditor::make('description_en')

                            ->fileAttachmentsDirectory('pages/attachments')
                            ->columnSpanFull()
                            ->label('الوصف بالإنجليزية'),
                    ])->collapsed()


            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                ImageEntry::make('banner')
                    ->label('البانر'),
                TextEntry::make('name')
                    ->label('الاسم'),
                TextEntry::make('name_ar')
                    ->label('الاسم بالعربية'),
                TextEntry::make('name_en')
                    ->label('الاسم بالإنجليزية'),
                TextEntry::make('slug')
                    ->label('الرابط'),
                TextEntry::make('title')
                    ->label('العنوان'),
                TextEntry::make('title_ar')
                    ->label('العنوان بالعربية'),
                TextEntry::make('title_en')
                    ->label('العنوان بالإنجليزية'),
                TextEntry::make('short_description_ar')
                    ->label('الوصف القصير بالعربية')
                    ->placeholder('-'),
                TextEntry::make('short_description_en')
                    ->label('الوصف القصير بالإنجليزية')
                    ->placeholder('-'),
                TextEntry::make('order')
                    ->label('الترتيب'),
                TextEntry::make('is_active')
                    ->badge()
                    ->color(fn ($state) => $state ? 'success' : 'danger')
                    ->formatStateUsing(fn ($state) => $state ? 'مفعل' : 'غير مفعل')
                    ->label('الحالة'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->label('تاريخ الإنشاء'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->label('تاريخ التحديث'),
                TextEntry::make('description_ar')
                    ->html()
                    ->label('الوصف بالعربية')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('description_en')
                    ->html()
                    ->label('الوصف بالإنجليزية')
                    ->placeholder('-')
                    ->columnSpanFull(),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('banner')->circular()->label('البانر'),
                TextColumn::make('name')->searchable(['name_en', 'name_ar'])->sortable()->label('الاسم'),
                TextColumn::make('name_ar')->searchable()->sortable()->label('الاسم بالعربية')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('name_en')->searchable()->sortable()->label('الاسم بالإنجليزية')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('title')->searchable(['title_en', 'title_ar'])->sortable()->label('العنوان'),
                TextColumn::make('title_ar')->searchable()->sortable()->label('العنوان بالعربية')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('title_en')->searchable()->sortable()->label('العنوان بالإنجليزية')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('slug')->searchable()->sortable()->label('الرابط'),
                TextColumn::make('order')->sortable()->label('الترتيب'),
                IconColumn::make('is_active')->boolean()->sortable()->label('مفعل'),
                TextColumn::make('created_at')->dateTime()->sortable()->label('تاريخ الإنشاء')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('order', 'asc')
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make()
                    ->visible(fn () => auth()->user()->can('pages.show')),
                EditAction::make()
                    ->visible(fn ($record) => auth()->user()->can('pages.update')), // always allow edit (we control slug inside)
                DeleteAction::make()
                    ->before(function (DeleteAction $action, $record) {
                        if ($record->can_delete === false) {
                            Notification::make()
                                ->warning()
                                ->title('لا يمكن حذف هذه الصفحة')
                                ->body('لا يمكن حذف الصفحات المحمية')
                                ->send();

                            $action->halt();
                        }
                    })
                    ->visible(fn ($record) => $record->can_delete && auth()->user()->can('pages.delete')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()->can('pages.delete'))
                        ->action(function ($records) {
                            $undeletable = $records->where('can_delete', false);

                            if ($undeletable->count()) {
                                Notification::make()
                                    ->title('تم تجاهل بعض الصفحات')
                                    ->body('لا يمكن حذف الصفحات المحمية: ' . $undeletable->pluck('title')->join(', '))
                                    ->warning()
                                    ->send();
                            }

                            $records->where('can_delete', true)->each->delete();
                        })
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPages::route('/'),
            'create' => CreatePage::route('/create'),
            'edit' => EditPage::route('/{record}/edit'),
            'view' => ViewPage::route('/{record}'),
        ];
    }

}
