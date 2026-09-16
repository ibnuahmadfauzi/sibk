<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Consultation;
use App\Support\ServiceRecordStatus;
use Illuminate\Validation\Rule;

class UpdateConsultationRequest extends StoreConsultationRequest
{
    public function authorize(): bool
    {
        $consultation = $this->route('consultation');

        return $consultation instanceof Consultation
            && ($this->user()?->can('update', $consultation) ?? false);
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        $consultation = $this->route('consultation');
        $isCompleted = $consultation instanceof Consultation
            && $consultation->loadMissing('status')->status?->code === ServiceRecordStatus::COMPLETED;
        $rules = $this->consultationRules();
        $rules['change_reason'] = [Rule::requiredIf($isCompleted), 'nullable', 'string', 'min:10', 'max:500'];
        $rules['status_id'] = $isCompleted
            ? ['required', 'integer', Rule::in([(int) $consultation->status_id])]
            : ['required', 'integer', Rule::exists('references', 'id')->where(
                fn ($statuses) => $statuses
                    ->where('category', 'consultation_status')
                    ->where('is_active', true)
                    ->where('code', '!=', ServiceRecordStatus::COMPLETED),
            )];

        return $rules;
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            ...parent::messages(),
            'change_reason.required' => 'Alasan perubahan wajib diisi untuk data yang telah selesai.',
        ];
    }
}
