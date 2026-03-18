<?php

use App\Models\Applicant;
use App\Models\SocialLink;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('signed in user can view presenter profile settings page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('applicant-profile.edit'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/ApplicantProfile')
            ->where('applicant.fullName', $user->name)
            ->where('applicant.email', $user->email),
        );
});

test('signed in user can create and update own applicant profile with social links', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->put(route('applicant-profile.update'), [
            'applicant_type' => 'individual',
            'full_name' => 'Abel Tadesse',
            'email' => 'abel@example.com',
            'phone' => '0911223344',
            'bio' => 'Presenter profile for Negadras.',
            'national_id_or_registration_ref' => 'NEG-2026-AB',
            'social_links' => [
                [
                    'platform' => 'LinkedIn',
                    'url' => 'https://linkedin.com/in/abel-tadesse',
                ],
                [
                    'platform' => 'Website',
                    'url' => 'https://abel.example.com',
                ],
            ],
        ])
        ->assertRedirect(route('applicant-profile.edit'));

    $applicant = Applicant::query()->where('user_id', $user->id)->firstOrFail();

    expect($applicant->full_name)->toBe('Abel Tadesse')
        ->and($applicant->socialLinks()->count())->toBe(2);

    $this->actingAs($user)
        ->put(route('applicant-profile.update'), [
            'applicant_type' => 'team',
            'full_name' => 'Abel Tadesse Team',
            'email' => 'abel@example.com',
            'phone' => '0911223344',
            'bio' => 'Updated presenter profile.',
            'national_id_or_registration_ref' => 'NEG-2026-AB',
            'social_links' => [
                [
                    'platform' => 'LinkedIn',
                    'url' => 'https://linkedin.com/in/abel-tadesse',
                ],
            ],
        ])
        ->assertRedirect(route('applicant-profile.edit'));

    expect($applicant->fresh()->applicant_type->value)->toBe('team')
        ->and($applicant->fresh()->full_name)->toBe('Abel Tadesse Team')
        ->and($applicant->fresh()->socialLinks()->count())->toBe(1);

    expect(SocialLink::query()->where('applicant_id', $applicant->id)->first()?->is_verified)->toBeFalse();
});
