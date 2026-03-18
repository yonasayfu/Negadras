<?php

namespace App\Support;

use App\Models\Applicant;
use App\Models\Organization;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class OrganizationProfileWriter
{
    /**
     * @param  array<string, mixed>  $validated
     */
    public function sync(
        Organization $organization,
        array $validated,
        User $actor,
        ?Applicant $ownerApplicant = null,
        bool $preserveApplicantLinks = false,
        ?UploadedFile $logo = null,
    ): Organization {
        return DB::transaction(function () use ($organization, $validated, $actor, $ownerApplicant, $preserveApplicantLinks, $logo): Organization {
            $organization->fill([
                'legal_name' => $validated['legal_name'],
                'display_name' => $validated['display_name'],
                'registration_number' => $validated['registration_number'] ?: null,
                'industry_id' => $validated['industry_id'] ?: null,
                'website' => $validated['website'] ?: null,
                'description' => $validated['description'] ?: null,
                'contact_email' => $validated['contact_email'] ?: null,
                'contact_phone' => $validated['contact_phone'] ?: null,
                'address' => $validated['address'] ?: null,
            ]);
            $organization->save();

            $existingMembers = $organization->teamMembers()->get()->keyBy('id');
            $persistedMemberIds = [];

            foreach ($validated['team_members'] as $memberData) {
                /** @var TeamMember $teamMember */
                $teamMember = $existingMembers->get($memberData['id']) ?? new TeamMember;

                $applicantId = null;

                if ((bool) $memberData['is_primary_contact'] && $ownerApplicant !== null) {
                    $applicantId = $ownerApplicant->id;
                } elseif ($preserveApplicantLinks && $teamMember->exists) {
                    $applicantId = $teamMember->applicant_id;
                }

                $teamMember->fill([
                    'organization_id' => $organization->id,
                    'applicant_id' => $applicantId,
                    'full_name' => $memberData['full_name'],
                    'role_title' => $memberData['role_title'],
                    'email' => $memberData['email'] ?: null,
                    'phone' => $memberData['phone'] ?: null,
                    'bio' => $memberData['bio'] ?: null,
                    'is_primary_contact' => (bool) $memberData['is_primary_contact'],
                ]);
                $teamMember->save();

                $persistedMemberIds[] = $teamMember->id;
            }

            $organization->teamMembers()
                ->whereNotIn('id', $persistedMemberIds)
                ->delete();

            if ($logo instanceof UploadedFile) {
                $existingLogo = $organization->logo;

                if ($existingLogo !== null) {
                    Storage::disk($existingLogo->disk)->delete($existingLogo->path);
                    $existingLogo->delete();
                }

                $logoMedia = MediaUploader::store(
                    file: $logo,
                    user: $actor,
                    collection: 'organization-logo',
                    attachable: $organization,
                    metadata: [
                        'organization_id' => $organization->id,
                    ],
                );

                $organization->forceFill([
                    'logo_path' => $logoMedia->path,
                ])->save();
            }

            return $organization->fresh([
                'industry',
                'teamMembers.applicant.user',
                'logo',
            ]);
        });
    }
}
