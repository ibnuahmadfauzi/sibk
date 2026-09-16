<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Policies\WakaMonitoringPolicy;
use App\Support\ServiceRecordStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WakaMonitoringRequest extends FormRequest
{
    /**
     * Kolom yang diizinkan untuk sorting — nilai request tidak boleh dipakai langsung sebagai nama kolom SQL.
     *
     * @var list<string>
     */
    public const array HANDLING_SORT_ALLOWLIST = ['murid', 'kelas', 'bidang', 'status', 'guru_bk', 'tanggal'];

    /** @var list<string> */
    public const array STUDENT_SORT_ALLOWLIST = ['murid', 'kelas', 'status', 'guru_bk'];

    public function authorize(): bool
    {
        $user = $this->user();

        if ($user === null) {
            return false;
        }

        $policy = app(WakaMonitoringPolicy::class);

        return $this->routeIs('waka.monitoring.export')
            ? $policy->exportMonitoring($user)
            : $policy->viewMonitoring($user);
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        $sortAllowlist = $this->routeIs('waka.monitoring.students')
            ? self::STUDENT_SORT_ALLOWLIST
            : self::HANDLING_SORT_ALLOWLIST;

        return [
            'period' => ['nullable', 'string', 'regex:/^\d{4}-\d{2}$/'],
            'status' => ['nullable', 'string', Rule::in(ServiceRecordStatus::codes())],
            'sort' => ['nullable', 'string', Rule::in($sortAllowlist)],
            'direction' => ['nullable', 'string', Rule::in(['asc', 'desc'])],
            'page' => ['nullable', 'integer', 'min:1'],
            'format' => ['nullable', 'string', Rule::in(['csv'])],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'period.regex' => 'Format periode harus YYYY-MM.',
            'status.in' => 'Status tidak dikenali.',
            'sort.in' => 'Pilihan urutan tidak tersedia.',
            'direction.in' => 'Arah urutan harus asc atau desc.',
            'format.in' => 'Format ekspor tidak tersedia.',
        ];
    }

    /**
     * Kembalikan parameter yang sudah dinormalisasi untuk keperluan audit dan query.
     *
     * @return array<string, string|null>
     */
    public function normalizedParams(): array
    {
        $defaultSort = $this->routeIs('waka.monitoring.students') ? 'murid' : 'tanggal';

        return [
            'period' => $this->input('period'),
            'status' => $this->input('status'),
            'sort' => $this->input('sort', $defaultSort),
            'direction' => $this->input('direction', 'desc'),
            'page' => (string) $this->input('page', 1),
        ];
    }
}
