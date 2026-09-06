<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'county' => ['required', 'string', 'max:80'],
            'city' => ['required', 'string', 'max:100'],
            'latitude' => ['nullable', 'numeric', 'between:43,49'],
            'longitude' => ['nullable', 'numeric', 'between:20,30'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'consumers' => ['required', 'array', 'min:1', 'max:100'],
            'consumers.*.name' => ['required', 'string', 'max:100'],
            'consumers.*.quantity' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'consumers.*.nominal_power_w' => ['required', 'integer', 'min:1', 'max:50000'],
            'consumers.*.startup_power_w' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'consumers.*.hours_per_day' => ['required', 'numeric', 'min:0.01', 'max:24'],
            'consumers.*.priority' => ['required', 'integer', 'between:1,3'],
            'consumers.*.is_essential' => ['nullable', 'boolean'],
            'system_name' => ['required', 'string', 'max:120'],
            'panel_count' => ['required', 'integer', 'min:1', 'max:200'],
            'panel_power_w' => ['required', 'integer', 'min:50', 'max:1000'],
            'orientation' => ['required', 'in:N,NE,E,SE,S,SV,V,NV'],
            'tilt' => ['required', 'integer', 'between:0,90'],
            'losses_percent' => ['required', 'numeric', 'between:0,60'],
            'inverter_name' => ['required', 'string', 'max:120'],
            'inverter_power_w' => ['required', 'integer', 'min:100', 'max:100000'],
            'battery_enabled' => ['nullable', 'boolean'],
            'battery_capacity_kwh' => ['required_if:battery_enabled,1', 'numeric', 'min:0.5', 'max:500'],
            'battery_power_w' => ['required_if:battery_enabled,1', 'integer', 'min:100', 'max:100000'],
            'initial_soc' => ['nullable', 'numeric', 'between:10,100'],
        ];
    }
}
