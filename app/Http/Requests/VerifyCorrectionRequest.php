<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Correction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VerifyCorrectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $correction = $this->route('correction');

        return $correction instanceof Correction && ($this->user()?->can('verify', $correction) ?? false);
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'decision' => ['required', Rule::in(['approved', 'rejected', 'revision_requested'])],
            'review_notes' => ['nullable', 'string', 'max:10000', Rule::requiredIf(fn (): bool => in_array($this->input('decision'), ['rejected', 'revision_requested'], true))],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'decision.required' => 'Keputusan verifikasi wajib dipilih.',
            'decision.in' => 'Keputusan verifikasi tidak tersedia.',
            'review_notes.required' => 'Catatan pemeriksaan wajib diisi untuk penolakan atau permintaan perbaikan.',
        ];
    }
}
