# Negadras Execution Tracker

## Decision

Recommended base:

- `starter-business-v1`

Do not start from:

- `starter-core-v1` only
- `starter-enterprise` yet

## Why `starter-business-v1` is the correct base

Negadras already needs these shared foundations on day one:

- settings
- media/file handling
- notes/comments
- workflow status management
- import/restore patterns
- dashboard and reporting base

Those are already solved in `starter-business-v1`.

At the same time, Negadras does **not** yet require a full `starter-enterprise` jump as the first move because the current requirements do not clearly force:

- multi-tenancy
- localization-first rollout
- organization-wide approval engines
- finance-grade formatting/reporting
- enterprise-scale background processing as the first blocker

So the practical rule is:

- base platform = `starter-business-v1`
- build Negadras domain modules on top of it
- pull selected enterprise ideas later only if the project proves it needs them

## Project Positioning

Negadras is a:

- season-based competition management platform
- judging and review workflow platform
- live-session coordination platform
- archive and showcase platform

That makes it a domain application built on top of `starter-business`, not a new generic starter.

## Source of Truth

Primary requirement file:

- `Negadras/Negadras_Requirments.md`

Execution tracker:

- `Negadras/Negadras_Tracker.md`

Starter references:

- `TheRoadmap/starterBusinessV1.md`
- `TheRoadmap/starterBusinessTaskList.md`
- `TheRoadmap/boilerplateLevels.md`

Learning/reference archive:

- `TheRoadmap/laravelbasics.md`

## Build Strategy

### Phase N0: Project Freeze

- confirm `starter-business-v1` as the base
- define what stays generic starter behavior and what becomes Negadras-specific
- freeze naming, roles, and season terminology

### Phase N1: Domain Model

- seasons
- stages
- sessions
- tracks/categories
- applicants
- organizations/teams
- submissions
- reviewer assignments
- judge assignments
- panels
- rubrics
- score records
- comments/feedback visibility rules

### Phase N2: Operational Workflow

- application intake
- screening review
- technical review
- shortlist flow
- live-session preparation
- score locking
- results finalization
- archive creation

### Phase N3: Live Judging Surface

- judge tablet scoring screen
- live session control
- real-time status updates
- moderator/session operator tooling
- projection-safe session views

### Phase N4: Public Layer

- call for applications
- current season page
- approved showcase pages
- winners/finalists pages
- archive browsing

### Phase N5: AI Advisory Layer

- submission summarization
- transcript extraction
- judge prep summaries
- comparison/risk hints

AI must remain advisory only.

### Phase N6: Hardening

- audit tightening
- permission review
- notification coverage
- deployment and production operations
- data retention and archival policy

## Recommended Initial Roles

- Super Admin
- Admin
- Program Manager
- Secretary
- Reviewer
- Judge
- Presenter
- Production Team
- Public Guest
- Auditor

These should be implemented with both:

- role-based permissions
- assignment-based record access

## What To Reuse Directly From `starter-business-v1`

- auth and Fortify security flows
- RBAC baseline
- admin shell
- users and roles
- settings module
- media library
- notes/comments pattern
- workflow/status pattern
- import baseline
- restore pattern
- dashboard/reporting foundations
- notifications/activity log
- public pages/public layout

## What Must Be Built As Negadras Domain Modules

- seasons and stages
- sessions and live scheduling
- application/submission model
- applicant/team/organization structures
- reviewer and judge assignments
- rubric and scoring engine
- score confidentiality rules
- live aggregation and reveal rules
- archive vault
- public showcase logic

## Do Not Build First

- full mobile app
- advanced AI automation
- generalized enterprise approval engine
- multi-tenant architecture
- integrations that are not required for launch

## MCP and Source Guidance

During implementation, use these sources in this order:

1. Local project code and tracker docs
2. Laravel Boost docs search for framework/package patterns
3. Laravel route/schema/test inspection from the local app
4. Browser logs when frontend/runtime behavior is unclear
5. External web sources only when the requirement is actually time-sensitive

That keeps the project grounded in the real codebase instead of drifting into generic advice.

## Recommended Next Decision

Start Negadras from:

- repository: `business-starter-kit`
- release tag: `starter-business-v1`

Then create a dedicated Negadras implementation branch/repo flow on top of that base.
