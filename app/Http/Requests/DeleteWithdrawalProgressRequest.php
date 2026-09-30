<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\WithdrawalProgress;
use Illuminate\Foundation\Http\FormRequest;

final class DeleteWithdrawalProgressRequest extends FormRequest
{
    public function authorize(): bool
    {
        $withdrawal = $this->route('withdrawal');

        return $withdrawal instanceof WithdrawalProgress
            && ($this->user()?->can('delete', $withdrawal) ?? false);
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [];
    }
}
