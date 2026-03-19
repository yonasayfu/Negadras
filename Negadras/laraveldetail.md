# Negadras Laravel Detail Log

## Entry 001: Phase N1 Step 1 - Competition Structure Foundation

### Scope

This batch implemented the first Negadras business module on top of `starter-business-v1`:

- seasons
- stages
- industries

The goal was to create the structural competition layer before applicants, organizations, and submissions depend on it.

### Files and why they changed

#### [app/SeasonStatus.php](/Users/yonassayfu/Herd/Negadras/app/SeasonStatus.php)

Before:

```diff
- file did not exist
```

After:

```diff
+ enum SeasonStatus: string
+ case Draft = 'draft';
+ case Active = 'active';
+ case Closed = 'closed';
+ case Archived = 'archived';
```

Why:

- Season state should be explicit and typed.
- The UI, validation, and quick actions now all read from one source instead of hard-coded strings.

#### [app/StageStatus.php](/Users/yonassayfu/Herd/Negadras/app/StageStatus.php)

Before:

```diff
- file did not exist
```

After:

```diff
+ enum StageStatus: string
+ case Draft = 'draft';
+ case Open = 'open';
+ case Closed = 'closed';
```

Why:

- Stages need a smaller state machine than seasons.
- This is the first layer of future submission-flow control.

#### [app/StageType.php](/Users/yonassayfu/Herd/Negadras/app/StageType.php)

Before:

```diff
- file did not exist
```

After:

```diff
+ case Registration = 'registration';
+ case Intake = 'intake';
+ case Screening = 'screening';
+ case Review = 'review';
+ case Live = 'live';
+ case Final = 'final';
```

Why:

- Stage type is not the same as status.
- Type answers "what kind of stage is this?"
- Status answers "is this stage draft/open/closed right now?"

#### [database/migrations/2026_03_18_144128_create_seasons_table.php](/Users/yonassayfu/Herd/Negadras/database/migrations/2026_03_18_144128_create_seasons_table.php)

Key additions:

```diff
+ $table->string('name');
+ $table->unsignedInteger('year');
+ $table->string('slug')->unique();
+ $table->string('status')->default(SeasonStatus::Draft->value);
+ $table->timestamp('registration_open_at')->nullable();
+ $table->timestamp('registration_close_at')->nullable();
+ $table->text('description')->nullable();
```

Why:

- This matches the business tracker directly.
- `slug` gives stable internal and future public references.
- Registration dates are needed before presenter submission opens.

#### [database/migrations/2026_03_18_144128_create_stages_table.php](/Users/yonassayfu/Herd/Negadras/database/migrations/2026_03_18_144128_create_stages_table.php)

Key additions:

```diff
+ $table->foreignId('season_id')->constrained()->cascadeOnDelete();
+ $table->string('code');
+ $table->string('type')->default(StageType::Intake->value);
+ $table->unsignedInteger('order_index');
+ $table->boolean('is_live_stage')->default(false);
+ $table->unique(['season_id', 'code']);
```

Why:

- Stage records belong to a season.
- `code` is a machine-friendly identifier.
- `order_index` is the first workflow ordering mechanism.
- Composite uniqueness prevents duplicate stage codes within one season.

#### [database/migrations/2026_03_18_144128_create_industries_table.php](/Users/yonassayfu/Herd/Negadras/database/migrations/2026_03_18_144128_create_industries_table.php)

Key additions:

```diff
+ $table->string('name');
+ $table->string('slug')->unique();
+ $table->text('description')->nullable();
+ $table->boolean('is_active')->default(true);
```

Why:

- Submissions will need a stable industry taxonomy.
- `is_active` supports operational disabling without deleting history.

#### Models

- [Season.php](/Users/yonassayfu/Herd/Negadras/app/Models/Season.php)
- [Stage.php](/Users/yonassayfu/Herd/Negadras/app/Models/Stage.php)
- [Industry.php](/Users/yonassayfu/Herd/Negadras/app/Models/Industry.php)

What changed:

```diff
+ casts() now returns enum and datetime casts
+ Season hasMany stages()
+ Stage belongsTo season()
+ Industry casts is_active to boolean
```

Why:

- Eloquent models should describe the real domain structure, not just hold table names.
- This relationship layer is what future submissions and reporting will use.

#### Form Requests

- [StoreSeasonRequest.php](/Users/yonassayfu/Herd/Negadras/app/Http/Requests/Admin/StoreSeasonRequest.php)
- [UpdateSeasonRequest.php](/Users/yonassayfu/Herd/Negadras/app/Http/Requests/Admin/UpdateSeasonRequest.php)
- [StoreStageRequest.php](/Users/yonassayfu/Herd/Negadras/app/Http/Requests/Admin/StoreStageRequest.php)
- [UpdateStageRequest.php](/Users/yonassayfu/Herd/Negadras/app/Http/Requests/Admin/UpdateStageRequest.php)
- [StoreIndustryRequest.php](/Users/yonassayfu/Herd/Negadras/app/Http/Requests/Admin/StoreIndustryRequest.php)
- [UpdateIndustryRequest.php](/Users/yonassayfu/Herd/Negadras/app/Http/Requests/Admin/UpdateIndustryRequest.php)

Why:

- Validation belongs outside controllers in Laravel.
- These requests normalize slugs, booleans, and date-order rules before data touches the model.

Example:

```diff
+ Rule::unique('stages', 'code')->where(
+     fn ($query) => $query->where('season_id', $this->integer('season_id'))
+ )
```

This is what enforces "duplicate stage code in same season is rejected."

#### Policies and Registration

- [SeasonPolicy.php](/Users/yonassayfu/Herd/Negadras/app/Policies/SeasonPolicy.php)
- [StagePolicy.php](/Users/yonassayfu/Herd/Negadras/app/Policies/StagePolicy.php)
- [IndustryPolicy.php](/Users/yonassayfu/Herd/Negadras/app/Policies/IndustryPolicy.php)
- [AppServiceProvider.php](/Users/yonassayfu/Herd/Negadras/app/Providers/AppServiceProvider.php)

Why:

- UI hiding is not enough for a business app.
- Policies ensure route access and controller actions stay protected even if someone bypasses the sidebar.

#### [database/seeders/RolePermissionSeeder.php](/Users/yonassayfu/Herd/Negadras/database/seeders/RolePermissionSeeder.php)

What changed:

```diff
+ seasons.view/create/update/delete
+ stages.view/create/update/delete
+ industries.view/create/update/delete
```

Why:

- Negadras now has its first true domain permissions.
- Admin and Manager can operate the competition structure.
- Member, ReadOnly, and External stay out.

#### Controllers

- [SeasonManagementController.php](/Users/yonassayfu/Herd/Negadras/app/Http/Controllers/Admin/SeasonManagementController.php)
- [StageManagementController.php](/Users/yonassayfu/Herd/Negadras/app/Http/Controllers/Admin/StageManagementController.php)
- [IndustryManagementController.php](/Users/yonassayfu/Herd/Negadras/app/Http/Controllers/Admin/IndustryManagementController.php)

What these now do:

- Inertia index/create/edit responses
- store/update/destroy
- quick actions:
  - season activate/close
  - stage open/close
  - industry toggle active
- activity logging

Why:

- Controllers are the operational boundary between form requests, models, policies, and Inertia pages.

#### [routes/web.php](/Users/yonassayfu/Herd/Negadras/routes/web.php)

Why it changed:

- Added the admin routes for seasons, stages, and industries.
- Each route is also guarded with permission middleware, so authorization is enforced before controller logic runs.

#### [resources/js/navigation/app.ts](/Users/yonassayfu/Herd/Negadras/resources/js/navigation/app.ts)

Before:

```diff
- no Negadras-specific competition links
```

After:

```diff
+ Seasons
+ Stages
+ Industries
```

Why:

- The module must be reachable from the app shell.
- Sidebar visibility stays permission-aware through the existing starter infrastructure.

#### Admin Pages

- [admin/Seasons/Index.vue](/Users/yonassayfu/Herd/Negadras/resources/js/pages/admin/Seasons/Index.vue)
- [admin/Seasons/Create.vue](/Users/yonassayfu/Herd/Negadras/resources/js/pages/admin/Seasons/Create.vue)
- [admin/Seasons/Edit.vue](/Users/yonassayfu/Herd/Negadras/resources/js/pages/admin/Seasons/Edit.vue)
- [admin/Stages/Index.vue](/Users/yonassayfu/Herd/Negadras/resources/js/pages/admin/Stages/Index.vue)
- [admin/Stages/Create.vue](/Users/yonassayfu/Herd/Negadras/resources/js/pages/admin/Stages/Create.vue)
- [admin/Stages/Edit.vue](/Users/yonassayfu/Herd/Negadras/resources/js/pages/admin/Stages/Edit.vue)
- [admin/Industries/Index.vue](/Users/yonassayfu/Herd/Negadras/resources/js/pages/admin/Industries/Index.vue)
- [admin/Industries/Create.vue](/Users/yonassayfu/Herd/Negadras/resources/js/pages/admin/Industries/Create.vue)
- [admin/Industries/Edit.vue](/Users/yonassayfu/Herd/Negadras/resources/js/pages/admin/Industries/Edit.vue)

Why:

- These are the actual operator screens.
- They reuse the starter patterns:
  - `PageHeader`
  - `PageContainer`
  - `FormSection`
  - `ResourceToolbar`
  - `ResourceTable`
  - `ResourcePagination`
  - `ConfirmActionDialog`

That keeps Negadras domain work aligned with the starter structure instead of inventing a second UI style.

#### [tests/Feature/CompetitionStructureCrudTest.php](/Users/yonassayfu/Herd/Negadras/tests/Feature/CompetitionStructureCrudTest.php)

Why it changed:

- Replaced the example test with real phase coverage:
  - admin index access
  - manager create/update
  - member forbidden
  - duplicate stage code rejection
  - quick actions for season/stage/industry

Why:

- This phase changes both backend authorization and frontend pages.
- A project like Negadras needs feature tests at every domain phase.

### Laravel takeaways from this phase

1. Eloquent models describe relationships and casts.
2. Form Requests own validation and input normalization.
3. Policies protect access independently from the sidebar.
4. Inertia pages stay thin when the controller already prepares the data shape.
5. RBAC becomes useful only when it is tied to real domain modules.

## Entry 002: Phase N1 Step 2 - Applicant and Presenter Foundation

### Scope

This batch implemented the presenter-owned profile layer for Negadras:

- applicants
- social links
- presenter self-service profile rules
- admin applicant management

The goal was to make presenter identity a first-class business record before organizations and submissions are introduced.

### Files and why they changed

#### [app/ApplicantType.php](/Users/yonassayfu/Herd/Negadras/app/ApplicantType.php)

Before:

```diff
- file did not exist
```

After:

```diff
+ enum ApplicantType: string
+ case Individual = 'individual';
+ case Team = 'team';
+ case Organization = 'organization';
```

Why:

- Presenter type is domain data, not a free-text field.
- Submissions and organizations will later depend on whether the presenter acts as an individual, team, or organization.

#### [database/migrations/2026_03_18_201627_create_applicants_table.php](/Users/yonassayfu/Herd/Negadras/database/migrations/2026_03_18_201627_create_applicants_table.php)

Key additions:

```diff
+ $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
+ $table->string('applicant_type')->default(ApplicantType::Individual->value);
+ $table->string('full_name');
+ $table->string('email')->unique();
+ $table->string('phone', 30)->nullable()->unique();
+ $table->text('bio')->nullable();
+ $table->string('national_id_or_registration_ref')->nullable();
```

Why:

- The project decision is now explicit: every presenter profile belongs to a real user account.
- `user_id` is unique so one signed-in user owns one presenter profile.
- Email and phone uniqueness prevent duplicate presenter identity records.

#### [database/migrations/2026_03_18_201627_create_social_links_table.php](/Users/yonassayfu/Herd/Negadras/database/migrations/2026_03_18_201627_create_social_links_table.php)

Key additions:

```diff
+ $table->foreignId('applicant_id')->nullable()->constrained()->cascadeOnDelete();
+ $table->unsignedBigInteger('organization_id')->nullable();
+ $table->string('platform');
+ $table->string('url', 2048);
+ $table->boolean('is_verified')->default(false);
```

Why:

- Social links need to work for presenter profiles now and organization profiles later.
- `is_verified` is included early so operations staff can verify links without another schema rewrite.

#### Models

- [Applicant.php](/Users/yonassayfu/Herd/Negadras/app/Models/Applicant.php)
- [SocialLink.php](/Users/yonassayfu/Herd/Negadras/app/Models/SocialLink.php)
- [User.php](/Users/yonassayfu/Herd/Negadras/app/Models/User.php)

What changed:

```diff
+ Applicant belongsTo user()
+ Applicant hasMany socialLinks()
+ SocialLink belongsTo applicant()
+ User hasOne applicant()
+ applicant_type is cast to ApplicantType
```

Why:

- This turns presenter profiles into a real relationship graph instead of scattered profile fields on `users`.
- Future organizations, teams, and submissions can now reference `applicants` cleanly.

#### [app/Support/ApplicantProfileWriter.php](/Users/yonassayfu/Herd/Negadras/app/Support/ApplicantProfileWriter.php)

Before:

```diff
- file did not exist
```

After:

```diff
+ sync(Applicant $applicant, array $validated): Applicant
+ fill applicant fields
+ replace social links from validated payload
+ preserve verification flag when admin sends it
```

Why:

- The same write logic is needed in two places:
  - presenter self-service profile editing
  - admin applicant management
- Putting it in one service prevents controller duplication and keeps social-link sync rules consistent.

#### Requests

- [UpdateApplicantProfileRequest.php](/Users/yonassayfu/Herd/Negadras/app/Http/Requests/Settings/UpdateApplicantProfileRequest.php)
- [UpdateApplicantRequest.php](/Users/yonassayfu/Herd/Negadras/app/Http/Requests/Admin/UpdateApplicantRequest.php)

Important rule change:

```diff
- Rule::unique('applicants', 'email')->ignore($this->user()?->applicant?->id)
+ $applicantId = Applicant::query()->where('user_id', $this->user()?->id)->value('id');
+ Rule::unique('applicants', 'email')->ignore($applicantId)
```

Why:

- The self-service request failed on the second update because validation was implicitly depending on the authenticated user's relation state.
- Resolving the current applicant id directly from the database makes the unique ignore rule stable across repeated updates.

Other important behavior:

