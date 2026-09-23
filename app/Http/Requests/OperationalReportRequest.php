<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\User;
use App\Policies\ReportPolicy;
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

        if ($user === null) {
            return false;
        }

        $policy = app(ReportPolicy::class);

        return $this->routeIs('reports.index')
            ? $policy->viewAny($user)
            : $policy->viewDocument($user);
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'academic_year_id' => ['nullable', 'integer', 'exists:academic_years,id'],
            'classroom_id' => ['nullable', 'integer', 'exists:classrooms,id'],
            'service_type' => ['required', Rule::in(['all', 'case', 'consultation'])],
            'per_page' => ['required', 'integer', Rule::in([10, 25, 50, 100])],
            'page' => ['nullable', 'integer', 'min:1'],
            'format' => [
                Rule::requiredIf($this->routeIs('reports.export')),
                'nullable',
                Rule::in(['xlsx']),
            ],
        ];
    }

    /** @return array<string, mixed> */
    public function filters(): array
    {
        return Arr::only($this->validated(), [
            'academic_year_id',
            'classroom_id',
            'service_type',
            'per_page',
            'page',
        ]);
    }

    /** @return list<callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if (! $this->filled('classroom_id')) {
                return;
            }

            /** @var User|null $actor */
            $actor = $this->user();
            $allowed = $actor !== null && Classroom::query()
                ->whereKey($this->integer('classroom_id'))
                ->when(
                    $this->filled('academic_year_id'),
                    fn (Builder $query): Builder => $query->where(
                        'academic_year_id',
                        $this->integer('academic_year_id'),
                    ),
                )
                ->when(
                    ! $actor->hasRole('waka_kesiswaan'),
                    fn (Builder $query): Builder => $query->whereHas(
                        'studentClassMemberships.student',
                        fn (Builder $students): Builder => $students->accessibleTo($actor),
                    ),
                )
                ->exists();

            if (! $allowed) {
                $validator->errors()->add(
                    'classroom_id',
                    'Kelas tidak tersedia pada tahun ajaran atau kewenangan Anda.',
                );
            }
        }];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'academic_year_id.exists' => 'Tahun ajaran tidak tersedia.',
            'classroom_id.exists' => 'Kelas tidak tersedia.',
            'service_type.required' => 'Jenis layanan wajib dipilih.',
            'service_type.in' => 'Jenis layanan tidak tersedia.',
            'per_page.in' => 'Jumlah data harus 10, 25, 50, atau 100.',
            'format.required' => 'Format unduhan wajib dipilih.',
            'format.in' => 'Format unduhan hanya Excel.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $yearId = $this->input('academic_year_id');
        if (blank($yearId)) {
            $yearId = AcademicYear::query()
                ->active()
                ->orderByDesc('starts_on')
                ->value('id')
                ?? AcademicYear::query()->orderByDesc('starts_on')->value('id');
        }

        $this->merge([
            'academic_year_id' => $yearId,
            'service_type' => $this->input('service_type', 'all'),
            'per_page' => $this->input('per_page', 10),
        ]);
    }

    protected function getRedirectUrl(): string
    {
        return route('reports.index');
    }
}
