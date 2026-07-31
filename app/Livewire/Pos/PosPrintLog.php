<?php

namespace App\Livewire\Pos;

use App\Models\PrintJob;
use App\Services\PosService;
use App\Services\PrintJobService;
use App\Support\DispatchesPrintJobs;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.pos')]
class PosPrintLog extends Component
{
    use DispatchesPrintJobs;
    use WithPagination;

    public string $filterDate = '';

    public string $filterStatus = 'all';

    public ?string $flashMessage = null;

    public function mount(): void
    {
        $this->filterDate = now()->format('Y-m-d');
    }

    public function updatedFilterDate(): void
    {
        $this->resetPage();
    }

    public function updatedFilterStatus(): void
    {
        $this->resetPage();
    }

    public function retry(int $jobId): void
    {
        $job = PrintJob::query()->find($jobId);

        if ($job === null || ! $job->status->canRetry()) {
            $this->flashMessage = 'لا يمكن إعادة طباعة هذا السجل.';

            return;
        }

        app(PrintJobService::class)->prepareRetry($job);
        $this->dispatchPrintJob($job->fresh());
        $this->flashMessage = 'تم إرسال أمر الطباعة مجدداً — '.$job->referenceLabel();
    }

    public function retryAllFailed(): void
    {
        $jobs = app(PrintJobService::class)->retryableFailed();

        if ($jobs->isEmpty()) {
            $this->flashMessage = 'لا توجد طباعات فاشلة لإعادة تشغيلها.';

            return;
        }

        foreach ($jobs as $job) {
            app(PrintJobService::class)->prepareRetry($job);
            $this->dispatchPrintJob($job->fresh());
        }

        $this->flashMessage = 'تم إرسال '.$jobs->count().' أمر طباعة.';
    }

    public function render()
    {
        $jobs = app(PrintJobService::class)->paginate(
            filled($this->filterDate) ? $this->filterDate : null,
            $this->filterStatus !== 'all' ? $this->filterStatus : null,
        );

        $pos = app(PosService::class);

        return view('livewire.pos.print-log', [
            'jobs' => $jobs,
            'failedCount' => app(PrintJobService::class)->failedCount(),
            'openTablesCount' => $pos->openTablesCount(),
        ])->title('سجل الطباعة — نقطة البيع');
    }
}
