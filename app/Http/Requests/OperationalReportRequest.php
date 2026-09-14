<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\User;
use App\Policies\ReportPolicy;
use App\Services\OperationalReportRecapService;
use App\Services\ReportService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class OperationalReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && app(ReportPolicy::class)->viewAny($user);
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'tab' => [Rule::requiredIf($this->routeIs('reports.index')), 'nullable', Rule::in(OperationalReportRecapService::tabs())],
            'type' => ['nullable', Rule::in(ReportService::types())],
            'q' => ['nullable', 'string', 'max:100', 'not_regex:/[\x00-\x1F\x7F]/u'],
            'academic_year_id' => ['nullable', 'integer', 'exists:academic_years,id'],
            'date_start' => ['nullable', 'date'],
            'date_end' => ['nullable', 'date', 'after_or_equal:date_start'],
            'classroom_id' => ['nullable', 'integer', 'exists:classrooms,id'],
            'counselor_id' => ['nullable', 'integer', 'exists:users,id'],
            'student_id' => ['nullable', 'integer', 'exists:students,id'],
            'category' => ['nullable', 'string', 'max:100'],
            'service_field_id' => ['nullable', 'integer', 'exists:references,id'],
            'status_id' => ['nullable', 'integer', 'exists:references,id'],
            'achievement_type_id' => ['nullable', 'integer', 'exists:references,id'],
            'achievement_level_id' => ['nullable', 'integer', 'exists:references,id'],
            'minimum_points' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'format' => [Rule::requiredIf($this->routeIs('reports.export')), 'nullable', Rule::in(['csv'])],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /** @return array<string, mixed> */
    public function filters(): array
    {
        $data = $this->safe()->except('format');

        return isset($data['tab'])
            ? Arr::only($data, ['tab', 'q', 'academic_year_id', 'date_start', 'date_end', 'classroom_id', 'counselor_id', 'page'])
            : Arr::only($data, ['type', 'academic_year_id', 'date_start', 'date_end', 'classroom_id', 'student_id', 'category', 'service_field_id', 'status_id', 'achievement_type_id', 'achievement_level_id', 'counselor_id', 'minimum_points', 'page']);
    }

    /** @return list<callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $hasTab = $this->filled('tab');
            $hasType = $this->filled('type');

            if ($this->routeIs('reports.index') && $hasType) {
                $validator->errors()->add('type', 'Mode laporan lama tidak dapat digabungkan dengan tab.');
            }
            if ($this->routeIs('reports.export') && $hasTab === $hasType) {
                $validator->errors()->add('tab', 'Pilih tepat satu jenis laporan untuk diekspor.');
                $validator->errors()->add('type', 'Pilih tepat satu jenis laporan untuk diekspor.');
            }

            if (! $hasTab) {
                return;
            }

            $actor = $this->user();
            $year = $this->filled('academic_year_id')
                ? AcademicYear::query()->find($this->integer('academic_year_id'))
                : AcademicYear::query()->active()->orderByDesc('starts_on')->first()
                    ?? AcademicYear::query()->orderByDesc('starts_on')->first();

            if ($this->filled('classroom_id')) {
                $allowed = Classroom::query()
                    ->whereKey($this->integer('classroom_id'))
                    ->when($year, fn (Builder $query, AcademicYear $selected): Builder => $query
                        ->where('academic_year_id', $selected->id))
                    ->whereHas('studentClassMemberships.student', fn (Builder $students): Builder => $students
                        ->accessibleTo($actor))
                    ->exists();
                if (! $allowed) {
                    $validator->errors()->add('classroom_id', 'Kelas tidak tersedia untuk laporan ini.');
                }
            }

            if ($this->filled('counselor_id')) {
                $allowed = $actor?->hasRole('koordinator_bk') === true
                    && $this->string('tab')->toString() === OperationalReportRecapService::TAB_SERVICES
                    && User::query()->active()->whereKey($this->integer('counselor_id'))
                        ->whereHas('roles', fn (Builder $roles): Builder => $roles
                            ->where('slug', 'guru_bk')->where('is_active', true))
                        ->exists();
                if (! $allowed) {
                    $validator->errors()->add('counselor_id', 'Guru BK tidak tersedia untuk laporan ini.');
                }
            }
        }];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'tab.required' => 'Tab laporan wajib dipilih.',
            'tab.in' => 'Tab laporan tidak tersedia.',
            'type.in' => 'Jenis laporan tidak tersedia.',
            'q.max' => 'Pencarian nama murid maksimal 100 karakter.',
            'q.not_regex' => 'Pencarian nama murid memuat karakter yang tidak diizinkan.',
            'academic_year_id.exists' => 'Tahun ajaran tidak tersedia.',
            'date_start.date' => 'Tanggal awal harus berupa tanggal yang valid.',
            'date_end.date' => 'Tanggal akhir harus berupa tanggal yang valid.',
            'date_end.after_or_equal' => 'Tanggal akhir tidak boleh sebelum tanggal awal.',
            'classroom_id.exists' => 'Kelas tidak tersedia untuk laporan ini.',
            'counselor_id.exists' => 'Guru BK tidak tersedia untuk laporan ini.',
            'format.required' => 'Format ekspor wajib dipilih.',
            'format.in' => 'Format ekspor belum tersedia. Hanya CSV yang didukung.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->routeIs('reports.index') && ! $this->has('tab')) {
            $this->merge(['tab' => OperationalReportRecapService::TAB_SERVICES]);
        }
    }

    protected function getRedirectUrl(): string
    {
        $tab = $this->string('tab')->toString();
        if (in_array($tab, OperationalReportRecapService::tabs(), true)) {
            return route('reports.index', ['tab' => $tab]);
        }

        $type = $this->string('type')->toString();
        if ($this->routeIs('reports.export') && in_array($type, ReportService::types(), true)) {
            return route('reports.preview', ['type' => $type]);
        }

        return route('reports.index', ['tab' => OperationalReportRecapService::TAB_SERVICES]);
    }
}