```diff
+ prepareForValidation() filters empty social link rows
+ social_links.*.url uses url:http,https
+ self-service flow forces is_verified = false
+ admin flow accepts social_links.*.is_verified
```

Why:

- Presenters can manage their own links, but they cannot mark them as verified.
- That is an operations/admin responsibility.

#### Policies

- [ApplicantPolicy.php](/Users/yonassayfu/Herd/Negadras/app/Policies/ApplicantPolicy.php)
- [AppServiceProvider.php](/Users/yonassayfu/Herd/Negadras/app/Providers/AppServiceProvider.php)

Core rule:

```diff
+ return $user->can('applicants.update') || $applicant->user_id === $user->id;
```

Why:

- Presenters need to manage only their own profile.
- Managers and admins need broader access for operations.
- This is the first Negadras-specific ownership rule beyond generic RBAC.

#### Controllers

- [ApplicantProfileController.php](/Users/yonassayfu/Herd/Negadras/app/Http/Controllers/Settings/ApplicantProfileController.php)
- [ApplicantManagementController.php](/Users/yonassayfu/Herd/Negadras/app/Http/Controllers/Admin/ApplicantManagementController.php)

What they now do:

- presenter self-service page under settings
- create profile on first save if none exists
- update existing profile on later saves
- admin list/search/edit applicants
- verify social links from the admin edit page
- write activity-log events for profile creation and update

Why:

- This gives Negadras both sides of the workflow:
  - presenter-owned profile maintenance
  - operations-side oversight and correction

#### [routes/settings.php](/Users/yonassayfu/Herd/Negadras/routes/settings.php)

What changed:

```diff
+ Route::get('applicant-profile', ...)
+ Route::put('applicant-profile', ...)
```

Why:

- Presenter profile belongs in the signed-in settings area, not the admin module.
- This keeps ownership flows separate from staff operations.

#### [routes/web.php](/Users/yonassayfu/Herd/Negadras/routes/web.php)

What changed:

```diff
+ applicants.index
+ applicants.edit
+ applicants.update
```

Why:

- Operations staff need a dedicated applicant management surface.
- These routes are still permission-gated so presenter self-service and admin oversight do not blur together.

#### Frontend pages and component

- [resources/js/pages/settings/ApplicantProfile.vue](/Users/yonassayfu/Herd/Negadras/resources/js/pages/settings/ApplicantProfile.vue)
- [resources/js/pages/admin/Applicants/Edit.vue](/Users/yonassayfu/Herd/Negadras/resources/js/pages/admin/Applicants/Edit.vue)
- [resources/js/pages/admin/Applicants/Index.vue](/Users/yonassayfu/Herd/Negadras/resources/js/pages/admin/Applicants/Index.vue)
- [resources/js/components/applicants/SocialLinksFields.vue](/Users/yonassayfu/Herd/Negadras/resources/js/components/applicants/SocialLinksFields.vue)

Why:

- `ApplicantProfile.vue` gives the presenter a real profile page inside settings.
- `SocialLinksFields.vue` prevents duplicate Vue logic between presenter and admin forms.
- The admin pages expose search, editing, and verification without creating a separate social-links module.

#### Navigation

- [resources/js/layouts/settings/Layout.vue](/Users/yonassayfu/Herd/Negadras/resources/js/layouts/settings/Layout.vue)
- [resources/js/navigation/app.ts](/Users/yonassayfu/Herd/Negadras/resources/js/navigation/app.ts)

What changed:

```diff
+ Presenter Profile in settings navigation
+ Applicants in admin navigation
```

Why:

- The UI now reflects the two distinct ownership models:
  - presenter self-management
  - staff management

#### Permissions

- [database/seeders/RolePermissionSeeder.php](/Users/yonassayfu/Herd/Negadras/database/seeders/RolePermissionSeeder.php)
- [app/Http/Controllers/Admin/RoleManagementController.php](/Users/yonassayfu/Herd/Negadras/app/Http/Controllers/Admin/RoleManagementController.php)

What changed:

```diff
+ applicants.view
+ applicants.create
+ applicants.update
+ applicants.delete
```

Why:

- Applicant management is now a first-class Negadras domain permission set.
- Manager can inspect and update applicants.
- Presenter self-management remains policy-based through ownership.

#### Tests

- [tests/Feature/ApplicantProfileTest.php](/Users/yonassayfu/Herd/Negadras/tests/Feature/ApplicantProfileTest.php)
- [tests/Feature/Admin/ApplicantManagementTest.php](/Users/yonassayfu/Herd/Negadras/tests/Feature/Admin/ApplicantManagementTest.php)

What they prove:

- a signed-in user can open the presenter profile page
- a signed-in user can create and later update their own applicant profile
- social links persist and resync correctly
- a manager can inspect and update applicants
- a member cannot use applicant admin routes

### Laravel tip from this phase

For self-service forms that update a related record, avoid depending on an implicitly loaded relation inside unique validation. Resolve the current model id directly from the database and ignore that id explicitly. It is more stable under repeated requests and makes the rule easier to reason about.

## Entry 003: Phase N1 Step 3 - Organization and Team Foundation

### Scope

This batch implemented the company or startup layer that sits between presenter profiles and future submissions:

- organizations
- team members
- presenter-owned organization profile
- admin organization management
- logo upload through the shared media system
- one-primary-contact rule

This phase matters because later submissions should be able to point to either:

- an individual presenter only
- or an organization with a real team structure

The business decision now recorded in the tracker is:

- organization is optional for future submissions
- individual presenters remain valid
- if an organization exists, it must have exactly one primary contact

### Architecture decision

I did **not** create a separate file-upload system for organization logos.

Instead, the organization logo is attached through the shared `Media` model that already exists in the business starter. That keeps files, audit, and future reuse aligned with the starter architecture.

### File-by-file breakdown

#### [database/migrations/2026_03_18_204801_create_organizations_table.php](/Users/yonassayfu/Herd/Negadras/database/migrations/2026_03_18_204801_create_organizations_table.php)

Before:

```diff
- organizations table only had id and timestamps
```

After:

```diff
+ $table->string('legal_name');
+ $table->string('display_name');
+ $table->string('registration_number')->nullable()->unique();
+ $table->foreignIdFor(Industry::class)->nullable()->constrained()->nullOnDelete();
+ $table->string('website')->nullable();
+ $table->text('description')->nullable();
+ $table->string('contact_email')->nullable();
+ $table->string('contact_phone', 30)->nullable();
+ $table->string('logo_path')->nullable();
+ $table->text('address')->nullable();
```

Why:

- `legal_name` and `display_name` are intentionally separate.
  - legal name is the official record
  - display name is what future public surfaces and event screens can show
- `industry_id` connects the organization to the Negadras competition taxonomy
- `logo_path` is kept as a convenience pointer even though the actual file is managed by `media`

#### [database/migrations/2026_03_18_204801_create_team_members_table.php](/Users/yonassayfu/Herd/Negadras/database/migrations/2026_03_18_204801_create_team_members_table.php)

Before:

```diff
- team_members table only had id and timestamps
```

After:

```diff
+ $table->foreignIdFor(Organization::class)->constrained()->cascadeOnDelete();
+ $table->foreignIdFor(Applicant::class)->nullable()->constrained()->nullOnDelete();
+ $table->string('full_name');
+ $table->string('role_title');
+ $table->string('email')->nullable();
+ $table->string('phone', 30)->nullable();
+ $table->text('bio')->nullable();
+ $table->boolean('is_primary_contact')->default(false);
+ $table->index(['organization_id', 'is_primary_contact']);
```

Why:

- `organization_id` makes team members a child record of one organization
- `applicant_id` is nullable because not every teammate needs a linked system account in Phase 1
- `is_primary_contact` is the ownership anchor for presenter-managed organizations

The key design is:

```diff
+ presenter ownership is inferred through the primary team member linked to the current applicant
```

That avoids storing a second conflicting owner field on `organizations`.

#### [app/Models/Organization.php](/Users/yonassayfu/Herd/Negadras/app/Models/Organization.php)

What changed:

```diff
+ fillable organization profile fields
+ industry(): BelongsTo
+ teamMembers(): HasMany
+ primaryContact(): HasOne where is_primary_contact = true
+ media(): MorphMany
+ logo(): MorphOne filtered to collection = organization-logo
+ notes(): MorphMany
+ isManagedBy(User $user): bool
```

Why:

- `primaryContact()` gives one clear record for ownership and later notifications
- `logo()` avoids scanning all files manually
- `isManagedBy()` keeps the ownership rule close to the model instead of scattering it across controllers

Important code shape:

```php
public function isManagedBy(User $user): bool
{
    $applicantId = $user->applicant?->id;

    if ($applicantId === null) {
        return false;
    }

    return $this->teamMembers()
        ->where('applicant_id', $applicantId)
        ->where('is_primary_contact', true)
        ->exists();
}
```

This is the rule that makes presenter-owned organization editing safe.

#### [app/Models/TeamMember.php](/Users/yonassayfu/Herd/Negadras/app/Models/TeamMember.php)

What changed:

```diff
+ fillable team member fields
+ cast is_primary_contact => boolean
+ organization(): BelongsTo
+ applicant(): BelongsTo
```

Why:

- Team members are not just form rows; they are first-class records
- Linking a member to an `Applicant` lets future phases reuse presenter identities across organizations, submissions, feedback, and sessions

#### [app/Support/MediaUploader.php](/Users/yonassayfu/Herd/Negadras/app/Support/MediaUploader.php)

Before:

```diff
- store(UploadedFile $file, User $user, string $collection = 'library', string $disk = 'local')
```

After:

```diff
+ store(
+     UploadedFile $file,
+     User $user,
+     string $collection = 'library',
+     string $disk = 'local',
+     ?Model $attachable = null,
+     array $metadata = [],
+ )
```

Why:

- The shared uploader needed to become attachable-aware so organization logos could use the same file pipeline as the rest of the business starter
- This keeps the boilerplate clean:
  - one upload service
  - one media table
  - one storage policy

Important code shape:

```php
'attachable_type' => $attachable?->getMorphClass(),
'attachable_id' => $attachable?->getKey(),
```

That is what makes the organization logo a real morph-attached media record.

#### [app/Support/OrganizationProfileWriter.php](/Users/yonassayfu/Herd/Negadras/app/Support/OrganizationProfileWriter.php)

This file is the core of the phase.

Before:

```diff
- file did not exist
```

After:

```diff
+ sync(
+     Organization $organization,
+     array $validated,
+     User $actor,
+     ?Applicant $ownerApplicant = null,
+     bool $preserveApplicantLinks = false,
+     ?UploadedFile $logo = null,
+ ): Organization
```

Why this service exists:

- presenter self-service and admin management both update the same organization structure
- team members and logo replacement are multi-step operations
- that logic does not belong duplicated in two controllers

Important behavior inside the writer:

```php
if ((bool) $memberData['is_primary_contact'] && $ownerApplicant !== null) {
    $applicantId = $ownerApplicant->id;
} elseif ($preserveApplicantLinks && $teamMember->exists) {
    $applicantId = $teamMember->applicant_id;
}
```

Why:

- in self-service mode, the current presenter becomes the linked applicant for the primary contact
- in admin mode, existing applicant links are preserved instead of being destroyed by an unrelated staff edit

Logo replacement behavior:

```php
$existingLogo = $organization->logo;

if ($existingLogo !== null) {
    Storage::disk($existingLogo->disk)->delete($existingLogo->path);
    $existingLogo->delete();
}
```

Why:

- uploading a new logo should replace the old one cleanly
- otherwise the media table and storage disk would accumulate stale organization logos

#### Requests

- [StoreOrganizationRequest.php](/Users/yonassayfu/Herd/Negadras/app/Http/Requests/Admin/StoreOrganizationRequest.php)
- [UpdateOrganizationRequest.php](/Users/yonassayfu/Herd/Negadras/app/Http/Requests/Admin/UpdateOrganizationRequest.php)
- [UpdateOrganizationProfileRequest.php](/Users/yonassayfu/Herd/Negadras/app/Http/Requests/Settings/UpdateOrganizationProfileRequest.php)

These files now own the primary-contact rule.

Important validation pattern:

```php
$primaryContacts = collect($this->input('team_members', []))
    ->filter(fn (array $member): bool => (bool) ($member['is_primary_contact'] ?? false))
    ->count();

if ($primaryContacts !== 1) {
    $validator->errors()->add('team_members', 'Exactly one primary contact is required.');
}
```

Why:

- this rule is business logic, not UI decoration
- Vue can help the user, but Laravel must enforce the final invariant

The settings request also adds a presenter-specific rule:

```php
if (($primaryMember['email'] ?? null) !== null && $primaryMember['email'] !== $applicant->email) {
    $validator->errors()->add('team_members.'.$primaryIndex.'.email', 'Primary contact email must match your presenter profile.');
}
```

Why:

- the presenter-owned organization must stay anchored to the real signed-in presenter identity

#### [app/Policies/OrganizationPolicy.php](/Users/yonassayfu/Herd/Negadras/app/Policies/OrganizationPolicy.php)

Core rule:

```diff
+ return $user->can('organizations.update') || $organization->isManagedBy($user);
```

Why:

- admins and managers need broad operations access
- presenters need narrow ownership-based access
- the policy unifies both without separate route trees pretending to be secure

#### [app/Http/Controllers/Settings/OrganizationProfileController.php](/Users/yonassayfu/Herd/Negadras/app/Http/Controllers/Settings/OrganizationProfileController.php)

This is the presenter-facing entry point.

Key rule:

```php
if ($applicant === null) {
    return to_route('applicant-profile.edit')->with('error', 'Create your presenter profile before creating an organization.');
}
```

Why:

- organization ownership depends on the presenter profile existing first
- that makes Phase 1 dependency order explicit in code

This controller also pre-seeds the form from the presenter profile when no organization exists yet. That reduces duplicate data entry and keeps the primary contact aligned with the presenter.

#### [app/Http/Controllers/Admin/OrganizationManagementController.php](/Users/yonassayfu/Herd/Negadras/app/Http/Controllers/Admin/OrganizationManagementController.php)

This is the staff-facing entry point.

What it now does:

- organizations index with search
- create page
- edit page
- update and delete
- industry option loading
- team-member summaries
- logo download link exposure

Search shape:

```php
->where('legal_name', 'ilike', "%{$search}%")
->orWhere('display_name', 'ilike', "%{$search}%")
->orWhere('registration_number', 'ilike', "%{$search}%")
->orWhere('contact_email', 'ilike', "%{$search}%");
```

