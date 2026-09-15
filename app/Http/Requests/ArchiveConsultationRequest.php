<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Consultation;
use Illuminate\Foundation\Http\FormRequest;

class ArchiveConsultationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $consultation = $this->route('consultation');

        return $consultation instanceof Consultation
            && ($this->user()?->can('archive', $consultation) ?? false);
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [];
    }
}
