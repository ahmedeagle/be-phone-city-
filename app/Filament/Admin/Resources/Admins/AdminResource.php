<?php

namespace App\Filament\Admin\Resources\Admins;

use App\Filament\Admin\Resources\Admins\Pages\CreateAdmin;
use App\Filament\Admin\Resources\Admins\Pages\EditAdmin;
use App\Filament\Admin\Resources\Admins\Pages\ListAdmins;
use App\Filament\Admin\Resources\Admins\Pages\ViewAdmin;
use App\Models\Admin;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Spatie\Permission\Models\Role;

class AdminResource extends Resource
{
    protected static ?string $model = Admin::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::UserCircle;

    protected static ?string $navigationLabel = 'المدراء';

    protected static ?string $pluralLabel = 'المدراء';

    protected static ?string $label = 'مدير';

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return 'الإعدادات والنظام';
    }

    public static function canViewAny(): bool
    {
        return auth('admin')->check() && auth('admin')->user()->can('admins.show');
    }

    public static function canCreate(): bool
    {
        return auth('admin')->check() && auth('admin')->user()->can('admins.create');
    }

    public static function canEdit($record): bool
    {
        return auth('admin')->check() && auth('admin')->user()->can('admins.update');
    }

    public static function canDelete($record): bool
    {
        return auth('admin')->check() && auth('admin')->user()->can('admins.delete');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('معلومات المدير')
                    ->description('المعلومات الأساسية للمدير')
                    ->icon('heroicon-o-user')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->label('الاسم'),

                        TextInput::make('email')
                            ->required()
                            ->email()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->label('البريد الإلكتروني'),

                        TextInput::make('password')
                            ->password()
                            ->label('كلمة المرور')
                            ->required(fn ($livewire) => $livewire instanceof CreateAdmin)
                            ->dehydrated(fn ($state) => filled($state))
                            ->helperText(fn ($livewire) => $livewire instanceof EditAdmin ? 'اتركه فارغاً إذا لم ترد تغيير كلمة المرور' : ''),
                    ])
                    ->columns(1),

                Section::make('الأدوار')
                    ->description('اختر الأدوار الممنوحة لهذا المدير')
                    ->icon('heroicon-o-shield-check')
                    ->schema([
                        CheckboxList::make('roles')
                            ->label('الأدوار')
                            ->options(function () {
                                return Role::where('guard_name', 'admin')
                                    ->orderBy('name')
                                    ->get()
                                    ->mapWithKeys(function ($role) {
                                        return [$role->id => $role->name];
                                    })
                                    ->toArray();
                            })
                            ->searchable()
                            ->bulkToggleable()
                            ->gridDirection('row')
                            ->columns(2)
                            ->descriptions(function () {
                                $roles = Role::where('guard_name', 'admin')->get();
                                $descriptions = [];
                                foreach ($roles as $role) {
                                    $permissionsCount = $role->permissions()->count();
                                    $descriptions[$role->id] = "{$permissionsCount} صلاحية";
                                }
                                return $descriptions;
                            })
                            ->disableOptionWhen(function ($value, $record) {
                                // Only disable owner checkbox if this admin is the LAST admin with owner role
                                if ($record && $record->hasRole('owner')) {
                                    $ownerRole = Role::where('name', 'owner')->where('guard_name', 'admin')->first();
                                    if ($ownerRole && $value == $ownerRole->id) {
                                        // Count how many admins have owner role
                                        $ownersCount = \App\Models\Admin::role('owner')->count();
                                        // If this is the only admin with owner role, disable it
                                        return $ownersCount === 1;
                                    }
                                }
                                return false;
                            })
                            ->helperText(function ($record) {
                                if ($record && $record->hasRole('owner')) {
                                    $ownersCount = \App\Models\Admin::role('owner')->count();
                                    if ($ownersCount === 1) {
                                        return 'لا يمكن تغيير دور المالك - هذا المدير هو الوحيد الذي لديه دور المالك';
                                    }
                                }
                                return null;
                            }),
                    ])
                    ->columns(1),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('معلومات المدير')
                    ->icon('heroicon-o-user')
                    ->schema([
                        TextEntry::make('name')
                            ->label('الاسم')
                            ->weight('bold')
                            ->size('lg'),

                        TextEntry::make('email')
                            ->label('البريد الإلكتروني')
                            ->copyable()
                            ->icon('heroicon-o-envelope'),

                        TextEntry::make('roles_count')
                            ->label('عدد الأدوار')
                            ->state(fn ($record) => $record->roles()->count())
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

                Section::make('الأدوار')
                    ->icon('heroicon-o-shield-check')
                    ->schema([
                        TextEntry::make('roles.name')
                            ->label('')
                            ->listWithLineBreaks()
                            ->badge()
                            ->color('primary')
                            ->placeholder('لا توجد أدوار'),
                    ])
                    ->columns(1),

                Section::make('الصلاحيات')
                    ->icon('heroicon-o-key')
                    ->description('جميع الصلاحيات الممنوحة من خلال الأدوار')
                    ->schema([
                        TextEntry::make('all_permissions')
                            ->label('')
                            ->state(function ($record) {
                                $permissions = $record->getAllPermissions();
                                return $permissions->map(fn ($p) => $p->name_ar ?? $p->name)->toArray();
                            })
                            ->listWithLineBreaks()
                            ->badge()
                            ->color('info')
                            ->placeholder('لا توجد صلاحيات'),
                    ])
                    ->columns(1)
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->label('الاسم')
                    ->weight('bold'),

                TextColumn::make('email')
                    ->searchable()
                    ->sortable()
                    ->label('البريد الإلكتروني')
                    ->copyable()
                    ->icon('heroicon-o-envelope'),

                TextColumn::make('roles.name')
                    ->label('الأدوار')
                    ->badge()
                    ->color('primary')
                    ->separator(',')
                    ->limit(3)
                    ->tooltip(function ($record) {
                        $roles = $record->roles->pluck('name')->toArray();
                        return implode(', ', $roles);
                    }),

                TextColumn::make('roles_count')
                    ->counts('roles')
                    ->sortable()
                    ->label('عدد الأدوار')
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
                EditAction::make(),
                DeleteAction::make()
                    ->visible(function ($record) {
                        // Check if user has delete permission
                        if (!auth('admin')->check() || !auth('admin')->user()->can('admins.delete')) {
                            return false;
                        }
                        // Only hide delete if this is the last admin with owner role
                        if ($record->hasRole('owner')) {
                            $ownersCount = \App\Models\Admin::role('owner')->count();
                            return $ownersCount > 1;
                        }
                        return true;
                    })
                    ->requiresConfirmation()
                    ->disabled(function ($record) {
                        // Only disable delete if this is the last admin with owner role
                        if ($record->hasRole('owner')) {
                            $ownersCount = \App\Models\Admin::role('owner')->count();
                            return $ownersCount === 1;
                        }
                        return false;
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => auth('admin')->check() && auth('admin')->user()->can('admins.delete'))
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            // Check permission before allowing delete
                            if (!auth('admin')->check() || !auth('admin')->user()->can('admins.delete')) {
                                \Filament\Notifications\Notification::make()
                                    ->danger()
                                    ->title('خطأ')
                                    ->body('ليس لديك صلاحية لحذف المدراء')
                                    ->send();
                                return;
                            }

                            $ownersCount = \App\Models\Admin::role('owner')->count();
                            $filtered = $records->filter(function ($record) use ($ownersCount) {
                                // Only filter out if this is the last owner
                                if ($record->hasRole('owner') && $ownersCount === 1) {
                                    return false;
                                }
                                return true;
                            });

                            if ($filtered->count() < $records->count()) {
                                \Filament\Notifications\Notification::make()
                                    ->warning()
                                    ->title('تحذير')
                                    ->body('تم تخطي المدير الوحيد الذي لديه دور المالك')
                                    ->send();
                            }
                            $filtered->each->delete();
                            \Filament\Notifications\Notification::make()
                                ->success()
                                ->title('تم الحذف')
                                ->body('تم حذف المدراء المحددين بنجاح')
                                ->send();
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAdmins::route('/'),
            'create' => CreateAdmin::route('/create'),
            'edit' => EditAdmin::route('/{record}/edit'),
            'view' => ViewAdmin::route('/{record}'),
        ];
    }
}
