<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreProvisionalAcademicYearRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manageDataMaster') ?? false;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:20', 'regex:/^\d{4}\/\d{4}$/D'],
            'starts_on' => ['required', 'date_format:Y-m-d'],
            'ends_on' => ['required', 'date_format:Y-m-d', 'after:starts_on'],
            'preparation_reference' => ['required', 'string', 'max:500'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama tahun ajaran wajib diisi.',
            'name.regex' => 'Nama tahun ajaran harus memakai format 2027/2028.',
            'starts_on.required' => 'Tanggal mulai wajib diisi.',
            'starts_on.date_format' => 'Tanggal mulai tidak valid.',
            'ends_on.required' => 'Tanggal selesai wajib diisi.',
            'ends_on.date_format' => 'Tanggal selesai tidak valid.',
            'ends_on.after' => 'Tanggal selesai harus setelah tanggal mulai.',
            'preparation_reference.required' => 'Dasar resmi sekolah wajib diisi.',
            'preparation_reference.max' => 'Dasar resmi sekolah maksimal 500 karakter.',
        ];
    }
}
