<?php

namespace App\Domains\Seasons\Http\Requests\Admin;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class StoreSeasonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'starts_at' => [Rule::requiredIf(fn () => in_array($this->input('status'), ['scheduled', 'active'], true)), 'nullable', 'date'],
            'ends_at' => [Rule::requiredIf(fn () => in_array($this->input('status'), ['scheduled', 'active'], true)), 'nullable', 'date', 'after:starts_at'],
            'status' => ['sometimes', 'string', 'in:draft,scheduled,active,ended,finalized'],
        ];
    }
}
