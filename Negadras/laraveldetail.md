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

## Entry 011: Phase N2 Technical Review Foundation

### Scope

This batch added the second review track after screening:

- technical reviewer assignment separated from screening assignment
- technical review table and deeper evaluation form
- reviewer-facing technical queue
- manager-facing technical queue
- comparison of screening output vs technical output

The key design rule was simple: do not create a second reviewer identity system. Keep one reviewer profile, separate the workflow by assignment type and review table.

### Files and why they changed

#### [app/ReviewAssignmentType.php](/Users/yonassayfu/Herd/Negadras/app/ReviewAssignmentType.php)

Before:

```diff
- file did not exist
```

After:

```diff
+ case Screening = 'screening';
+ case Technical = 'technical';
```

Why:

- Negadras already had reviewer assignments, but they were implicitly screening-only.
- Once technical review starts, the assignment itself must declare which workflow it belongs to.
- This avoids building two parallel assignment systems.

#### [database/migrations/2026_03_19_083419_add_assignment_type_to_reviewer_assignments_table.php](/Users/yonassayfu/Herd/Negadras/database/migrations/2026_03_19_083419_add_assignment_type_to_reviewer_assignments_table.php)

Key addition:

```diff
+ $table->string('assignment_type', 32)->default('screening')->after('stage_id');
```

Why:

- old assignments continue to work because the default is `screening`
- new technical work can now live in the same operational table without mixing logic

#### [database/migrations/2026_03_19_083405_create_technical_reviews_table.php](/Users/yonassayfu/Herd/Negadras/database/migrations/2026_03_19_083405_create_technical_reviews_table.php)

Key additions:

```diff
+ $table->foreignId('reviewer_assignment_id')->constrained()->cascadeOnDelete();
+ $table->unsignedTinyInteger('innovation_score_optional')->nullable();
+ $table->unsignedTinyInteger('feasibility_score_optional')->nullable();
+ $table->unsignedTinyInteger('execution_score_optional')->nullable();
+ $table->unsignedTinyInteger('market_score_optional')->nullable();
+ $table->text('strengths')->nullable();
+ $table->text('weaknesses')->nullable();
+ $table->text('risk_note')->nullable();
+ $table->string('recommendation', 32);
+ $table->timestamp('submitted_at')->nullable();
```

Why:

- technical review is intentionally deeper than screening
- scores are optional to keep the first technical phase flexible
- the real locked boundary is still `submitted_at`
- `reviewer_assignment_id` is what preserves ownership and lock rules cleanly

#### [app/Models/ReviewerAssignment.php](/Users/yonassayfu/Herd/Negadras/app/Models/ReviewerAssignment.php)

Important changes:

```diff
+ 'assignment_type',
+ 'assignment_type' => ReviewAssignmentType::class,
+ public function technicalReview(): HasOne
+ public function isScreening(): bool
+ public function isTechnical(): bool
```

Why:

- all old queue code now needs a reliable way to say "screening only" or "technical only"
- using helper methods is safer than repeating string comparisons in controllers

#### [app/Support/ReviewerAssignmentService.php](/Users/yonassayfu/Herd/Negadras/app/Support/ReviewerAssignmentService.php)

This is the most important backend file in the phase.

Before:

```diff
- assign() only understood screening reviewer assignment
- no technical draft/save/submit methods existed
```

After:

```diff
+ assign(..., ReviewAssignmentType $assignmentType = ReviewAssignmentType::Screening)
+ allowedSubmissionStatuses() separates Eligible vs Shortlisted entry points
+ saveDraftTechnicalReview()
+ submitTechnicalReview()
```

Why:

- screening assignment should still require `eligible`
- technical assignment should only happen after `shortlisted`
- both flows reuse one service, but the business gate changes by assignment type

Most important logic snippet:

```php
return match ($assignmentType) {
    ReviewAssignmentType::Screening => [SubmissionStatus::Eligible],
    ReviewAssignmentType::Technical => [SubmissionStatus::Shortlisted],
};
```

That one branch is what prevents technical review from leaking into the earlier intake/screening stage.

#### [app/Http/Controllers/TechnicalReviewerQueueController.php](/Users/yonassayfu/Herd/Negadras/app/Http/Controllers/TechnicalReviewerQueueController.php)

Why it exists:

- reviewer queue and technical reviewer queue are not the same operational surface
- the technical reviewer needs deeper context, including submitted screening output

Important filter:

```diff
+ ->where('assignment_type', ReviewAssignmentType::Technical)
```

Without this, the reviewer would see mixed screening and technical work in one list.

#### [app/Http/Controllers/Admin/TechnicalQueueController.php](/Users/yonassayfu/Herd/Negadras/app/Http/Controllers/Admin/TechnicalQueueController.php)

Why it exists:

- managers needed a dedicated place to:
  - assign technical experts
  - see technical review state
  - compare multiple technical outputs
  - compare screening evidence against technical evidence

Important summary logic:

```php
$technicalAssignments = $submission->reviewerAssignments
    ->filter(fn (ReviewerAssignment $assignment): bool => $assignment->isTechnical());
```

This is the operational split of the whole phase. The manager queue stops pretending all reviewer work is one homogeneous thing.

#### [app/Http/Requests/StoreTechnicalReviewRequest.php](/Users/yonassayfu/Herd/Negadras/app/Http/Requests/StoreTechnicalReviewRequest.php)

Why:

- draft technical review should stay light
- final submit should require the real qualitative fields

Example:

```diff
+ 'strengths' => [$submitting ? 'required' : 'nullable', 'string', 'max:10000'],
+ 'weaknesses' => [$submitting ? 'required' : 'nullable', 'string', 'max:10000'],
+ 'recommendation' => [$submitting ? 'required' : 'nullable', Rule::enum(TechnicalReviewRecommendation::class)],
```

That is the Laravel rule boundary between "work in progress" and "submitted review".

#### [resources/js/pages/reviewers/TechnicalReview.vue](/Users/yonassayfu/Herd/Negadras/resources/js/pages/reviewers/TechnicalReview.vue)

Why:

- the technical reviewer needs a different form than screening
- screening had one optional score and one note block
- technical review now records multiple score dimensions, strengths, weaknesses, and risk

#### [resources/js/pages/admin/Technical/Show.vue](/Users/yonassayfu/Herd/Negadras/resources/js/pages/admin/Technical/Show.vue)

Why:

- this is where manager comparison actually becomes usable
- one screen now shows:
  - screening reviews
  - technical assignments
  - submitted technical outputs
  - technical averages
  - new expert assignment action

This file matters because it turns the backend separation into an actual decision workspace instead of just new tables.

#### [database/seeders/RolePermissionSeeder.php](/Users/yonassayfu/Herd/Negadras/database/seeders/RolePermissionSeeder.php)

Added permissions:

```diff
+ technical-reviewer-queue.view
+ technical-queue.view
+ technical-reviews.view
+ technical-reviews.create
+ technical-reviews.update
```

Why:

- reviewer role can now access technical review when assigned
- manager/admin can oversee the technical queue
- screening permissions remain separate

This preserves Negadras RBAC clarity instead of turning "reviewer" into one giant permission blob.

#### Migration ordering fix

During verification, a clean database install exposed a real defect:

```diff
- 2026_03_19_072945_create_reviewers_table.php
+ 2026_03_19_072944_create_reviewers_table.php
```

Why this matters:

- `reviewer_assignments` depends on `reviewers`
- same-timestamp reviewer migrations were safe only in an already-migrated dev database
- a real production bootstrap or a fresh CI database could fail

This fix is part of the phase because release-ready work must survive a fresh install, not just incremental local development.

### Tests added and why they matter

- [TechnicalReviewFlowTest.php](/Users/yonassayfu/Herd/Negadras/tests/Feature/TechnicalReviewFlowTest.php)
- [ManagerTechnicalQueueTest.php](/Users/yonassayfu/Herd/Negadras/tests/Feature/ManagerTechnicalQueueTest.php)
- [TechnicalReviewComparisonTest.php](/Users/yonassayfu/Herd/Negadras/tests/Feature/TechnicalReviewComparisonTest.php)

Why:

- reviewer technical draft/save/submit must lock correctly
- manager must be able to assign a technical reviewer
- manager technical queue filtering and comparison must return the right data

### Laravel takeaways from this phase

