<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\BkCase;
use App\Models\ReferenceValue;
use App\Support\ServiceRecordStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        $case = $this->route('case');

        return $case instanceof BkCase
            && ($this->user()?->can('update', $case) ?? false);
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        $case = $this->route('case');
        $isCompleted = $case instanceof BkCase
            && $case->loadMissing('status')->status?->code === ServiceRecordStatus::COMPLETED;

        return [
            'case_source_id' => ['required', 'integer', Rule::exists('references', 'id')->where('category', 'case_source')->where('is_active', true)],
            'service_field_id' => ['required', 'integer', Rule::exists('references', 'id')->where('category', 'service_field')->where('is_active', true)],
            'status_id' => $isCompleted
                ? ['required', 'integer', Rule::in([(int) $case->status_id])]
                : ['required', 'integer', Rule::exists('references', 'id')->where(
                    fn ($statuses) => $statuses
                        ->where('category', 'case_status')
                        ->where('is_active', true)
                        ->where('code', '!=', ServiceRecordStatus::COMPLETED),
                )],
            'change_reason' => [Rule::requiredIf($isCompleted), 'nullable', 'string', 'min:10', 'max:500'],
            'service_date' => ['required', 'date', 'before_or_equal:today'],
            'referrer' => ['nullable', 'string', 'max:150'],
            'initial_info' => ['required', 'string', 'max:10000'],
            'initial_action' => ['required', 'string', 'max:10000'],
            'internal_note' => ['nullable', 'string', 'max:10000'],
            'waka_summary' => [
                Rule::requiredIf(fn (): bool => $this->selectedStatusCode() !== ServiceRecordStatus::NEW),
                'nullable',
                'string',
                'max:500',
            ],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'case_source_id.required' => 'Sumber kasus wajib dipilih.',
            'case_source_id.exists' => 'Sumber kasus tidak tersedia.',
            'service_field_id.required' => 'Bidang layanan wajib dipilih.',
            'service_field_id.exists' => 'Bidang layanan tidak tersedia.',
            'status_id.required' => 'Status kasus wajib dipilih.',
            'status_id.exists' => 'Status kasus tidak tersedia.',
            'change_reason.required' => 'Alasan perubahan wajib diisi untuk data yang telah selesai.',
            'service_date.required' => 'Tanggal layanan wajib diisi.',
            'service_date.before_or_equal' => 'Tanggal layanan tidak boleh berada di masa depan.',
            'initial_info.required' => 'Informasi awal wajib diisi.',
            'initial_action.required' => 'Penanganan awal wajib diisi.',
            'waka_summary.required' => 'Ringkasan Penanganan untuk Waka wajib diisi ketika kasus mulai diproses.',
            'waka_summary.max' => 'Ringkasan Penanganan untuk Waka maksimal 500 karakter.',
        ];
    }

    private function selectedStatusCode(): ?string
    {
        $statusId = $this->integer('status_id');
        if ($statusId === 0) {
            return null;
        }

        return ReferenceValue::query()
            ->active()
            ->forCategory('case_status')
            ->whereKey($statusId)
            ->value('code');
    }
}
