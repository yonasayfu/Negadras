<?php

namespace App\Http\Controllers\Settings;

use App\ApplicantType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateApplicantProfileRequest;
use App\Models\Applicant;
use App\Support\ActivityLogger;
use App\Support\ApplicantProfileWriter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ApplicantProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        $applicant = $request->user()->applicant()->with('socialLinks')->first();

        if ($applicant !== null) {
            $this->authorize('view', $applicant);
        }

        return Inertia::render('settings/ApplicantProfile', [
            'applicant' => [
                'applicantType' => $applicant?->applicant_type->value ?? 'individual',
                'fullName' => $applicant?->full_name ?? $request->user()->name,
                'email' => $applicant?->email ?? $request->user()->email,
                'phone' => $applicant?->phone,
                'bio' => $applicant?->bio,
                'nationalIdOrRegistrationRef' => $applicant?->national_id_or_registration_ref,
                'socialLinks' => collect($applicant?->socialLinks ?? [])
                    ->map(fn ($socialLink): array => [
                        'platform' => $socialLink->platform,
                        'url' => $socialLink->url,
                    ])
                    ->values()
                    ->all(),
            ],
            'typeOptions' => $this->typeOptions(),
        ]);
    }

    public function update(UpdateApplicantProfileRequest $request, ApplicantProfileWriter $writer): RedirectResponse
    {
        $applicant = $request->user()->applicant()->first();

        if ($applicant === null) {
            $this->authorize('create', Applicant::class);

            $applicant = new Applicant([
                'user_id' => $request->user()->id,
            ]);
        } else {
            $this->authorize('update', $applicant);
        }

        $wasRecentlyCreated = ! $applicant->exists;

        $writer->sync($applicant, $request->validated());

        ActivityLogger::record(
            actor: $request->user(),
            event: $wasRecentlyCreated ? 'negadras.applicants.created-own-profile' : 'negadras.applicants.updated-own-profile',
            description: $wasRecentlyCreated ? 'Created presenter profile.' : 'Updated presenter profile.',
            subject: $applicant,
            properties: [
                'social_links_count' => $applicant->socialLinks()->count(),
                'applicant_type' => $applicant->applicant_type->value,
            ],
            request: $request,
        );

        return to_route('applicant-profile.edit')->with('success', 'Presenter profile saved successfully.');
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
