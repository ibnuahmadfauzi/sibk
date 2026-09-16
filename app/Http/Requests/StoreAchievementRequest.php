<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Achievement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAchievementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Achievement::class) ?? false;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'student_id' => ['required', 'integer', Rule::exists('students', 'id')->where('is_active', true)],
            ...$this->achievementRules(),
        ];
    }

    /** @return array<string, list<mixed>> */
    protected function achievementRules(): array
    {
        return [
            'type_id' => ['required', 'integer', Rule::exists('references', 'id')->where('category', 'achievement_type')->where('is_active', true)],
            'level_id' => ['required', 'integer', Rule::exists('references', 'id')->where('category', 'achievement_level')->where('is_active', true)],
            'activity_name' => ['required', 'string', 'max:250'],
            'organizer' => ['required', 'string', 'max:200'],
            'achievement_date' => ['required', 'date', 'before_or_equal:today'],
            'result' => ['required', 'string', 'max:250'],
            'evidence_reference' => ['required', 'string', 'max:2000'],
            'evidence_description' => ['nullable', 'string', 'max:5000'],
            'notes' => ['nullable', 'string', 'max:10000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'student_id.required' => 'Murid wajib dipilih.',
            'student_id.exists' => 'Murid yang dipilih tidak tersedia atau sudah tidak aktif.',
            'type_id.required' => 'Jenis prestasi wajib dipilih.',
            'type_id.exists' => 'Jenis prestasi tidak tersedia.',
            'level_id.required' => 'Tingkat prestasi wajib dipilih.',
            'level_id.exists' => 'Tingkat prestasi tidak tersedia.',
            'activity_name.required' => 'Nama kegiatan wajib diisi.',
            'organizer.required' => 'Penyelenggara wajib diisi.',
            'achievement_date.required' => 'Tanggal prestasi wajib diisi.',
            'achievement_date.before_or_equal' => 'Tanggal prestasi tidak boleh berada di masa depan.',
            'result.required' => 'Hasil atau peringkat wajib diisi.',
            'evidence_reference.required' => 'Referensi bukti prestasi wajib diisi.',
        ];
    }
}
