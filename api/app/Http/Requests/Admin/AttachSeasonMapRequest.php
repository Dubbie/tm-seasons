<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AttachSeasonMapRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $seasonId = $this->route('season')?->id;

        return [
            'map_id' => [
                'required',
                'integer',
                'exists:maps,id',
                Rule::unique('season_maps', 'map_id')->where(fn ($query) => $query->where('season_id', $seasonId)),
            ],
            'order_index' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
