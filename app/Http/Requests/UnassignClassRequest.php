<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\TeacherAssignment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UnassignClassRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', TeacherAssignment::class) ?? false;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return ['user_id' => ['required', 'integer', Rule::exists('users', 'id')]];
    }
}
