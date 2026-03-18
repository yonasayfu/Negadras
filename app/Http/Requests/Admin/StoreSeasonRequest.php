<?php

namespace App\Http\Requests\Admin;

use App\SeasonStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreSeasonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('seasons.create') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $name = (string) $this->input('name', '');
        $slug = (string) $this->input('slug', '');

        $this->merge([
            'slug' => Str::slug($slug !== '' ? $slug : $name),
            'status' => (string) $this->input('status', SeasonStatus::Draft->value),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('seasons', 'slug')],
            'status' => ['required', Rule::enum(SeasonStatus::class)],
            'registration_open_at' => ['nullable', 'date'],
            'registration_close_at' => ['nullable', 'date', 'after_or_equal:registration_open_at'],
            'description' => ['nullable', 'string', 'max:4000'],
        ];
    }
}
