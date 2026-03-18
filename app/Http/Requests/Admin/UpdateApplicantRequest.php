<?php

namespace App\Http\Requests\Admin;

use App\ApplicantType;
use App\Models\Applicant;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateApplicantRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $applicant = $this->route('applicant');

        return $this->user()?->can('update', $applicant instanceof Applicant ? $applicant : Applicant::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $socialLinks = collect($this->input('social_links', []))
            ->filter(fn ($socialLink) => filled($socialLink['platform'] ?? null) || filled($socialLink['url'] ?? null))
            ->map(fn ($socialLink): array => [
                'platform' => (string) ($socialLink['platform'] ?? ''),
                'url' => (string) ($socialLink['url'] ?? ''),
                'is_verified' => (bool) ($socialLink['is_verified'] ?? false),
            ])
            ->values()
            ->all();

        $this->merge([
            'social_links' => $socialLinks,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Applicant $applicant */
        $applicant = $this->route('applicant');

        return [
            'applicant_type' => ['required', Rule::enum(ApplicantType::class)],
            'full_name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email:rfc',
                'max:255',
                Rule::unique('applicants', 'email')->ignore($applicant->id),
            ],
            'phone' => [
                'nullable',
                'string',
                'max:30',
                Rule::unique('applicants', 'phone')->ignore($applicant->id),
            ],
            'bio' => ['nullable', 'string', 'max:4000'],
            'national_id_or_registration_ref' => ['nullable', 'string', 'max:255'],
            'social_links' => ['array'],
            'social_links.*.platform' => ['required', 'string', 'max:80'],
            'social_links.*.url' => ['required', 'url:http,https', 'max:2048'],
            'social_links.*.is_verified' => ['boolean'],
        ];
    }
}