Why:

- operations staff need organization lookup by both formal and informal identifiers

#### [routes/settings.php](/Users/yonassayfu/Herd/Negadras/routes/settings.php)

New routes:

```diff
+ settings/organization-profile
+ organization-profile.edit
+ organization-profile.update
```

Why:

- organization profile belongs in the signed-in presenter settings area
- it is not an admin-only object

#### [routes/web.php](/Users/yonassayfu/Herd/Negadras/routes/web.php)

New admin routes:

```diff
+ organizations.index
+ organizations.create
+ organizations.store
+ organizations.edit
+ organizations.update
+ organizations.destroy
```

Why:

- staff still need full operational visibility and correction tools
- presenter ownership and admin oversight now exist side by side

#### Frontend component

- [resources/js/components/organizations/TeamMembersFields.vue](/Users/yonassayfu/Herd/Negadras/resources/js/components/organizations/TeamMembersFields.vue)

Why it matters:

- it centralizes the repeatable row logic for team members
- it prevents the presenter page and admin pages from drifting into different field behavior

Important UI behavior:

```diff
+ add/remove rows
+ primary-contact checkbox
+ per-row validation messages
+ linked applicant name display when available
```

#### Frontend pages

- [resources/js/pages/settings/OrganizationProfile.vue](/Users/yonassayfu/Herd/Negadras/resources/js/pages/settings/OrganizationProfile.vue)
- [resources/js/pages/admin/Organizations/Index.vue](/Users/yonassayfu/Herd/Negadras/resources/js/pages/admin/Organizations/Index.vue)
- [resources/js/pages/admin/Organizations/Create.vue](/Users/yonassayfu/Herd/Negadras/resources/js/pages/admin/Organizations/Create.vue)
- [resources/js/pages/admin/Organizations/Edit.vue](/Users/yonassayfu/Herd/Negadras/resources/js/pages/admin/Organizations/Edit.vue)

What these pages demonstrate:

- self-service presenter profile for organizations
- admin CRUD for organizations
- logo upload through the shared upload field
- repeatable team-member editing
- one-primary-contact UX assistance before Laravel enforces the final rule

Important frontend logic:

```ts
if (key === 'isPrimaryContact' && value === true) {
    form.team_members = form.team_members.map((member, memberIndex) => ({
        ...member,
        isPrimaryContact: memberIndex === index,
    }));
}
```

Why:

- the UI helps users keep one primary contact selected
- the backend still validates it, so the rule is enforced twice:
  - once for usability
  - once for integrity

#### Navigation and settings shell

- [resources/js/navigation/app.ts](/Users/yonassayfu/Herd/Negadras/resources/js/navigation/app.ts)
- [resources/js/layouts/settings/Layout.vue](/Users/yonassayfu/Herd/Negadras/resources/js/layouts/settings/Layout.vue)

What changed:

```diff
+ Organizations in admin navigation
+ Organization Profile in settings navigation
```

Why:

- organizations now exist as both:
  - a presenter-owned business object
  - an admin-managed operational record

#### Permissions and policy registration

- [database/seeders/RolePermissionSeeder.php](/Users/yonassayfu/Herd/Negadras/database/seeders/RolePermissionSeeder.php)
- [app/Http/Controllers/Admin/RoleManagementController.php](/Users/yonassayfu/Herd/Negadras/app/Http/Controllers/Admin/RoleManagementController.php)
- [app/Providers/AppServiceProvider.php](/Users/yonassayfu/Herd/Negadras/app/Providers/AppServiceProvider.php)

What changed:

```diff
+ organizations.view
+ organizations.create
+ organizations.update
+ organizations.delete
+ Gate::policy(Organization::class, OrganizationPolicy::class)
```

Why:

- organization management is now a first-class Negadras capability
- managers can operate it
- presenters can only touch their own organization through policy ownership rules

#### Tests

- [tests/Feature/OrganizationProfileTest.php](/Users/yonassayfu/Herd/Negadras/tests/Feature/OrganizationProfileTest.php)
- [tests/Feature/Admin/OrganizationManagementTest.php](/Users/yonassayfu/Herd/Negadras/tests/Feature/Admin/OrganizationManagementTest.php)

What they prove:

- a presenter with an applicant profile can open the organization profile page
- a presenter can create an organization with logo and team members
- exactly one primary contact is required
- a manager can view the organizations index
- a manager can update organization details and replace the logo
- a member cannot access organization admin routes

### Laravel takeaways from this phase

1. Use a writer/service when one business object is edited from both self-service and admin flows.
2. Put ownership logic in the model or policy, not in the Vue page.
3. Reuse polymorphic media instead of creating one-off file columns and controllers.
4. Enforce business invariants twice:
   - assist the user in the frontend
   - enforce the rule in Laravel validation
5. Keep Phase 1 dependencies explicit in code:
   - presenter profile first
   - organization profile second
   - submission layer later

---

## Entry 004: Phase N1 Step 4 - Submission Core Foundation

This phase introduced the first real Negadras business record that combines the earlier foundations:

- competition structure
- presenter identity
- optional organization ownership

The goal was to create a submission record that a presenter owns, can save as draft, can finally submit, and that staff can inspect from a separate operational surface.

### What existed before

Before this phase:

- seasons existed
- stages existed
- industries existed
- applicants existed
- organizations existed
- there was no submission record tying them together
- there was no presenter-facing draft flow
- there was no staff-facing intake screen

That meant the Negadras domain had identity and taxonomy, but not the actual business object the competition depends on.

### What changed by file and why

#### [database/migrations/2026_03_19_054510_create_submissions_table.php](/Users/yonassayfu/Herd/Negadras/database/migrations/2026_03_19_054510_create_submissions_table.php)

This migration created the first real intake table.

Important structure:

```diff
+ $table->foreignId('season_id')->constrained()->cascadeOnDelete();
+ $table->foreignId('current_stage_id')->nullable()->constrained('stages')->nullOnDelete();
+ $table->foreignId('industry_id')->nullable()->constrained()->nullOnDelete();
+ $table->foreignId('applicant_id')->constrained()->cascadeOnDelete();
+ $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
+ $table->string('title');
+ $table->text('summary')->nullable();
+ $table->text('problem_statement')->nullable();
+ $table->text('solution_description')->nullable();
+ $table->text('business_model')->nullable();
+ $table->string('status')->default(SubmissionStatus::Draft->value);
+ $table->timestamp('submitted_at')->nullable();
+ $table->boolean('is_public_after_approval')->default(false);
+ $table->unsignedBigInteger('current_version_id')->nullable();
```

Why:

- one submission must belong to exactly one presenter
- one submission must belong to exactly one season
- one submission can optionally belong to one organization
- stage is stored as the current movement point in the workflow
- the narrative fields stay nullable so draft mode is possible
- `current_version_id` is added now so later versioning does not require redesign

#### [app/SubmissionStatus.php](/Users/yonassayfu/Herd/Negadras/app/SubmissionStatus.php)

This enum made the first Negadras submission lifecycle explicit.

Important rule:

```php
public function allowsPresenterEdits(): bool
{
    return match ($this) {
        self::Draft, self::IncompleteReturned => true,
        self::Submitted, self::Eligible, self::ScreeningRejected => false,
    };
}
```

Why:

- draft records stay editable
- returned records stay editable
- staff-controlled statuses become locked to the presenter
- this keeps future screening and eligibility changes out of the presenter UI

#### [app/Models/Submission.php](/Users/yonassayfu/Herd/Negadras/app/Models/Submission.php)

This model became the center of the Negadras intake layer.

Core behavior:

```diff
+ public function season(): BelongsTo
+ public function currentStage(): BelongsTo
+ public function industry(): BelongsTo
+ public function applicant(): BelongsTo
+ public function organization(): BelongsTo
+
+ public function scopeDraft(Builder $query): Builder
+ public function scopeSubmitted(Builder $query): Builder
+ public function scopeEligible(Builder $query): Builder
+
+ public function isOwnedBy(User $user): bool
+ public function isEditableByPresenter(): bool
```

Why:

- the model must carry both relationship meaning and workflow meaning
- the presenter ownership check belongs here because controllers and policies both depend on it
- the scopes prepare later reporting and queueing logic without raw query duplication

#### Relationship files

- [app/Models/Season.php](/Users/yonassayfu/Herd/Negadras/app/Models/Season.php)
- [app/Models/Stage.php](/Users/yonassayfu/Herd/Negadras/app/Models/Stage.php)
- [app/Models/Industry.php](/Users/yonassayfu/Herd/Negadras/app/Models/Industry.php)
- [app/Models/Applicant.php](/Users/yonassayfu/Herd/Negadras/app/Models/Applicant.php)
- [app/Models/Organization.php](/Users/yonassayfu/Herd/Negadras/app/Models/Organization.php)

What changed:

```diff
+ public function submissions(): HasMany
```

Why:

- Negadras reporting, screening, judging, and archive phases will all pivot around these reverse links
- adding them now avoids ad-hoc query building in later phases

#### [app/Policies/SubmissionPolicy.php](/Users/yonassayfu/Herd/Negadras/app/Policies/SubmissionPolicy.php)

This file is the actual security boundary.

Important rule:

```diff
- return $user->applicant !== null || $user->can('submissions.view');
+ return true;
```

Why that change mattered:

- the first attempt blocked users before the controller could redirect them to create a presenter profile
- making `viewAny` and `create` open to authenticated users allows the workflow to guide them correctly
- ownership is still enforced on actual records with `view`, `update`, and `delete`

The real enforcement:

```php
return $user->can('submissions.update')
    || ($submission->isOwnedBy($user) && $submission->isEditableByPresenter());
```

Why:

- managers/admins need broad operational access
- presenters need narrow ownership-based access
- locked statuses must not become editable just because the presenter owns the record

#### [app/Http/Requests/StoreSubmissionRequest.php](/Users/yonassayfu/Herd/Negadras/app/Http/Requests/StoreSubmissionRequest.php)
#### [app/Http/Requests/UpdateSubmissionRequest.php](/Users/yonassayfu/Herd/Negadras/app/Http/Requests/UpdateSubmissionRequest.php)

These requests enforce the difference between draft mode and final submit.

Key rule:

```php
$submitting = $intent === 'submit';

'summary' => [$submitting ? 'required' : 'nullable', 'string', 'max:4000'],
'problem_statement' => [$submitting ? 'required' : 'nullable', 'string', 'max:10000'],
'solution_description' => [$submitting ? 'required' : 'nullable', 'string', 'max:10000'],
'business_model' => [$submitting ? 'required' : 'nullable', 'string', 'max:10000'],
```

Why:

- draft must stay flexible
- final submit must be structurally complete
- the validation rule expresses business intent directly instead of forcing the Vue layer to guess

Cross-table protection:

```php
$matchesSeason = Stage::query()
    ->whereKey($stageId)
    ->where('season_id', $seasonId)
    ->exists();
```

Why:

- a submission must not attach a stage from a different season
- this is a domain integrity rule, not just a select-box convenience

Organization ownership protection:

```php
->whereHas('teamMembers', function ($query) use ($applicantId): void {
    $query
        ->where('applicant_id', $applicantId)
        ->where('is_primary_contact', true);
})
```

Why:

- a presenter cannot attach a random organization
- only organizations they manage as the primary contact can be claimed by the submission

#### [app/Http/Controllers/SubmissionController.php](/Users/yonassayfu/Herd/Negadras/app/Http/Controllers/SubmissionController.php)

This is the presenter-facing workflow controller.

What it now does:

- submissions index
- create page
- draft create
- final submit
- detail page
- edit page
- draft update
- delete draft

Important branching:

```php
'status' => $intent === 'submit' ? SubmissionStatus::Submitted : SubmissionStatus::Draft,
'submitted_at' => $intent === 'submit' ? now() : null,
```

Why:

- draft and final submit are the same record path, not separate models
- keeping the branching in the controller makes the business flow explicit and easy to audit

Important presenter guard:

```php
if ($applicant === null) {
    return to_route('applicant-profile.edit')->with('error', 'Create your presenter profile before creating a submission.');
}
```

Why:

- this preserves the intended dependency order of the project
- presenter profile must exist before submission intake begins

#### [app/Http/Controllers/Admin/SubmissionManagementController.php](/Users/yonassayfu/Herd/Negadras/app/Http/Controllers/Admin/SubmissionManagementController.php)

This is the staff-facing operational surface.

Why it is separate:

- staff should inspect submissions differently from presenters
- presenter pages are ownership-driven and action-heavy
- admin pages are search-first and inspection-first

Search logic:

```php
->where('title', 'ilike', "%{$search}%")
->orWhereHas('applicant', ...)
->orWhereHas('organization', ...)
```

Why:

- intake staff usually search by project title, presenter, or company name

#### [routes/web.php](/Users/yonassayfu/Herd/Negadras/routes/web.php)

New presenter routes:

```diff
+ submissions.index
+ submissions.create
+ submissions.store
+ submissions.show
+ submissions.edit
+ submissions.update
+ submissions.destroy
```

New staff routes:

```diff
+ admin-submissions.index
+ admin-submissions.show
```

Why:

- presenter and staff use the same model but different operational surfaces
- admin routes stay behind permission middleware
- presenter routes rely on policy ownership and profile dependency checks

#### [database/seeders/RolePermissionSeeder.php](/Users/yonassayfu/Herd/Negadras/database/seeders/RolePermissionSeeder.php)
#### [app/Http/Controllers/Admin/RoleManagementController.php](/Users/yonassayfu/Herd/Negadras/app/Http/Controllers/Admin/RoleManagementController.php)

New permissions:

```diff
+ submissions.view
+ submissions.create
+ submissions.update
+ submissions.delete
```

Why:

- staff operations around intake need explicit access controls
- presenters do not need those permissions for their own records because policy ownership handles that path
- this keeps Negadras ready for secretary/reviewer/judge-specific role design later

#### Frontend submission pages

- [resources/js/pages/submissions/Index.vue](/Users/yonassayfu/Herd/Negadras/resources/js/pages/submissions/Index.vue)
- [resources/js/pages/submissions/Create.vue](/Users/yonassayfu/Herd/Negadras/resources/js/pages/submissions/Create.vue)
- [resources/js/pages/submissions/Edit.vue](/Users/yonassayfu/Herd/Negadras/resources/js/pages/submissions/Edit.vue)
- [resources/js/pages/submissions/Show.vue](/Users/yonassayfu/Herd/Negadras/resources/js/pages/submissions/Show.vue)

