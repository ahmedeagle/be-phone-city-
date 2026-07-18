<?php

namespace App\Filament\Admin\Resources\Categories;

use App\Filament\Admin\Resources\Categories\Pages\CreateCategory;
use App\Filament\Admin\Resources\Categories\Pages\EditCategory;
use App\Filament\Admin\Resources\Categories\Pages\ListCategories;
use App\Filament\Admin\Resources\Categories\Pages\ViewCategory;
use App\Models\Category;
use BackedEnum;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Squares2x2;

    protected static ?string $navigationLabel = 'التصنيفات';

    protected static ?string $pluralLabel = 'التصنيفات';

    protected static ?string $label = 'تصنيف';

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return 'إدارة المتجر';
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('categories.show');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->can('categories.create');
    }

    public static function canEdit($record): bool
    {
        return auth()->user()->can('categories.update');
    }

    public static function canDelete($record): bool
    {
        return auth()->user()->can('categories.delete');
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
                Select::make('parent_id')
                    ->relationship('parent', 'name_ar')
                    ->searchable()
                    ->preload()
                    ->nullable()
                    ->label('التصنيف الأب'),
                FileUpload::make('image')
                    ->image()
                    ->imageEditor()
                    ->nullable()
                    ->label('الصورة'),
                FileUpload::make('icon')
                    ->image()
                    ->imageEditor()
                    ->nullable()
                    ->label('الأيقونة'),
                Toggle::make('is_trademark')
                    ->label('علامة تجارية')
                    ->default(false),

                Toggle::make('is_bank_transfer')
                    ->label('تحويل بنكي فقط')
                    ->default(false),

                Toggle::make('is_installment')
                    ->label('قسم تقسيط اموال وتابي')
                    ->default(false),

                Toggle::make('is_madfu')
                    ->label('قسم تقسيط مدفوع')
                    ->default(false),
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
                TextEntry::make('parent.name_ar')
                    ->placeholder('-')
                    ->label('التصنيف الأب'),
                ImageEntry::make('image')
                    ->label('الصورة'),
                ImageEntry::make('icon')
                    ->label('الأيقونة'),
                TextEntry::make('is_trademark')
                    ->state(fn ($record) => $record->is_trademark ? 'نعم' : 'لا')
                    ->label('علامة تجارية'),
                TextEntry::make('is_bank_transfer')
                    ->state(fn ($record) => $record->is_bank_transfer ? 'نعم' : 'لا')
                    ->label('تحويل بنكي فقط'),
                TextEntry::make('is_installment')
                    ->state(fn ($record) => $record->is_installment ? 'نعم' : 'لا')
                    ->label('قسم تقسيط اموال وتابي'),
                TextEntry::make('is_madfu')
                    ->state(fn ($record) => $record->is_madfu ? 'نعم' : 'لا')
                    ->label('قسم تقسيط مدفوع'),
                TextEntry::make('children_count')
                    ->state(fn ($record) => $record->children()->count())
                    ->label('عدد التصنيفات الفرعية'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-')
                    ->label('تاريخ الإنشاء'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image')
                    ->circular()
                    ->label('الصورة'),
                ImageColumn::make('icon')
                    ->circular()
                    ->label('الأيقونة'),
                TextColumn::make('name_ar')
                    ->searchable()
                    ->sortable()
                    ->label('الاسم بالعربية'),
                TextColumn::make('name_en')
                    ->searchable()
                    ->sortable()
                    ->label('الاسم بالإنجليزية'),
                TextColumn::make('parent.name_ar')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-')
                    ->label('التصنيف الأب'),
                TextColumn::make('children_count')
                    ->counts('children')
                    ->sortable()
                    ->label('التصنيفات الفرعية'),
                IconColumn::make('is_trademark')
                    ->boolean()
                    ->sortable()
                    ->label('علامة تجارية'),
                IconColumn::make('is_bank_transfer')
                    ->boolean()
                    ->sortable()
                    ->label('تحويل بنكي فقط'),
                IconColumn::make('is_installment')
                    ->boolean()
                    ->sortable()
                    ->label('قسم تقسيط اموال وتابي'),
                IconColumn::make('is_madfu')
                    ->boolean()
                    ->sortable()
                    ->label('قسم تقسيط مدفوع'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label('تاريخ الإنشاء')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('is_trademark')
                    ->options([
                        true => 'نعم',
                        false => 'لا',
                    ])
                    ->label('علامة تجارية'),
            ])
            ->recordActions([
                ViewAction::make()
                    ->visible(fn () => auth()->user()->can('categories.show')),
                EditAction::make()
                    ->visible(fn () => auth()->user()->can('categories.update')),
                DeleteAction::make()
                    ->visible(fn () => auth()->user()->can('categories.delete'))
                    ->requiresConfirmation(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()->can('categories.delete')),
                    BulkAction::make('markAsTrademark')
                        ->label('تعيين كعلامة تجارية')
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->update(['is_trademark' => true]);
                            }
                        })
                        ->requiresConfirmation(),
                    BulkAction::make('unmarkAsTrademark')
                        ->label('إلغاء كونها علامة تجارية')
                        ->icon('heroicon-o-x-circle')
                        ->color('gray')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->update(['is_trademark' => false]);
                            }
                        })
                        ->requiresConfirmation(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCategories::route('/'),
            'create' => CreateCategory::route('/create'),
            'edit' => EditCategory::route('/{record}/edit'),
            'view' => ViewCategory::route('/{record}'),
        ];
    }
}
