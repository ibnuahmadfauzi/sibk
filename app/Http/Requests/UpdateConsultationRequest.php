<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Consultation;

class UpdateConsultationRequest extends StoreConsultationRequest
{
    public function authorize(): bool
    {
        $consultation = $this->route('consultation');

        return $consultation instanceof Consultation
            && ($this->user()?->can('update', $consultation) ?? false);
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return $this->consultationRules();
    }
}