1. One actor type can participate in multiple workflows without duplicating the profile model.
2. Workflow separation should usually happen at the assignment layer first, not by cloning whole user tables.
3. A new review phase usually needs both:
   - a new review table
   - a typed assignment boundary
4. If a model gains a new enum field, old eager-load column lists must be updated or derived helper methods will silently break.
5. Fresh-install migration safety is part of feature completeness.

### Practical Negadras result

At the end of this phase:

- technical review is now a real workflow, not a future placeholder
- technical reviewer assignment is separated from screening assignment
- reviewers can work technical tasks from a dedicated technical queue
- managers can assign technical experts independently from screening reviewers
- managers can compare screening output with submitted technical output
- multiple technical reviews per submission are now supported through separate assignments

### Progress position after this phase

Using the current detailed tracker:

- Phase N2 is roughly **61% complete**
- the full tracked Negadras roadmap is roughly **37% complete**

What is still intentionally not done in this phase:

- needs-more-review manager decision path
- shortlist record table and ranking
- technical review reopen flow
- reviewer reminder scheduling
- live judging and panel scoring

## Entry 012: Phase N2 Operational Workflow Closeout

### Scope

This batch closed the rest of the practical Phase 2 workflow:

- bulk reviewer assignment
- structured screening checklist enforcement
- manager reopen flow for submitted reviews
- persisted review decisions
- shortlist records and approval
- shortlist export placeholder
- overdue review expiry and notification handling

The goal was to stop treating shortlist and decision logic as controller side-effects and make them first-class workflow records.

### Files and why they changed

#### [app/Models/ReviewDecision.php](/Users/yonassayfu/Herd/Negadras/app/Models/ReviewDecision.php)
#### [app/Models/ShortlistRecord.php](/Users/yonassayfu/Herd/Negadras/app/Models/ShortlistRecord.php)

Before:

```diff
- no persisted review decision table
- no persisted shortlist table
```

After:

```diff
+ ReviewDecision belongs to submission, stage, decider
+ ShortlistRecord belongs to submission, stage, creator, approver
+ both models cast their enums and timestamps explicitly
```

Why:

- Phase 2 decisions must be auditable
- shortlist membership must survive beyond one status change
- approval and export state belong to a shortlist record, not to `submissions`

#### [database/migrations/2026_03_19_103234_create_review_decisions_table.php](/Users/yonassayfu/Herd/Negadras/database/migrations/2026_03_19_103234_create_review_decisions_table.php)
#### [database/migrations/2026_03_19_103234_create_shortlist_records_table.php](/Users/yonassayfu/Herd/Negadras/database/migrations/2026_03_19_103234_create_shortlist_records_table.php)
#### [database/migrations/2026_03_19_103411_add_eligibility_checklist_to_screening_reviews_table.php](/Users/yonassayfu/Herd/Negadras/database/migrations/2026_03_19_103411_add_eligibility_checklist_to_screening_reviews_table.php)
#### [database/migrations/2026_03_19_103411_add_shortlist_approval_fields_to_shortlist_records_table.php](/Users/yonassayfu/Herd/Negadras/database/migrations/2026_03_19_103411_add_shortlist_approval_fields_to_shortlist_records_table.php)

Key additions:

```diff
+ review_decisions.decision_type
+ review_decisions.decision_reason
+ shortlist_records.rank_order_optional
+ shortlist_records.approval_status
+ shortlist_records.approved_by
+ shortlist_records.exported_at
+ screening_reviews.eligibility_checklist
```

Why:

- the checklist now has storage, not just frontend state
- shortlist approval is explicit instead of implied
- export placeholder work can now mark what has already been exported

#### [app/Support/ReviewDecisionService.php](/Users/yonassayfu/Herd/Negadras/app/Support/ReviewDecisionService.php)

This is the core decision boundary:

```diff
+ record()
+ fromSubmissionStatus()
+ toSubmissionStatus()
+ allowsStatusTransition()
```

Why:

- screening and technical decisions now share one decision writer
- status changes are now mapped from a decision instead of being handwritten in controllers
- shortlist record creation happens centrally when the decision is `Shortlisted`

#### [app/Support/ReviewerAssignmentService.php](/Users/yonassayfu/Herd/Negadras/app/Support/ReviewerAssignmentService.php)

Important changes:

```diff
+ saveDraftReview() now stores eligibility_checklist
+ bulkAssign()
+ reopenReview() now notifies the reviewer
```

Why:

- submit validation would be incomplete if draft persistence ignored checklist data
- bulk assignment belongs in the service because duplicate prevention still matters there
- reopen is a workflow event, not just a status flip

#### [app/Http/Requests/StoreScreeningReviewRequest.php](/Users/yonassayfu/Herd/Negadras/app/Http/Requests/StoreScreeningReviewRequest.php)

The important change is the post-validation rule:

```diff
+ withValidator()
+ if submit and any checklist item is false => add error
```

Why:

- required checklist logic is business validation, not UI convenience
- draft save stays permissive
- final submit becomes the actual control point

#### [app/Http/Controllers/Admin/ReviewerAssignmentManagementController.php](/Users/yonassayfu/Herd/Negadras/app/Http/Controllers/Admin/ReviewerAssignmentManagementController.php)

New actions:

```diff
+ storeBulk()
+ storeTechnicalBulk()
+ transition()
```

Why:

- managers needed a real bulk path for workload distribution
- reopen needed a formal route and request boundary
- this keeps reviewer-assignment mutations grouped in one controller instead of scattering them across queue pages

#### [app/Http/Controllers/Admin/ScreeningQueueController.php](/Users/yonassayfu/Herd/Negadras/app/Http/Controllers/Admin/ScreeningQueueController.php)

Key change:

```diff
+ screening decisions now also write ReviewDecision rows
+ screening detail now exposes decision history
```

Why:

- status history answers "what happened to the submission"
- review decisions answer "what managerial decision was actually made"
- those are related, but not the same record

#### [app/Http/Controllers/Admin/TechnicalQueueController.php](/Users/yonassayfu/Herd/Negadras/app/Http/Controllers/Admin/TechnicalQueueController.php)

This file became the real manager technical decision surface:

```diff
+ decide()
+ technical decision options
+ submitted-technical-review guard
+ review decision history on show page
```

Why:

- technical review needed its own manager closure step
- `needs_more_review` belongs here because it is a technical workflow decision, not a submission status
- managers should not decide technical outcomes without submitted technical evidence

#### [app/Http/Controllers/Admin/ShortlistController.php](/Users/yonassayfu/Herd/Negadras/app/Http/Controllers/Admin/ShortlistController.php)

This is the shortlist operations surface:

```diff
+ index()
+ store()
+ update()
+ export()
```

Why:

- shortlist records now have their own operational page
- approval and ranking happen there, not in queue detail forms only
- export placeholder is now a real path with CSV output and `exported_at` marking

#### Reviewer queue controllers

- [app/Http/Controllers/ReviewerQueueController.php](/Users/yonassayfu/Herd/Negadras/app/Http/Controllers/ReviewerQueueController.php)
- [app/Http/Controllers/TechnicalReviewerQueueController.php](/Users/yonassayfu/Herd/Negadras/app/Http/Controllers/TechnicalReviewerQueueController.php)

What changed:

```diff
+ expire overdue assignments before queue rendering
+ expose intakeNotes to reviewers
+ screening review detail now exposes eligibilityChecklist
```

Why:

- overdue state should behave as a workflow fact, not a UI color only
- reviewers and technical reviewers now see prior intake context when it exists

#### Frontend pages

- [resources/js/pages/admin/Screening/Show.vue](/Users/yonassayfu/Herd/Negadras/resources/js/pages/admin/Screening/Show.vue)
- [resources/js/pages/admin/Technical/Show.vue](/Users/yonassayfu/Herd/Negadras/resources/js/pages/admin/Technical/Show.vue)
- [resources/js/pages/admin/Shortlist/Index.vue](/Users/yonassayfu/Herd/Negadras/resources/js/pages/admin/Shortlist/Index.vue)
- [resources/js/pages/reviewers/Review.vue](/Users/yonassayfu/Herd/Negadras/resources/js/pages/reviewers/Review.vue)
- [resources/js/pages/reviewers/TechnicalReview.vue](/Users/yonassayfu/Herd/Negadras/resources/js/pages/reviewers/TechnicalReview.vue)
- [resources/js/pages/admin/Reviewers/Index.vue](/Users/yonassayfu/Herd/Negadras/resources/js/pages/admin/Reviewers/Index.vue)

