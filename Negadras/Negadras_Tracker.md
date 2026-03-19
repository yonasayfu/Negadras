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

Current progress:

- competition structure foundation completed
- applicant and presenter foundation completed
- organization and team foundation completed
- submission core foundation completed
- submission versioning foundation completed
- submission file upload foundation completed
- submission status tracking foundation completed
- intake management foundation completed
- presenter portal UX refinement and dashboard operations completed
- reviewer foundation completed
- reviewer assignment foundation completed
- screening review draft and submit flow completed
- reviewer queue completed
- manager screening queue completed
- screening decision workflow completed
- reviewer reassignment workflow completed
- technical review foundation completed
- technical reviewer assignment is now separated from screening assignment
- reviewer technical queue and manager technical queue completed
- technical comparison view for manager completed
- bulk reviewer assignment completed
- structured screening eligibility checklist completed
- manager reopen flow for submitted screening and technical reviews completed
- ranking snapshot generation completed
- tie-break logging and manual override support completed
- award management completed
- presenter feedback packet generation and delivery completed
- archive record generation completed
- session highlight management completed
- public showcase pages completed
- review decisions persistence completed
- shortlist records, approval flow, and shortlist export placeholder completed
- overdue review expiration and in-app overdue notifications completed
- judge foundation completed
- panel and panel-member foundation completed
- rubric and rubric-criteria foundation completed
- judge workspace scoring completed
- conflict declaration workflow completed
- score locking and visibility event tracking completed
- panel scoring operations surface completed
- seasons, stages, industries, applicants, social links, organizations, team members, submission intake, submission versioning, private submission file handling, status history tracking, intake operations, presenter counters, open-call visibility, and dashboard metrics are now in place
- reviewer profiles, reviewer permissions, reviewer-only visibility rules, reviewer assignments, reviewer notifications, reviewer workload visibility, screening recommendations, structured eligibility checks, screening draft save, final screening submission, reviewer work queue, manager screening queue, reassignment, manager reopen flow, shortlist or reject or revision decisions, technical assignments, technical queue operations, technical expert comparison, shortlist approval, and overdue-review handling are now in place
- judge profiles, judge permissions, panel membership, rubric bindings, panel submission assignment, private scoring, presenter-visible comments, conflict handling, lock and reopen flow, and score visibility history are now in place
- competition sessions, presenter queue control, live session event logging, polling-first live snapshots, judge live tablet view, moderator controls, studio dashboard, and projection request flow are now in place
- checklist estimate:
  - detailed Phase 2 screening and reviewer workflow backlog is complete
  - detailed Phase 3 judge, panel, and rubric foundation backlog is substantially complete
  - detailed Phase 4 live-session and dashboard backlog is complete for the polling-first MVP cut
  - full multi-phase Negadras roadmap is roughly 61% complete

### Phase N2: Operational Workflow

- application intake
- screening review
- technical review
- shortlist flow
- live-session preparation
- score locking
- results finalization
- archive creation

### Phase N3: Judge, Panel, and Live Operations

- judge tablet scoring screen
- panel scoring controls
- live session control
- polling-first live status updates
- moderator/session operator tooling
- projection-safe session views

### Phase N4: Public Layer

- call for applications
- current season page
- approved showcase pages
- winners/finalists pages
- archive browsing

Current progress:

- approved showcase pages completed
- winner and archive showcase detail pages completed

### Phase N5: Feedback, Ranking, Awards & Archive

- ranking snapshots
- awards
- presenter feedback packets
- archive records
- public showcase publication

Current progress:

- completed

Operational closeout completed after Phase N5:

- reporting dashboard
- export center
- notification logging and reminder command
- governance dashboard
- override-event trail

### Phase N6: AI Judge Copilot & Advisory Layer

- Laravel AI SDK foundation
- submission asset ingestion for PDF, docs, images, audio, and video transcripts
- judge copilot chat per submission
- grounded internal document retrieval
- optional external web research with clear source labeling
- advisory summaries, risks, and feedback drafts

AI must remain advisory only.

Important architecture note:

- Phase N6 should use Laravel AI SDK for the in-app copilot
- MCP is not the primary implementation path for this phase
- MCP can be considered later if Negadras should be exposed to external AI clients like ChatGPT or Claude

### Phase N7: Hardening

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
