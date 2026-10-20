<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateEtatibAutomaticScheduleRequest extends FormRequest
{
    protected $errorBag = 'etatib_schedule';

    public function authorize(): bool
    {
        return $this->user()?->can('manageDataMaster') ?? false;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'schedule_days' => ['required', 'array', 'min:1', 'max:7'],
            'schedule_days.*' => ['required', 'integer', 'between:0,6', 'distinct'],
            'schedule_times' => ['required', 'array', 'min:1', 'max:24'],
            'schedule_times.*' => ['required', 'date_format:H:i', 'distinct'],
            'current_password' => ['required', 'string', 'current_password:web'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'schedule_days.required' => 'Pilih minimal satu hari.',
            'schedule_days.array' => 'Pilihan hari tidak valid.',
            'schedule_days.min' => 'Pilih minimal satu hari.',
            'schedule_days.max' => 'Hari yang dipilih terlalu banyak.',
            'schedule_days.*.integer' => 'Hari harus berupa angka.',
            'schedule_days.*.between' => 'Hari yang dipilih tidak valid.',
            'schedule_days.*.distinct' => 'Hari yang sama tidak boleh dipilih dua kali.',
            'schedule_times.required' => 'Isi minimal satu jam.',
            'schedule_times.array' => 'Daftar jam tidak valid.',
            'schedule_times.min' => 'Isi minimal satu jam.',
            'schedule_times.max' => 'Maksimal 24 jam pembaruan.',
            'schedule_times.*.date_format' => 'Gunakan format jam HH:MM.',
            'schedule_times.*.distinct' => 'Jam yang sama tidak boleh diulang.',
            'current_password.required' => 'Kata sandi saat ini wajib diisi.',
            'current_password.current_password' => 'Kata sandi saat ini tidak sesuai.',
        ];
    }

    /** @return list<int> */
    public function days(): array
    {
        return array_values(array_map('intval', $this->validated('schedule_days')));
    }

    /** @return list<string> */
    public function times(): array
    {
        return array_values($this->validated('schedule_times'));
    }
}
