<?php

namespace App\Http\Requests\Admin\Accounting\JournalEntry;

use Illuminate\Foundation\Http\FormRequest;

class StoreJournalEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'date' => 'required|date',
            'description' => 'required|string|max:500',
            'lines' => 'required|array|min:2',
            'lines.*.account_id' => 'required|exists:chart_of_accounts,id',
            'lines.*.debit' => 'required|numeric|min:0',
            'lines.*.credit' => 'required|numeric|min:0',
            'lines.*.description' => 'nullable|string|max:255',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $lines = $this->input('lines', []);

            if (! is_array($lines)) {
                return;
            }

            $totalDebit = round(collect($lines)->sum(fn ($line) => (float) ($line['debit'] ?? 0)), 2);
            $totalCredit = round(collect($lines)->sum(fn ($line) => (float) ($line['credit'] ?? 0)), 2);

            if ($totalDebit !== $totalCredit) {
                $validator->errors()->add('lines', 'مجموع المدين يجب أن يساوي مجموع الدائن.');
            }

            if ($totalDebit <= 0) {
                $validator->errors()->add('lines', 'يجب إدخال مبالغ في القيد.');
            }

            foreach ($lines as $index => $line) {
                $debit = (float) ($line['debit'] ?? 0);
                $credit = (float) ($line['credit'] ?? 0);

                if ($debit <= 0 && $credit <= 0) {
                    $validator->errors()->add(
                        "lines.{$index}.debit",
                        'أدخل مبلغ مدين أو دائن في كل سطر.',
                    );
                }
            }
        });
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'date.required' => 'تاريخ القيد مطلوب.',
            'description.required' => 'وصف القيد مطلوب.',
            'lines.required' => 'يجب إضافة سطرين على الأقل.',
            'lines.min' => 'يجب إضافة سطرين على الأقل.',
            'lines.*.account_id.required' => 'اختر حساباً لكل سطر.',
            'lines.*.account_id.exists' => 'الحساب المختار غير موجود.',
        ];
    }
}
