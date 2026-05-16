<?php

namespace App\Domains\Seasons\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSeasonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'starts_at' => [Rule::requiredIf(fn () => in_array($this->input('status', $this->route('season')?->status?->value), ['scheduled', 'active'], true)), 'nullable', 'date'],
            'ends_at' => [Rule::requiredIf(fn () => in_array($this->input('status', $this->route('season')?->status?->value), ['scheduled', 'active'], true)), 'nullable', 'date', 'after:starts_at'],
            'status' => ['sometimes', 'string', 'in:draft,scheduled,active,ended,finalized'],
        ];
    }
}
