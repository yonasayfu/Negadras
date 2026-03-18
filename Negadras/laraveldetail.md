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
