<?php

namespace App\Filament\Admin\Resources\Roles;

use App\Filament\Admin\Resources\Roles\Pages\CreateRole;
use App\Filament\Admin\Resources\Roles\Pages\EditRole;
use App\Filament\Admin\Resources\Roles\Pages\ListRoles;
use App\Filament\Admin\Resources\Roles\Pages\ViewRole;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ShieldCheck;

    protected static ?string $navigationLabel = 'الأدوار';

    protected static ?string $pluralLabel = 'الأدوار';

    protected static ?string $label = 'دور';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return 'الإعدادات والنظام';
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('roles.show');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->can('roles.create');
    }

    public static function canEdit($record): bool
    {
        return auth()->user()->can('roles.update');
    }

    public static function canDelete($record): bool
    {
        return auth()->user()->can('roles.delete');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('معلومات الدور')
                    ->description('المعلومات الأساسية للدور')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->label('اسم الدور')
                            ->helperText('اسم الدور باللغة الإنجليزية (مثال: admin, manager)')
                            ->disabled(fn ($record) => $record && $record->name === 'owner')
                            ->dehydrated(fn ($record) => !$record || $record->name !== 'owner'),
                    ])
                    ->columns(1),

                Section::make('الصلاحيات')
                    ->description('اختر الصلاحيات الممنوحة لهذا الدور')
                    ->icon('heroicon-o-key')
                    ->schema([
                        CheckboxList::make('permissions')
                            ->label('الصلاحيات')
                            ->options(function () {
                                return Permission::where('guard_name', 'admin')
                                    ->orderBy('name')
                                    ->get()
                                    ->mapWithKeys(function ($permission) {
                                        return [$permission->id => $permission->name_ar ?? $permission->name];
                                    })
                                    ->toArray();
                            })
                            ->searchable()
                            ->bulkToggleable()
                            ->gridDirection('row')
                            ->columns(2)
                            ->descriptions(function () {
                                $permissions = Permission::where('guard_name', 'admin')->get();
                                $descriptions = [];
                                foreach ($permissions as $permission) {
                                    $descriptions[$permission->id] = $permission->name;
                                }
                                return $descriptions;
                            })
                            ->disabled(fn ($record) => $record && $record->name === 'owner')
                            ->dehydrated(fn ($record) => !$record || $record->name !== 'owner'),
                    ])
                    ->columns(1),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('معلومات الدور')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        TextEntry::make('name')
                            ->label('اسم الدور')
                            ->badge()
                            ->color(fn ($record) => $record->name === 'owner' ? 'danger' : 'primary'),

                        TextEntry::make('guard_name')
                            ->label('نوع الحماية')
                            ->badge()
                            ->color('gray'),

                        TextEntry::make('permissions_count')
                            ->label('عدد الصلاحيات')
                            ->state(fn ($record) => $record->permissions()->count())
                            ->badge()
                            ->color('success'),

                        TextEntry::make('created_at')
                            ->label('تاريخ الإنشاء')
                            ->dateTime(),

                        TextEntry::make('updated_at')
                            ->label('تاريخ التحديث')
                            ->dateTime(),
                    ])
                    ->columns(2),

                Section::make('الصلاحيات')
                    ->icon('heroicon-o-key')
                    ->schema([
                        TextEntry::make('permissions.name_ar')
                            ->label('')
                            ->listWithLineBreaks()
                            ->badge()
                            ->color('info')
                            ->placeholder('لا توجد صلاحيات'),
                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->label('اسم الدور')
                    ->badge()
                    ->color(fn ($record) => $record->name === 'owner' ? 'danger' : 'primary'),

                TextColumn::make('guard_name')
                    ->label('نوع الحماية')
                    ->badge()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('permissions_count')
                    ->counts('permissions')
                    ->sortable()
                    ->label('عدد الصلاحيات')
                    ->badge()
                    ->color('success'),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label('تاريخ الإنشاء')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn ($record) => $record->name !== 'owner'),
                DeleteAction::make()
                    ->visible(fn ($record) => $record->name !== 'owner')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        if ($record->name === 'owner') {
                            Notification::make()
                                ->danger()
                                ->title('خطأ')
                                ->body('لا يمكن حذف دور المالك')
                                ->send();
                            return;
                        }
                        $record->delete();
                        Notification::make()
                            ->success()
                            ->title('تم الحذف')
                            ->body('تم حذف الدور بنجاح')
                            ->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $filtered = $records->filter(fn ($record) => $record->name !== 'owner');
                            if ($filtered->count() < $records->count()) {
                                Notification::make()
                                    ->warning()
                                    ->title('تحذير')
                                    ->body('تم تخطي دور المالك')
                                    ->send();
                            }
                            $filtered->each->delete();
                            Notification::make()
                                ->success()
                                ->title('تم الحذف')
                                ->body('تم حذف الأدوار المحددة بنجاح')
                                ->send();
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRoles::route('/'),
            'create' => CreateRole::route('/create'),
            'edit' => EditRole::route('/{record}/edit'),
            'view' => ViewRole::route('/{record}'),
        ];
    }
}