Why these changes matter:

- screening and technical manager pages now support bulk assignment and reopen flows directly
- technical manager page now records decisions in the same place managers compare technical outputs
- shortlist page gives Phase 2 a real approval surface
- reviewer screening page now forces a visible checklist instead of a free-form recommendation only
- reviewer and technical reviewer pages show prior intake notes
- reviewer management now supports specialization filtering

#### [routes/web.php](/Users/yonassayfu/Herd/Negadras/routes/web.php)
#### [routes/console.php](/Users/yonassayfu/Herd/Negadras/routes/console.php)

Important route additions:

```diff
+ admin-submissions.reviewer-assignments.bulk-store
+ admin-submissions.technical-reviewer-assignments.bulk-store
+ reviewer-assignments.transition
+ technical-queue.decision
+ shortlist.index
+ shortlist.store
+ shortlist.update
+ shortlist.export
+ schedule negadras:notify-overdue-reviews hourly
```

Why:

- this phase needed explicit endpoints for the new operations
- overdue handling should not depend only on manual execution

### Tests added and updated

- [PhaseTwoCloseoutTest.php](/Users/yonassayfu/Herd/Negadras/tests/Feature/PhaseTwoCloseoutTest.php)
- [ScreeningReviewFlowTest.php](/Users/yonassayfu/Herd/Negadras/tests/Feature/ScreeningReviewFlowTest.php)

Covered behaviors:

- screening submit requires a complete checklist
- manager can bulk assign reviewers
- manager can reopen a submitted screening review
- manager can record technical `needs_more_review`
- shortlist creation, approval, and export work
- overdue command expires assignments and sends notifications

### Laravel takeaways from this phase

1. Status history and decision history solve different problems. Keep both if you need real operational auditability.
2. Bulk actions still belong in services if the single-item constraints must remain enforced.
3. "Locked after submit" is not enough in a real workflow. You also need a controlled reopen path.
4. If a reviewer queue depends on due dates, overdue state should become persisted workflow state, not just a calculated badge.
5. Export placeholders are more useful when they already mark what was exported and when.

### Practical Negadras result

At the end of this phase:

- reviewer and technical review workflows are now operationally complete for Phase 2
- manager decisions are persisted
- shortlist records are manageable and approvable
- overdue reviewer assignments now expire and notify
- reviewer queues now show the intake context reviewers actually need

### Progress position after this phase

- detailed Phase 2 screening and reviewer workflow backlog is now **complete**
- full tracked Negadras roadmap is now roughly **43% complete**

What is intentionally still outside this phase:

- judge and panel scoring engine
- live-session control
- score locking and final result publication
- public showcase and archive layer

## Entry 013: Phase N3 - Judge, Panel, and Rubric Foundation

### Scope

This batch implemented the first official judging layer for Negadras:

- judges
- panels and panel members
- rubrics and weighted criteria
- submission-to-panel assignment
- judge scoring workspace
- conflict declarations
- score locking and visibility history

The business shift in this phase is important. Until Phase 2, the system only answered:

- can this submission pass screening?
- can a manager shortlist it?

Phase 3 changes the question to:

- how do official judges evaluate shortlisted work consistently, privately, and auditable?

### Files and why they changed

#### Judge domain foundation

- [app/Models/Judge.php](/Users/yonassayfu/Herd/Negadras/app/Models/Judge.php)
- [database/migrations/2026_03_19_110520_create_judges_table.php](/Users/yonassayfu/Herd/Negadras/database/migrations/2026_03_19_110520_create_judges_table.php)
- [app/Policies/JudgePolicy.php](/Users/yonassayfu/Herd/Negadras/app/Policies/JudgePolicy.php)
- [app/Http/Controllers/Admin/JudgeManagementController.php](/Users/yonassayfu/Herd/Negadras/app/Http/Controllers/Admin/JudgeManagementController.php)

Core additions:

```diff
+ judges.user_id
+ judges.professional_title
+ judges.organization
+ judges.specialization
+ judges.bio
+ judges.is_active
```

Why:

- `Reviewer` and `Judge` are not the same role in Negadras.
- Reviewers screen and technically assess.
- Judges score shortlisted entries inside panels.
- A separate `judges` table preserves that operational boundary instead of overloading `reviewers`.

Important model relationship:

```php
public function user(): BelongsTo
{
    return $this->belongsTo(User::class);
}

public function panels(): BelongsToMany
{
    return $this->belongsToMany(Panel::class, 'panel_members')
        ->using(PanelMember::class)
        ->withPivot(['role_in_panel', 'display_order'])
        ->withTimestamps();
}
```

Why it matters:

- judges authenticate through `users`
- judging access is not global
- access is constrained by panel membership

#### Panel and panel-member foundation

- [app/Models/Panel.php](/Users/yonassayfu/Herd/Negadras/app/Models/Panel.php)
- [app/Models/PanelMember.php](/Users/yonassayfu/Herd/Negadras/app/Models/PanelMember.php)
- [app/PanelRole.php](/Users/yonassayfu/Herd/Negadras/app/PanelRole.php)
- [app/PanelStatus.php](/Users/yonassayfu/Herd/Negadras/app/PanelStatus.php)
- [database/migrations/2026_03_19_110520_create_panels_table.php](/Users/yonassayfu/Herd/Negadras/database/migrations/2026_03_19_110520_create_panels_table.php)
- [database/migrations/2026_03_19_110520_create_panel_members_table.php](/Users/yonassayfu/Herd/Negadras/database/migrations/2026_03_19_110520_create_panel_members_table.php)
- [app/Http/Controllers/Admin/PanelManagementController.php](/Users/yonassayfu/Herd/Negadras/app/Http/Controllers/Admin/PanelManagementController.php)

Core additions:

```diff
+ panels.season_id
+ panels.stage_id
+ panels.rubric_id
+ panels.status
+ panel_members.panel_id
+ panel_members.judge_id
+ panel_members.role_in_panel
+ panel_members.display_order
+ unique(panel_id, judge_id)
```

Why:

- a panel is the real scoring unit
- a judge does not score "the whole competition"
- a judge scores through panel membership for a season and stage
- `display_order` gives deterministic presentation order for future live views
- `role_in_panel` supports chair/member distinctions now instead of bolting that on later

One practical controller pattern:

```php
$panel->members()->sync(
    collect($validated['members'])
        ->mapWithKeys(fn (array $member): array => [
            $member['judge_id'] => [
                'role_in_panel' => $member['role_in_panel'],
                'display_order' => $member['display_order'],
            ],
        ])
        ->all(),
);
```

Why this is the right Laravel shape:

- panel membership is a pivot concern
- `sync()` expresses replacement cleanly
- it avoids manual delete/reinsert controller noise

#### Rubrics and weighted criteria

- [app/Models/Rubric.php](/Users/yonassayfu/Herd/Negadras/app/Models/Rubric.php)
- [app/Models/RubricCriterion.php](/Users/yonassayfu/Herd/Negadras/app/Models/RubricCriterion.php)
- [database/migrations/2026_03_19_110527_create_rubrics_table.php](/Users/yonassayfu/Herd/Negadras/database/migrations/2026_03_19_110527_create_rubrics_table.php)
- [database/migrations/2026_03_19_110527_create_rubric_criteria_table.php](/Users/yonassayfu/Herd/Negadras/database/migrations/2026_03_19_110527_create_rubric_criteria_table.php)
- [database/migrations/2026_03_19_110538_create_rubric_stage_bindings_table.php](/Users/yonassayfu/Herd/Negadras/database/migrations/2026_03_19_110538_create_rubric_stage_bindings_table.php)
- [database/migrations/2026_03_19_110538_create_rubric_industry_bindings_table.php](/Users/yonassayfu/Herd/Negadras/database/migrations/2026_03_19_110538_create_rubric_industry_bindings_table.php)
- [app/Http/Controllers/Admin/RubricManagementController.php](/Users/yonassayfu/Herd/Negadras/app/Http/Controllers/Admin/RubricManagementController.php)
- [resources/js/components/judging/RubricCriteriaEditor.vue](/Users/yonassayfu/Herd/Negadras/resources/js/components/judging/RubricCriteriaEditor.vue)

Core additions:

```diff
+ rubrics.total_weight
+ rubrics.is_active
+ rubric_criteria.max_score
+ rubric_criteria.weight
+ rubric_criteria.is_required
+ rubric_criteria.visibility_rule
+ rubric_criteria.help_text
```

