<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreOrganizationRequest;
use App\Http\Requests\Admin\UpdateOrganizationRequest;
use App\Models\Industry;
use App\Models\Organization;
use App\Models\TeamMember;
use App\Support\ActivityLogger;
use App\Support\OrganizationProfileWriter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationManagementController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Organization::class);

        $search = $request->string('search')->trim()->toString();

        return Inertia::render('admin/Organizations/Index', [
            'organizations' => Organization::query()
                ->with(['industry:id,name', 'primaryContact', 'logo'])
                ->withCount('teamMembers')
                ->when($search !== '', function ($query) use ($search): void {
                    $query->where(function ($organizationQuery) use ($search): void {
                        $organizationQuery
                            ->where('legal_name', 'ilike', "%{$search}%")
                            ->orWhere('display_name', 'ilike', "%{$search}%")
                            ->orWhere('registration_number', 'ilike', "%{$search}%")
                            ->orWhere('contact_email', 'ilike', "%{$search}%");
                    });
                })
                ->orderBy('display_name')
                ->paginate(10)
                ->withQueryString()
                ->through(fn (Organization $organization): array => $this->organizationSummary($organization)),
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Organization::class);

        return Inertia::render('admin/Organizations/Create', [
            'industryOptions' => $this->industryOptions(),
            'organization' => [
                'teamMembers' => [
                    $this->blankTeamMember(),
                ],
            ],
        ]);
    }

    public function store(StoreOrganizationRequest $request, OrganizationProfileWriter $writer): RedirectResponse
    {
        $this->authorize('create', Organization::class);

        $organization = $writer->sync(
            organization: new Organization,
            validated: $request->validated(),
            actor: $request->user(),
            logo: $request->file('logo'),
        );

        ActivityLogger::record(
            actor: $request->user(),
            event: 'negadras.organizations.created',
            description: "Created organization {$organization->display_name}.",
            subject: $organization,
            properties: [
                'team_members_count' => $organization->teamMembers()->count(),
            ],
            request: $request,
        );

        return to_route('organizations.edit', $organization)->with('success', 'Organization created successfully.');
    }

    public function edit(Organization $organization): Response
    {
        $this->authorize('view', $organization);

        $organization->load(['industry:id,name', 'teamMembers.applicant.user', 'logo']);

        return Inertia::render('admin/Organizations/Edit', [
            'organization' => $this->organizationDetail($organization),
            'industryOptions' => $this->industryOptions(),
        ]);
    }

    public function update(UpdateOrganizationRequest $request, Organization $organization, OrganizationProfileWriter $writer): RedirectResponse
    {
        $this->authorize('update', $organization);

        $organization = $writer->sync(
            organization: $organization,
            validated: $request->validated(),
            actor: $request->user(),
            preserveApplicantLinks: true,
            logo: $request->file('logo'),
        );

        ActivityLogger::record(
            actor: $request->user(),
            event: 'negadras.organizations.updated',
            description: "Updated organization {$organization->display_name}.",
            subject: $organization,
            properties: [
                'team_members_count' => $organization->teamMembers()->count(),
            ],
            request: $request,
        );

        return to_route('organizations.edit', $organization)->with('success', 'Organization updated successfully.');
    }

    public function destroy(Request $request, Organization $organization): RedirectResponse
    {
        $this->authorize('delete', $organization);

        ActivityLogger::record(
            actor: $request->user(),
            event: 'negadras.organizations.deleted',
            description: "Deleted organization {$organization->display_name}.",
            subject: $organization,
            request: $request,
        );

        $organization->delete();

        return to_route('organizations.index')->with('success', 'Organization deleted successfully.');
    }

    /**
     * @return array<int, array{value: number, label: string}>
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

    /**
     * @return array<string, mixed>
     */
    private function organizationSummary(Organization $organization): array
    {
        return [
            'id' => $organization->id,
            'legalName' => $organization->legal_name,
            'displayName' => $organization->display_name,
            'registrationNumber' => $organization->registration_number,
            'industryName' => $organization->industry?->name,
            'website' => $organization->website,
            'contactEmail' => $organization->contact_email,
            'contactPhone' => $organization->contact_phone,
            'teamMembersCount' => $organization->team_members_count ?? 0,
            'primaryContactName' => $organization->primaryContact?->full_name,
            'logoFileName' => $organization->logo?->original_name,
            'createdAt' => $organization->created_at?->toDateTimeString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function organizationDetail(Organization $organization): array
    {
        return [
            ...$this->organizationSummary($organization),
            'description' => $organization->description,
            'address' => $organization->address,
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

    /**
     * @return array<string, mixed>
     */
    private function blankTeamMember(): array
    {
        return [
            'id' => null,
            'applicantId' => null,
            'fullName' => '',
            'roleTitle' => '',
            'email' => '',
            'phone' => '',
            'bio' => '',
            'isPrimaryContact' => true,
            'linkedApplicantName' => null,
        ];
    }
}
