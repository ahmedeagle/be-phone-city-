<?php

namespace App\Filament\Admin\Resources\Tickets;

use App\Filament\Admin\Resources\Tickets\Pages\CreateTicket;
use App\Filament\Admin\Resources\Tickets\Pages\EditTicket;
use App\Filament\Admin\Resources\Tickets\Pages\ListTickets;
use App\Filament\Admin\Resources\Tickets\Pages\ViewTicket;
use App\Models\Admin;
use App\Models\Ticket;
use App\Models\User;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TicketResource extends Resource
{
    protected static ?string $model = Ticket::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Ticket;

    protected static ?string $navigationLabel = 'التذاكر';

    protected static ?string $pluralLabel = 'التذاكر';

    protected static ?string $label = 'تذكرة';

    protected static ?int $navigationSort = 5;

    public static function getNavigationGroup(): ?string
    {
        return 'الدعم الفني';
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', \App\Models\Ticket::STATUS_PENDING)->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('tickets.show');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->can('tickets.create');
    }

    public static function canEdit($record): bool
    {
        return auth()->user()->can('tickets.update');
    }

    public static function canDelete($record): bool
    {
        return auth()->user()->can('tickets.delete');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('المعلومات الأساسية')
                    ->schema([
                        TextInput::make('ticket_number')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->label('رقم التذكرة')
                            ->disabled()
                            ->helperText('يتم إنشاؤه تلقائياً'),
                        Select::make('user_id')
                            ->label('العميل المسجل')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->helperText('اختياري - للعملاء المسجلين فقط'),
                        TextInput::make('name')
                            ->label('الاسم')
                            ->maxLength(255)
                            ->nullable()
                            ->helperText('للعملاء غير المسجلين'),
                        TextInput::make('email')
                            ->label('البريد الإلكتروني')
                            ->email()
                            ->maxLength(255)
                            ->nullable()
                            ->helperText('للعملاء غير المسجلين'),
                        TextInput::make('phone')
                            ->label('الهاتف')
                            ->tel()
                            ->maxLength(20)
                            ->nullable()
                            ->helperText('للعملاء غير المسجلين'),
                        Select::make('admin_id')
                            ->label('موظف الدعم')
                            ->relationship('admin', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->helperText('اختر موظف الدعم المسؤول عن هذه التذكرة'),
                        Select::make('status')
                            ->label('الحالة')
                            ->options([
                                Ticket::STATUS_PENDING => 'قيد الانتظار',
                                Ticket::STATUS_IN_PROGRESS => 'قيد المعالجة',
                                Ticket::STATUS_RESOLVED => 'تم الحل',
                                Ticket::STATUS_CLOSED => 'مغلق',
                            ])
                            ->default(Ticket::STATUS_PENDING)
                            ->required(),
                        Select::make('priority')
                            ->label('الأولوية')
                            ->options([
                                Ticket::PRIORITY_LOW => 'منخفض',
                                Ticket::PRIORITY_MEDIUM => 'متوسط',
                                Ticket::PRIORITY_HIGH => 'عالي',
                                Ticket::PRIORITY_URGENT => 'عاجل',
                            ])
                            ->default(Ticket::PRIORITY_MEDIUM)
                            ->required(),
                        Select::make('type')
                            ->label('النوع')
                            ->options([
                                Ticket::TYPE_SUPPORT => 'دعم فني',
                                Ticket::TYPE_COMPLAINT => 'شكوى',
                                Ticket::TYPE_INQUIRY => 'استفسار',
                                Ticket::TYPE_TECHNICAL => 'تقني',
                                Ticket::TYPE_BILLING => 'فوترة',
                                Ticket::TYPE_OTHER => 'أخرى',
                            ])
                            ->default(Ticket::TYPE_SUPPORT)
                            ->required(),
                    ])
                    ->columns(3)
                    ->collapsed(),

                Section::make('الموضوع والوصف')
                    ->schema([
                        TextInput::make('subject')
                            ->required()
                            ->label('الموضوع')
                            ->maxLength(255),
                        RichEditor::make('description')
                            ->fileAttachmentsDirectory('tickets/attachments')
                            ->columnSpanFull()
                            ->label('الوصف')
                            ->required(),
                    ])
                    ->collapsed(),

                Section::make('ملاحظات الحل')
                    ->schema([
                        Textarea::make('resolution_notes')
                            ->rows(4)
                            ->label('ملاحظات الحل')
                            ->maxLength(2000)
                            ->helperText('ملاحظات حول حل المشكلة')
                            ->visible(fn ($get) => in_array($get('status'), [Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED])),
                        DateTimePicker::make('resolved_at')
                            ->label('تاريخ الحل')
                            ->nullable()
                            ->visible(fn ($get) => in_array($get('status'), [Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED])),
                    ])
                    ->collapsed(),

                Section::make('المرفقات')
                    ->description('الصور والملفات المرفقة مع التذكرة')
                    ->icon('heroicon-o-photo')
                    ->schema([
                        Repeater::make('images')
                            ->relationship()
                            ->schema([
                                \Filament\Forms\Components\FileUpload::make('path')
                                    ->image()
                                    ->imageEditor()
                                    ->maxSize(5120)
                                    ->required()
                                    ->label('الصورة')
                                    ->directory('tickets/images')
                                    ->helperText('يفضل أن تكون الصورة بحجم 1200x800 بكسل'),

                                \Filament\Forms\Components\TextInput::make('sort_order')
                                    ->numeric()
                                    ->default(0)
                                    ->label('الترتيب')
                                    ->helperText('رقم الترتيب للصورة'),
                            ])
                            ->columns(2)
                            ->defaultItems(0)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => 'صورة ' . ($state['sort_order'] ?? 'جديدة'))
                            ->label('صور التذكرة')
                            ->addActionLabel('إضافة صورة')
                            ->reorderable('sort_order')
                            ->orderColumn('sort_order'),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('معلومات التذكرة')
                    ->schema([
                        TextEntry::make('ticket_number')
                            ->label('رقم التذكرة')
                            ->copyable()
                            ->badge()
                            ->color('primary')
                            ->size('lg')
                            ->weight('bold'),
                        TextEntry::make('created_at')
                            ->dateTime('d/m/Y H:i')
                            ->label('تاريخ الإنشاء')
                            ->icon('heroicon-o-calendar')
                            ->color('gray'),
                    ])
                    ->columns(2)
                    ->collapsible(false),

                Section::make('معلومات العميل')
                    ->schema([
                        TextEntry::make('name')
                            ->label('الاسم')
                            ->getStateUsing(function ($record) {
                                if ($record && $record->user_id && $record->user) {
                                    return $record->user->name ?? '-';
                                }
                                return $record->name ?? '-';
                            })
                            ->placeholder('-')
                            ->icon('heroicon-o-user')
                            ->size('lg')
                            ->weight('medium'),
                        TextEntry::make('email')
                            ->label('البريد الإلكتروني')
                            ->getStateUsing(function ($record) {
                                if ($record && $record->user_id && $record->user) {
                                    return $record->user->email ?? '-';
                                }
                                return $record->email ?? '-';
                            })
                            ->placeholder('-')
                            ->icon('heroicon-o-envelope')
                            ->copyable(),
                        TextEntry::make('phone')
                            ->label('الهاتف')
                            ->placeholder('-')
                            ->icon('heroicon-o-phone')
                            ->copyable(),
                        TextEntry::make('user_id')
                            ->label('نوع العميل')
                            ->formatStateUsing(fn ($record) => $record && $record->user_id ? 'عميل مسجل' : 'زائر')
                            ->badge()
                            ->color(fn ($record) => $record && $record->user_id ? 'success' : 'warning')
                            ->icon(fn ($record) => $record && $record->user_id ? 'heroicon-o-check-circle' : 'heroicon-o-user-circle'),
                    ])
                    ->columns(2)
                    ->collapsible(false)
                    ->icon('heroicon-o-user-group'),

                Section::make('الرسالة')
                    ->schema([
                        TextEntry::make('description')
                            ->label('نص الرسالة')
                            ->html()
                            ->columnSpanFull()
                            ->placeholder('لا توجد رسالة')
                            ->prose(),
                    ])
                    ->collapsible(false)
                    ->icon('heroicon-o-chat-bubble-left-right'),

                Section::make('ملاحظات الحل')
                    ->schema([
                        TextEntry::make('resolution_notes')
                            ->label('ملاحظات الحل')
                            ->placeholder('لا توجد ملاحظات')
                            ->columnSpanFull()
                            ->visible(fn ($record) => $record && $record->resolution_notes),
                        TextEntry::make('resolved_at')
                            ->dateTime('d/m/Y H:i')
                            ->label('تاريخ الحل')
                            ->placeholder('-')
                            ->visible(fn ($record) => $record && $record->resolved_at),
                    ])
                    ->collapsible()
                    ->collapsed(true)
                    ->visible(fn ($record) => $record && ($record->resolution_notes || $record->resolved_at)),

                Section::make('المرفقات')
                    ->schema([
                        RepeatableEntry::make('images')
                            ->label('الصور المرفقة')
                            ->schema([
                                ImageEntry::make('path')
                                    ->label('الصورة')
                                    ->height(300),
                            ])
                            ->columns(3),
                    ])
                    ->collapsible(true)
                    ->collapsed(false)
                    ->visible(fn ($record) => $record && $record->images && !$record->images->isEmpty())
                    ->icon('heroicon-o-photo'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('ticket_number')
                    ->searchable()
                    ->sortable()
                    ->label('رقم التذكرة')
                    ->copyable()
                    ->badge()
                    ->color('primary'),
                TextColumn::make('name')
                    ->label('الاسم')
                    ->searchable(query: function ($query, $search) {
                        return $query->where(function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%")
                              ->orWhereHas('user', fn($q) => $q->where('name', 'like', "%{$search}%"));
                        });
                    })
                    ->sortable()
                    ->getStateUsing(function ($record) {
                        if ($record && $record->user_id && $record->user) {
                            return $record->user->name ?? '-';
                        }
                        return $record->name ?? '-';
                    })
                    ->placeholder('-'),
                TextColumn::make('email')
                    ->label('البريد الإلكتروني')
                    ->searchable(query: function ($query, $search) {
                        return $query->where(function ($q) use ($search) {
                            $q->where('email', 'like', "%{$search}%")
                              ->orWhereHas('user', fn($q) => $q->where('email', 'like', "%{$search}%"));
                        });
                    })
                    ->sortable()
                    ->getStateUsing(function ($record) {
                        if ($record && $record->user_id && $record->user) {
                            return $record->user->email ?? '-';
                        }
                        return $record->email ?? '-';
                    })
                    ->placeholder('-'),
                TextColumn::make('phone')
                    ->label('الهاتف')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),
                TextColumn::make('description')
                    ->label('الرسالة')
                    ->searchable()
                    ->limit(100)
                    ->wrap()
                    ->placeholder('-'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        Ticket::STATUS_PENDING => 'قيد الانتظار',
                        Ticket::STATUS_IN_PROGRESS => 'قيد المعالجة',
                        Ticket::STATUS_RESOLVED => 'تم الحل',
                        Ticket::STATUS_CLOSED => 'مغلق',
                    ]),
                SelectFilter::make('priority')
                    ->label('الأولوية')
                    ->options([
                        Ticket::PRIORITY_LOW => 'منخفض',
                        Ticket::PRIORITY_MEDIUM => 'متوسط',
                        Ticket::PRIORITY_HIGH => 'عالي',
                        Ticket::PRIORITY_URGENT => 'عاجل',
                    ]),
                SelectFilter::make('type')
                    ->label('النوع')
                    ->options([
                        Ticket::TYPE_SUPPORT => 'دعم فني',
                        Ticket::TYPE_COMPLAINT => 'شكوى',
                        Ticket::TYPE_INQUIRY => 'استفسار',
                        Ticket::TYPE_TECHNICAL => 'تقني',
                        Ticket::TYPE_BILLING => 'فوترة',
                        Ticket::TYPE_OTHER => 'أخرى',
                    ]),
                SelectFilter::make('admin_id')
                    ->label('موظف الدعم')
                    ->relationship('admin', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('user_id')
                    ->label('العميل')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('عرض')
                    ->visible(fn () => auth()->user()->can('tickets.show')),
                DeleteAction::make()
                    ->label('حذف')
                    ->visible(fn () => auth()->user()->can('tickets.delete'))
                    ->requiresConfirmation()
                    ->modalHeading('حذف التذكرة')
                    ->modalDescription('هل أنت متأكد من حذف هذه التذكرة؟ لا يمكن التراجع عن هذا الإجراء.')
                    ->modalSubmitActionLabel('حذف')
                    ->modalCancelActionLabel('إلغاء'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()->can('tickets.delete')),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTickets::route('/'),
            'create' => CreateTicket::route('/create'),
            'edit' => EditTicket::route('/{record}/edit'),
            'view' => ViewTicket::route('/{record}'),
        ];
    }
}

