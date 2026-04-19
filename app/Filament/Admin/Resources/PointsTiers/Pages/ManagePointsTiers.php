<?php

namespace App\Filament\Admin\Resources\PointsTiers\Pages;

use App\Filament\Admin\Resources\PointsTiers\PointsTierResource;
use App\Models\Setting;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;

class ManagePointsTiers extends ManageRecords
{
    protected static string $resource = PointsTierResource::class;

    public function getSubheading(): ?string
    {
        $settings = Setting::getSettings();
        $pointValue = (float) ($settings->point_value ?? 1.00);

        if ($pointValue > 0) {
            $pointsPerRiyal = round(1 / $pointValue);
            return "قيمة النقطة الحالية: كل {$pointsPerRiyal} نقاط = 1 ر.س";
        }

        return "قيمة النقطة الحالية: {$pointValue} ر.س";
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('editPointValue')
                ->label('تعديل قيمة النقطة')
                ->icon('heroicon-o-cog-6-tooth')
                ->color('warning')
                ->form([
                    TextInput::make('point_value')
                        ->label('قيمة النقطة الواحدة (ر.س)')
                        ->required()
                        ->numeric()
                        ->minValue(0.01)
                        ->step(0.01)
                        ->prefix('ر.س')
                        ->default(fn () => (float) (Setting::getSettings()->point_value ?? 1.00))
                        ->helperText('مثال: 0.10 يعني كل 10 نقاط = 1 ر.س، أو 1.00 يعني كل نقطة = 1 ر.س'),
                ])
                ->action(function (array $data): void {
                    $settings = Setting::getSettings();
                    $settings->point_value = $data['point_value'];
                    $settings->save();

                    Notification::make()
                        ->title('تم تحديث قيمة النقطة بنجاح')
                        ->success()
                        ->send();
                }),
            CreateAction::make(),
        ];
    }
}