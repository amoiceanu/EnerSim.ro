<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportEquipmentFeedRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'feed' => ['required', 'file', 'mimes:xlsx', 'max:10240'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'feed.required' => 'Selectează un fișier Excel pentru import.',
            'feed.mimes' => 'Feedul trebuie să fie un fișier .xlsx.',
            'feed.max' => 'Feedul nu poate depăși 10 MB.',
        ];
    }
}
