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
