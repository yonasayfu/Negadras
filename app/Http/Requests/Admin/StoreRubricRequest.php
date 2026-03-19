<?php

namespace App\Http\Requests\Admin;

use App\Models\Industry;
use App\Models\Rubric;
use App\Models\Stage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreRubricRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Rubric::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['boolean'],
            'stage_ids' => ['array'],
            'stage_ids.*' => ['integer', Rule::exists(Stage::class, 'id')],
            'industry_ids' => ['array'],
            'industry_ids.*' => ['integer', Rule::exists(Industry::class, 'id')],
            'criteria' => ['required', 'array', 'min:1'],
            'criteria.*.name' => ['required', 'string', 'max:255'],
            'criteria.*.description' => ['nullable', 'string', 'max:2000'],
            'criteria.*.max_score' => ['required', 'numeric', 'min:1', 'max:100'],
            'criteria.*.weight' => ['required', 'numeric', 'min:1', 'max:100'],
            'criteria.*.order_index' => ['nullable', 'integer', 'min:1'],
            'criteria.*.is_required' => ['boolean'],
            'criteria.*.visibility_rule' => ['nullable', 'string', 'max:255'],
            'criteria.*.help_text' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $criteria = collect($this->input('criteria', []));

            if ($criteria->sum(fn (array $criterion): float => (float) ($criterion['weight'] ?? 0)) <= 0) {
                $validator->errors()->add('criteria', 'Rubrics must contain at least one weighted criterion.');
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $criteria = collect($this->input('criteria', []))
            ->values()
            ->map(fn (array $criterion, int $index): array => [
                'name' => $criterion['name'] ?? null,
                'description' => $criterion['description'] ?? null,
                'max_score' => $criterion['max_score'] ?? null,
                'weight' => $criterion['weight'] ?? null,
                'order_index' => $criterion['order_index'] ?? ($index + 1),
                'is_required' => filter_var($criterion['is_required'] ?? false, FILTER_VALIDATE_BOOL),
                'visibility_rule' => $criterion['visibility_rule'] ?? null,
                'help_text' => $criterion['help_text'] ?? null,
            ])
            ->all();

        $this->merge([
            'is_active' => $this->boolean('is_active', true),
            'criteria' => $criteria,
        ]);
    }
}