What they demonstrate:

- create presenter-facing draft list
- handle missing presenter profile cleanly
- filter available stages by selected season
- offer save draft and final submit as separate actions
- show locked or editable state through status and action visibility

Important frontend rule:

```ts
const availableStages = computed(() =>
    props.stageOptions.filter((stage) => String(stage.seasonId) === form.season_id),
);
```

Why:

- the UI reduces bad combinations early
- the backend still validates the season-stage link for real protection

Final submit confirmation:

```vue
<ConfirmActionDialog
    title="Final submit this draft?"
    description="After final submission, the draft becomes locked until Negadras returns it for correction."
    confirm-label="Submit now"
    @confirm="submitFinal"
/>
```

Why:

- this is a real business transition, not just a save button
- the user needs one deliberate checkpoint before locking the record

#### Frontend admin pages

- [resources/js/pages/admin/Submissions/Index.vue](/Users/yonassayfu/Herd/Negadras/resources/js/pages/admin/Submissions/Index.vue)
- [resources/js/pages/admin/Submissions/Show.vue](/Users/yonassayfu/Herd/Negadras/resources/js/pages/admin/Submissions/Show.vue)

Why:

- staff need a dedicated intake board
- admin viewing is read-focused and search-focused
- this separation avoids letting operational review accidentally become the presenter editing UI

#### Tests

- [tests/Feature/SubmissionFlowTest.php](/Users/yonassayfu/Herd/Negadras/tests/Feature/SubmissionFlowTest.php)
- [tests/Feature/Admin/SubmissionManagementTest.php](/Users/yonassayfu/Herd/Negadras/tests/Feature/Admin/SubmissionManagementTest.php)

What they prove:

- a signed-in user without an applicant profile can still enter the submission area and gets redirected correctly before create
- a presenter can save a draft and later submit it
- a presenter cannot view another presenter’s submission
- a manager can inspect admin submission pages
- a member cannot access admin submission pages

### One practical verification detail

The first test run failed because the new Inertia pages were not yet present in the built Vite manifest.

What fixed it:

```bash
npm run build
php artisan test --compact tests/Feature/SubmissionFlowTest.php tests/Feature/Admin/SubmissionManagementTest.php
```

Why this matters:

- when you add new Inertia pages, Laravel test rendering may need a fresh production build if the manifest is stale
- this is not a business-logic failure, it is an asset registration issue

### Laravel takeaways from this phase

1. Keep business transitions like draft versus final submit in the controller and request layer, not hidden in a frontend-only flag.
2. Use policies for ownership and staff overrides at the same time instead of splitting into insecure parallel controllers.
3. Validate cross-table consistency explicitly:
   - season must match stage
   - presenter must control the linked organization
4. Use enums early when a workflow will expand later.
5. Separate presenter UX from staff UX even when they read the same model.

---

## Entry 005: Phase N1 Step 5 - Submission Versioning Foundation

This phase added immutable submission history on top of the draft and final-submit flow from the previous phase.

The goal was to:

- freeze each final submission snapshot
- keep a stable `current_version_id`
- show version history on the detail pages
- prepare later phases like file attachments, screening, and judging to reference a locked version instead of a moving draft

### What existed before

Before this phase:

- a submission record existed
- final submit changed the submission status
- no immutable snapshot of that final state was stored
- `current_version_id` existed on the table but did nothing yet

That meant the system knew a submission had been sent, but it had no durable record of what exactly was sent at each finalization moment.

### What changed by file and why

#### [database/migrations/2026_03_19_060518_create_submission_versions_table.php](/Users/yonassayfu/Herd/Negadras/database/migrations/2026_03_19_060518_create_submission_versions_table.php)

This migration created the history table and connected `submissions.current_version_id` to it.

Core structure:

```diff
+ $table->foreignId('submission_id')->constrained()->cascadeOnDelete();
+ $table->unsignedInteger('version_no');
+ $table->json('snapshot_json');
+ $table->text('change_note')->nullable();
+ $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
+ $table->boolean('is_locked')->default(true);
+ $table->unique(['submission_id', 'version_no']);
```

And the second important part:

```diff
+ $table->foreign('current_version_id')
+     ->references('id')
+     ->on('submission_versions')
+     ->nullOnDelete();
```

Why:

- every version belongs to one submission
- version numbers must be unique per submission
- the snapshot must be stored as a real payload, not recomputed later from a moving record
- `current_version_id` must point to one specific locked version

#### [app/Models/SubmissionVersion.php](/Users/yonassayfu/Herd/Negadras/app/Models/SubmissionVersion.php)

This model represents one immutable submission snapshot.

Important fields:

```diff
+ 'submission_id',
+ 'version_no',
+ 'snapshot_json',
+ 'change_note',
+ 'created_by',
+ 'is_locked',
```

Casts:

```php
return [
    'snapshot_json' => 'array',
    'is_locked' => 'boolean',
];
```

Why:

- `snapshot_json` should behave like structured data in PHP
- `is_locked` is explicit because later phases may distinguish locked historical versions from in-progress working states if needed

#### [app/Models/Submission.php](/Users/yonassayfu/Herd/Negadras/app/Models/Submission.php)

This model gained the relationships that turn versioning into a usable API.

New relationships:

```diff
+ public function currentVersion(): BelongsTo
+ public function versions(): HasMany
```

Why:

- `currentVersion` gives one direct pointer for the active frozen snapshot
- `versions()` provides the ordered history
- later review modules can use `currentVersion` without re-deriving “latest locked version” every time

#### [app/Support/SubmissionVersionSnapshotter.php](/Users/yonassayfu/Herd/Negadras/app/Support/SubmissionVersionSnapshotter.php)

This is the core business service of the phase.

It is intentionally separate from the controller so version creation rules stay reusable and testable.

Important flow:

```php
$nextVersionNumber = (int) $submission->versions()->max('version_no') + 1;

$version = $submission->versions()->create([
    'version_no' => $nextVersionNumber,
    'snapshot_json' => $this->snapshotPayload($submission),
    'change_note' => $changeNote,
    'created_by' => $actor?->id,
    'is_locked' => true,
]);

$submission->forceFill([
    'current_version_id' => $version->id,
])->save();
```

Why:

- new final submissions create new rows instead of mutating old versions
- old history stays intact
- `current_version_id` moves forward atomically inside the same transaction

The snapshot payload includes:

- narrative fields
- status and submitted timestamp
- visibility preference
- season, stage, industry, applicant, and organization summaries

Why:

- later phases should be able to inspect what was submitted at that moment even if related master records change later

#### [app/Http/Controllers/SubmissionController.php](/Users/yonassayfu/Herd/Negadras/app/Http/Controllers/SubmissionController.php)

This controller now triggers version creation at the correct business transition.

Initial final submit:

```php
if ($intent === 'submit') {
    $snapshotter->createSnapshot(
        submission: $submission,
        actor: $request->user(),
        changeNote: 'Initial final submission.',
    );
}
```

Resubmission path:

```php
if ($intent === 'submit') {
    $snapshotter->createSnapshot(
        submission: $submission->fresh(),
        actor: $request->user(),
        changeNote: $wasReturned
            ? 'Presenter resubmitted after correction.'
            : ($wasPreviouslySubmitted ? 'Presenter submitted a new revision.' : 'Presenter finalized the submission draft.'),
    );
}
```

Why:

- snapshot creation belongs to the final-submit transition, not to every draft save
- returned submissions should create a second version when they are sent again
- the note makes the history readable for staff later

This controller also now exposes:

```diff
+ currentVersionNumber
+ versionCount
+ versionHistory
```

Why:

- the frontend needs structured version data instead of rebuilding it from raw snapshots

#### [app/Http/Controllers/Admin/SubmissionManagementController.php](/Users/yonassayfu/Herd/Negadras/app/Http/Controllers/Admin/SubmissionManagementController.php)

The staff-facing show page now also exposes version history.

Why:

- intake and screening staff should inspect the locked history
- they should not need the presenter-facing page to understand version progression

#### [resources/js/types/admin.ts](/Users/yonassayfu/Herd/Negadras/resources/js/types/admin.ts)

New type:

```diff
+ export type SubmissionVersionEntry = {
+     id: number;
+     versionNo: number;
+     changeNote: string | null;
+     createdAt: string | null;
+     createdBy: string | null;
+     isLocked: boolean;
+     isCurrent: boolean;
+     snapshotTitle: string;
+     snapshotStatus: string;
+ };
```

And `ManagedSubmission` now includes:

```diff
+ currentVersionNumber?: number | null;
+ versionCount?: number;
+ versionHistory?: SubmissionVersionEntry[];
```

Why:

- the frontend needs a typed history structure
- later file uploads and status history can reuse the same submission detail type cleanly

#### Presenter detail UI

- [resources/js/pages/submissions/Show.vue](/Users/yonassayfu/Herd/Negadras/resources/js/pages/submissions/Show.vue)

What changed:

```diff
+ Current version vX badge
+ version count
+ version history list
+ current versus locked labels
```

Why:

- presenters should understand what final version is currently authoritative
- they should also see when a new locked version was created

#### Admin detail UI

- [resources/js/pages/admin/Submissions/Show.vue](/Users/yonassayfu/Herd/Negadras/resources/js/pages/admin/Submissions/Show.vue)

Why:

- staff needs the same historical visibility
- review operations should stay on the admin side

#### Tests

- [tests/Feature/SubmissionVersioningTest.php](/Users/yonassayfu/Herd/Negadras/tests/Feature/SubmissionVersioningTest.php)
- [tests/Feature/Admin/SubmissionVersionHistoryTest.php](/Users/yonassayfu/Herd/Negadras/tests/Feature/Admin/SubmissionVersionHistoryTest.php)

What they prove:

1. final submission creates version 1 and points `current_version_id` to it
2. resubmitting a returned submission creates version 2 instead of overwriting version 1
3. presenter detail page exposes current version label and history
4. manager can inspect version history on the admin detail page

### Laravel takeaways from this phase

1. Immutable history should be a separate model, not a field bundle inside the live record.
2. Put snapshot creation in a dedicated service when multiple controllers or later jobs may need the same behavior.
3. `current_version_id` is a convenience pointer, not a substitute for a real history table.
4. Store enough relational context in the snapshot so later changes to master records do not erase the original submitted meaning.
5. Version history is useful only if both the presenter side and the staff side can read it in the right context.
## Entry 005: Phase N1 Step 5 - Submission File Upload Foundation

### Scope

This batch added the first real attachment system for Negadras submissions:

- `submission_files`
- private file storage
- upload, list, replace, delete, download
- required vs optional file categories
- version binding from draft files into locked submission versions

The key design rule for this phase was:

- files must belong to the submission workflow
- locked history must stay intact
- drafts can change, versions cannot

### Architecture decision

I did **not** reuse the generic media library table for submission attachments.

Why:

- the generic media library is useful for broad business uploads
- submission attachments have stricter workflow rules
- they need:
  - `submission_id`
  - optional `submission_version_id`
  - required/optional category logic
  - presenter ownership rules
  - locked-version protection

So the correct Negadras design is a dedicated `submission_files` table plus a small registry/service layer.

### Files and why they changed

#### [database/migrations/2026_03_19_061917_create_submission_files_table.php](/Users/yonassayfu/Herd/Negadras/database/migrations/2026_03_19_061917_create_submission_files_table.php)

Before:

```diff
- file did not exist
```

After:

```diff
+ $table->foreignId('submission_id')->constrained()->cascadeOnDelete();
+ $table->foreignId('submission_version_id')->nullable()->constrained('submission_versions')->nullOnDelete();
+ $table->string('file_type');
+ $table->string('disk')->default('local');
+ $table->string('original_name');
+ $table->string('file_path');
+ $table->string('mime_type')->nullable();
+ $table->unsignedBigInteger('file_size')->default(0);
+ $table->text('description')->nullable();
+ $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
+ $table->timestamp('uploaded_at')->nullable();
+ $table->boolean('is_required')->default(false);
+ $table->boolean('is_verified')->default(false);
```

Why:

- `submission_id` ties the file to the live submission record
- `submission_version_id` lets a file become part of an immutable historical version later
- `disk` keeps storage configurable
- `is_required` and `is_verified` prepare both intake completeness and later secretary review

The important modeling decision is:

- draft file = `submission_version_id = null`
- locked historical file = `submission_version_id = some version id`

That split is what makes draft editing and historical integrity coexist.

#### [app/Models/SubmissionFile.php](/Users/yonassayfu/Herd/Negadras/app/Models/SubmissionFile.php)

Before:

```diff
- file did not exist
```

After:

```diff
+ class SubmissionFile extends Model
+ public function submission(): BelongsTo
+ public function version(): BelongsTo
+ public function uploadedBy(): BelongsTo
+ public function typeLabel(): string
```

Why:

- this model is the center of file ownership and authorization
- `typeLabel()` avoids scattering category labels across controllers and Vue pages
- the `version()` relation is what lets the UI show `Version v1`, `Version v2`, and so on

#### [app/Support/SubmissionFileRegistry.php](/Users/yonassayfu/Herd/Negadras/app/Support/SubmissionFileRegistry.php)

Before:

```diff
- file did not exist
```

After:

```diff
+ 'application_pdf' => ['required' => true, 'multiple' => false, ...]
+ 'pitch_deck' => ['required' => true, 'multiple' => false, ...]
+ 'pitch_video' => ['required' => false, 'multiple' => false, ...]
+ 'gallery_image' => ['required' => false, 'multiple' => true, ...]
+ 'business_document' => ['required' => false, 'multiple' => true, ...]
```

Why:

- Negadras needs category rules in one backend-owned place
- the UI should not invent what file types exist
- validation should not hardcode mime rules in multiple requests

This registry now answers:

- what categories exist
- which are required
- whether replacement or multiple upload is allowed
- what mime/extensions are allowed
- what max size applies

That makes later changes safer. If Negadras changes a required document type, the change belongs here first.

#### [app/Support/SubmissionFileBinder.php](/Users/yonassayfu/Herd/Negadras/app/Support/SubmissionFileBinder.php)

Before:

```diff
- file did not exist
```

After:

```diff
+ public function bindDraftFilesToVersion(Submission $submission, SubmissionVersion $version): void
+ {
+     $submission->files()
+         ->whereNull('submission_version_id')
+         ->update(['submission_version_id' => $version->id]);
+ }
```

