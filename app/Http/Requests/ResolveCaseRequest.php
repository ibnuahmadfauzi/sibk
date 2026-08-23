<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\BkCase;
use Illuminate\Foundation\Http\FormRequest;

class ResolveCaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        $case = $this->route('case');

        return $case instanceof BkCase && ($this->user()?->can('resolve', $case) ?? false);
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'closed_at' => ['required', 'date', 'before_or_equal:today'],
            'final_result' => ['required', 'string', 'max:10000'],
            'resolution_summary' => ['required', 'string', 'max:10000'],
            'continued_plan' => ['nullable', 'string', 'max:10000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'closed_at.required' => 'Tanggal selesai wajib diisi.',
            'closed_at.before_or_equal' => 'Tanggal selesai tidak boleh berada di masa depan.',
            'final_result.required' => 'Hasil akhir wajib diisi.',
            'resolution_summary.required' => 'Ringkasan penyelesaian wajib diisi.',
        ];
    }
}
