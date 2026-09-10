<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\DapodikSyncPreviewItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MapDapodikPreviewItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manageDataMaster') ?? false;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'decision' => [
                'required',
                Rule::in([
                    DapodikSyncPreviewItem::DECISION_MAP_EXISTING,
                    DapodikSyncPreviewItem::DECISION_CREATE_NEW,
                ]),
            ],
            'candidate_id' => [
                'nullable',
                'integer',
                'min:1',
                'required_if:decision,'.DapodikSyncPreviewItem::DECISION_MAP_EXISTING,
                'prohibited_if:decision,'.DapodikSyncPreviewItem::DECISION_CREATE_NEW,
            ],
            'decision_revision' => ['required', 'integer', 'min:0'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'decision.required' => 'Keputusan pemetaan wajib dipilih.',
            'decision.in' => 'Keputusan pemetaan tidak diizinkan.',
            'candidate_id.required_if' => 'Data sementara yang akan dicocokkan wajib dipilih.',
            'candidate_id.prohibited_if' => 'Kandidat tidak boleh dikirim ketika membuat data resmi baru.',
            'decision_revision.required' => 'Versi keputusan wajib dikirim.',
        ];
    }
}
