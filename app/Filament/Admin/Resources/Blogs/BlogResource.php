<?php

namespace App\Filament\Admin\Resources\Blogs;

use App\Filament\Admin\Resources\Blogs\Pages\CreateBlog;
use App\Filament\Admin\Resources\Blogs\Pages\EditBlog;
use App\Filament\Admin\Resources\Blogs\Pages\ListBlogs;
use App\Filament\Admin\Resources\Blogs\Pages\ViewBlog;
use App\Models\Blog;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class BlogResource extends Resource
{
    protected static ?string $model = Blog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::DocumentText;

    protected static ?string $navigationLabel = 'المدونة';

    protected static ?string $pluralLabel = 'المدونات';

    protected static ?string $label = 'مقال';

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): ?string
    {
        return 'المحتوى التسويقي';
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('blogs.show');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->can('blogs.create');
    }

    public static function canEdit($record): bool
    {
        return auth()->user()->can('blogs.update');
    }

    public static function canDelete($record): bool
    {
        return auth()->user()->can('blogs.delete');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('المعلومات الأساسية')
                    ->schema([
                        FileUpload::make('featured_image')
                            ->image()
                            ->imageEditor()
                            ->directory('blogs/featured')
                            ->disk('public')
                            ->visibility('public')
                            ->nullable()
                            ->label('الصورة الرئيسية'),
                        Toggle::make('is_published')
                            ->default(false)
                            ->label('منشور')
                            ->helperText('سيتم عرض المقال للجمهور عند تفعيله'),
                        DateTimePicker::make('published_at')
                            ->label('تاريخ النشر')
                            ->nullable()
                            ->default(now())
                            ->visible(fn ($get) => $get('is_published')),
                        Toggle::make('allow_comments')
                            ->default(true)
                            ->label('السماح بالتعليقات'),
                    ])
                    ->columns(2)
                    ->collapsed(),

                Section::make('العنوان')
                    ->schema([
                        TextInput::make('title_ar')
                            ->required()
                            ->label('العنوان بالعربية')
                            ->maxLength(255),
                        TextInput::make('title_en')
                            ->required()
                            ->label('العنوان بالإنجليزية')
                            ->maxLength(255),
                    ])
                    ->columns(2)
                    ->collapsed(),

                Section::make('الوصف القصير')
                    ->schema([
                        Textarea::make('short_description_ar')
                            ->rows(3)
                            ->label('الوصف القصير بالعربية')
                            ->maxLength(500),
                        Textarea::make('short_description_en')
                            ->rows(3)
                            ->label('الوصف القصير بالإنجليزية')
                            ->maxLength(500),
                    ])
                    ->columns(2)
                    ->collapsed(),

                Section::make('المحتوى بالعربية')
                    ->schema([
                        RichEditor::make('content_ar')
                            ->fileAttachmentsDirectory('blogs/attachments')
                            ->columnSpanFull()
                            ->label('المحتوى بالعربية'),
                    ])
                    ->collapsed(),

                Section::make('المحتوى بالإنجليزية')
                    ->schema([
                        RichEditor::make('content_en')
                            ->fileAttachmentsDirectory('blogs/attachments')
                            ->columnSpanFull()
                            ->label('المحتوى بالإنجليزية'),
                    ])
                    ->collapsed(),

                Section::make('تحسين محركات البحث (SEO)')
                    ->schema([
                        Textarea::make('meta_description_ar')
                            ->rows(3)
                            ->label('وصف الميتا بالعربية')
                            ->maxLength(160)
                            ->helperText('وصف قصير للمقال (حوالي 150-160 حرف)'),
                        Textarea::make('meta_description_en')
                            ->rows(3)
                            ->label('وصف الميتا بالإنجليزية')
                            ->maxLength(160)
                            ->helperText('وصف قصير للمقال (حوالي 150-160 حرف)'),
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
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                ImageEntry::make('featured_image')
                    ->label('الصورة الرئيسية')
                    ->state(function ($record) {
                        if (!$record || !$record->featured_image) {
                            return null;
                        }
                        // Check if it's already a full URL
                        if (filter_var($record->featured_image, FILTER_VALIDATE_URL)) {
                            return $record->featured_image;
                        }
                        // Use asset() to get the correct URL
                        return asset('storage/' . $record->featured_image);
                    })
                    ->height(200),
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
                TextEntry::make('is_published')
                    ->badge()
                    ->color(fn ($state) => $state ? 'success' : 'danger')
                    ->formatStateUsing(fn ($state) => $state ? 'منشور' : 'مسودة')
                    ->label('الحالة'),
                TextEntry::make('published_at')
                    ->dateTime()
                    ->label('تاريخ النشر')
                    ->placeholder('-'),
                TextEntry::make('views_count')
                    ->label('عدد المشاهدات')
                    ->numeric(),
                TextEntry::make('allow_comments')
                    ->badge()
                    ->color(fn ($state) => $state ? 'success' : 'danger')
                    ->formatStateUsing(fn ($state) => $state ? 'مسموح' : 'غير مسموح')
                    ->label('التعليقات'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->label('تاريخ الإنشاء'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->label('تاريخ التحديث'),
                TextEntry::make('content_ar')
                    ->html()
                    ->label('المحتوى بالعربية')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('content_en')
                    ->html()
                    ->label('المحتوى بالإنجليزية')
                    ->placeholder('-')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('featured_image')
                    ->label('الصورة')
                    ->disk('public')
                    ->circular(false)
                    ->size(60),
                TextColumn::make('title')
                    ->searchable(['title_en', 'title_ar'])
                    ->sortable()
                    ->label('العنوان')
                    ->limit(50),
                IconColumn::make('is_published')
                    ->boolean()
                    ->sortable()
                    ->label('منشور'),
                TextColumn::make('published_at')
                    ->dateTime()
                    ->sortable()
                    ->label('تاريخ النشر')
                    ->toggleable(),
                TextColumn::make('views_count')
                    ->sortable()
                    ->label('المشاهدات')
                    ->numeric(),
                IconColumn::make('allow_comments')
                    ->boolean()
                    ->label('التعليقات')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label('تاريخ الإنشاء')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('is_published')
                    ->label('الحالة')
                    ->options([
                        true => 'منشور',
                        false => 'مسودة',
                    ]),
                SelectFilter::make('allow_comments')
                    ->label('التعليقات')
                    ->options([
                        true => 'مسموح',
                        false => 'غير مسموح',
                    ]),
            ])
            ->recordActions([
                ViewAction::make()
                    ->visible(fn () => auth()->user()->can('blogs.show')),
                EditAction::make()
                    ->visible(fn () => auth()->user()->can('blogs.update')),
                DeleteAction::make()
                    ->visible(fn () => auth()->user()->can('blogs.delete'))
                    ->requiresConfirmation(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()->can('blogs.delete')),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBlogs::route('/'),
            'create' => CreateBlog::route('/create'),
            'edit' => EditBlog::route('/{record}/edit'),
            'view' => ViewBlog::route('/{record}'),
        ];
    }
}