Why:

- this is the key bridge between editable draft uploads and immutable versions
- the snapshotter creates the submission version record
- the binder attaches the current draft files to that version

Without this service, file history would drift away from submission history.

#### [app/Http/Requests/StoreSubmissionFileRequest.php](/Users/yonassayfu/Herd/Negadras/app/Http/Requests/StoreSubmissionFileRequest.php)

What changed:

```diff
+ 'file_type' => ['required', 'string', Rule::in(array_keys(SubmissionFileRegistry::definitions()))]
+ 'file' => ['required', 'file', 'mimes:...dynamic...', 'max:...dynamic...']
+ 'description' => ['nullable', 'string', 'max:1000']
```

Why:

- file validation must depend on the selected category
- `application_pdf` should not accept image mime types
- `gallery_image` should not accept PDFs

This request is also where the controller gets simplified:

- controller handles storage and persistence
- request handles input legitimacy

#### [app/Policies/SubmissionFilePolicy.php](/Users/yonassayfu/Herd/Negadras/app/Policies/SubmissionFilePolicy.php)

Why this file matters:

- presenters can only manage files for their own editable submissions
- staff with `submissions.view` or `submissions.update` can access through policy
- private downloads are not just hidden UI links; they are route-protected

Important rule:

```diff
+ presenter delete allowed only when parent submission is editable
+ locked version files cannot be deleted
```

So even if the user knows a download or delete URL, authorization still holds.

#### [app/Http/Controllers/SubmissionFileController.php](/Users/yonassayfu/Herd/Negadras/app/Http/Controllers/SubmissionFileController.php)

This file is the main operational controller for submission attachments.

What it now does:

```diff
+ store()
+ download()
+ destroy()
```

Key behavior in `store()`:

```diff
+ if the file category is single-file:
+     delete any existing unversioned file for that category
+     remove old physical file from storage
+ store the new file under:
+     negadras/submissions/{submission_id}/{file_type}
+ create submission_files row with uploaded_by/uploaded_at/is_required
```

Why:

- single-file categories need replace behavior, not duplicate stacking
- multi-file categories like gallery images should accumulate
- storage path structure should stay predictable for operations and debugging

Key behavior in `destroy()`:

```diff
+ abort_if($submissionFile->submission_version_id !== null, 403, 'Locked version files cannot be deleted.')
```

Why:

- once a file belongs to a locked submission version, it becomes historical evidence
- deletion at that point would corrupt the version record

#### [app/Support/SubmissionVersionSnapshotter.php](/Users/yonassayfu/Herd/Negadras/app/Support/SubmissionVersionSnapshotter.php)

What changed:

```diff
+ 'files' => $submission->files()
+     ->whereNull('submission_version_id')
+     ->get()
+     ->map(...)
```

Why:

- the snapshot now stores a file summary alongside text fields
- later reviewers or judges can know which categories existed at submission time
- even if UI rendering changes later, the version snapshot still preserves the submitted state

This is not the physical file copy. It is the metadata snapshot that belongs in the version JSON.

#### [app/Http/Controllers/SubmissionController.php](/Users/yonassayfu/Herd/Negadras/app/Http/Controllers/SubmissionController.php)

This was the most important integration point.

Before:

```diff
- submission create/edit/show only dealt with narrative fields and version history
- final submit created a version snapshot but had no file-binding step
```

After:

```diff
+ create/edit/show now pass submissionFileDefinitions
+ show/edit now eager load files.version and files.uploadedBy
+ submissionDetail() now returns draftFiles and currentVersionFiles
+ final submit now:
+     $version = $snapshotter->createSnapshot(...)
+     $fileBinder->bindDraftFilesToVersion($submission, $version)
```

Why:

- the presenter pages need both the editable draft file set and the current locked file set
- final submit must move draft files into the version boundary immediately

This is the real business rule of the whole phase:

- the live submission can still evolve
- the locked version is the official submitted package

#### [app/Http/Controllers/Admin/SubmissionManagementController.php](/Users/yonassayfu/Herd/Negadras/app/Http/Controllers/Admin/SubmissionManagementController.php)

What changed:

```diff
+ show() now loads files.version and files.uploadedBy
+ returns draftFiles
+ returns currentVersionFiles
+ returns submissionFileDefinitions
```

Why:

- staff needs visibility into what is merely drafted versus what is officially locked
- intake and later screening phases depend on this distinction

This keeps admin and presenter views aligned without duplicating business logic in Vue.

#### [resources/js/components/submissions/SubmissionFilesPanel.vue](/Users/yonassayfu/Herd/Negadras/resources/js/components/submissions/SubmissionFilesPanel.vue)

Before:

```diff
- file did not exist
```

After:

```diff
+ one reusable panel renders all file categories
+ draft files section
+ current locked version files section
+ per-category upload controls
+ delete draft file action
+ download action
+ required/optional badge
+ single/multiple badge
+ private storage explanation
```

Why:

- the upload UI should not be duplicated in presenter edit, presenter show, and admin show pages
- one component keeps category rendering and file actions consistent

This is also where the UX rule became clear:

- presenters manage draft files in edit mode
- presenter show page is read-only
- admin show page is read-only but fully visible

#### Vue page integrations

- [resources/js/pages/submissions/Edit.vue](/Users/yonassayfu/Herd/Negadras/resources/js/pages/submissions/Edit.vue)
- [resources/js/pages/submissions/Show.vue](/Users/yonassayfu/Herd/Negadras/resources/js/pages/submissions/Show.vue)
- [resources/js/pages/admin/Submissions/Show.vue](/Users/yonassayfu/Herd/Negadras/resources/js/pages/admin/Submissions/Show.vue)

What changed:

```diff
+ each page now mounts SubmissionFilesPanel
+ edit page passes can-manage
+ show pages render the same grouped file data in read-only mode
```

Why:

- the submission pages now explain the real attachment state
- file visibility is part of the submission record, not a separate hidden module

#### [resources/js/types/admin.ts](/Users/yonassayfu/Herd/Negadras/resources/js/types/admin.ts)

What changed:

```diff
+ type SubmissionFileDefinition
+ type ManagedSubmissionFile
+ ManagedSubmission now includes draftFiles and currentVersionFiles
```

Why:

- strong typing is what keeps Inertia controller payloads and Vue rendering aligned
- once the payload shape became richer, the frontend types needed to become explicit too

#### [tests/Feature/SubmissionFileFlowTest.php](/Users/yonassayfu/Herd/Negadras/tests/Feature/SubmissionFileFlowTest.php)

This test file proves the presenter-side business rules:

```diff
+ upload file
+ replace single-file category
+ final submit binds files to current version
+ unauthorized presenter download is forbidden
```

Why:

- file uploads are one of the easiest places to introduce security gaps
- this test guards private download access and version binding

#### [tests/Feature/Admin/SubmissionFileManagementTest.php](/Users/yonassayfu/Herd/Negadras/tests/Feature/Admin/SubmissionFileManagementTest.php)

What it proves:

```diff
+ manager can download an authorized submission file through the private route
```

Why:

- staff visibility must be explicit
- Negadras later depends on managers/secretaries inspecting intake files without turning storage public

### Laravel takeaways from this phase

1. Use a dedicated domain table when workflow rules differ from the generic media library.
2. Separate draft state from locked historical state with a nullable foreign key boundary.
3. Put file-category rules in a backend registry, not scattered across forms.
4. Private storage is only useful if download routes are policy-protected.
5. Submission versioning is incomplete unless files are bound into the same version boundary.

### Practical Negadras result

At the end of this phase:

- a presenter can upload required and optional submission files
- single-file categories can be replaced cleanly
- files are stored privately
- authorized users can download them
- final submit binds the draft files into the current locked version
- presenter and admin submission detail pages can both inspect the file state clearly

What is still intentionally not done in this phase:

- video link support as an alternative to upload
- secretary verification actions
- required-file completeness gate during intake status transitions
- file review notes per attachment

Those belong to later intake/review phases, not this storage foundation phase.

## Entry 006: Phase N1 Step 6 - Submission Status Tracking Foundation

### Scope

This batch added the real workflow history layer for submissions:

- `submission_status_history`
- controlled transition service
- `under_intake_check`, `eligible`, `rejected`
- required reason handling for return/reject
- timeline rendering on presenter and admin detail pages

The point of this phase was to stop treating `status` as a single mutable field and start treating it as an auditable workflow.

### Architecture decision

Status tracking is now split into two layers:

- current state on `submissions.status`
- immutable event history in `submission_status_history`

That matters because:

- current state tells the app what the submission is now
- history tells staff and presenters how it got there

Without both layers, Negadras would not be defensible once intake decisions start happening.

### Files and why they changed

#### [app/SubmissionStatus.php](/Users/yonassayfu/Herd/Negadras/app/SubmissionStatus.php)

Before:

```diff
- case Draft = 'draft'
- case Submitted = 'submitted'
- case IncompleteReturned = 'incomplete_returned'
- case Eligible = 'eligible'
- case ScreeningRejected = 'screening_rejected'
```

After:

```diff
+ case UnderIntakeCheck = 'under_intake_check'
+ case Rejected = 'rejected'
- case ScreeningRejected = 'screening_rejected'
```

Why:

- `under_intake_check` is a real operational state, not just a label
- `rejected` is the cleaner domain term for Phase 1 than `screening_rejected`
- the enum now matches the tracker language directly

This also changed:

```diff
+ label()
+ tone()
+ allowsPresenterEdits()
```

So the UI badge tone and presenter edit lock rules now understand the new workflow states.

#### [database/migrations/2026_03_19_064336_create_submission_status_histories_table.php](/Users/yonassayfu/Herd/Negadras/database/migrations/2026_03_19_064336_create_submission_status_histories_table.php)

Before:

```diff
- empty artisan stub with timestamps()
```

After:

```diff
+ submission_id
+ from_status nullable
+ to_status
+ changed_by nullable
+ reason nullable
+ created_at
```

Why:

- every transition needs actor, reason, and timestamp
- `from_status` is nullable because the initial event has no previous state

This is not a generic audit-log substitute. It is a submission-specific workflow ledger.

#### [database/migrations/2026_03_19_064916_migrate_submission_screening_rejected_status.php](/Users/yonassayfu/Herd/Negadras/database/migrations/2026_03_19_064916_migrate_submission_screening_rejected_status.php)

Why this exists:

- earlier development used `screening_rejected`
- the enum now uses `rejected`
- old rows would break enum casting if left unchanged

So this migration normalizes old `submissions.status` values safely.

#### [app/Models/SubmissionStatusHistory.php](/Users/yonassayfu/Herd/Negadras/app/Models/SubmissionStatusHistory.php)

What changed:

```diff
+ protected $table = 'submission_status_history'
+ public const UPDATED_AT = null
+ casts from_status/to_status to SubmissionStatus
+ submission() relation
+ actor() relation
```

Why:

- this model should read like a domain event record, not a generic note
- enum casts keep timeline rendering consistent with the current submission status system

#### [app/Models/Submission.php](/Users/yonassayfu/Herd/Negadras/app/Models/Submission.php)

What changed:

```diff
+ statusHistory(): HasMany
+ scopeUnderIntakeCheck()
```

Why:

- the model now exposes the workflow ledger directly
- the detail pages and future intake dashboards need timeline access without raw queries

#### [app/Support/SubmissionStatusTransitionService.php](/Users/yonassayfu/Herd/Negadras/app/Support/SubmissionStatusTransitionService.php)

This is the core of the phase.

Before:

```diff
- empty artisan class
```

After:

```diff
+ private const TRANSITIONS = [...]
+ availableStaffTransitions()
+ transition()
+ recordInitialStatus()
+ requiresReason()
```

Why:

- the transition map is now centralized
- controllers no longer invent allowed moves ad hoc
- return and reject reason enforcement lives in one place

Important transition rules now encoded:

```diff
+ draft -> submitted
+ submitted -> under_intake_check | incomplete_returned | eligible | rejected
+ under_intake_check -> incomplete_returned | eligible | rejected
+ incomplete_returned -> submitted
```

That is the first real Negadras workflow engine.

#### [app/Http/Requests/TransitionSubmissionStatusRequest.php](/Users/yonassayfu/Herd/Negadras/app/Http/Requests/TransitionSubmissionStatusRequest.php)

Why this file matters:

- admin/staff status updates need dedicated validation, not inline controller checks
- allowed transitions are pulled from the service
- return/reject requires a reason

The key part is:

```diff
+ 'status' must be one of the available transitions for this submission
+ 'reason' is enforced by custom closure logic for return/reject
```

This keeps invalid or context-free transitions out of the system.

#### [app/Http/Controllers/SubmissionController.php](/Users/yonassayfu/Herd/Negadras/app/Http/Controllers/SubmissionController.php)

This file changed in two important ways.

1. Presenter create/update now logs workflow history.

```diff
+ recordInitialStatus() on creation
+ transition(... Submitted ...) on final submit
```

2. Presenter detail payload now includes timeline data.

```diff
+ latestStatusReason
+ statusTimeline
+ eager load statusHistory.actor
```

Why:

- presenter actions must contribute to the same status ledger as staff actions
- the presenter detail page should explain why a submission was returned, not force the user to guess

#### [app/Http/Controllers/Admin/SubmissionManagementController.php](/Users/yonassayfu/Herd/Negadras/app/Http/Controllers/Admin/SubmissionManagementController.php)

This is where staff transitions became operational.

What changed:

```diff
+ transition() action
+ availableTransitions in Inertia props
+ canTransitionStatus in Inertia props
+ latestStatusReason
+ statusTimeline
```

Why:

- staff needs an explicit intake control surface
- the admin detail page is now the first real operational review screen

The controller now does two things:

- renders the current timeline
- applies the next valid state through the transition service

#### [database/seeders/RolePermissionSeeder.php](/Users/yonassayfu/Herd/Negadras/database/seeders/RolePermissionSeeder.php)

What changed:

```diff
+ Manager now receives submissions.update
```

Why:

- Phase 1 tracker expects operational staff to move intake statuses
- without this, Manager could inspect submissions but not act on them

This is a real business permission change, not just a UI tweak.

#### [routes/web.php](/Users/yonassayfu/Herd/Negadras/routes/web.php)

What changed:

```diff
+ POST admin/submissions/{submission}/status
+ name: admin-submissions.transition
```

Why:

- the workflow needed a dedicated staff transition route
- route-level permission middleware now protects that action before controller code runs

