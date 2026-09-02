<?php

namespace App\Http\Requests\Admin\Inventory\PurchaseInvoice;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'supplier_id' => 'required|exists:suppliers,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'invoice_number' => 'nullable|string|max:100|unique:purchase_invoices,invoice_number',
            'due_date' => 'nullable|date',
        ];
    }
}
