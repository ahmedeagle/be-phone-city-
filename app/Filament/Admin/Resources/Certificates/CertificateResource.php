<?php

namespace App\Filament\Admin\Resources\Certificates;

use App\Filament\Admin\Resources\Certificates\Pages\CreateCertificate;
use App\Filament\Admin\Resources\Certificates\Pages\EditCertificate;
use App\Filament\Admin\Resources\Certificates\Pages\ListCertificates;
use App\Filament\Admin\Resources\Certificates\Pages\ViewCertificate;
use App\Filament\Admin\Resources\Certificates\Schemas\CertificateForm;
use App\Filament\Admin\Resources\Certificates\Tables\CertificatesTable;
use App\Models\Certificate;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CertificateResource extends Resource
{
    protected static ?string $model = Certificate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::AcademicCap;

    protected static ?string $navigationLabel = 'الشهادات';

    protected static ?string $pluralLabel = 'الشهادات';

    protected static ?string $label = 'شهادة';

    protected static ?int $navigationSort = 4;

    public static function getNavigationGroup(): ?string
    {
        return 'المحتوى التسويقي';
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('certificates.show');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->can('certificates.create');
    }

    public static function canEdit($record): bool
    {
        return auth()->user()->can('certificates.update');
    }

    public static function canDelete($record): bool
    {
        return auth()->user()->can('certificates.delete');
    }

    public static function form(Schema $schema): Schema
    {
        return CertificateForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CertificatesTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Schemas\Components\Section::make('معلومات الشهادة')
                    ->schema([

                        \Filament\Infolists\Components\TextEntry::make('name_en')
                            ->label('الاسم بالإنجليزية'),
                        \Filament\Infolists\Components\TextEntry::make('name_ar')
                            ->label('الاسم بالعربية'),
                    ])
                    ->columns(3),
                \Filament\Schemas\Components\Section::make('الصور')
                    ->schema([
                        \Filament\Infolists\Components\ImageEntry::make('image')
                            ->label('الصورة')
                            ->height(200),
                        \Filament\Infolists\Components\ImageEntry::make('main_image')
                            ->label('الصورة الرئيسية')
                            ->height(200),
                    ])
                    ->columns(2),
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
            'index' => ListCertificates::route('/'),
            'create' => CreateCertificate::route('/create'),
            'edit' => EditCertificate::route('/{record}/edit'),
            'view' => ViewCertificate::route('/{record}'),
        ];
    }
}

