<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\AcademicYear;
use App\Policies\WakaMonitoringPolicy;
use App\Support\ServiceRecordStatus;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class WakaReportRequest extends FormRequest
{
    /** @var list<string> */
    public const array TABS = ['penanganan', 'rekap', 'laporan-akhir'];

    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && app(WakaMonitoringPolicy::class)->viewReportTab($user, $this->tab());
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'tab' => ['nullable', 'string', Rule::in(self::TABS)],
            'period' => ['nullable', 'string', 'regex:/^\d{4}-\d{2}$/'],
            'status' => ['nullable', 'string', Rule::in(ServiceRecordStatus::codes())],
            'sort' => ['nullable', 'string', Rule::in(WakaMonitoringRequest::HANDLING_SORT_ALLOWLIST)],
            'direction' => ['nullable', 'string', Rule::in(['asc', 'desc'])],
            'academic_year_id' => ['nullable', 'integer', 'exists:academic_years,id'],
            'date_start' => ['nullable', 'date'],
            'date_end' => ['nullable', 'date', 'after_or_equal:date_start'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'tab.in' => 'Tab laporan tidak tersedia.',
            'period.regex' => 'Format periode harus YYYY-MM.',
            'status.in' => 'Status tidak dikenali.',
            'sort.in' => 'Pilihan urutan tidak tersedia.',
            'direction.in' => 'Arah urutan harus asc atau desc.',
            'academic_year_id.exists' => 'Tahun ajaran tidak tersedia.',
            'date_start.date' => 'Tanggal awal harus berupa tanggal yang valid.',
            'date_end.date' => 'Tanggal akhir harus berupa tanggal yang valid.',
            'date_end.after_or_equal' => 'Tanggal akhir tidak boleh sebelum tanggal awal.',
        ];
    }

    /** @return list<callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($this->tab() !== 'rekap' || $validator->errors()->isNotEmpty()) {
                return;
            }

            $year = AcademicYear::query()->find($this->integer('academic_year_id'));
            if ($year === null || $year->starts_on === null || $year->ends_on === null) {
                $validator->errors()->add('academic_year_id', 'Tahun ajaran untuk rekap belum tersedia lengkap.');

                return;
            }

            $start = CarbonImmutable::parse($this->string('date_start')->toString());
            $end = CarbonImmutable::parse($this->string('date_end')->toString());
            if ($start->lt($year->starts_on) || $end->gt($year->ends_on)) {
                $validator->errors()->add('date_start', 'Rentang rekap harus berada di dalam tahun ajaran terpilih.');
            }
        }];
    }

    public function tab(): string
    {
        return $this->string('tab', 'penanganan')->toString();
    }

    /** @return array<string, string|null> */
    public function monitoringParams(): array
    {
        return [
            'period' => $this->input('period'),
            'status' => $this->input('status'),
            'sort' => $this->input('sort', 'tanggal'),
            'direction' => $this->input('direction', 'desc'),
            'page' => (string) $this->input('page', 1),
        ];
    }

    /** @return array{academic_year_id: int, date_start: string, date_end: string} */
    public function recapParams(): array
    {
        return [
            'academic_year_id' => $this->integer('academic_year_id'),
            'date_start' => $this->string('date_start')->toString(),
            'date_end' => $this->string('date_end')->toString(),
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('tab')) {
            $this->merge(['tab' => 'penanganan']);
        }

        if ($this->tab() !== 'rekap') {
            return;
        }

        $year = $this->filled('academic_year_id')
            ? AcademicYear::query()->find($this->integer('academic_year_id'))
            : AcademicYear::query()
                ->orderByDesc('is_active')
                ->orderByDesc('starts_on')
                ->orderByDesc('id')
                ->first();

        if ($year === null) {
            return;
        }

        $this->merge([
            'academic_year_id' => $this->input('academic_year_id', $year->getKey()),
            'date_start' => $this->input('date_start', $year->starts_on?->toDateString()),
            'date_end' => $this->input('date_end', $year->ends_on?->toDateString()),
        ]);
    }
}
