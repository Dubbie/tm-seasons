<?php

namespace App\Domains\Trackmania\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ImportMapRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'uid' => ['required', 'string', 'max:255'],
        ];
    }
}