Why:

- Negadras scoring should not be a free-form comment box
- a rubric defines the official evaluation language
- criteria weights make aggregate scoring predictable and auditable
- stage bindings and industry bindings let the same rubric architecture serve different rounds without hardcoding conditions in controllers

Weight validation pattern:

```php
if (round($totalWeight, 2) > 100) {
    throw ValidationException::withMessages([
        'criteria' => 'Rubric criteria weight cannot exceed 100.',
    ]);
}
```

Why:

- bad weight math breaks every downstream score
- validating it centrally is safer than trusting frontend totals

#### Panel submission assignment

- [app/Models/PanelSubmissionAssignment.php](/Users/yonassayfu/Herd/Negadras/app/Models/PanelSubmissionAssignment.php)
- [app/PanelSubmissionAssignmentStatus.php](/Users/yonassayfu/Herd/Negadras/app/PanelSubmissionAssignmentStatus.php)
- [database/migrations/2026_03_19_110527_create_panel_submission_assignments_table.php](/Users/yonassayfu/Herd/Negadras/database/migrations/2026_03_19_110527_create_panel_submission_assignments_table.php)
- [app/Http/Controllers/Admin/PanelScoringController.php](/Users/yonassayfu/Herd/Negadras/app/Http/Controllers/Admin/PanelScoringController.php)

Key controller guard:

```php
if (! in_array($submission->status, [SubmissionStatus::Eligible, SubmissionStatus::Shortlisted], true)) {
    throw ValidationException::withMessages([
        'submission_id' => 'Only eligible or shortlisted submissions can enter a judging panel.',
    ]);
}
```

Why:

- judging should not start from raw submitted records
- the assignment is the operational handoff from screening/technical review into formal scoring
- Phase 3 deliberately keeps this boundary explicit in code

#### Score engine

- [app/Support/ScoreEngine.php](/Users/yonassayfu/Herd/Negadras/app/Support/ScoreEngine.php)
- [app/Models/ScoreEntry.php](/Users/yonassayfu/Herd/Negadras/app/Models/ScoreEntry.php)
- [app/Models/JudgeComment.php](/Users/yonassayfu/Herd/Negadras/app/Models/JudgeComment.php)
- [app/JudgeCommentType.php](/Users/yonassayfu/Herd/Negadras/app/JudgeCommentType.php)
- [database/migrations/2026_03_19_110528_create_score_entries_table.php](/Users/yonassayfu/Herd/Negadras/database/migrations/2026_03_19_110528_create_score_entries_table.php)
- [database/migrations/2026_03_19_110538_create_judge_comments_table.php](/Users/yonassayfu/Herd/Negadras/database/migrations/2026_03_19_110538_create_judge_comments_table.php)

This file is the center of the phase.

Main responsibilities:

```diff
+ saveScores()
+ isLocked()
+ hasActiveConflict()
+ judgeProgress()
+ judgeTotal()
+ aggregateTotal()
+ allJudgesSubmitted()
```

Why one service:

- scoring rules are easy to fragment
- if lock checks, conflict checks, total calculation, and comment persistence live in different controllers, the judging workflow will drift
- a single service keeps the scoring rules coherent

Important save pattern:

```php
$entry = ScoreEntry::query()->updateOrCreate(
    [
        'panel_submission_assignment_id' => $assignment->id,
        'judge_id' => $judge->id,
        'rubric_criterion_id' => $criterion->id,
    ],
    [
        'submission_id' => $assignment->submission_id,
        'score_value' => $score['score_value'],
        'comment' => $score['comment'] ?? null,
        'submitted_at' => $submit ? now() : null,
    ],
);
```

Why:

- draft and submit use the same persistence path
- `updateOrCreate()` prevents duplicate criterion scores
- judge progress can be computed directly from stored entries

Important fix during this phase:

```diff
- checked allJudgesSubmitted() against stale loaded scoreEntries
+ refreshed scoreEntries before completion checks
```

Why:

- without refreshing the relation, the assignment stayed in `scoring` even after the final judge submitted
- this is a classic Eloquent state-staleness issue in write-heavy flows

#### Conflict declarations

- [app/Models/ConflictOfInterestDeclaration.php](/Users/yonassayfu/Herd/Negadras/app/Models/ConflictOfInterestDeclaration.php)
- [app/ConflictOfInterestType.php](/Users/yonassayfu/Herd/Negadras/app/ConflictOfInterestType.php)
- [app/ConflictOfInterestStatus.php](/Users/yonassayfu/Herd/Negadras/app/ConflictOfInterestStatus.php)
- [database/migrations/2026_03_19_110520_create_conflict_of_interest_declarations_table.php](/Users/yonassayfu/Herd/Negadras/database/migrations/2026_03_19_110520_create_conflict_of_interest_declarations_table.php)
- [app/Http/Requests/StoreConflictDeclarationRequest.php](/Users/yonassayfu/Herd/Negadras/app/Http/Requests/StoreConflictDeclarationRequest.php)
- [app/Http/Requests/Admin/UpdateConflictDeclarationRequest.php](/Users/yonassayfu/Herd/Negadras/app/Http/Controllers/Admin/PanelScoringController.php)

Why:

- conflict handling must be workflow state, not a free-text note
- once a conflict is active, `ScoreEngine::hasActiveConflict()` blocks scoring
- manager/admin review of the declaration is separated from judge declaration itself

Business effect:

- judges can safely declare before scoring
- admin can resolve or keep the conflict active
- the system can prove why a judge could not continue

#### Score locking and visibility history

- [app/Models/ScoreLock.php](/Users/yonassayfu/Herd/Negadras/app/Models/ScoreLock.php)
- [app/Models/ScoreVisibilityEvent.php](/Users/yonassayfu/Herd/Negadras/app/Models/ScoreVisibilityEvent.php)
- [app/ScoreVisibilityAction.php](/Users/yonassayfu/Herd/Negadras/app/ScoreVisibilityAction.php)
- [database/migrations/2026_03_19_110538_create_score_locks_table.php](/Users/yonassayfu/Herd/Negadras/database/migrations/2026_03_19_110538_create_score_locks_table.php)
- [database/migrations/2026_03_19_110538_create_score_visibility_events_table.php](/Users/yonassayfu/Herd/Negadras/database/migrations/2026_03_19_110538_create_score_visibility_events_table.php)

Why:

- Negadras needs two separate audit concepts:
  - is scoring editable?
  - who revealed or hid scoring visibility?
- those are related but not the same event stream

Lock update flow:

```diff
+ lock => create ScoreLock, set assignment status locked, mark score entries locked
+ unlock => store reopen reason, reopen actor, clear score-entry lock flags
```

Why:

- lock/reopen history should not disappear when the current lock state changes
- the record needs both the original lock and the reopen metadata

#### Judge workspace

- [app/Http/Controllers/JudgeWorkspaceController.php](/Users/yonassayfu/Herd/Negadras/app/Http/Controllers/JudgeWorkspaceController.php)
- [resources/js/pages/judges/Index.vue](/Users/yonassayfu/Herd/Negadras/resources/js/pages/judges/Index.vue)
- [resources/js/pages/judges/Show.vue](/Users/yonassayfu/Herd/Negadras/resources/js/pages/judges/Show.vue)

The judge side now has two real surfaces:

- assignment queue
- scoring detail

Important ownership rule:

```php
private function judgeOwnsAssignment(Judge $judge, PanelSubmissionAssignment $assignment): bool
{
    return $assignment->panel()
        ->whereHas('members', fn ($query) => $query->where('judge_id', $judge->id))
        ->exists();
}
```

Why:

- a judge must never score by route guessing
- panel membership is the real access control
- this rule is stronger than a sidebar permission check

Judge detail now exposes:

- rubric criteria
- existing criterion scores
- private comment
- presenter-visible comment
- aggregate score
- submission files
- active conflict declarations

#### Admin scoring operations surface

- [resources/js/pages/admin/Panels/Show.vue](/Users/yonassayfu/Herd/Negadras/resources/js/pages/admin/Panels/Show.vue)
- [resources/js/pages/admin/Panels/Scoring.vue](/Users/yonassayfu/Herd/Negadras/resources/js/pages/admin/Panels/Scoring.vue)
- [resources/js/components/judging/PanelMembersEditor.vue](/Users/yonassayfu/Herd/Negadras/resources/js/components/judging/PanelMembersEditor.vue)

Why:

