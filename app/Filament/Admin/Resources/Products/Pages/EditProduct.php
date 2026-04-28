<?php

namespace App\Filament\Admin\Resources\Products\Pages;

use App\Filament\Admin\Resources\Products\ProductResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function authorizeAccess(): void
    {
        abort_unless(auth()->user()->can('products.update'), 403);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->label('عرض')
                ->visible(fn () => auth()->user()->can('products.show')),
            Actions\DeleteAction::make()
                ->label('حذف')
                ->visible(fn () => auth()->user()->can('products.delete'))
                ->requiresConfirmation()
                ->action(function ($record, $action) {
                    $hasOrderItems = DB::table('order_items')
                        ->where('product_id', $record->id)
                        ->exists();

                    if ($hasOrderItems) {
                        Notification::make()
                            ->title('لا يمكن حذف المنتج')
                            ->body('هذا المنتج مرتبط بطلبات سابقة، لا يمكن حذفه. يمكنك إخفاؤه بدلاً من ذلك.')
                            ->danger()
                            ->send();
                        $action->cancel();
                        return;
                    }

                    try {
                        $record->delete();

                        Notification::make()
                            ->title('تم حذف المنتج بنجاح')
                            ->success()
                            ->send();

                        $this->redirect($this->getResource()::getUrl('index'));
                    } catch (\Illuminate\Database\QueryException $e) {
                        Notification::make()
                            ->title('تعذر حذف المنتج')
                            ->body('المنتج مرتبط ببيانات أخرى ولا يمكن حذفه.')
                            ->danger()
                            ->send();
                        $action->cancel();
                    }
                }),
            ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
