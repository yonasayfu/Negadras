<?php

namespace App\Support;

use App\Models\Submission;

class SubmissionIntakeChecklist
{
    /**
     * @return array{items: array<int, array{key: string, label: string, passed: bool}>, passedCount: int, totalCount: int, isReady: bool}
     */
    public function forSubmission(Submission $submission): array
    {
        $submission->loadMissing([
            'applicant',
            'organization.teamMembers',
            'files',
        ]);

        $requiredFileTypes = collect(SubmissionFileRegistry::definitions())
            ->filter(fn (array $definition): bool => $definition['required'] === true)
            ->keys();

        $uploadedFileTypes = $submission->files
            ->pluck('file_type')
            ->unique();

        $primaryContact = $submission->organization?->teamMembers
            ->firstWhere('is_primary_contact', true);

        $items = [
            [
                'key' => 'profile_complete',
                'label' => 'Presenter profile complete',
                'passed' => filled($submission->applicant?->full_name)
                    && filled($submission->applicant?->email)
                    && filled($submission->applicant?->phone),
            ],
            [
                'key' => 'organization_info_complete',
                'label' => 'Organization info complete',
                'passed' => $submission->organization === null
                    || (
                        filled($submission->organization->display_name)
                        && filled($submission->organization->contact_email)
                        && $primaryContact !== null
                    ),
            ],
            [
                'key' => 'required_files_uploaded',
                'label' => 'Required files uploaded',
                'passed' => $requiredFileTypes->every(
                    fn (string $type): bool => $uploadedFileTypes->contains($type),
                ),
            ],
            [
                'key' => 'industry_selected',
                'label' => 'Industry selected',
                'passed' => $submission->industry_id !== null,
            ],
            [
                'key' => 'summary_completed',
                'label' => 'Summary completed',
                'passed' => filled($submission->summary)
                    && filled($submission->problem_statement)
                    && filled($submission->solution_description)
                    && filled($submission->business_model),
            ],
            [
                'key' => 'contact_info_valid',
                'label' => 'Contact info valid',
                'passed' => filled($submission->applicant?->email)
                    && filled($submission->applicant?->phone),
            ],
        ];

        $passedCount = collect($items)->where('passed', true)->count();

        return [
            'items' => $items,
            'passedCount' => $passedCount,
            'totalCount' => count($items),
            'isReady' => $passedCount === count($items),
        ];
    }
}