#### [resources/js/types/admin.ts](/Users/yonassayfu/Herd/Negadras/resources/js/types/admin.ts)

What changed:

```diff
+ latestStatusReason on ManagedSubmission
+ statusTimeline on ManagedSubmission
+ SubmissionStatusTimelineEntry type
+ SubmissionTransitionOption type
```

Why:

- the admin and presenter pages now consume richer workflow data
- explicit types keep the controller payload and Vue render layer synchronized

#### [resources/js/pages/submissions/Show.vue](/Users/yonassayfu/Herd/Negadras/resources/js/pages/submissions/Show.vue)

What changed:

```diff
+ latest status note block
+ status timeline panel
```

Why:

- when a submission is returned, the presenter needs the operational reason in the main detail view
- this is the first user-facing explanation layer for Negadras intake decisions

#### [resources/js/pages/admin/Submissions/Show.vue](/Users/yonassayfu/Herd/Negadras/resources/js/pages/admin/Submissions/Show.vue)

This was the main frontend operational change.

What changed:

```diff
+ status transition form
+ next-status select
+ reason textarea
+ timeline panel
+ latest status note block
```

Why:

- staff needs to move submissions through intake without leaving the detail page
- timeline and action controls belong together in the same review surface

Important UX rule now implemented:

- return/reject prompts for a reason
- timeline immediately shows the note after transition

#### [tests/Feature/SubmissionStatusTrackingTest.php](/Users/yonassayfu/Herd/Negadras/tests/Feature/SubmissionStatusTrackingTest.php)

This is the test file that proves the phase works.

It now covers:

```diff
+ presenter status history on final submit
+ manager transition flow
+ required reason for incomplete_returned
+ presenter/admin timeline payloads
```

Why:

- status transitions are business-critical
- this phase would be unsafe without tests around allowed moves and reason handling

### Laravel takeaways from this phase

1. Current state and history should be modeled separately.
2. Transition rules belong in a service, not copied across controllers.
3. Reasons for workflow decisions should be validated at the request boundary and recorded in the history layer.
4. Policies and permission middleware still matter even when the transition map is correct.
5. Inertia detail pages become much more useful when they carry operational history, not only current values.

### Practical Negadras result

At the end of this phase:

- presenters still submit drafts/finals normally
- staff can move submissions to `under_intake_check`
- staff can return incomplete submissions with a reason
- staff can mark submissions eligible
- staff can reject submissions with a reason
- presenter and admin detail pages now show the status timeline
- the latest return/reject note is visible instead of being buried

What is still intentionally not done in this phase:

- intake checklist scoring
- batch transition tools on the submissions list
- reviewer assignment after intake
- secretary-specific role split

Those belong to later intake-management and operational workflow phases.

## Entry 007: Phase N1 Step 7 - Intake Management Module

### Scope

This batch turned the admin submissions queue into the first real intake operations surface:

- admin/manager/secretary intake list
- season, stage, industry, and status filters
- derived intake checklist
- return-for-correction notes from the list workflow
- quick operational review actions

The goal was to make staff able to work from the queue instead of opening every submission detail page just to decide the next intake step.

### Architecture decision

The intake checklist is **derived**, not persisted.

Why:

- profile completeness, required files, industry selection, and narrative completeness already exist in the core submission data
- storing a second `is_intake_ready` field would drift out of sync quickly

So the correct design here is:

- one service computes intake readiness from the current submission state
- both the list and the detail page render the same derived result

That keeps the intake rules centralized and auditable.

### Files and why they changed

#### [app/Support/SubmissionIntakeChecklist.php](/Users/yonassayfu/Herd/Negadras/app/Support/SubmissionIntakeChecklist.php)

Before:

```diff
- empty artisan stub
```

After:

```diff
+ forSubmission(Submission $submission): array
+ returns:
+   items
+   passedCount
+   totalCount
+   isReady
```

What it checks:

```diff
+ presenter profile complete
+ organization info complete
+ required files uploaded
+ industry selected
+ summary completed
+ contact info valid
```

Why:

- this is the first operational readiness engine for intake
- it converts raw submission data into a staff-facing decision summary

This service is deliberately simple and deterministic. Later phases can extend it, but Phase 1 needed a trustworthy baseline first.

#### [database/seeders/RolePermissionSeeder.php](/Users/yonassayfu/Herd/Negadras/database/seeders/RolePermissionSeeder.php)

What changed:

```diff
+ added Secretary role
+ Manager keeps submissions.update
+ Secretary receives submissions.view + submissions.update
```

Why:

- the tracker explicitly calls out admin, manager, and secretary as intake actors
- the system needed a real `Secretary` role instead of treating everything as `Manager`

This matters because Negadras is not just RBAC by page. It is operational role separation.

#### [app/Http/Controllers/Admin/SubmissionManagementController.php](/Users/yonassayfu/Herd/Negadras/app/Http/Controllers/Admin/SubmissionManagementController.php)

This file absorbed most of the intake behavior.

What changed in `index()`:

```diff
+ filters:
+   season_id
+   stage_id
+   industry_id
+   status
+ richer eager loading for checklist computation
+ seasonOptions
+ stageOptions
+ industryOptions
+ statusOptions
+ submissionSummary(...) now receives:
+   SubmissionIntakeChecklist
+   SubmissionStatusTransitionService
```

Why:

- the intake queue needs operational filters, not only text search
- checklist readiness depends on related data like applicant phone, organization contact email, primary contact, and uploaded files

Important correction made during this phase:

- the checklist was initially false-negative because the list only eager-loaded `applicant.full_name` and `applicant.email`
- the service also needed `applicant.phone`
- I fixed the controller eager loads instead of weakening the checklist rules

That is the right engineering choice because the business rule was correct; the query shape was incomplete.

What changed in `submissionSummary()`:

```diff
+ latestStatusReason
+ intakeChecklist
+ availableTransitions
```

Why:

- the queue now needs enough data to let staff judge readiness and act immediately
- the row should expose whether the submission is intake-ready and what transitions are currently allowed

What changed in `show()`:

```diff
+ checklist-safe eager loads
+ show page now inherits the same derived intake summary as the list
```

Why:

- the intake checklist should not disagree between queue and detail page

#### [resources/js/types/admin.ts](/Users/yonassayfu/Herd/Negadras/resources/js/types/admin.ts)

What changed:

```diff
+ SubmissionIntakeChecklist
+ SubmissionIntakeChecklistItem
+ ManagedSubmission.intakeChecklist
+ ManagedSubmission.availableTransitions
+ ResourceFilters now includes seasonId, stageId, industryId, status
```

Why:

- once the queue payload grew beyond simple summary rows, the frontend types had to become explicit
- this keeps the Inertia payload contract honest

#### [resources/js/pages/admin/Submissions/Index.vue](/Users/yonassayfu/Herd/Negadras/resources/js/pages/admin/Submissions/Index.vue)

This is the main intake-management page.

Before:

```diff
- search only
- summary table
- view button only
```

After:

```diff
+ filters for season, stage, industry, status
+ checklist summary column
+ latest status note under current badge
+ quick review action block per row
+ reason textarea in list workflow
+ list-level transition post to admin-submissions.transition
```

Why:

- the user requirement was explicit: intake staff should work from the queue
- return-for-correction note handling must exist in the list workflow, not only on the detail page

This page now functions like the first real intake cockpit.

#### [resources/js/pages/admin/Submissions/Show.vue](/Users/yonassayfu/Herd/Negadras/resources/js/pages/admin/Submissions/Show.vue)

What changed:

```diff
+ intake checklist panel
```

Why:

- even though the queue is now stronger, the detail page still needs the same readiness breakdown
- the checklist service is now reused in both places

That reuse is important. Intake logic should not split into “queue rules” and “detail rules.”

#### [tests/Feature/Admin/SubmissionIntakeManagementTest.php](/Users/yonassayfu/Herd/Negadras/tests/Feature/Admin/SubmissionIntakeManagementTest.php)

This test file proves the intake module works.

It now covers:

```diff
+ secretary can access the intake queue
+ queue exposes checklist data and filter option payloads
+ queue filters by status
+ a checklist can resolve to ready when the submission is actually complete
+ manager can apply a quick return-for-correction transition with a note
```

Why:

- this phase changed the queue from read-only to operational
- without feature tests, the checklist and quick review workflow would be too easy to regress

### Laravel takeaways from this phase

1. Derived operational state should usually come from a service, not a duplicated database flag.
2. If a derived service depends on relation fields, the controller query must load the full shape required by that service.
3. Queue pages become genuinely useful only when they carry both decision data and action controls.
4. Role modeling should follow the business process; adding `Secretary` here was a workflow decision, not just a permission change.
5. Inertia list pages can support real operations without needing a second separate “review module” page when the payload is shaped correctly.

### Practical Negadras result

At the end of this phase:

- admin, manager, and secretary can work from the intake queue
- the queue filters by season, stage, industry, status, and search text
- every row now shows a real intake readiness summary
- staff can see the latest return/review note immediately
- staff can move a submission from the list with a reason when needed
- the admin detail page shows the same checklist logic as the list

What is still intentionally not done in this phase:

- batch transitions
- checklist persistence override
- secretary-specific assignment queue
- reviewer assignment from the intake list

## Entry 008: Phase N1 Presenter Portal UX Refinement and Dashboard Operations

### Scope

This batch closed the presenter-facing UX gap that was still left after the intake foundation.

The goal was to make Negadras usable from the presenter side, not only structurally correct in the admin flow.

This phase added:

- current season and open-call visibility
- presenter submission counters
- stronger create-submission CTA rules
- progress indicators on submission forms
- draft-only autosave for editable submissions
- Negadras-specific dashboard metrics for both presenter and staff views

### The core design decision

The important decision in this phase was:

- keep explicit `Save draft` and `Final submit`
- add autosave only as a background convenience for editable draft fields
- do not let autosave blur the actual submission boundary

That is why autosave is:

- only on the edit page
- only for presenter-owned editable submissions
- separate from the final submit route

This keeps the business rule clear: autosave is not submission.

### Files and why they changed

#### [app/Http/Controllers/DashboardController.php](/Users/yonassayfu/Herd/Negadras/app/Http/Controllers/DashboardController.php)

Before:

```diff
- dashboard still reflected generic starter-business metrics
- pages/media/imports/users were the main focus
- no presenter counters
- no current season visibility
```

After:

```diff
+ currentSeason summary
+ presenterPortal payload
+ operations metrics for Negadras submission workflow
+ submissionBreakdown for staff
+ recentSubmissions list
+ platformHealth kept as the shared business baseline
```

Why:

- the old dashboard was technically valid but operationally wrong for Negadras
- presenters need to know whether the call is open and how many drafts or returned records they have
- staff need immediate counts for total, returned, eligible, and active season visibility

Important implementation detail:

```diff
+ private function currentSeason(): ?Season
+ private function seasonSummary(?Season $season): ?array
+ private function presenterPortal(?Applicant $applicant, ?Season $currentSeason): ?array
```

Why this matters:

- season visibility rules are now centralized instead of spread across frontend conditionals
- the dashboard payload now describes business state directly

#### [app/Http/Controllers/SubmissionController.php](/Users/yonassayfu/Herd/Negadras/app/Http/Controllers/SubmissionController.php)

This file changed in three important ways.

First, the submission index now returns presenter counters and open-call context.

Before:

```diff
- only hasApplicantProfile
- only raw submission list
```

After:

```diff
+ openSeason
+ submissionCounts
```

Why:

- the presenter submission list should behave like a working portal, not a plain record index
- the user needs to see draft/submitted/returned state before opening a specific record

Second, the create and edit pages now receive `openSeason`.

Why:

- the forms should show the current call boundary while the presenter is editing
- this makes the season window visible at the point of action, not only on the dashboard

Third, a new autosave endpoint was added.

New method:

```diff
+ public function autosave(UpdateSubmissionRequest $request, Submission $submission): JsonResponse
```

Key guard logic:

```diff
+ abort_unless(
+     $request->user() !== null
+         && $submission->isOwnedBy($request->user())
+         && $submission->status->allowsPresenterEdits(),
+     403,
+ );
```

Why:

- policy `update()` also allows staff with `submissions.update`
- autosave must not become a generic staff-edit endpoint
- only the owning presenter can silently autosave the draft

This is one of those places where policy-level access and business-intent access are not identical.

#### [routes/web.php](/Users/yonassayfu/Herd/Negadras/routes/web.php)

New route:

```diff
+ Route::put('submissions/{submission}/autosave', [SubmissionController::class, 'autosave'])->name('submissions.autosave');
```

Why:

- explicit save and final submit already existed
- autosave needed a separate endpoint with a different response shape
- reusing the main update route would have redirected the page and broken the draft-edit flow

This is a good Laravel lesson:

- if two user actions have different transport/response expectations, they usually deserve separate endpoints even if they touch the same model

#### [resources/js/pages/Dashboard.vue](/Users/yonassayfu/Herd/Negadras/resources/js/pages/Dashboard.vue)

This page was heavily reworked.

Before:

```diff
- business starter workspace
- generic reports/pages/import links
- generic report highlight cards
```

After:

```diff
+ current season panel
+ presenter portal panel
+ presenter counters
+ recent presenter submissions
+ operational submission breakdown
+ recent submission activity
+ platform baseline block kept as secondary
```

Why:

- Negadras no longer needs a generic starter dashboard as the primary surface
- the dashboard must now answer:
  - Is the season open?
  - Can I create a submission?
  - How many drafts/returned items exist?
  - What is the intake team seeing right now?

This file now serves both presenter and staff use cases in one page without creating two dashboard routes too early.

#### [resources/js/pages/submissions/Index.vue](/Users/yonassayfu/Herd/Negadras/resources/js/pages/submissions/Index.vue)

Before:

```diff
- list page with create button
- profile-required empty state
- basic submission cards
```

After:

```diff
+ current season/open-call card
+ draft/submitted/returned/total counters
+ CTA changes based on presenter profile and open call state
+ closed-call message when new draft creation should pause
```

Why:

- this page is the real presenter workspace, not just a list of rows
- the CTA must change based on business state:
  - no profile -> complete presenter profile
  - profile + open call -> create submission
  - profile + closed call -> CTA disabled

That conditional CTA is one of the most important UX upgrades in this batch.

#### [resources/js/pages/submissions/Create.vue](/Users/yonassayfu/Herd/Negadras/resources/js/pages/submissions/Create.vue)

