<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Achievement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VerifyAchievementRequest extends FormRequest
{
    public function authorize(): bool
    {
        $achievement = $this->route('achievement');

        return $achievement instanceof Achievement
            && ($this->user()?->can('verify', $achievement) ?? false);
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'decision' => ['required', Rule::in(['terverifikasi', 'ditolak'])],
            'verification_notes' => ['nullable', 'string', 'max:10000', Rule::requiredIf($this->string('decision')->toString() === 'ditolak')],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'decision.required' => 'Keputusan verifikasi wajib dipilih.',
            'decision.in' => 'Keputusan verifikasi tidak tersedia.',
            'verification_notes.required' => 'Alasan penolakan wajib diisi.',
        ];
    }
}
