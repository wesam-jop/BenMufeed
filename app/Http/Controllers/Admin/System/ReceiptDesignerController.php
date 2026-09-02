<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Admin\System\AdminController;

use App\Admin\Settings;
use App\Http\Requests\Admin\System\Settings\UpdateReceiptTemplateRequest;
use App\Services\Shared\BusinessSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReceiptDesignerController extends AdminController
{
    public function __construct(private BusinessSettingsService $businessSettings) {}

    public function edit(Request $request): Response
    {
        $this->authorize('view', Settings::class);

        $settings = $this->businessSettings->all();

        return Inertia::render('admin/settings/receipt-designer', [
            'template' => $this->businessSettings->receiptTemplate(),
            'defaultTemplate' => $this->businessSettings->defaultReceiptTemplate(
                $settings['receipt_footer'] ?? 'شكراً لزيارتكم'
            ),
            'receiptBusiness' => $this->businessSettings->receiptBusiness(),
            'sectionCatalog' => $this->sectionCatalog(),
        ]);
    }

    public function update(UpdateReceiptTemplateRequest $request): RedirectResponse
    {
        $this->authorize('update', Settings::class);

        $this->businessSettings->saveReceiptTemplate($request->validated());

        return back()->with('toast', [
            'type' => 'success',
            'message' => __('toast.receipt_saved'),
        ]);
    }

    public function reset(): RedirectResponse
    {
        $this->authorize('update', Settings::class);

        $settings = $this->businessSettings->all();
        $this->businessSettings->saveReceiptTemplate(
            $this->businessSettings->defaultReceiptTemplate($settings['receipt_footer'] ?? 'شكراً لزيارتكم')
        );

        return back()->with('toast', [
            'type' => 'success',
            'message' => __('toast.receipt_reset'),
        ]);
    }

    /** @return list<array{type: string, label: string, description: string}> */
    private function sectionCatalog(): array
    {
        return [
            ['type' => 'brand', 'label' => 'الشعار والاسم', 'description' => 'شعار النشاط واسمه'],
            ['type' => 'header_meta', 'label' => 'بيانات التواصل', 'description' => 'هاتف، عنوان، الرقم الضريبي'],
            ['type' => 'invoice_meta', 'label' => 'بيانات الفاتورة', 'description' => 'الرقم، التاريخ، الكاشير، الحالة'],
            ['type' => 'items', 'label' => 'بنود الفاتورة', 'description' => 'قائمة الأصناف والكميات'],
            ['type' => 'totals', 'label' => 'المجاميع', 'description' => 'فرعي، خصم، ضريبة، إجمالي'],
            ['type' => 'currency_conversions', 'label' => 'التصريف', 'description' => 'عملات إضافية على الفاتورة'],
            ['type' => 'payment', 'label' => 'الدفع', 'description' => 'طريقة الدفع والمبلغ المدفوع'],
            ['type' => 'custom_text', 'label' => 'نص حر', 'description' => 'عنوان أو تذييل أو ملاحظة'],
            ['type' => 'divider', 'label' => 'فاصل', 'description' => 'خط متقطع'],
            ['type' => 'spacer', 'label' => 'مسافة', 'description' => 'فراغ عمودي'],
        ];
    }
}
