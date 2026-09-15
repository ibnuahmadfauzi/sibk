<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\BkCase;
use Illuminate\Foundation\Http\FormRequest;

class ArchiveCaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        $case = $this->route('case');

        return $case instanceof BkCase && ($this->user()?->can('archive', $case) ?? false);
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [];
    }
}
