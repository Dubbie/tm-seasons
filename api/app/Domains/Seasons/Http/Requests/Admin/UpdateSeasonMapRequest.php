<?php

namespace App\Domains\Seasons\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSeasonMapRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_index' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
