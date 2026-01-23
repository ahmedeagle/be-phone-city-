<?php

namespace App\Filament\Admin\Resources\Pages\Pages;

use App\Filament\Admin\Resources\Pages\PageResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditPage extends EditRecord
{
    protected static string $resource = PageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()->before(function (DeleteAction $action, $record) {
                        if ($record->can_delete === false) {
                            Notification::make()
                                ->warning()
                                ->title('لا يمكن حذف هذه الصفحة')
                                ->body('لا يمكن حذف الصفحات المحمية')
                                ->send();

                            $action->halt();
                        }
                    })->visible(fn ($record) => $record->can_delete),
        ];
    }
}