- managers need one place to see:
  - judge progress
  - judge totals
  - conflicts
  - locks
  - visibility events
- this page is the operational judging console before Phase 4 live-session tooling exists

Frontend note:

- the Wayfinder imports here had to be corrected after generation
- route helpers for nested endpoints must come from the generated nested path, not the top-level route barrel

Example fix:

```diff
- import { visibility } from '@/routes/panel-scoring'
+ import { store as visibilityStore } from '@/routes/panel-scoring/visibility'
```

Why:

- this repo uses Wayfinder-generated route functions
- wrong import paths compile badly and then surface as build failures

### Tests added

- [tests/Feature/JudgeManagementTest.php](/Users/yonassayfu/Herd/Negadras/tests/Feature/JudgeManagementTest.php)
- [tests/Feature/PanelRubricManagementTest.php](/Users/yonassayfu/Herd/Negadras/tests/Feature/PanelRubricManagementTest.php)
- [tests/Feature/JudgeWorkspaceScoringTest.php](/Users/yonassayfu/Herd/Negadras/tests/Feature/JudgeWorkspaceScoringTest.php)
- [tests/Feature/ConflictOfInterestTest.php](/Users/yonassayfu/Herd/Negadras/tests/Feature/ConflictOfInterestTest.php)

What they prove:

- admin can manage judge profiles
- admin can build panels and bind rubrics
- judges can only score assigned submissions
- active conflict blocks scoring
- locked scoring cannot be edited
- presenter-visible comments persist
- aggregate scoring and progress logic behave as expected

### Laravel takeaways from this phase

1. When a workflow becomes rule-heavy, move it into a service before the controllers drift apart.
2. Membership-based access control is often the real rule, not just role-based access control.
3. Persist workflow history separately when the business meaning differs. Lock history and visibility history are not the same log.
4. In Inertia + Wayfinder apps, route-generation mistakes usually show up first in `types:check` or build, not in PHP tests.
5. After write-heavy service work, refresh loaded relations before computing derived workflow state.

### Practical Negadras result

At the end of this phase:

- judges now exist as real workflow actors
- panels can be created and staffed
- rubrics and weighted criteria are operational
- eligible/shortlisted submissions can enter a scoring panel
- judges can score privately with progress tracking
- conflicts, locks, reopen reasons, and visibility changes are auditable

What is intentionally still outside this phase:

- live session scheduling and operator controls
- real-time score broadcasting
- tablet-first live judging layout
- public reveal/projection workflow
- final ranking, awards, and archive publication

## Entry 014: Phase N4 - Live Session, Real-Time Dashboard, and Broadcast-Ready Operations

### Scope

This phase implemented the presentation-day operations layer for Negadras:

- competition sessions
- presenter queue management
- session event logging
- polling-first live status snapshots
- judge live tablet scoring view
- moderator control surface
- public/studio dashboard
- projection request and approval placeholder

The key engineering decision in this phase was deliberate:

- do not force websocket infrastructure into the project before it is needed and stable
- build a broadcast-ready domain model now
- ship the MVP transport as polling against persisted live snapshots

That gives Negadras a working live-day operations surface without adding deployment complexity too early.

### Files and why they changed

#### Live-session domain foundation

- [app/Models/CompetitionSession.php](/Users/yonassayfu/Herd/Negadras/app/Models/CompetitionSession.php)
- [app/Models/SessionPresenter.php](/Users/yonassayfu/Herd/Negadras/app/Models/SessionPresenter.php)
- [app/Models/SessionEvent.php](/Users/yonassayfu/Herd/Negadras/app/Models/SessionEvent.php)
- [app/Models/DashboardProjectionSession.php](/Users/yonassayfu/Herd/Negadras/app/Models/DashboardProjectionSession.php)
- [app/Models/SessionMedium.php](/Users/yonassayfu/Herd/Negadras/app/Models/SessionMedium.php)
- [app/Models/LiveStatusSnapshot.php](/Users/yonassayfu/Herd/Negadras/app/Models/LiveStatusSnapshot.php)
- [app/CompetitionSessionStatus.php](/Users/yonassayfu/Herd/Negadras/app/CompetitionSessionStatus.php)
- [app/CompetitionSessionType.php](/Users/yonassayfu/Herd/Negadras/app/CompetitionSessionType.php)
- [app/SessionAppearanceStatus.php](/Users/yonassayfu/Herd/Negadras/app/SessionAppearanceStatus.php)
- [app/SessionEventType.php](/Users/yonassayfu/Herd/Negadras/app/SessionEventType.php)
- [app/DashboardProjectionStatus.php](/Users/yonassayfu/Herd/Negadras/app/DashboardProjectionStatus.php)

Important naming decision:

```diff
- sessions
+ competition_sessions
```

Why:

- Laravel already owns a `sessions` table for authentication/session persistence
- using `competition_sessions` avoids a confusing collision
- the domain remains explicit in queries, policies, and logs

Core relationship shape:

```php
public function presenters(): HasMany
{
    return $this->hasMany(SessionPresenter::class)->orderBy('order_index');
}

public function snapshot(): HasOne
{
    return $this->hasOne(LiveStatusSnapshot::class);
}
```

Why:

- live moderation depends on deterministic queue order
- the current state of a session must be queryable fast
- a single snapshot row is cheaper to poll than rebuilding the full live status from scratch on every browser refresh

#### Migrations and state persistence

- [database/migrations/2026_03_19_122556_create_competition_sessions_table.php](/Users/yonassayfu/Herd/Negadras/database/migrations/2026_03_19_122556_create_competition_sessions_table.php)
- [database/migrations/2026_03_19_122556_create_session_presenters_table.php](/Users/yonassayfu/Herd/Negadras/database/migrations/2026_03_19_122556_create_session_presenters_table.php)
- [database/migrations/2026_03_19_122557_create_session_events_table.php](/Users/yonassayfu/Herd/Negadras/database/migrations/2026_03_19_122557_create_session_events_table.php)
- [database/migrations/2026_03_19_122557_create_dashboard_projection_sessions_table.php](/Users/yonassayfu/Herd/Negadras/database/migrations/2026_03_19_122557_create_dashboard_projection_sessions_table.php)
- [database/migrations/2026_03_19_122557_create_session_media_table.php](/Users/yonassayfu/Herd/Negadras/database/migrations/2026_03_19_122557_create_session_media_table.php)
- [database/migrations/2026_03_19_122557_create_live_status_snapshots_table.php](/Users/yonassayfu/Herd/Negadras/database/migrations/2026_03_19_122557_create_live_status_snapshots_table.php)

What these migrations establish:

```diff
+ competition session schedule and status
+ presenter queue ordering
+ auditable event stream
+ projection approval history
+ session-linked media
+ cached live snapshot payload
```

Why this split matters:

- `session_events` is the audit stream
- `live_status_snapshots` is the fast read model
- those two responsibilities should not be collapsed into one table

#### Live orchestration service

- [app/Support/LiveSessionCoordinator.php](/Users/yonassayfu/Herd/Negadras/app/Support/LiveSessionCoordinator.php)
- [app/Support/LiveStatusSnapshotBuilder.php](/Users/yonassayfu/Herd/Negadras/app/Support/LiveStatusSnapshotBuilder.php)

This is the center of the phase.

Main responsibilities:

```diff
+ startSession()
+ pauseSession()
+ resumeSession()
+ completeSession()
+ activatePresenter()
+ advancePresenter()
+ reorderQueue()
+ setScoresVisibility()
+ updateProjection()
+ refreshSnapshot()
```

Why one coordinator service:

- moderator actions are tightly related
- if queue movement, reveal logic, projection changes, and snapshot refreshes live in separate controllers, the session state will drift
- the service keeps the business transitions coherent and auditable

Example transition pattern:

```php
$session->forceFill([
    'status' => CompetitionSessionStatus::Live,
    'started_at' => $session->started_at ?? now(),
])->save();

$this->recordEvent($session, SessionEventType::SessionStarted, $actor);
$this->refreshSnapshot($session, $actor);
```

Why:

- each operator action updates the canonical session state
- each action is logged
- each action refreshes the read model used by live screens

#### Session and moderator controllers

