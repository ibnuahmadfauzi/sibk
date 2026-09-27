<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\WithdrawalProgress;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'progress' => ['required', 'string', Rule::in(array_keys(WithdrawalProgress::labels()))],
        ];
    }
}
