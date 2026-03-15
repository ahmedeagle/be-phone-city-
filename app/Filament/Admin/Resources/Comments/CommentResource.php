<?php

namespace App\Filament\Admin\Resources\Comments;

use App\Filament\Admin\Resources\Comments\Pages\CreateComment;
use App\Filament\Admin\Resources\Comments\Pages\EditComment;
use App\Filament\Admin\Resources\Comments\Pages\ListComments;
use App\Filament\Admin\Resources\Comments\Pages\ViewComment;
use App\Models\Blog;
use App\Models\Comment;
use App\Models\User;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CommentResource extends Resource
{
    protected static ?string $model = Comment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ChatBubbleLeftRight;

    protected static ?string $navigationLabel = 'التعليقات';

    protected static ?string $pluralLabel = 'التعليقات';

    protected static ?string $label = 'تعليق';

    protected static ?int $navigationSort = 4;

    public static function getNavigationGroup(): ?string
    {
        return 'الدعم الفني';
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('comments.show');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->can('comments.create');
    }

    public static function canEdit($record): bool
    {
        return auth()->user()->can('comments.update');
    }

    public static function canDelete($record): bool
    {
        return auth()->user()->can('comments.delete');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('المعلومات الأساسية')
                    ->schema([
                        Select::make('blog_id')
                            ->label('المقال')
                            ->relationship('blog', 'title_en')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->reactive()
                            ->afterStateUpdated(function ($state, $set) {
                                // Clear parent_id if blog changes
                                $set('parent_id', null);
                            }),
                        Select::make('user_id')
                            ->label('المستخدم')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->reactive()
                            ->afterStateUpdated(function ($state, $set) {
                                // Clear guest fields if user is selected
                                if ($state) {
                                    $set('guest_name', null);
                                    $set('guest_email', null);
                                }
                            }),
                        Select::make('parent_id')
                            ->label('التعليق الأب')
                            ->relationship('parent', 'content', function ($query, $get) {
                                $blogId = $get('blog_id');
                                if ($blogId) {
                                    $query->where('blog_id', $blogId);
                                }
                                return $query;
                            })
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->helperText('اختر تعليقاً أب إذا كان هذا رداً على تعليق آخر'),
                        Toggle::make('is_approved')
                            ->default(false)
                            ->label('موافق عليه')
                            ->helperText('سيتم عرض التعليق للجمهور عند الموافقة عليه'),
                    ])
                    ->columns(2)
                    ->collapsed(),

                Section::make('معلومات الضيف (إذا لم يكن المستخدم مسجل دخول)')
                    ->schema([
                        TextInput::make('guest_name')
                            ->label('اسم الضيف')
                            ->maxLength(255)
                            ->visible(fn ($get) => !$get('user_id'))
                            ->required(fn ($get) => !$get('user_id')),
                        TextInput::make('guest_email')
                            ->label('بريد الضيف')
                            ->email()
                            ->maxLength(255)
                            ->visible(fn ($get) => !$get('user_id'))
                            ->required(fn ($get) => !$get('user_id')),
                    ])
                    ->columns(2)
                    ->collapsed()
                    ->visible(fn ($get) => !$get('user_id')),

                Section::make('محتوى التعليق')
                    ->schema([
                        RichEditor::make('content')
                            ->required()
                            ->label('المحتوى')
                            ->maxLength(5000)
                            ->columnSpanFull(),
                    ])
                    ->collapsed(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('blog.title_en')
                    ->label('المقال'),
                TextEntry::make('user.name')
                    ->label('المستخدم')
                    ->placeholder('-'),
                TextEntry::make('guest_name')
                    ->label('اسم الضيف')
                    ->placeholder('-'),
                TextEntry::make('guest_email')
                    ->label('بريد الضيف')
                    ->placeholder('-'),
                TextEntry::make('commenter_name')
                    ->label('اسم المعلق')
                    ->getStateUsing(fn ($record) => $record->commenter_name),
                TextEntry::make('is_guest_comment')
                    ->badge()
                    ->color(fn ($state) => $state ? 'warning' : 'success')
                    ->formatStateUsing(fn ($state) => $state ? 'ضيف' : 'مستخدم مسجل')
                    ->label('نوع المعلق'),
                TextEntry::make('content')
                    ->html()
                    ->label('المحتوى')
                    ->columnSpanFull(),
                TextEntry::make('is_approved')
                    ->badge()
                    ->color(fn ($state) => $state ? 'success' : 'danger')
                    ->formatStateUsing(fn ($state) => $state ? 'موافق عليه' : 'في الانتظار')
                    ->label('الحالة'),
                TextEntry::make('parent.content')
                    ->label('التعليق الأب')
                    ->placeholder('-')
                    ->limit(50),
                TextEntry::make('is_reply')
                    ->badge()
                    ->color(fn ($state) => $state ? 'info' : 'gray')
                    ->formatStateUsing(fn ($state) => $state ? 'رد' : 'تعليق رئيسي')
                    ->label('النوع'),
                TextEntry::make('approved_replies_count')
                    ->label('عدد الردود الموافق عليها')
                    ->numeric(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->label('تاريخ الإنشاء'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->label('تاريخ التحديث'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('blog.title_en')
                    ->searchable()
                    ->sortable()
                    ->label('المقال')
                    ->limit(30),
                TextColumn::make('commenter_name')
                    ->label('المعلق')
                    ->getStateUsing(fn ($record) => $record->commenter_name)
                    ->searchable(['user.name', 'guest_name']),
                TextColumn::make('content')
                    ->html()
                    ->label('المحتوى')
                    ->limit(50)
                    ->wrap(),
                IconColumn::make('is_approved')
                    ->boolean()
                    ->sortable()
                    ->label('موافق عليه'),
                IconColumn::make('is_guest_comment')
                    ->boolean()
                    ->label('ضيف')
                    ->getStateUsing(fn ($record) => $record->is_guest_comment)
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_reply')
                    ->boolean()
                    ->label('رد')
                    ->getStateUsing(fn ($record) => $record->is_reply)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('parent.content')
                    ->label('التعليق الأب')
                    ->limit(30)
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label('تاريخ الإنشاء')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('is_approved')
                    ->label('الحالة')
                    ->options([
                        true => 'موافق عليه',
                        false => 'في الانتظار',
                    ]),
                SelectFilter::make('blog_id')
                    ->label('المقال')
                    ->relationship('blog', 'title_en')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('is_guest_comment')
                    ->label('نوع المعلق')
                    ->options([
                        true => 'ضيف',
                        false => 'مستخدم مسجل',
                    ])
                    ->query(function ($query, $state) {
                        if ($state === true) {
                            return $query->whereNull('user_id');
                        } elseif ($state === false) {
                            return $query->whereNotNull('user_id');
                        }
                        return $query;
                    }),
                SelectFilter::make('is_reply')
                    ->label('النوع')
                    ->options([
                        true => 'رد',
                        false => 'تعليق رئيسي',
                    ])
                    ->query(function ($query, $state) {
                        if ($state === true) {
                            return $query->whereNotNull('parent_id');
                        } elseif ($state === false) {
                            return $query->whereNull('parent_id');
                        }
                        return $query;
                    }),
            ])
            ->recordActions([
                ViewAction::make()
                    ->visible(fn () => auth()->user()->can('comments.show')),
                EditAction::make()
                    ->visible(fn () => auth()->user()->can('comments.update')),
                DeleteAction::make()
                    ->visible(fn () => auth()->user()->can('comments.delete'))
                    ->requiresConfirmation(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()->can('comments.delete')),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListComments::route('/'),
            'create' => CreateComment::route('/create'),
            'edit' => EditComment::route('/{record}/edit'),
            'view' => ViewComment::route('/{record}'),
        ];
    }
}