- [app/Http/Controllers/Admin/CompetitionSessionManagementController.php](/Users/yonassayfu/Herd/Negadras/app/Http/Controllers/Admin/CompetitionSessionManagementController.php)
- [app/Http/Controllers/Admin/LiveSessionController.php](/Users/yonassayfu/Herd/Negadras/app/Http/Controllers/Admin/LiveSessionController.php)
- [app/Http/Requests/Admin/StoreCompetitionSessionRequest.php](/Users/yonassayfu/Herd/Negadras/app/Http/Requests/Admin/StoreCompetitionSessionRequest.php)
- [app/Http/Requests/Admin/UpdateCompetitionSessionRequest.php](/Users/yonassayfu/Herd/Negadras/app/Http/Requests/Admin/UpdateCompetitionSessionRequest.php)
- [app/Http/Requests/Admin/StoreSessionPresenterRequest.php](/Users/yonassayfu/Herd/Negadras/app/Http/Requests/Admin/StoreSessionPresenterRequest.php)
- [app/Http/Requests/Admin/ReorderSessionPresentersRequest.php](/Users/yonassayfu/Herd/Negadras/app/Http/Requests/Admin/ReorderSessionPresentersRequest.php)
- [app/Http/Requests/Admin/TransitionCompetitionSessionRequest.php](/Users/yonassayfu/Herd/Negadras/app/Http/Requests/Admin/TransitionCompetitionSessionRequest.php)
- [app/Http/Requests/Admin/UpdateProjectionSessionRequest.php](/Users/yonassayfu/Herd/Negadras/app/Http/Requests/Admin/UpdateProjectionSessionRequest.php)

Important cleanup made during the phase:

```diff
- chained nullsafe relation into submissionAssignments()->with(...)
+ guarded missing panel explicitly before querying available submissions
```

Why:

- the earlier chain could still reach a method call on `null`
- this is easy to miss in review because the `?->` visually suggests the whole chain is safe
- explicit guards are clearer in business-critical controller code

#### Judge live view

- [app/Http/Controllers/JudgeLiveSessionController.php](/Users/yonassayfu/Herd/Negadras/app/Http/Controllers/JudgeLiveSessionController.php)
- [resources/js/pages/judges/LiveIndex.vue](/Users/yonassayfu/Herd/Negadras/resources/js/pages/judges/LiveIndex.vue)
- [resources/js/pages/judges/LiveShow.vue](/Users/yonassayfu/Herd/Negadras/resources/js/pages/judges/LiveShow.vue)

What changed conceptually:

- the normal judge workspace is assignment-centric
- the live judge view is session-centric
- the screen needs the current presenter, next presenter, score progress, and submission files in one faster layout

Important controller correction:

```diff
- $competitionSession->panel?->rubric?->criteria->map(...)
+ $rubricCriteria = $competitionSession->panel?->rubric?->criteria ?? collect()
+ $rubricCriteria->map(...)
```

Why:

- nullsafe access stops only the immediate property/method access
- the later `->map()` would still fail if the criteria collection was `null`
- guarding it once makes the render payload safe

#### Moderator and studio frontend

- [resources/js/pages/admin/CompetitionSessions/Index.vue](/Users/yonassayfu/Herd/Negadras/resources/js/pages/admin/CompetitionSessions/Index.vue)
- [resources/js/pages/admin/CompetitionSessions/Create.vue](/Users/yonassayfu/Herd/Negadras/resources/js/pages/admin/CompetitionSessions/Create.vue)
- [resources/js/pages/admin/CompetitionSessions/Edit.vue](/Users/yonassayfu/Herd/Negadras/resources/js/pages/admin/CompetitionSessions/Edit.vue)
- [resources/js/pages/admin/CompetitionSessions/Show.vue](/Users/yonassayfu/Herd/Negadras/resources/js/pages/admin/CompetitionSessions/Show.vue)
- [resources/js/pages/admin/LiveSessions/Show.vue](/Users/yonassayfu/Herd/Negadras/resources/js/pages/admin/LiveSessions/Show.vue)
- [resources/js/pages/live-dashboard/Show.vue](/Users/yonassayfu/Herd/Negadras/resources/js/pages/live-dashboard/Show.vue)

What the moderator page now does:

- start, pause, resume, and complete a session
- activate or advance presenters
- reorder the presenter queue
- reveal or hide scores
- request, approve, or end projection
- watch live judge completion and current aggregate state

Important UX fix made during closeout:

```diff
- queue save posted the original props order back to the server
+ queue is now locally reorderable with up/down controls
+ queue state resyncs when polling reloads fresh session props
```

Why:

- a save button without real local reordering is misleading
- polling can overwrite local state if the page never resyncs the reactive queue copy
- the page now behaves honestly for the MVP

#### Route generation and TypeScript route proxies

- [routes/web.php](/Users/yonassayfu/Herd/Negadras/routes/web.php)
- [resources/js/routes/live-sessions.ts](/Users/yonassayfu/Herd/Negadras/resources/js/routes/live-sessions.ts)
- [resources/js/routes/live-dashboard.ts](/Users/yonassayfu/Herd/Negadras/resources/js/routes/live-dashboard.ts)
- [resources/js/routes/panel-scoring.ts](/Users/yonassayfu/Herd/Negadras/resources/js/routes/panel-scoring.ts)

What was needed:

```diff
+ flat re-export proxies for generated route folders
```

Why:

- this repo already uses a mixed pattern where some pages import route helpers from flat module names
- generated Wayfinder folders exist under `resources/js/routes/<name>/index.ts`
- a few pages and existing imports expected flat module paths like `@/routes/live-sessions`
- the proxies keep the generated structure intact while making TypeScript resolution stable

#### RBAC and policy layer

- [app/Policies/CompetitionSessionPolicy.php](/Users/yonassayfu/Herd/Negadras/app/Policies/CompetitionSessionPolicy.php)
- [app/Policies/SessionPresenterPolicy.php](/Users/yonassayfu/Herd/Negadras/app/Policies/SessionPresenterPolicy.php)
- [app/Policies/DashboardProjectionSessionPolicy.php](/Users/yonassayfu/Herd/Negadras/app/Policies/DashboardProjectionSessionPolicy.php)
- [app/Providers/AppServiceProvider.php](/Users/yonassayfu/Herd/Negadras/app/Providers/AppServiceProvider.php)
- [database/seeders/RolePermissionSeeder.php](/Users/yonassayfu/Herd/Negadras/database/seeders/RolePermissionSeeder.php)
- [resources/js/navigation/app.ts](/Users/yonassayfu/Herd/Negadras/resources/js/navigation/app.ts)

New permission surface:

```diff
+ competition-sessions.view
+ competition-sessions.create
+ competition-sessions.update
+ live-operations.view
+ live-operations.update
+ live-dashboard.view
+ judge-live.view
+ Production Team role
```

Why:

- live-day operations are not regular admin CRUD
- moderation, projection, judge-live, and public dashboard access need explicit boundaries
- the `Production Team` role lets operational staff participate without broader admin authority

### Tests added

- [tests/Feature/CompetitionSessionManagementTest.php](/Users/yonassayfu/Herd/Negadras/tests/Feature/CompetitionSessionManagementTest.php)
- [tests/Feature/LiveSessionModeratorFlowTest.php](/Users/yonassayfu/Herd/Negadras/tests/Feature/LiveSessionModeratorFlowTest.php)
- [tests/Feature/JudgeLiveSessionAccessTest.php](/Users/yonassayfu/Herd/Negadras/tests/Feature/JudgeLiveSessionAccessTest.php)
- [tests/Feature/LiveDashboardVisibilityTest.php](/Users/yonassayfu/Herd/Negadras/tests/Feature/LiveDashboardVisibilityTest.php)

What they prove:

- manager can create and inspect competition sessions
- moderator can start a session, advance presenters, reveal scores, and reorder the queue
- judge live access is constrained to panel membership
- public live dashboard respects reveal privacy before scores are exposed

### Laravel takeaways from this phase

1. For live workflows, split the audit stream from the fast read model. `session_events` and `live_status_snapshots` serve different jobs.
2. Do not overcommit to websocket infrastructure before the domain model is stable. A polling-first transport can still be architecturally clean.
3. Nullsafe chains are not a substitute for business guards when later collection methods are involved.
4. In Inertia + Wayfinder codebases, route-shape errors usually appear in `vue-tsc` before they show up in the browser.
5. Live operator screens need honest interactions. If the queue can be saved, it must be truly reorderable.

### Practical Negadras result

At the end of this phase:

- Negadras can schedule live competition sessions
- moderators can operate the session flow safely
- judges have a session-aware live scoring surface
- the studio can open a live dashboard without private details leaking early
- projection actions are structured and auditable
- the system is ready for future broadcasting without requiring it today

