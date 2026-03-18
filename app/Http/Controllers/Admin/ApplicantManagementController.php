<?php

namespace App\Http\Controllers\Admin;

use App\ApplicantType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateApplicantRequest;
use App\Models\Applicant;
use App\Support\ActivityLogger;
use App\Support\ApplicantProfileWriter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ApplicantManagementController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Applicant::class);

        $search = $request->string('search')->trim()->toString();

        $applicants = Applicant::query()
            ->with('user:id,name,email')
            ->withCount('socialLinks')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($applicantQuery) use ($search): void {
                    $applicantQuery
                        ->where('full_name', 'ilike', "%{$search}%")
                        ->orWhere('email', 'ilike', "%{$search}%")
                        ->orWhere('phone', 'ilike', "%{$search}%");
                });
            })
            ->orderBy('full_name')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Applicant $applicant): array => $this->applicantSummary($applicant));

        return Inertia::render('admin/Applicants/Index', [
            'applicants' => $applicants,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function edit(Applicant $applicant): Response
    {
        $this->authorize('view', $applicant);

        $applicant->load(['user:id,name,email', 'socialLinks']);

        return Inertia::render('admin/Applicants/Edit', [
            'applicant' => [
                ...$this->applicantSummary($applicant),
                'bio' => $applicant->bio,
                'nationalIdOrRegistrationRef' => $applicant->national_id_or_registration_ref,
                'socialLinks' => $applicant->socialLinks
                    ->map(fn ($socialLink): array => [
                        'platform' => $socialLink->platform,
                        'url' => $socialLink->url,
                        'isVerified' => $socialLink->is_verified,
                    ])
                    ->values()
                    ->all(),
            ],
            'typeOptions' => $this->typeOptions(),
        ]);
    }

    public function update(UpdateApplicantRequest $request, Applicant $applicant, ApplicantProfileWriter $writer): RedirectResponse
    {
        $this->authorize('update', $applicant);

        $writer->sync($applicant, $request->validated());

        ActivityLogger::record(
            actor: $request->user(),
            event: 'negadras.applicants.updated',
            description: "Updated applicant {$applicant->full_name}.",
            subject: $applicant,
            properties: [
                'social_links_count' => $applicant->socialLinks()->count(),
                'applicant_type' => $applicant->applicant_type->value,
            ],
            request: $request,
        );

        return to_route('applicants.edit', $applicant)->with('success', 'Applicant updated successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function applicantSummary(Applicant $applicant): array
    {
        return [
            'id' => $applicant->id,
            'userId' => $applicant->user_id,
            'linkedUserName' => $applicant->user?->name,
            'linkedUserEmail' => $applicant->user?->email,
            'fullName' => $applicant->full_name,
            'email' => $applicant->email,
            'phone' => $applicant->phone,
            'applicantType' => $applicant->applicant_type->value,
            'applicantTypeLabel' => $applicant->applicant_type->label(),
            'socialLinksCount' => $applicant->social_links_count ?? $applicant->socialLinks()->count(),
            'createdAt' => $applicant->created_at?->toDateTimeString(),
        ];
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function typeOptions(): array
    {
        return collect(ApplicantType::cases())
            ->map(fn (ApplicantType $type): array => [
                'value' => $type->value,
                'label' => $type->label(),
            ])
            ->all();
    }
}
