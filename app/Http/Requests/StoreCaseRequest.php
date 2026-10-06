<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\BkCase;
use App\Models\ExternalTatibRecord;
use App\Models\ReferenceValue;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', BkCase::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('etatib_record_id') && ! $this->filled('etatib_record_ids')) {
            $this->merge([
                'etatib_record_ids' => [(int) $this->input('etatib_record_id')],
            ]);
        }

        $sourceId = $this->integer('case_source_id');
        $source = ReferenceValue::query()->find($sourceId);
        $isEtatib = $source?->code === 'e_tatib';

        if ($isEtatib && $this->filled('etatib_record_ids')) {
            $etatibIds = (array) $this->input('etatib_record_ids');
            $firstId = reset($etatibIds);
            if ($firstId) {
                $record = ExternalTatibRecord::query()->find($firstId);
                if ($record) {
                    if ($record->student_id && ! $this->filled('student_id')) {
                        $this->merge(['student_id' => $record->student_id]);
                    }
                    if (! $this->filled('student_id')) {
                        $this->merge([
                            'temporary_nisn' => $record->nisn,
                            'temporary_name' => $record->source_student_name ?: 'Murid e-Tatib',
                        ]);
                    }
                }
            }
        } elseif (! $isEtatib) {
            // Bersihkan data e-tatib bila bukan sumber e-tatib
            $this->merge(['etatib_record_ids' => []]);
            if ($this->filled('student_id')) {
                $this->merge([
                    'temporary_nisn' => null,
                    'temporary_name' => null,
                    'temporary_classroom_id' => null,
                ]);
            } elseif ($this->filled('temporary_nisn')) {
                $this->merge(['student_id' => null]);
            }
        }
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        /** @var User|null $actor */
        $actor = $this->user();
        $sourceId = $this->integer('case_source_id');
        $source = ReferenceValue::query()->find($sourceId);
        $isEtatib = $source?->code === 'e_tatib';

        $studentId = $this->integer('student_id') ?: null;
        $temporaryNisn = $this->string('temporary_nisn')->trim()->toString();

        $etatibRecordExists = Rule::exists('external_tatib_records', 'id')->where(
            function ($records) use ($actor, $studentId, $temporaryNisn): void {
                $records->where('is_active', true);

                if ($studentId !== null && $actor !== null) {
                    $records
                        ->where('student_id', $studentId)
                        ->whereIn('student_id', Student::query()
                            ->active()
                            ->forActiveTeacherAssignment($actor)
                            ->select('students.id'));

                    return;
                }

                if ($temporaryNisn !== '') {
                    $records->whereNull('student_id')->where('nisn', $temporaryNisn);

                    return;
                }

                $records->whereRaw('1 = 0');
            },
        );

        $rules = [
            'case_source_id' => ['required', 'integer', Rule::exists('references', 'id')->where('category', 'case_source')->where('is_active', true)],
            'service_field_id' => ['required', 'integer', Rule::exists('references', 'id')->where('category', 'service_field')->where('is_active', true)],
            'service_date' => ['required', 'date', 'before_or_equal:today'],
            'referrer' => ['nullable', 'string', 'max:150'],
            'initial_info' => ['required', 'string', 'max:10000'],
            'initial_action' => ['required', 'string', 'max:10000'],
            'resolution_summary' => ['nullable', 'string', 'max:10000'],
            'internal_note' => ['nullable', 'string', 'max:10000'],
        ];

        if ($isEtatib) {
            $rules['etatib_record_ids'] = ['required', 'array', 'min:1'];
            $rules['etatib_record_ids.*'] = ['integer', 'distinct', $etatibRecordExists];
            $rules['student_id'] = ['nullable', 'integer', Rule::exists('students', 'id')];
            $rules['temporary_nisn'] = ['nullable', 'string', 'max:20', 'regex:/^[0-9]+$/'];
            $rules['temporary_name'] = ['nullable', 'string', 'max:150'];
            $rules['temporary_classroom_id'] = ['nullable', 'integer', Rule::exists('classrooms', 'id')];
        } else {
            $rules['student_id'] = ['nullable', 'integer', 'prohibits:temporary_nisn,temporary_name', Rule::exists('students', 'id')];
            $rules['temporary_nisn'] = ['nullable', 'string', 'max:20', 'regex:/^[0-9]+$/', 'required_without:student_id', 'prohibits:student_id'];
            $rules['temporary_name'] = ['nullable', 'string', 'max:150', 'required_without:student_id'];
            $rules['temporary_classroom_id'] = ['nullable', 'integer', 'prohibits:student_id', Rule::exists('classrooms', 'id')];
            $rules['etatib_record_ids'] = ['sometimes', 'array'];
        }

        return $rules;
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'student_id.required_without' => 'Pilih murid atau isi data murid baru.',
            'student_id.prohibits' => 'Pilih hanya satu jenis identitas murid.',
            'student_id.exists' => 'Murid yang dipilih tidak tersedia.',
            'temporary_nisn.required_without' => 'NISN murid wajib diisi.',
            'temporary_nisn.regex' => 'NISN hanya boleh berisi angka.',
            'temporary_nisn.prohibits' => 'Pilih murid di daftar atau isi data murid baru.',
            'temporary_name.required_without' => 'Nama murid wajib diisi.',
            'temporary_classroom_id.required_without' => 'Rombel murid wajib dipilih.',
            'temporary_classroom_id.exists' => 'Rombel yang dipilih tidak valid.',
            'case_source_id.required' => 'Sumber permasalahan wajib dipilih.',
            'case_source_id.exists' => 'Sumber permasalahan tidak tersedia.',
            'service_field_id.required' => 'Jenis masalah wajib dipilih.',
            'service_field_id.exists' => 'Jenis masalah tidak tersedia.',
            'service_date.required' => 'Tanggal layanan wajib diisi.',
            'service_date.before_or_equal' => 'Tanggal layanan tidak boleh berada di masa depan.',
            'initial_info.required' => 'Latar belakang permasalahan wajib diisi.',
            'initial_action.required' => 'Tindakan penanganan wajib diisi.',
            'etatib_record_ids.required' => 'Pilih salah satu data pelanggaran e-Tatib.',
            'etatib_record_ids.min' => 'Pilih salah satu data pelanggaran e-Tatib.',
            'etatib_record_ids.*.exists' => 'Data e-Tatib yang dipilih tidak tersedia.',
            'etatib_record_ids.*.distinct' => 'Data e-Tatib tidak boleh dipilih lebih dari sekali.',
        ];
    }
}
