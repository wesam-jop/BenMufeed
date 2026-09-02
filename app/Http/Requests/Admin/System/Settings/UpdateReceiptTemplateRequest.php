<?php

namespace App\Http\Requests\Admin\System\Settings;

use App\Services\Shared\BusinessSettingsService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateReceiptTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $bool = ['nullable', 'boolean'];
        $text = ['nullable', 'string', 'max:120'];

        return [
            'paper_width_mm' => ['nullable', 'integer', 'in:0,58,80,210'],
            'sections' => ['required', 'array', 'min:1', 'max:40'],
            'sections.*.id' => ['required', 'string', 'max:64'],
            'sections.*.type' => ['required', 'string', Rule::in(BusinessSettingsService::sectionTypes())],
            'sections.*.enabled' => ['required', 'boolean'],
            'sections.*.props' => ['nullable', 'array'],
            'sections.*.props.text' => ['nullable', 'string', 'max:500'],
            'sections.*.props.align' => ['nullable', 'string', Rule::in(['right', 'center', 'left'])],
            'sections.*.props.size' => ['nullable', 'string', Rule::in(['sm', 'md', 'lg'])],
            'sections.*.props.height' => ['nullable', 'integer', 'min:4', 'max:48'],

            'sections.*.props.show_logo' => $bool,
            'sections.*.props.show_name' => $bool,
            'sections.*.props.show_tagline' => $bool,
            'sections.*.props.show_phone' => $bool,
            'sections.*.props.show_address' => $bool,
            'sections.*.props.show_tax_number' => $bool,
            'sections.*.props.show_title' => $bool,
            'sections.*.props.show_date' => $bool,
            'sections.*.props.show_invoice_number' => $bool,
            'sections.*.props.show_cashier' => $bool,
            'sections.*.props.show_status' => $bool,
            'sections.*.props.show_unit_price' => $bool,
            'sections.*.props.show_qty' => $bool,
            'sections.*.props.show_item_total' => $bool,
            'sections.*.props.show_subtotal' => $bool,
            'sections.*.props.show_discount' => $bool,
            'sections.*.props.show_tax' => $bool,
            'sections.*.props.show_total' => $bool,
            'sections.*.props.show_conversions_title' => $bool,
            'sections.*.props.show_payment_method' => $bool,
            'sections.*.props.show_paid' => $bool,

            'sections.*.props.name_override' => $text,
            'sections.*.props.tagline_override' => $text,
            'sections.*.props.tax_number_label' => $text,
            'sections.*.props.title' => $text,
            'sections.*.props.invoice_number_label' => $text,
            'sections.*.props.cashier_label' => $text,
            'sections.*.props.status_label' => $text,
            'sections.*.props.item_label' => $text,
            'sections.*.props.qty_label' => $text,
            'sections.*.props.price_label' => $text,
            'sections.*.props.amount_label' => $text,
            'sections.*.props.subtotal_label' => $text,
            'sections.*.props.discount_label' => $text,
            'sections.*.props.tax_amount_label' => $text,
            'sections.*.props.total_label' => $text,
            'sections.*.props.conversions_title' => $text,
            'sections.*.props.payment_method_label' => $text,
            'sections.*.props.paid_label' => $text,
        ];
    }
}
