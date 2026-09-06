<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SimulationTickRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['time' => 'required|date', 'soc' => 'required|numeric|min:0|max:100', 'weather' => 'required|string', 'grid_mode' => 'required|in:prosumer,zero_export,offline,limited', 'grid_available' => 'required|boolean', 'active_consumers' => 'array', 'active_consumers.*' => 'integer', 'random_weather' => 'boolean', 'tick_minutes' => 'integer|min:1|max:1440'];
    }
}
