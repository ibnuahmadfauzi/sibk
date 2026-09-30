<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\WithdrawalProgress;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateWithdrawalProgressRequest extends FormRequest
{
    public function authorize(): bool
    {
        $withdrawal = $this->route('withdrawal');

        return $withdrawal instanceof WithdrawalProgress
            && ($this->user()?->can('update', $withdrawal) ?? false);
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'recorded_on' => ['required', 'date', 'before_or_equal:today'],
            'note' => ['nullable', 'string', 'max:1000'],
            'reason' => ['required', 'string', 'max:2000'],
        ];
    }
}