What is intentionally still outside this phase:

- true websocket broadcasting with Reverb/Echo
- live countdown timer orchestration
- public comments/highlights reveal rules
- post-session ranking, awards, and archive publication

---

## Entry 015: Phase 5 - Feedback Packets, Ranking, Awards, and Archive

### What this phase needed to solve

After live judging and final scoring, Negadras still needed a post-decision layer:

- convert panel scoring into auditable ranking snapshots
- allow controlled award recording
- transform selected review content into presenter-safe feedback packets
- freeze outcomes into archive records
- expose approved winners and finalists publicly without leaking internal review state

This phase implemented that full post-judging flow.

### Main backend files

#### Ranking and awards domain

- [app/Models/RankingSnapshot.php](/Users/yonassayfu/Herd/Negadras/app/Models/RankingSnapshot.php)
- [app/Models/AwardRecord.php](/Users/yonassayfu/Herd/Negadras/app/Models/AwardRecord.php)
- [app/Support/RankingSnapshotBuilder.php](/Users/yonassayfu/Herd/Negadras/app/Support/RankingSnapshotBuilder.php)
- [app/Http/Controllers/Admin/RankingManagementController.php](/Users/yonassayfu/Herd/Negadras/app/Http/Controllers/Admin/RankingManagementController.php)
- [app/Http/Controllers/Admin/AwardManagementController.php](/Users/yonassayfu/Herd/Negadras/app/Http/Controllers/Admin/AwardManagementController.php)

Core design:

```php
return RankingSnapshot::query()->updateOrCreate(
    [
        'stage_id' => $stage->id,
        'competition_session_id' => $competitionSession?->id,
        'submission_id' => $row['submission']->id,
    ],
    [
        'season_id' => $row['submission']->season_id,
        'aggregate_score' => $row['aggregateScore'],
        'rank_position' => $index + 1,
        'tie_break_reason_optional' => $this->tieBreakReason($index, $row, $scoredRows),
    ],
);
```

Why this structure:

- rankings are snapshots, not live calculated every page load
- each snapshot is tied to a stage and optional live session
- the system can preserve tie-break reasoning and later manual override notes

The important business decision here is that ranking is generated from locked scoring outcomes, then stored. That makes awards, archive, and public publication stable even if staff reopen other parts of the workflow later.

#### Feedback packet layer

- [app/Models/PresenterFeedbackPacket.php](/Users/yonassayfu/Herd/Negadras/app/Models/PresenterFeedbackPacket.php)
- [app/Support/FeedbackPacketBuilder.php](/Users/yonassayfu/Herd/Negadras/app/Support/FeedbackPacketBuilder.php)
- [app/Http/Controllers/Admin/FeedbackPacketManagementController.php](/Users/yonassayfu/Herd/Negadras/app/Http/Controllers/Admin/FeedbackPacketManagementController.php)
- [app/Http/Controllers/PresenterFeedbackController.php](/Users/yonassayfu/Herd/Negadras/app/Http/Controllers/PresenterFeedbackController.php)

Important filtering rule:

```php
$presenterVisibleComments = $submission->judgeComments
    ->where('comment_type', JudgeCommentType::PresenterVisible)
    ->where('is_archived', false)
    ->pluck('content');
```

Why this matters:

- judges and reviewers can write internal or private notes
- only explicitly presenter-visible comments may cross into the presenter packet
- this prevents accidental leakage of internal deliberation

The packet builder also pulls the latest score summary and decision context, but only into a controlled, reviewable packet. Staff can still edit the packet before sending it.

#### Archive and public showcase

- [app/Models/ArchiveRecord.php](/Users/yonassayfu/Herd/Negadras/app/Models/ArchiveRecord.php)
- [app/Models/SessionHighlight.php](/Users/yonassayfu/Herd/Negadras/app/Models/SessionHighlight.php)
- [app/Models/PublicShowcaseEntry.php](/Users/yonassayfu/Herd/Negadras/app/Models/PublicShowcaseEntry.php)
- [app/Support/ArchivePublisher.php](/Users/yonassayfu/Herd/Negadras/app/Support/ArchivePublisher.php)
- [app/Http/Controllers/Admin/ArchiveManagementController.php](/Users/yonassayfu/Herd/Negadras/app/Http/Controllers/Admin/ArchiveManagementController.php)
- [app/Http/Controllers/PublicShowcaseController.php](/Users/yonassayfu/Herd/Negadras/app/Http/Controllers/PublicShowcaseController.php)

Important correction made during implementation:

```diff
- archive_status was validated but ignored during create
+ archive_status is now passed into ArchivePublisher and persisted directly
```

Why that correction mattered:

- archive workflow would have looked configurable in the UI
- but the stored state would still be derived indirectly
- that mismatch is dangerous in operations code because staff think they are controlling publication while the system is silently overriding them

The archive layer now does two separate jobs:

1. preserve the official record
2. optionally expose a curated public showcase entry

That split is important. Not every archived record should become public, and public storytelling data should not force changes to the internal record.

### Main frontend files

- [resources/js/pages/admin/Rankings/Index.vue](/Users/yonassayfu/Herd/Negadras/resources/js/pages/admin/Rankings/Index.vue)
- [resources/js/pages/admin/Awards/Index.vue](/Users/yonassayfu/Herd/Negadras/resources/js/pages/admin/Awards/Index.vue)
- [resources/js/pages/admin/FeedbackPackets/Index.vue](/Users/yonassayfu/Herd/Negadras/resources/js/pages/admin/FeedbackPackets/Index.vue)
- [resources/js/pages/admin/Archive/Index.vue](/Users/yonassayfu/Herd/Negadras/resources/js/pages/admin/Archive/Index.vue)
- [resources/js/pages/feedback/Index.vue](/Users/yonassayfu/Herd/Negadras/resources/js/pages/feedback/Index.vue)
- [resources/js/pages/feedback/Show.vue](/Users/yonassayfu/Herd/Negadras/resources/js/pages/feedback/Show.vue)
- [resources/js/pages/public/Showcase/Index.vue](/Users/yonassayfu/Herd/Negadras/resources/js/pages/public/Showcase/Index.vue)
- [resources/js/pages/public/Showcase/Show.vue](/Users/yonassayfu/Herd/Negadras/resources/js/pages/public/Showcase/Show.vue)

How the UI is intentionally split:

- admin pages manage official operations
- presenter pages only read released packets
- public pages only read approved showcase data

That prevents the usual mistake of reusing admin payloads on public screens.

### Route and permission layer

- [routes/web.php](/Users/yonassayfu/Herd/Negadras/routes/web.php)
- [database/seeders/RolePermissionSeeder.php](/Users/yonassayfu/Herd/Negadras/database/seeders/RolePermissionSeeder.php)
- [app/Providers/AppServiceProvider.php](/Users/yonassayfu/Herd/Negadras/app/Providers/AppServiceProvider.php)
- [resources/js/navigation/app.ts](/Users/yonassayfu/Herd/Negadras/resources/js/navigation/app.ts)

New permission surface:

```php
'rankings.view',
'rankings.update',
'awards.view',
'awards.update',
'feedback-packets.view',
'feedback-packets.update',
'archive.view',
'archive.update',
```

Why this matters:

- post-judging operations are not the same as screening or live moderation
- archive publication and presenter feedback are business-sensitive actions
- they need their own access boundary instead of piggybacking on a generic submission permission

### Tests added

- [tests/Feature/RankingAndAwardsFlowTest.php](/Users/yonassayfu/Herd/Negadras/tests/Feature/RankingAndAwardsFlowTest.php)
- [tests/Feature/PresenterFeedbackPacketTest.php](/Users/yonassayfu/Herd/Negadras/tests/Feature/PresenterFeedbackPacketTest.php)
- [tests/Feature/ArchiveShowcaseFlowTest.php](/Users/yonassayfu/Herd/Negadras/tests/Feature/ArchiveShowcaseFlowTest.php)

What they prove:

- managers can generate rankings and record awards
- presenter packets only become readable after the release action
- public showcase pages only read published archive/showcase records

### Important practical lesson from this phase

The most important architectural lesson is this:

- judging output should not jump directly to the public site

There should always be a controlled post-judging layer:

1. ranking
2. award
3. feedback packet
4. archive record
5. public showcase

That extra layer is what makes the system safe for a real competition program instead of only technically functional.

---

