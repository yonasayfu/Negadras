<?php

namespace App\Support;

use App\Models\Applicant;

class ApplicantProfileWriter
{
    /**
     * @param  array<string, mixed>  $validated
     */
    public function sync(Applicant $applicant, array $validated): Applicant
    {
        $applicant->fill([
            'applicant_type' => $validated['applicant_type'],
            'full_name' => $validated['full_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?: null,
            'bio' => $validated['bio'] ?: null,
            'national_id_or_registration_ref' => $validated['national_id_or_registration_ref'] ?: null,
        ])->save();

        $applicant->socialLinks()->delete();

        collect($validated['social_links'] ?? [])
            ->each(function (array $socialLink) use ($applicant): void {
                $applicant->socialLinks()->create([
                    'platform' => $socialLink['platform'],
                    'url' => $socialLink['url'],
                    'is_verified' => (bool) ($socialLink['is_verified'] ?? false),
                ]);
            });

        return $applicant->load('socialLinks');
    }
}
