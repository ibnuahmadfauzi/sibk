<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Correction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProcessMasterCorrectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $correction = $this->route('correction');

        return $correction instanceof Correction && ($this->user()?->can('processMaster', $correction) ?? false);
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in(['processing', 'completed', 'rejected'])],
            'review_notes' => ['nullable', 'string', 'max:10000', Rule::requiredIf(fn (): bool => $this->input('action') === 'rejected')],
            'external_sync_run_id' => ['nullable', 'integer', Rule::exists('external_sync_runs', 'id'), Rule::requiredIf(fn (): bool => $this->input('action') === 'completed')],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'action.required' => 'Tindakan pemrosesan wajib dipilih.',
            'action.in' => 'Tindakan pemrosesan tidak tersedia.',
            'review_notes.required' => 'Catatan wajib diisi bila laporan koreksi master ditolak.',
            'external_sync_run_id.required' => 'Hasil sinkronisasi Dapodik wajib dipilih saat koreksi dinyatakan selesai.',
            'external_sync_run_id.exists' => 'Log sinkronisasi Dapodik tidak tersedia.',
        ];
    }
}