What changed:

```diff
+ computed progressSections
+ computed progressPercentage
+ submission progress card
+ current open call card
```

Why:

- the form is still single-page, but it behaves like a structured multi-part workflow
- presenters need immediate signal on how complete the draft is

Key pattern:

```diff
+ const progressSections = computed(() => [
+   { key: 'structure', completed: ..., total: 4 },
+   { key: 'narrative', completed: ..., total: 4 },
+   { key: 'visibility', completed: ..., total: 1 },
+ ]);
```

Why this pattern is good:

- the progress UI is derived from form state, not stored separately
- no extra synchronization bug is introduced

#### [resources/js/pages/submissions/Edit.vue](/Users/yonassayfu/Herd/Negadras/resources/js/pages/submissions/Edit.vue)

This file got the most important UX logic in the phase.

New parts:

```diff
+ progressSections including required files
+ progressPercentage
+ autosaveState
+ autosavedAt
+ autosavePayload
+ canAutosave
+ saveDraftSilently()
+ debounced watch() based autosave
```

The most important code path:

```diff
+ if (! canAutosave.value || form.processing) {
+     return;
+ }
```

Why:

- autosave should not fight explicit form submission
- autosave should stop if the minimum draft structure is not present

The fetch boundary:

```diff
+ const response = await fetch(autosaveSubmission(props.submission.id).url, {
+     method: 'PUT',
+     headers: {
+         'Accept': 'application/json',
+         'Content-Type': 'application/json',
+         'X-CSRF-TOKEN': csrfToken,
+         'X-Requested-With': 'XMLHttpRequest',
+     },
+     body: JSON.stringify(autosavePayload.value),
+     credentials: 'same-origin',
+ });
```

Why this approach was chosen:

- the Inertia form helpers are ideal for explicit actions
- autosave needed a silent JSON roundtrip
- using the main update form path would have redirected and interrupted editing

Also important:

```diff
+ requiredFileTypes
+ uploadedRequiredFileTypes
```

Why:

- submission progress should not ignore required files once the record exists
- edit progress is now more honest than create progress

#### [tests/Feature/PresenterPortalDashboardTest.php](/Users/yonassayfu/Herd/Negadras/tests/Feature/PresenterPortalDashboardTest.php)

This new test proves:

- an applicant with a signed-in user sees current season visibility
- open-call state is exposed correctly
- draft/submitted/returned/total counts are returned correctly
- recent presenter submissions are included in the dashboard props

Why:

- this phase introduced a lot of derived dashboard state
- without a dedicated test, the presenter portal could drift as later phases add more statuses

#### [tests/Feature/DashboardWidgetsTest.php](/Users/yonassayfu/Herd/Negadras/tests/Feature/DashboardWidgetsTest.php)

Before:

```diff
- asserted old generic starter-business dashboard props
```

After:

```diff
+ asserts operations.metrics
+ asserts submission breakdown exists
+ asserts currentSeason
+ asserts returned count and platform health values
```

Why:

- Negadras replaced the generic dashboard contract
- the test had to move with the real dashboard purpose

#### [tests/Feature/SubmissionFlowTest.php](/Users/yonassayfu/Herd/Negadras/tests/Feature/SubmissionFlowTest.php)

New test:

```diff
+ presenter can autosave an editable draft without final submission
```

Why:

- autosave is easy to implement incorrectly
- this test proves:
  - the title and summary really change
  - the submission remains `draft`
  - `submitted_at` stays null

That last point is critical. Autosave must never behave like submit.

### Laravel takeaways from this phase

1. A dashboard should change when the application identity changes. Reusing a generic starter dashboard too long becomes technical correctness with bad product behavior.
2. Business state like "open for applications" should be computed on the server and shipped as explicit props, not re-derived independently on multiple pages.
3. Autosave is safer when it is intentionally narrower than the main update flow.
4. Silent JSON endpoints are a valid complement to Inertia when the UX requires non-navigating persistence.
5. Progress indicators should come from live form state and file state, not stored percentages.

### Practical Negadras result

At the end of this phase:

- presenters can immediately see whether the current season is open
- presenters see draft, submitted, returned, and total counts
- the create-submission CTA now respects profile readiness and open-call state
- submission create/edit pages show completion progress
- editable drafts autosave in the background
- staff and presenters now see a Negadras-specific dashboard instead of the old starter-business one

### Progress position after this phase

Using the current detailed tracker:

- Phase N1 is roughly **79% complete**
- the full tracked Negadras roadmap is roughly **21% complete**

This is the correct shape right now:

- the foundational domain and presenter intake flow are getting strong
- judging, assignments, live session tooling, public showcase, AI advisory, and hardening are still ahead

Those belong to later operational and review phases.

## Entry 009: Phase N2 Screening and Reviewer Foundation

### Scope

This phase started the real post-intake workflow.

The goal was not to build the full judging engine yet. The goal was to create the first controlled review layer between intake staff and later judging:

- reviewer profiles
- reviewer role access
- reviewer assignment workflow
- reviewer-facing queue
- screening review draft and submission flow

This is the phase where Negadras starts behaving like a review platform instead of only an intake portal.

### Core design decision

The important architecture decision in this phase was:

- reviewers are real users with a reviewer profile
- assignments are the access boundary
- screening reviews belong to assignments, not directly to users alone

That means:

- the reviewer sees only what is assigned
- the assignment carries the stage and due date context
- the review can be audited against a specific assignment record

This is the right structure because later:

- reassignment
- multiple review layers
- technical review
- shortlist decisions

all need assignment-aware history.

### Files and why they changed

#### [app/Models/Reviewer.php](/Users/yonassayfu/Herd/Negadras/app/Models/Reviewer.php)

Before:

```diff
- empty artisan model stub
```

After:

```diff
+ fillable reviewer profile fields
+ casts for is_active
+ belongsTo user()
+ hasMany assignments()
```

Why:

- Negadras needs reviewer-specific metadata that should not live directly on `users`
- this keeps the user account generic while letting the reviewer workflow grow independently

The important relation:

```diff
+ public function user(): BelongsTo
+ public function assignments(): HasMany
```

This is what makes role membership and reviewer profile distinct but connected.

#### [app/Models/ReviewerAssignment.php](/Users/yonassayfu/Herd/Negadras/app/Models/ReviewerAssignment.php)

This model is the real center of the phase.

Before:

```diff
- no assignment model existed
```

After:

```diff
+ submission_id
+ reviewer_id
+ stage_id
+ assigned_at
+ due_at
+ status
+ screeningReview()
+ isOwnedBy(User $user)
```

Why:

- direct reviewer-to-submission access is too weak
- the assignment is where operational context lives

The ownership helper is the practical enforcement point:

```diff
+ return $this->reviewer?->user_id === $user->id;
```

That method is reused by policies so the reviewer queue is not protected only by frontend filtering.

#### [app/Models/ScreeningReview.php](/Users/yonassayfu/Herd/Negadras/app/Models/ScreeningReview.php)

What changed:

```diff
+ submission_id
+ reviewer_assignment_id
+ eligibility_status
+ recommendation
+ score_optional
+ notes
+ submitted_at
```

Why:

- Negadras needed draftable reviewer work before full judging
- this model captures the first decision layer without forcing the later rubric engine too early

The key rule:

```diff
+ one screening review per reviewer assignment
```

This keeps the flow simple for Phase 2 and prevents reviewer ambiguity.

#### [database/migrations/2026_03_19_072945_create_reviewers_table.php](/Users/yonassayfu/Herd/Negadras/database/migrations/2026_03_19_072945_create_reviewers_table.php)

Why this migration matters:

- it formalizes reviewer identity as a first-class workflow actor
- `user_id` is unique, which means one user has at most one reviewer profile

Important fields:

```diff
+ professional_title
+ organization
+ specialization
+ bio
+ is_active
```

These are not cosmetic. They prepare:

- reviewer selection
- future specialization filtering
- workload balancing

#### [database/migrations/2026_03_19_072945_create_reviewer_assignments_table.php](/Users/yonassayfu/Herd/Negadras/database/migrations/2026_03_19_072945_create_reviewer_assignments_table.php)

This migration created the workflow backbone.

Important columns:

```diff
+ submission_id
+ reviewer_id
+ stage_id
+ assigned_at
+ due_at
+ status
```

Why `stage_id` is important:

- the same submission can move through multiple stages
- later review decisions must still know which stage the assignment belonged to

#### [database/migrations/2026_03_19_072945_create_screening_reviews_table.php](/Users/yonassayfu/Herd/Negadras/database/migrations/2026_03_19_072945_create_screening_reviews_table.php)

Important rule recorded in schema:

```diff
+ unique reviewer_assignment_id
```

Why:

- one active screening decision record should map to one assignment
- this avoids duplicate draft/final review rows fighting each other

#### [app/ReviewerAssignmentStatus.php](/Users/yonassayfu/Herd/Negadras/app/ReviewerAssignmentStatus.php)

This moved from placeholder to real enum.

After:

```diff
+ Assigned
+ InProgress
+ Submitted
+ Expired
+ Cancelled
+ label()
+ tone()
+ isActive()
```

Why:

- reviewer queue and assignment management both need one shared status language
- the UI and policies should not invent separate string literals

#### [app/ScreeningRecommendation.php](/Users/yonassayfu/Herd/Negadras/app/ScreeningRecommendation.php)

After:

```diff
+ Pass
+ Reject
+ ReturnForRevision
+ Escalate
```

Why:

- the tracker required explicit recommendation paths
- those values need to be stable because later manager decision logic will depend on them

#### [app/ScreeningEligibilityStatus.php](/Users/yonassayfu/Herd/Negadras/app/ScreeningEligibilityStatus.php)

After:

```diff
+ Eligible
+ Ineligible
+ NeedsClarification
```

Why:

- recommendation and eligibility are not the same thing
- the project needs both dimensions:
  - "Is this entry eligible?"
  - "What should happen next?"

That separation is important for later shortlisting logic.

#### [app/Support/ReviewerAssignmentService.php](/Users/yonassayfu/Herd/Negadras/app/Support/ReviewerAssignmentService.php)

This is the most important backend file in the phase.

Before:

```diff
- empty class
```

After:

```diff
+ assign(...)
+ markInProgress(...)
+ complete(...)
+ cancel(...)
+ saveDraftReview(...)
+ submitReview(...)
```

Why this service exists:

- assignment rules and review state changes should not be scattered across controllers
- the service centralizes workflow side effects:
  - validation
  - assignment status changes
  - activity logging
  - notifications

Example of the key guard:

```diff
+ if ($submission->status !== SubmissionStatus::Eligible) {
+     throw ValidationException::withMessages([
+         'submission' => 'Only eligible submissions can be assigned to reviewers.',
+     ]);
+ }
```

Why:

- reviewer work should begin only after intake has marked the submission eligible
- this enforces the phase boundary between intake and screening

Duplicate protection also lives here:

```diff
+ ->whereIn('status', [
+     ReviewerAssignmentStatus::Assigned,
+     ReviewerAssignmentStatus::InProgress,
+ ])
```

Why:

- the same reviewer should not receive the same active assignment twice for the same submission/stage

#### [app/Http/Controllers/Admin/ReviewerManagementController.php](/Users/yonassayfu/Herd/Negadras/app/Http/Controllers/Admin/ReviewerManagementController.php)

This file created the admin-facing reviewer module.

It now handles:

- reviewer index
- create reviewer profile
- edit reviewer profile
- available user selection
- reviewer summary payloads

Important behavior:

```diff
+ selected user gets Reviewer role
+ reviewer profile is created separately
```

Why:

- a reviewer must be both:
  - a system user with permissions
  - a workflow actor with profile data

#### [app/Http/Controllers/Admin/ReviewerAssignmentManagementController.php](/Users/yonassayfu/Herd/Negadras/app/Http/Controllers/Admin/ReviewerAssignmentManagementController.php)

This controller intentionally stays thin.

It does two things:

- assign reviewer to submission
- cancel reviewer assignment

Why:

- the real workflow logic belongs in `ReviewerAssignmentService`
- the controller should remain a transport layer

That separation matters because reassign/bulk-assign later can reuse the same service rules.

#### [app/Http/Controllers/ReviewerQueueController.php](/Users/yonassayfu/Herd/Negadras/app/Http/Controllers/ReviewerQueueController.php)

This file is the reviewer-facing workspace.

What it now does:

```diff
+ index() shows only assignments owned by the signed-in reviewer
+ filters by search, status, and stage
+ show() loads full submission review context
```

Important gate:

```diff
+ abort_if($reviewer === null || ! $reviewer->is_active, 403);
```

Why:

- having the Reviewer role alone is not enough
- the user also needs an active reviewer profile

The detail page payload includes:

- submission narrative
- current version files
- version history
- status timeline
- review draft values
- recommendation and eligibility options

That payload is large on purpose. Reviewers need context without opening three other screens.

#### [app/Http/Controllers/ScreeningReviewController.php](/Users/yonassayfu/Herd/Negadras/app/Http/Controllers/ScreeningReviewController.php)

This file handles the reviewer save/submit boundary.

Important split:

```diff
+ intent = draft
+ intent = submit
```

Why:

- the system must support partial work
- final submission needs a stricter boundary than draft save

Also important:

```diff
+ already submitted reviews are locked
```

Why:

- reviewer decisions should not silently change after submission
- reopening should be an explicit later manager workflow, not implicit editing

#### [app/Policies/ReviewerPolicy.php](/Users/yonassayfu/Herd/Negadras/app/Policies/ReviewerPolicy.php)
#### [app/Policies/ReviewerAssignmentPolicy.php](/Users/yonassayfu/Herd/Negadras/app/Policies/ScreeningReviewPolicy.php)

These three policies are what make the reviewer workflow safe.

What changed:

```diff
+ admin permissions protect reviewer management
+ reviewer-owned assignments can be viewed by the reviewer
+ reviewer-owned reviews can be updated only before submission
```

Why:

- Negadras now has assignment-based access rules, not only role-based pages
- this is a step toward the stricter access model that judging will need later

#### [database/seeders/RolePermissionSeeder.php](/Users/yonassayfu/Herd/Negadras/database/seeders/RolePermissionSeeder.php)

This file changed in an important business way.

New permissions added:

```diff
+ reviewers.view
+ reviewers.create
+ reviewers.update
+ reviewer-assignments.view
+ reviewer-assignments.create
+ reviewer-assignments.update
+ reviewer-queue.view
+ screening-reviews.view
+ screening-reviews.create
+ screening-reviews.update
```

