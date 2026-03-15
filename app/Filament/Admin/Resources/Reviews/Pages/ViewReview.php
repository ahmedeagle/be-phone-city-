<?php

namespace App\Filament\Admin\Resources\Reviews\Pages;

use App\Filament\Admin\Resources\Reviews\ReviewResource;
use App\Models\Review;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewReview extends ViewRecord
{
    protected static string $resource = ReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->label('قبول التقييم')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('قبول التقييم')
                ->modalDescription('هل تريد قبول هذا التقييم ونشره للعامة؟')
                ->modalSubmitActionLabel('قبول')
                ->visible(fn (): bool => ! $this->getRecord()->isApproved())
                ->action(function (): void {
                    $this->getRecord()->approve();
                    Notification::make()
                        ->title('تم قبول التقييم ونشره للعامة')
                        ->success()
                        ->send();
                    $this->refreshFormData(['status']);
                }),

            Action::make('reject')
                ->label('رفض التقييم')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('رفض التقييم')
                ->modalDescription('هل تريد رفض هذا التقييم وعدم نشره؟')
                ->modalSubmitActionLabel('رفض')
                ->visible(fn (): bool => ! $this->getRecord()->isRejected())
                ->action(function (): void {
                    $this->getRecord()->reject();
                    Notification::make()
                        ->title('تم رفض التقييم')
                        ->danger()
                        ->send();
                    $this->refreshFormData(['status']);
                }),

            DeleteAction::make()
                ->requiresConfirmation(),
        ];
    }
}
