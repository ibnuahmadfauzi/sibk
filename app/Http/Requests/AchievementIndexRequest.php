<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Achievement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AchievementIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Achievement::class) ?? false;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:200'],
            'student_id' => ['nullable', 'integer', 'exists:students,id'],
            'classroom_id' => ['nullable', 'integer', 'exists:classrooms,id'],
            'type_id' => ['nullable', 'integer', Rule::exists('references', 'id')->where('category', 'achievement_type')->where('is_active', true)],
            'level_id' => ['nullable', 'integer', Rule::exists('references', 'id')->where('category', 'achievement_level')->where('is_active', true)],
            'status_id' => ['nullable', 'integer', Rule::exists('references', 'id')->where('category', 'achievement_verification_status')->where('is_active', true)],
            'date_start' => ['nullable', 'date'],
            'date_end' => ['nullable', 'date', 'after_or_equal:date_start'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'student_id.exists' => 'Murid tidak tersedia.',
            'classroom_id.exists' => 'Kelas tidak tersedia.',
            'type_id.exists' => 'Jenis prestasi tidak tersedia.',
            'level_id.exists' => 'Tingkat prestasi tidak tersedia.',
            'status_id.exists' => 'Status verifikasi tidak tersedia.',
            'date_start.date' => 'Tanggal awal tidak valid.',
            'date_end.date' => 'Tanggal akhir tidak valid.',
            'date_end.after_or_equal' => 'Tanggal akhir tidak boleh sebelum tanggal awal.',
        ];
    }
}
