<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Policies\ReportPolicy;
use App\Services\ReportService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $type = $this->string('type', ReportService::TYPE_SERVICE_RECAP)->toString();

        return $user !== null && app(ReportPolicy::class)->viewType($user, $type);
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(ReportService::types())],
            'academic_year_id' => ['nullable', 'integer', 'exists:academic_years,id'],
            'date_start' => ['nullable', 'date'],
            'date_end' => ['nullable', 'date', 'after_or_equal:date_start'],
            'classroom_id' => ['nullable', 'integer', 'exists:classrooms,id'],
            'student_id' => ['nullable', 'integer', 'exists:students,id'],
            'category' => ['nullable', 'string', 'max:100'],
            'service_field_id' => ['nullable', 'integer', 'exists:references,id'],
            'status_id' => ['nullable', 'integer', 'exists:references,id'],
            'achievement_type_id' => ['nullable', 'integer', 'exists:references,id'],
            'achievement_level_id' => ['nullable', 'integer', 'exists:references,id'],
            'counselor_id' => ['nullable', 'integer', 'exists:users,id'],
            'minimum_points' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'format' => [Rule::requiredIf($this->routeIs('reports.export')), 'nullable', Rule::in(['csv'])],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'type.required' => 'Jenis laporan wajib dipilih.',
            'type.in' => 'Jenis laporan tidak tersedia.',
            'academic_year_id.exists' => 'Tahun ajaran tidak tersedia.',
            'date_start.date' => 'Tanggal awal harus berupa tanggal yang valid.',
            'date_end.date' => 'Tanggal akhir harus berupa tanggal yang valid.',
            'date_end.after_or_equal' => 'Tanggal akhir tidak boleh sebelum tanggal awal.',
            'classroom_id.exists' => 'Kelas tidak tersedia.',
            'student_id.exists' => 'Murid tidak tersedia.',
            'service_field_id.exists' => 'Bidang layanan tidak tersedia.',
            'status_id.exists' => 'Status tidak tersedia.',
            'achievement_type_id.exists' => 'Jenis prestasi tidak tersedia.',
            'achievement_level_id.exists' => 'Tingkat prestasi tidak tersedia.',
            'counselor_id.exists' => 'Guru BK tidak tersedia.',
            'minimum_points.integer' => 'Ambang poin harus berupa angka bulat.',
            'format.in' => 'Format ekspor belum tersedia. Sprint 7 hanya menyediakan CSV.',
            'format.required' => 'Format ekspor wajib dipilih.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('type')) {
            $this->merge(['type' => ReportService::TYPE_SERVICE_RECAP]);
        }
    }
}
