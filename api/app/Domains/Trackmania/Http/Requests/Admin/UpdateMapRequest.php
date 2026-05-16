<?php

namespace App\Domains\Trackmania\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMapRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'author_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'map_type' => ['sometimes', 'nullable', 'string', 'max:255'],
            'map_style' => ['sometimes', 'nullable', 'string', 'max:255'],
            'collection_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'thumbnail_url' => ['sometimes', 'nullable', 'url', 'max:2048'],
            'author_time' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'gold_time' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'silver_time' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'bronze_time' => ['sometimes', 'nullable', 'integer', 'min:0'],
        ];
    }
}
