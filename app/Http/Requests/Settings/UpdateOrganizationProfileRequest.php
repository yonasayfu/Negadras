<?php

namespace App\Http\Requests\Settings;

use App\Models\Organization;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateOrganizationProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeTeamMembers();
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $organizationId = $this->currentOrganizationId();

        return [
            'legal_name' => ['required', 'string', 'max:255'],
            'display_name' => ['required', 'string', 'max:255'],
            'registration_number' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('organizations', 'registration_number')->ignore($organizationId),
            ],
            'industry_id' => ['nullable', 'integer', 'exists:industries,id'],
            'website' => ['nullable', 'url:http,https', 'max:255'],
            'description' => ['nullable', 'string', 'max:4000'],
            'contact_email' => ['nullable', 'email:rfc', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:4000'],
            'logo' => ['nullable', 'file', 'image', 'max:5120'],
            'team_members' => ['required', 'array', 'min:1'],
            'team_members.*.id' => ['nullable', 'integer'],
            'team_members.*.full_name' => ['required', 'string', 'max:255'],
            'team_members.*.role_title' => ['required', 'string', 'max:255'],
            'team_members.*.email' => ['nullable', 'email:rfc', 'max:255'],
            'team_members.*.phone' => ['nullable', 'string', 'max:30'],
            'team_members.*.bio' => ['nullable', 'string', 'max:2000'],
            'team_members.*.is_primary_contact' => ['boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $teamMembers = collect($this->input('team_members', []));
            $primaryContacts = $teamMembers
                ->filter(fn (array $member): bool => (bool) ($member['is_primary_contact'] ?? false))
                ->count();

            if ($primaryContacts !== 1) {
                $validator->errors()->add('team_members', 'Exactly one primary contact is required.');
            }

            $primaryIndex = $teamMembers->search(fn (array $member): bool => (bool) ($member['is_primary_contact'] ?? false));

            if (! is_int($primaryIndex) && ! is_string($primaryIndex)) {
                return;
            }

            $primaryMember = $teamMembers->get($primaryIndex);
            $applicant = $this->user()?->applicant;

            if ($applicant === null) {
                $validator->errors()->add('team_members', 'Create your presenter profile before creating an organization.');

                return;
            }

            if (($primaryMember['email'] ?? null) !== null && $primaryMember['email'] !== $applicant->email) {
                $validator->errors()->add('team_members.'.$primaryIndex.'.email', 'Primary contact email must match your presenter profile.');
            }
        });
    }

    private function currentOrganizationId(): ?int
    {
        $applicantId = $this->user()?->applicant?->id;

        if ($applicantId === null) {
            return null;
        }

        return Organization::query()
            ->whereHas('teamMembers', function ($query) use ($applicantId): void {
                $query
                    ->where('applicant_id', $applicantId)
                    ->where('is_primary_contact', true);
            })
            ->value('id');
    }

    private function normalizeTeamMembers(): void
    {
        $teamMembers = collect($this->input('team_members', []))
            ->filter(function ($member): bool {
                return filled($member['full_name'] ?? null)
                    || filled($member['role_title'] ?? null)
                    || filled($member['email'] ?? null)
                    || filled($member['phone'] ?? null);
            })
            ->map(fn ($member): array => [
                'id' => filled($member['id'] ?? null) ? (int) $member['id'] : null,
                'full_name' => (string) ($member['full_name'] ?? ''),
                'role_title' => (string) ($member['role_title'] ?? ''),
                'email' => filled($member['email'] ?? null) ? (string) $member['email'] : null,
                'phone' => filled($member['phone'] ?? null) ? (string) $member['phone'] : null,
                'bio' => filled($member['bio'] ?? null) ? (string) $member['bio'] : null,
                'is_primary_contact' => (bool) ($member['is_primary_contact'] ?? false),
            ])
            ->values()
            ->all();

        $this->merge([
            'team_members' => $teamMembers,
        ]);
    }
}
