<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Enums\PosServiceType;
use App\Filament\Resources\Orders\OrderResource;
use App\Services\PrintJobService;
use App\Services\WebsiteOrderWorkflowService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Validation\ValidationException;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $this->record->loadMissing(['items.addons', 'cashier', 'restaurantTable']);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('acceptWebsiteOrder')
                ->label('قبول → المطبخ')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('قبول الطلب وإرساله للمطبخ؟')
                ->visible(fn () => $this->canReviewWebsiteOrder())
                ->action(function (): void {
                    try {
                        $acceptance = app(WebsiteOrderWorkflowService::class)->accept($this->record, auth()->user());
                        $printJobs = app(PrintJobService::class);

                        $printJobs->queueCashier($acceptance->order->getKey(), 'website_accept');

                        if ($acceptance->kitchenTicket !== null) {
                            $printJobs->queueKitchen($acceptance->kitchenTicket->getKey(), 'website_accept');
                        }

                        Notification::make()
                            ->title('تم قبول الطلب — أُرسل للمطبخ')
                            ->body('للطباعة التلقائية: اترك نقطة البيع (/pos) مفتوحة مع QZ Tray.')
                            ->success()
                            ->send();

                        $this->refreshFormData(['status', 'reviewed_at']);
                    } catch (ValidationException $e) {
                        Notification::make()
                            ->title(collect($e->errors())->flatten()->first() ?? 'تعذّر قبول الطلب')
                            ->danger()
                            ->send();
                    }
                }),
            Action::make('rejectWebsiteOrder')
                ->label('رفض الطلب')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () => $this->canReviewWebsiteOrder())
                ->form([
                    Textarea::make('reason')
                        ->label('سبب الرفض (اختياري)')
                        ->rows(2)
                        ->maxLength(500),
                ])
                ->action(function (array $data): void {
                    try {
                        app(WebsiteOrderWorkflowService::class)->reject(
                            $this->record,
                            filled($data['reason'] ?? null) ? (string) $data['reason'] : null,
                        );

                        Notification::make()
                            ->title('تم رفض الطلب')
                            ->warning()
                            ->send();

                        $this->refreshFormData(['status', 'cancelled_at']);
                    } catch (ValidationException $e) {
                        Notification::make()
                            ->title(collect($e->errors())->flatten()->first() ?? 'تعذّر رفض الطلب')
                            ->danger()
                            ->send();
                    }
                }),
            EditAction::make(),
        ];
    }

    protected function canReviewWebsiteOrder(): bool
    {
        $order = $this->record;

        return $order->source === OrderSource::Website
            && in_array($order->service_type, [PosServiceType::Takeaway, PosServiceType::Delivery], true)
            && $order->status === OrderStatus::New;
    }
}
