<?php

namespace App\Http\Requests\Admin;

use App\Models\Industry;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateIndustryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('industries.update') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $name = (string) $this->input('name', '');
        $slug = (string) $this->input('slug', '');

        $this->merge([
            'slug' => Str::slug($slug !== '' ? $slug : $name),
            'is_active' => $this->boolean('is_active', true),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Industry $industry */
        $industry = $this->route('industry');

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('industries', 'slug')->ignore($industry)],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
