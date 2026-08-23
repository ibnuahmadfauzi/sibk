<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\BkCase;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveFollowUpRequest extends FormRequest
{
    public function authorize(): bool
    {
        $case = $this->route('case');

        return $case instanceof BkCase && ($this->user()?->can('update', $case) ?? false);
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'follow_up_type_id' => ['required', 'integer', Rule::exists('references', 'id')->where('category', 'follow_up_type')->where('is_active', true)],
            'status_id' => ['required', 'integer', Rule::exists('references', 'id')->where('category', 'follow_up_status')->where('is_active', true)],
            'planned_date' => ['required', 'date'],
            'execution_date' => ['nullable', 'date', 'after_or_equal:planned_date'],
            'result' => ['nullable', 'string', 'max:10000'],
            'next_plan' => ['nullable', 'string', 'max:10000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'follow_up_type_id.required' => 'Jenis tindak lanjut wajib dipilih.',
            'follow_up_type_id.exists' => 'Jenis tindak lanjut tidak tersedia.',
            'status_id.required' => 'Status pelaksanaan wajib dipilih.',
            'status_id.exists' => 'Status pelaksanaan tidak tersedia.',
            'planned_date.required' => 'Tanggal rencana wajib diisi.',
            'execution_date.after_or_equal' => 'Tanggal pelaksanaan tidak boleh sebelum tanggal rencana.',
        ];
    }
}
