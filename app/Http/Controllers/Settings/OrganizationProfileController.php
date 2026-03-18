<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateOrganizationProfileRequest;
use App\Models\Industry;
use App\Models\Organization;
use App\Models\TeamMember;
use App\Support\ActivityLogger;
use App\Support\OrganizationProfileWriter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationProfileController extends Controller
{
    public function edit(Request $request): Response|RedirectResponse
    {
        $applicant = $request->user()?->applicant;

        if ($applicant === null) {
            return to_route('applicant-profile.edit')->with('error', 'Create your presenter profile before creating an organization.');
        }

        $organization = $this->organizationForApplicant($applicant->id);

        if ($organization !== null) {
            $this->authorize('view', $organization);
        } else {
            $this->authorize('create', Organization::class);
        }

        return Inertia::render('settings/OrganizationProfile', [
            'organization' => $organization !== null
                ? $this->organizationPayload($organization)
                : [
                    'legalName' => '',
                    'displayName' => '',
                    'registrationNumber' => '',
                    'industryId' => null,
                    'website' => '',
                    'description' => '',
                    'contactEmail' => $applicant->email,
                    'contactPhone' => $applicant->phone ?? '',
                    'address' => '',
                    'logoFileName' => null,
                    'logoDownloadUrl' => null,
                    'teamMembers' => [
                        [
                            'id' => null,
                            'applicantId' => $applicant->id,
                            'fullName' => $applicant->full_name,
                            'roleTitle' => 'Primary contact',
                            'email' => $applicant->email,
                            'phone' => $applicant->phone ?? '',
                            'bio' => $applicant->bio ?? '',
                            'isPrimaryContact' => true,
                            'linkedApplicantName' => $applicant->full_name,
                        ],
                    ],
                ],
            'industryOptions' => $this->industryOptions(),
        ]);
    }

    public function update(UpdateOrganizationProfileRequest $request, OrganizationProfileWriter $writer): RedirectResponse
    {
        $applicant = $request->user()?->applicant;

        if ($applicant === null) {
            return to_route('applicant-profile.edit')->with('error', 'Create your presenter profile before creating an organization.');
        }

        $organization = $this->organizationForApplicant($applicant->id) ?? new Organization;
        $isNewOrganization = ! $organization->exists;

        if ($organization->exists) {
            $this->authorize('update', $organization);
        } else {
            $this->authorize('create', Organization::class);
        }

        $organization = $writer->sync(
            organization: $organization,
            validated: $request->validated(),
            actor: $request->user(),
            ownerApplicant: $applicant,
            preserveApplicantLinks: true,
            logo: $request->file('logo'),
        );

        ActivityLogger::record(
            actor: $request->user(),
            event: $isNewOrganization ? 'negadras.organizations.created-own-profile' : 'negadras.organizations.updated-own-profile',
            description: $isNewOrganization
                ? "Created organization profile {$organization->display_name}."
                : "Updated organization profile {$organization->display_name}.",
            subject: $organization,
            properties: [
                'team_members_count' => $organization->teamMembers()->count(),
            ],
            request: $request,
        );

        return to_route('organization-profile.edit')->with('success', 'Organization profile saved successfully.');
    }

    /**
     * @return array<int, array{value: int, label: string}>
     */
    private function industryOptions(): array
    {
        return Industry::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Industry $industry): array => [
                'value' => $industry->id,
                'label' => $industry->name,
            ])
            ->all();
    }

    private function organizationForApplicant(int $applicantId): ?Organization
    {
        return Organization::query()
            ->whereHas('teamMembers', function ($query) use ($applicantId): void {
                $query
                    ->where('applicant_id', $applicantId)
                    ->where('is_primary_contact', true);
            })
            ->with(['industry:id,name', 'teamMembers.applicant.user', 'logo'])
            ->first();
    }

    /**
     * @return array<string, mixed>
     */
    private function organizationPayload(Organization $organization): array
    {
        return [
            'legalName' => $organization->legal_name,
            'displayName' => $organization->display_name,
            'registrationNumber' => $organization->registration_number,
            'industryId' => $organization->industry_id,
            'website' => $organization->website,
            'description' => $organization->description,
            'contactEmail' => $organization->contact_email,
            'contactPhone' => $organization->contact_phone,
            'address' => $organization->address,
            'logoFileName' => $organization->logo?->original_name,
            'logoDownloadUrl' => $organization->logo ? route('media.download', $organization->logo) : null,
            'teamMembers' => $organization->teamMembers
                ->map(fn (TeamMember $teamMember): array => [
                    'id' => $teamMember->id,
                    'applicantId' => $teamMember->applicant_id,
                    'fullName' => $teamMember->full_name,
                    'roleTitle' => $teamMember->role_title,
                    'email' => $teamMember->email,
                    'phone' => $teamMember->phone,
                    'bio' => $teamMember->bio,
                    'isPrimaryContact' => $teamMember->is_primary_contact,
                    'linkedApplicantName' => $teamMember->applicant?->full_name,
                ])
                ->values()
                ->all(),
        ];
    }
}