## Entry 016: Phase 6 - Reporting, Exports, Notifications, and Governance

### What this phase needed to solve

By the end of Phase 5, Negadras could make decisions, rank submissions, release feedback, and publish the archive. What it still lacked was operational control:

- no Negadras-specific reports page
- no export job trail
- no notification delivery log
- no single governance surface
- no explicit override-event record for sensitive actions

Phase 6 closed that gap.

### Main backend files

#### Reporting and export layer

- [app/Http/Controllers/ReportsController.php](/Users/yonassayfu/Herd/Negadras/app/Http/Controllers/ReportsController.php)
- [app/Http/Controllers/ExportCenterController.php](/Users/yonassayfu/Herd/Negadras/app/Http/Controllers/ExportCenterController.php)
- [app/Models/ExportJob.php](/Users/yonassayfu/Herd/Negadras/app/Models/ExportJob.php)
- [app/ExportJobStatus.php](/Users/yonassayfu/Herd/Negadras/app/ExportJobStatus.php)

Important shift:

```php
ExportJob::query()->create([
    'type' => $type,
    'requested_by' => $request->user()->id,
    'status' => ExportJobStatus::Completed,
    'row_count' => $rowCount,
    'completed_at' => now(),
]);
```

Why this matters:

- exports are now observable operations, not hidden downloads
- governance can answer who exported what and when
- later queued/async exports can grow from the same table without redesign

The reports page was also rewritten away from the old starter-business `pages` report. It now summarizes actual Negadras workflow state:

- submission funnel
- review throughput
- season and stage distribution
- decision distribution
- ranking/award/feedback completion

#### Notification logging and reminders

- [app/Support/NotificationDispatcher.php](/Users/yonassayfu/Herd/Negadras/app/Support/NotificationDispatcher.php)
- [app/Models/NotificationLog.php](/Users/yonassayfu/Herd/Negadras/app/Models/NotificationLog.php)
- [app/Support/ReviewerAssignmentOverdueService.php](/Users/yonassayfu/Herd/Negadras/app/Support/ReviewerAssignmentOverdueService.php)
- [app/Console/Commands/NegadrasSendWorkflowReminders.php](/Users/yonassayfu/Herd/Negadras/app/Console/Commands/NegadrasSendWorkflowReminders.php)
- [routes/console.php](/Users/yonassayfu/Herd/Negadras/routes/console.php)

The core change was moving from direct `notify()` calls to a wrapper that also persists delivery metadata:

```php
$recipient->notificationLogs()->create([
    'category' => $category,
    'title' => $title,
    'message' => $message,
    'context_type' => $context?->getMorphClass(),
    'context_id' => $context?->getKey(),
    'sent_at' => now(),
]);
```

Why this design is better:

- database notifications remain the user-facing inbox
- `notification_logs` becomes the governance trail
- the workflow code no longer needs to duplicate notification-record creation

This phase also added the new scheduled reminder command:

```php
Schedule::command('negadras:send-workflow-reminders')->dailyAt('08:00');
```

That keeps reminder behavior explicit and testable instead of scattering reminder logic across controllers.

#### Governance and override trail

- [app/Http/Controllers/GovernanceController.php](/Users/yonassayfu/Herd/Negadras/app/Http/Controllers/GovernanceController.php)
- [app/Models/OverrideEvent.php](/Users/yonassayfu/Herd/Negadras/app/Models/OverrideEvent.php)
- [app/OverrideEventType.php](/Users/yonassayfu/Herd/Negadras/app/OverrideEventType.php)
- [app/Support/GovernanceRecorder.php](/Users/yonassayfu/Herd/Negadras/app/Support/GovernanceRecorder.php)

The override recorder was introduced so sensitive workflow actions create their own focused audit rows:

```php
$this->governance->record(
    eventType: OverrideEventType::RankingOverride,
    actor: $request->user(),
    reason: $rankingSnapshot->override_reason_optional,
    submission: $rankingSnapshot->submission,
    beforeState: $beforeState,
    afterState: [
        'rank_position' => $rankingSnapshot->rank_position,
        'override_reason_optional' => $rankingSnapshot->override_reason_optional,
    ],
);
```

Why not rely only on `activity_logs`?

- `activity_logs` are broad and human-readable
- `override_events` are narrow, structured, and governance-specific
- later board or compliance reporting can query override history directly without parsing generic audit text

The first set of override hooks now covers:

- submission status transitions
- reviewer reassignments
- review reopen actions
- ranking overrides
- score-lock changes
- score-visibility changes
- conflict decisions

### Existing workflow files enhanced in this phase

- [app/Support/SubmissionStatusTransitionService.php](/Users/yonassayfu/Herd/Negadras/app/Support/SubmissionStatusTransitionService.php)
- [app/Support/ReviewerAssignmentService.php](/Users/yonassayfu/Herd/Negadras/app/Support/ReviewerAssignmentService.php)
- [app/Http/Controllers/Admin/RankingManagementController.php](/Users/yonassayfu/Herd/Negadras/app/Http/Controllers/Admin/RankingManagementController.php)
- [app/Http/Controllers/Admin/PanelScoringController.php](/Users/yonassayfu/Herd/Negadras/app/Http/Controllers/Admin/PanelScoringController.php)
- [app/Http/Controllers/Admin/FeedbackPacketManagementController.php](/Users/yonassayfu/Herd/Negadras/app/Http/Controllers/Admin/FeedbackPacketManagementController.php)

The real architectural improvement here is not the new page. It is the extraction of shared operational concerns:

- notification dispatch
- reminder scheduling
- governance event recording

That keeps the domain controllers from turning into long chains of side effects.

### Main frontend files

- [resources/js/pages/reports/Index.vue](/Users/yonassayfu/Herd/Negadras/resources/js/pages/reports/Index.vue)
- [resources/js/pages/exports/Index.vue](/Users/yonassayfu/Herd/Negadras/resources/js/pages/exports/Index.vue)
- [resources/js/pages/governance/Index.vue](/Users/yonassayfu/Herd/Negadras/resources/js/pages/governance/Index.vue)
- [resources/js/navigation/app.ts](/Users/yonassayfu/Herd/Negadras/resources/js/navigation/app.ts)

Frontend result:

- reports are now Negadras-specific
- exports show recent export jobs, not just buttons
- governance brings override events, notification delivery, export jobs, and recent audit activity into one surface

This is important for a business app because operations teams need visibility, not only feature CRUD.

### Route and permission changes

- [routes/web.php](/Users/yonassayfu/Herd/Negadras/routes/web.php)
- [database/seeders/RolePermissionSeeder.php](/Users/yonassayfu/Herd/Negadras/database/seeders/RolePermissionSeeder.php)

New permission surface:

```php
'governance.view',
'governance.update',
```

Why this split matters:

- some operational users should see governance state
- fewer users should trigger reminder or control actions
- viewing governance and mutating governance are different trust levels

### Tests added

- [tests/Feature/ReportsGovernancePhaseTest.php](/Users/yonassayfu/Herd/Negadras/tests/Feature/ReportsGovernancePhaseTest.php)
- [tests/Feature/ExportCenterPhaseTest.php](/Users/yonassayfu/Herd/Negadras/tests/Feature/ExportCenterPhaseTest.php)
- [tests/Feature/NotificationGovernancePhaseTest.php](/Users/yonassayfu/Herd/Negadras/tests/Feature/NotificationGovernancePhaseTest.php)
- [tests/Feature/OverrideControlPhaseTest.php](/Users/yonassayfu/Herd/Negadras/tests/Feature/OverrideControlPhaseTest.php)

What they prove:

- managers can open the new reporting and governance surfaces
- export routes generate files and persist export jobs
- reminder workflows persist notification logs
- ranking overrides create governance events

### Laravel takeaways from this phase

1. A notification system and a notification-audit system are related, but they are not the same table.
2. If an operation matters to governance, give it a structured model instead of only a text log line.
3. Exports in business systems should be treated like stateful operations, not throwaway responses.
4. Wrapping notification delivery in a service is more maintainable than sprinkling `notify()` everywhere.
5. Reporting pages should reflect real domain flow, not recycled starter-kit metrics.

### Practical Negadras result

At the end of this phase:

- Negadras has a domain-specific reporting dashboard
- core CSV exports are available and auditable
- workflow reminders are schedulable
- notification delivery is logged
- override-sensitive actions create a governance trail
- managers have a dedicated governance page for operational oversight
