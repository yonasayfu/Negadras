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
