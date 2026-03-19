<?php

namespace App\Providers;

use App\Models\Applicant;
use App\Models\Industry;
use App\Models\Media;
use App\Models\Note;
use App\Models\Organization;
use App\Models\Page;
use App\Models\Reviewer;
use App\Models\ReviewerAssignment;
use App\Models\ScreeningReview;
use App\Models\Season;
use App\Models\Setting;
use App\Models\Stage;
use App\Models\Submission;
use App\Models\SubmissionFile;
use App\Models\User;
use App\Policies\ApplicantPolicy;
use App\Policies\IndustryPolicy;
use App\Policies\MediaPolicy;
use App\Policies\NotePolicy;
use App\Policies\OrganizationPolicy;
use App\Policies\PagePolicy;
use App\Policies\ReviewerAssignmentPolicy;
use App\Policies\ReviewerPolicy;
use App\Policies\RolePolicy;
use App\Policies\ScreeningReviewPolicy;
use App\Policies\SeasonPolicy;
use App\Policies\SettingPolicy;
use App\Policies\StagePolicy;
use App\Policies\SubmissionFilePolicy;
use App\Policies\SubmissionPolicy;
use App\Policies\UserPolicy;
use App\Support\ActivityLogger;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Applicant::class, ApplicantPolicy::class);
        Gate::policy(Media::class, MediaPolicy::class);
        Gate::policy(Industry::class, IndustryPolicy::class);
        Gate::policy(Page::class, PagePolicy::class);
        Gate::policy(Reviewer::class, ReviewerPolicy::class);
        Gate::policy(ReviewerAssignment::class, ReviewerAssignmentPolicy::class);
        Gate::policy(Note::class, NotePolicy::class);
        Gate::policy(Organization::class, OrganizationPolicy::class);
        Gate::policy(Season::class, SeasonPolicy::class);
        Gate::policy(ScreeningReview::class, ScreeningReviewPolicy::class);
        Gate::policy(Setting::class, SettingPolicy::class);
        Gate::policy(Stage::class, StagePolicy::class);
        Gate::policy(Submission::class, SubmissionPolicy::class);
        Gate::policy(SubmissionFile::class, SubmissionFilePolicy::class);
        Gate::policy(Role::class, RolePolicy::class);

        Gate::before(fn (User $user, string $ability): ?bool => $user->hasRole('Admin') ? true : null);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );

        Event::listen(function (Login $event): void {
            ActivityLogger::record(
                actor: $event->user,
                event: 'auth.login',
                description: 'Signed in successfully.',
                subject: $event->user,
            );
        });

        Event::listen(function (Logout $event): void {
            if ($event->user === null) {
                return;
            }

            ActivityLogger::record(
                actor: $event->user,
                event: 'auth.logout',
                description: 'Signed out successfully.',
                subject: $event->user,
            );
        });
    }
}
