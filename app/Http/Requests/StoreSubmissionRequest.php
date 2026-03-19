<?php

namespace App\Http\Requests;

use App\Models\Organization;
use App\Models\Stage;
use App\Models\Submission;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Submission::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $intent = $this->string('intent')->toString() ?: 'draft';
        $submitting = $intent === 'submit';

        return [
            'intent' => ['required', Rule::in(['draft', 'submit'])],
            'season_id' => ['required', 'integer', 'exists:seasons,id'],
            'current_stage_id' => ['required', 'integer', 'exists:stages,id'],
            'industry_id' => ['required', 'integer', 'exists:industries,id'],
            'organization_id' => ['nullable', 'integer', 'exists:organizations,id'],
            'title' => ['required', 'string', 'max:255'],
            'summary' => [$submitting ? 'required' : 'nullable', 'string', 'max:4000'],
            'problem_statement' => [$submitting ? 'required' : 'nullable', 'string', 'max:10000'],
            'solution_description' => [$submitting ? 'required' : 'nullable', 'string', 'max:10000'],
            'business_model' => [$submitting ? 'required' : 'nullable', 'string', 'max:10000'],
            'is_public_after_approval' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'intent' => $this->string('intent')->toString() ?: 'draft',
            'is_public_after_approval' => $this->boolean('is_public_after_approval'),
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $seasonId = $this->integer('season_id');
            $stageId = $this->integer('current_stage_id');

            if ($stageId > 0 && $seasonId > 0) {
                $matchesSeason = Stage::query()
                    ->whereKey($stageId)
                    ->where('season_id', $seasonId)
                    ->exists();

                if (! $matchesSeason) {
                    $validator->errors()->add('current_stage_id', 'Selected stage must belong to the selected season.');
                }
            }

            $organizationId = $this->integer('organization_id');
            $applicantId = $this->user()?->applicant?->id;

            if ($organizationId > 0 && $applicantId !== null) {
                $managedOrganization = Organization::query()
                    ->whereKey($organizationId)
                    ->whereHas('teamMembers', function ($query) use ($applicantId): void {
                        $query
                            ->where('applicant_id', $applicantId)
                            ->where('is_primary_contact', true);
                    })
                    ->exists();

                if (! $managedOrganization) {
                    $validator->errors()->add('organization_id', 'You can only attach an organization you manage as the primary contact.');
                }
            }
        });
    }
}
