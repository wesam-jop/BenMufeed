<?php

namespace App\Notifications;

use App\Models\PrintJob;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PrintJobFailedNotification extends Notification
{
    use Queueable;

    public function __construct(public PrintJob $printJob) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'فشلت الطباعة #'.$this->printJob->id,
            'body' => $this->printJob->referenceLabel().' — '.($this->printJob->error_message ?? 'خطأ غير معروف'),
            'format' => 'filament',
            'icon' => 'heroicon-o-printer',
            'iconColor' => 'danger',
            'status' => 'danger',
            'actions' => [
                [
                    'name' => 'view',
                    'label' => 'سجل الطباعة',
                    'url' => url('/pos/print-log'),
                    'color' => 'danger',
                ],
            ],
        ];
    }
}