New role added:

```diff
+ Reviewer
```

Why:

- reviewer work is now an explicit bounded workflow
- it should not be faked through Manager or Secretary access

#### [routes/web.php](/Users/yonassayfu/Herd/Negadras/routes/web.php)

This phase added the full route layer for reviewer operations.

New route groups now cover:

- reviewer queue index
- reviewer assignment detail
- screening review save/submit
- admin reviewer CRUD
- admin reviewer assignment create/cancel

Why:

- the project now needed both reviewer-facing and admin-facing endpoints
- these route names also power the Wayfinder frontend contract

#### [resources/js/pages/admin/Reviewers/Index.vue](/Users/yonassayfu/Herd/Negadras/resources/js/pages/admin/Reviewers/Index.vue)

This page is the admin reviewer registry.

It shows:

- reviewer identity
- active/inactive status
- reviewer profile context
- current active assignment count

Why:

- managers need quick operational visibility before assigning work
- workload count is the first simple balancing signal

#### [resources/js/pages/admin/Reviewers/Create.vue](/Users/yonassayfu/Herd/Negadras/resources/js/pages/admin/Reviewers/Create.vue)
#### [resources/js/pages/admin/Reviewers/Edit.vue](/Users/yonassayfu/Herd/Negadras/resources/js/pages/admin/Reviewers/Edit.vue)

These pages create and maintain reviewer profiles.

Why:

- reviewer management had to become a first-class admin task before assignment workflows could be trusted

#### [resources/js/pages/reviewers/Queue.vue](/Users/yonassayfu/Herd/Negadras/resources/js/pages/reviewers/Queue.vue)

This page is effectively the reviewer dashboard for Phase 2.

It now shows:

- assigned submissions only
- current assignment status
- overdue indicator
- stage filter
- status filter
- search by title or presenter

Why:

- reviewers need a focused work queue, not the general admin submissions list

#### [resources/js/pages/reviewers/Review.vue](/Users/yonassayfu/Herd/Negadras/resources/js/pages/reviewers/Review.vue)

This page is the screening work surface.

It combines:

- submission context
- file visibility
- status history
- version history
- draft review fields
- final submission action

Why:

- asking the reviewer to jump between multiple pages would slow down the workflow and increase mistakes

#### [resources/js/pages/admin/Submissions/Show.vue](/Users/yonassayfu/Herd/Negadras/resources/js/pages/admin/Submissions/Show.vue)

This file was extended rather than replaced.

New parts:

```diff
+ reviewer assignment form
+ reviewer options
+ assigned reviewer cards
+ cancel assignment action
```

Why:

- assignment belongs operationally near the submission detail
- intake staff and managers already work here

That is the correct local integration point for now.

#### [tests/Feature/ReviewerAssignmentManagementTest.php](/Users/yonassayfu/Herd/Negadras/tests/Feature/ReviewerAssignmentManagementTest.php)

This test proves:

- admin can create reviewer profile
- eligible submissions can be assigned
- duplicate active assignments are blocked

Why:

- assignment duplication is one of the easiest workflow bugs to ship if it is not explicitly tested

#### [tests/Feature/ReviewerQueueTest.php](/Users/yonassayfu/Herd/Negadras/tests/Feature/ReviewerQueueTest.php)

This test proves:

- reviewer sees only their own assignments
- reviewer cannot open another reviewer’s assignment detail

Why:

- reviewer access is assignment-scoped, not broad role access
- this is a core confidentiality rule

#### [tests/Feature/ScreeningReviewFlowTest.php](/Users/yonassayfu/Herd/Negadras/tests/Feature/ScreeningReviewFlowTest.php)

This test proves:

- reviewer can save screening review as draft
- reviewer can later submit it
- submitted review becomes locked

Why:

- this phase introduced the draft/final review boundary
- without a test, it would be easy to accidentally allow silent post-submit edits

### Laravel takeaways from this phase

1. Role-based access alone is too weak for reviewer workflows. Assignment-based policy checks are the real boundary.
2. Workflow services should own state changes, duplicate prevention, logging, and notifications together.
3. Review forms usually need a draft/save boundary before they need a full rubric engine.
4. A reviewer queue is more useful when it carries full context filters and overdue visibility from the first version.
5. If a review becomes auditable later, it should already be modeled as assignment-linked from the beginning.

### Practical Negadras result

At the end of this phase:

- reviewers exist as real users with reviewer profiles
- reviewer-specific permissions are live
- eligible submissions can be assigned to reviewers
- duplicate active reviewer assignments are blocked
- reviewers receive assignment notifications
- reviewers get a dedicated queue with filters and overdue visibility
- reviewers can save draft screening reviews and submit final recommendations
- submitted screening reviews are locked

What is intentionally still not done:

- bulk assignment
- reassignment workflow
- structured eligibility checklist
- technical review layer
- manager screening queue
- shortlist decision engine
- reopen submitted screening review flow

## Entry 010: Phase N2 Manager Screening Queue and Decision Layer

### Scope

This phase added the manager-facing layer on top of reviewer work.

The previous phase stopped at:

- reviewer assignment
- reviewer queue
- screening review draft/save/submit

That was not enough for real operations because someone still needed to:

- inspect submitted screening output
- reassign reviewer work when necessary
- make a final screening decision
- notify the presenter of the result

This phase closed that gap.

### Core design decision

I did **not** introduce a separate `review_decisions` table yet.

Why:

- your detailed tracker places full review-decision records later
- this batch needed a practical manager decision layer without expanding into the full shortlist engine

So the decision model in this phase is:

- screening evidence lives in reviewer assignments + screening reviews
- final screening outcome still lives on the submission status
- status history remains the audit trail

That keeps the scope controlled while still making the workflow real.

### Files and why they changed

#### [app/SubmissionStatus.php](/Users/yonassayfu/Herd/Negadras/app/SubmissionStatus.php)

Before:

```diff
- Draft
- Submitted
- UnderIntakeCheck
- IncompleteReturned
- Eligible
- Rejected
```

After:

```diff
+ Shortlisted
```

Why:

- the workflow now needed a clear positive screening outcome
- `Eligible` means intake passed
- `Shortlisted` means manager made a post-review decision

That distinction matters. They are not the same business state.

#### [app/Support/SubmissionStatusTransitionService.php](/Users/yonassayfu/Herd/Negadras/app/Support/SubmissionStatusTransitionService.php)

This file is the most important backend change in the phase.

Before:

```diff
- intake transitions only
- eligible had no next step
```

After:

```diff
+ availableIntakeTransitions()
+ availableScreeningDecisionTransitions()
+ eligible -> incomplete_returned
+ eligible -> shortlisted
+ eligible -> rejected
+ screening decision requires submitted screening review
+ active reviewer assignments auto-cancel on final decision
```

Why this change was necessary:

- intake staff and screening managers are not doing the same job
- the old transition helper would have mixed those two workflows together

The critical guard added here:

```diff
+ if (! $this->hasSubmittedScreeningReview($submission)) {
+     throw ValidationException::withMessages([
+         'status' => 'A submitted screening review is required before a manager can make a screening decision.',
+     ]);
+ }
```

Why:

- a manager should not shortlist or reject an entry without submitted reviewer evidence

Another important rule:

```diff
+ ReviewerAssignment::query()
+     ->whereIn('status', [Assigned, InProgress])
+     ->update(['status' => Cancelled]);
```

Why:

- once a final screening decision exists, open reviewer work should not continue as if the submission were still pending

#### [app/Support/ReviewerAssignmentService.php](/Users/yonassayfu/Herd/Negadras/app/Support/ReviewerAssignmentService.php)

This service was extended instead of bypassed.

New parts:

```diff
+ reassign(...)
+ notify Admin/Manager on submitted screening review
```

Why:

- reassignment should follow the same workflow service boundary as assignment creation
- review submission is an operational event, not only a reviewer-local action

The reassignment decision was implemented as:

```diff
+ cancel old active assignment
+ create new assignment through assign(...)
```

Why:

- it keeps the audit trail clear
- the new assignment gets its own identity, due date, notification, and activity log

That is cleaner than mutating the reviewer on the existing row.

#### [app/Http/Requests/Admin/ReassignReviewerAssignmentRequest.php](/Users/yonassayfu/Herd/Negadras/app/Http/Requests/Admin/ReassignReviewerAssignmentRequest.php)

Why this file exists:

- reassignment needed its own validation boundary
- this is not the same as first-time assignment

It now validates:

```diff
+ reviewer_id
+ due_at
+ reason
```

The `reason` is optional right now, but the field exists because reassignment is operationally sensitive and likely to become stricter later.

#### [app/Http/Requests/Admin/TransitionScreeningDecisionRequest.php](/Users/yonassayfu/Herd/Negadras/app/Http/Requests/Admin/TransitionScreeningDecisionRequest.php)

This request separates screening decisions from intake decisions.

Why:

- the allowed next states are different
- the validation rule source is different
- reason handling for decision-level actions should stay independent

This is the correct boundary because later shortlist and technical review flows will diverge further.

#### [app/Http/Controllers/Admin/ScreeningQueueController.php](/Users/yonassayfu/Herd/Negadras/app/Http/Controllers/Admin/ScreeningQueueController.php)

This is the main new controller of the phase.

It now handles:

- screening queue index
- screening detail page
- manager decision submission

Why a separate controller was the right move:

- the intake queue already has a different responsibility
- overloading `SubmissionManagementController` further would mix intake and screening concerns

The index page query now filters by:

```diff
+ reviewer
+ stage
+ industry
+ recommendation
+ queue_state
+ search
```

And it derives a queue state:

```diff
+ unassigned
+ review_in_progress
+ partially_reviewed
+ awaiting_decision
```

Why:

- manager action depends on workflow state, not only submission status
- a submission can still be `eligible` while being in very different screening situations

The `show()` action loads:

- reviewer assignments
- submitted screening review content
- submission narrative
- status timeline
- decision options

That gives managers one place to read the evidence and decide.

#### [app/Http/Controllers/Admin/ReviewerAssignmentManagementController.php](/Users/yonassayfu/Herd/Negadras/app/Http/Controllers/Admin/ReviewerAssignmentManagementController.php)

Before:

```diff
- store()
- destroy()
```

After:

```diff
+ update()
```

Why:

- reassigning had become a real workflow requirement
- the controller needed a first-class route instead of forcing staff to manually cancel then recreate

#### [database/seeders/RolePermissionSeeder.php](/Users/yonassayfu/Herd/Negadras/database/seeders/RolePermissionSeeder.php)

New permission:

```diff
+ screening-queue.view
```

Given to:

```diff
+ Admin
+ Manager
```

Why:

- reviewer queue and screening queue are different surfaces
- reviewers should not see the manager decision workspace
- secretary should remain in intake, not in screening decisions

That separation is important for Negadras role clarity.

#### [routes/web.php](/Users/yonassayfu/Herd/Negadras/routes/web.php)

New routes added:

```diff
+ reviewer-assignments.update
+ screening-queue.index
+ screening-queue.show
+ screening-queue.decision
```

Why:

- the manager queue needed its own entry point
- reassignment needed a direct update route
- decision submission needed a dedicated endpoint

#### [resources/js/pages/admin/Screening/Index.vue](/Users/yonassayfu/Herd/Negadras/resources/js/pages/admin/Screening/Index.vue)

This page is the manager screening cockpit.

It shows:

- screening queue state
- latest recommendation
- pending/submitted review counts
- reviewer/stage/industry/recommendation filters

Why:

- manager work starts with queue triage, not with opening individual submissions blindly

This page is intentionally different from intake:

- intake asks "is the submission complete?"
- screening asks "what reviewer evidence exists, and is this ready for a decision?"

#### [resources/js/pages/admin/Screening/Show.vue](/Users/yonassayfu/Herd/Negadras/resources/js/pages/admin/Screening/Show.vue)

This page is where the phase really lands.

It combines:

- reviewer assignments
- submitted review notes and recommendations
- reassignment forms
- final decision form

Why:

- the manager must see reviewer output and act from the same surface
- splitting those into multiple pages would slow down the workflow and weaken operational clarity

#### [resources/js/navigation/app.ts](/Users/yonassayfu/Herd/Negadras/resources/js/navigation/app.ts)

What changed:

```diff
+ Screening queue
```

Why:

- once the manager screening surface became real, it needed a distinct navigation entry
- this avoids treating screening work as a hidden extension of intake

#### [tests/Feature/ManagerScreeningQueueTest.php](/Users/yonassayfu/Herd/Negadras/tests/Feature/ManagerScreeningQueueTest.php)

This new test proves:

- manager can open the screening queue
- recommendation filtering works
- manager can open screening detail with submitted reviewer evidence

Why:

- queue pages are easy to regress because their payload is derived and filter-heavy

#### [tests/Feature/ScreeningDecisionWorkflowTest.php](/Users/yonassayfu/Herd/Negadras/tests/Feature/ScreeningDecisionWorkflowTest.php)

This new test proves:

- manager can reassign a reviewer
- manager can shortlist after submitted screening review
- presenter is notified on shortlist
- manager cannot decide without submitted review evidence

Why:

- these are the core business actions of the phase
- if they break, the whole screening layer becomes untrustworthy

### Laravel takeaways from this phase

1. Workflow states often need derived queue states in addition to stored domain status.
2. Reassignment is usually cleaner as "close old assignment, create new assignment" rather than mutating the original row.
3. Intake transitions and screening decisions should not share one catch-all request contract.
4. Manager-facing queues should be separated from reviewer-facing queues even when they touch the same submissions.
5. Notifications become more valuable once workflow state changes cross role boundaries.

### Practical Negadras result

At the end of this phase:

- managers have a dedicated screening queue
- queue filtering works by reviewer, stage, industry, recommendation, and queue state
- reviewer assignments can be reassigned from the screening workflow
- managers can inspect submitted screening results in one place
- managers can apply shortlist, reject, or revision decisions
- presenters are notified when a manager records the outcome
- manager decisions now sit on top of reviewer evidence instead of bypassing it

### Progress position after this phase

Using the current detailed tracker:

- Phase N2 is roughly **44% complete**
- the full tracked Negadras roadmap is roughly **32% complete**

What is still intentionally not done in this phase:

- technical review
- shortlist ranking records
- needs-more-review decision path
- overdue reviewer reminders
- reopen submitted screening reviews
- full review decision table/model
