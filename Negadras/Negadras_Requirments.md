Below is the **full merged master plan** for your Negadras platform, combining your original idea, the three AI sources, and my own architecture judgment.

I am treating this as a **real project that should be acceptable in front of decision-makers**, not just a student exercise. The goal is to give you a system that is strong enough to be proposed, phased, built, and defended.

Your uploaded roadmap already captures the core vision well: contestant registration, reviewer and judge workflows, confidential scoring, industry-specific criteria, real-time aggregation, live-session archiving, AI-assisted summaries, public showcase, and RBAC. 

---

# 1. Executive summary

**Negadras** should be built as a **competition management and judging platform** for innovation/startup programs, with four connected layers:

1. **Application & Screening**
2. **Review & Judging**
3. **Live Broadcast & Session Control**
4. **Archive, Feedback, and Public Showcase**

The system should support the full lifecycle of a contestant:

* register
* submit project
* upload files and media
* pass intake screening
* enter technical review
* reach live judging
* receive scores and feedback
* get archived as a participant/finalist/winner
* appear on the public showcase if approved

It should also support a full internal workflow for:

* super admins
* admins
* managers
* secretaries
* reviewers
* judges
* presenters/applicants
* production team
* public guests

The platform should be **Laravel + Vue + Inertia**, with strong RBAC, real-time updates, touch-friendly judge screens, and optional AI analysis as an advisory layer. Your uploaded roadmap and the merged source ideas strongly support this direction.  

---

# 2. Product vision

## 2.1 Problem being solved

Today, the process sounds fragmented:

* ideas come through forms
* files are manually reviewed
* judges work with incomplete information
* comments and scores may be scattered
* live sessions are hard to coordinate
* archival knowledge is weak
* contestants do not always receive structured feedback
* public showcase and institutional memory are limited

## 2.2 Vision

Build one platform that becomes the **official operating system** of Negadras:

* intake system
* screening engine
* judge workstation
* live show control layer
* archive vault
* alumni/public showcase
* reporting and institutional memory

## 2.3 Success definition

This app is successful if it can do three things well:

* make the competition **more organized**
* make judging **more fair, traceable, and informed**
* make the whole program **more professional and reusable season after season**

---

# 3. Core business model of the platform

The platform is not only for picking winners. It should also:

* organize the competition
* improve reviewer/judge efficiency
* protect scoring integrity
* preserve historical learning
* help contestants improve
* produce reusable media and data for future seasons
* give the public a clear view of the program

So the business identity of the product is:

**“A season-based innovation competition management, judging, live-session, and archive platform.”**

---

# 4. Competition structure

This is the clean structure I recommend.

## 4.1 Season

A season represents a yearly or major program cycle.

Examples:

* Negadras Season 4
* Negadras 2027

## 4.2 Stage

A stage represents a competition step inside a season.

Examples:

* Registration Open
* Initial Screening
* Technical Review
* Shortlist Review
* Live Show Week 1
* Semi-Final
* Final
* Post-Award Follow-Up

## 4.3 Session

A session is a concrete event inside a stage.

Examples:

* Episode 1 live judging
* Clean energy review session
* Finalist interview session

## 4.4 Track / Industry / Category

Submissions can belong to one or more evaluation domains.

Examples:

* Clean Energy
* Fintech
* Agritech
* Health
* Manufacturing
* Education

This matches the industry-specific checklist idea in your uploaded roadmap and the other source models. 

---

# 5. User types and role model

Your system needs more than simple RBAC. It needs **role + context + assignment**.

## 5.1 Roles

### Super Admin

Full control of infrastructure-level and policy-level settings.

### Admin

Manages users, seasons, stages, rubrics, and system configuration.

### Program Manager

Owns operational flow of a season, assignment logic, and final workflow oversight.

### Secretary

Handles intake, communication, scheduling, file checks, and coordination.

### Reviewer

Performs early-stage screening or technical review before live judges.

### Judge

Scores, comments, evaluates, and participates in live or private review sessions.

### Presenter / Applicant

Creates profile, submits project, uploads files, sees status, receives feedback.

### Organization Representative

Represents a company/startup if the submission is organizational.

### Production Team

Manages live session support, projection, overlays, media attachments, archive video links.

### Public Guest

Can only access public pages and approved archive/showcase content.

### Auditor / Observer

Read-only access to audit trail and scoring integrity where permitted.

## 5.2 Key permission principle

A role alone is not enough. Access should also depend on assignment:

* a judge sees only assigned sessions/submissions
* a reviewer sees only assigned review batches
* a presenter sees only their own applications and feedback
* a production user sees session/broadcast tools, not admin scoring controls

---

# 6. End-to-end workflow

This is the full lifecycle I recommend.

## Phase A — Season setup

Admin or manager creates:

* season
* stages
* timelines
* tracks/industries
* rubric templates
* judge pools
* reviewer pools
* public call-for-applications page

## Phase B — Application intake

Presenter:

* creates account or is invited
* creates applicant profile
* creates organization/team profile if relevant
* submits project/application
* uploads PDF, pitch deck, video, images, business docs
* adds social links
* chooses industry/category
* submits before deadline

System:

* validates required fields
* stores version snapshot
* marks application as submitted
* notifies secretary/admin

## Phase C — Intake validation

Secretary/admin checks:

* completeness
* document validity
* category correctness
* duplicate applications
* basic eligibility
* compliance issues

Possible outcomes:

* accepted for review
* returned for correction
* rejected as invalid/ineligible
* flagged for manual clarification

## Phase D — Screening review

Reviewers are assigned in batches.

Reviewers can:

* read submission
* view attachments
* use structured checklist
* mark eligibility and fit
* comment privately
* recommend shortlist / reject / clarify

Possible outputs:

* pass to technical review
* pass to shortlist
* reject
* request revision
* flag conflict or risk

## Phase E — Technical / expert review

More specialized reviewers may assess feasibility, sector quality, innovation, risk, and readiness.

Outputs:

* score or recommendation
* shortlist candidate pool
* panel assignment recommendation
* special remarks for judges

## Phase F — Live judge preparation

Manager/secretary creates live session and assigns:

* stage
* panel
* judges
* schedule
* order of presenters
* projection rights
* rubrics to use
* moderator notes
* visible/non-visible materials

## Phase G — Live judging

Judges on tablets can:

* view presenter profile
* see summarized project info
* review key documents
* score criteria
* write private notes
* write public/non-secret feedback
* choose whether to request projection to the shared dashboard if allowed

System shows:

* aggregated score
* rubric breakdown
* session status
* judge submission status
* optional reveal logic for individual scores

## Phase H — Finalization

After session:

* scores are locked
* tie-break rules run if needed
* panel result is finalized
* winner/rank/advance decision is saved
* non-secret comments are packaged
* archive record is created

## Phase I — Feedback and archive

Presenter receives:

* status result
* structured comments
* selected score breakdown if policy allows
* next steps or resubmission guidance

System archives:

* session data
* comments
* score snapshots
* linked video
* highlights
* transcript or summary if available

## Phase J — Public showcase

Approved public entries show:

* season
* participant/company
* project title
* category
* outcome
* episode/session
* success story if approved

---

# 7. Functional modules

This is the full module map I recommend.

## 7.1 Identity and security

* login
* email verification
* 2FA
* password reset
* device/session management
* RBAC
* permission groups
* audit access

## 7.2 User and profile management

* internal users
* presenter accounts
* judge profiles
* reviewer profiles
* production accounts
* organization representatives

## 7.3 Season and stage management

* seasons
* stages
* deadlines
* progression rules
* stage opening/closing
* stage-specific requirements

## 7.4 Applicant / organization management

* person profile
* startup/company profile
* team members
* representatives
* founders/co-founders
* contact records

## 7.5 Submission management

* draft submission
* submit/finalize
* attachments
* video links
* submission versions
* revision workflow
* stage-specific status

## 7.6 Review management

* reviewer assignment
* screening forms
* blind review options
* technical review notes
* shortlist recommendations

## 7.7 Judge and panel management

* judge pools
* panel creation
* panel-stage assignment
* panel-submission assignment
* conflict-of-interest declaration
* judge availability

## 7.8 Rubric and scoring engine

* rubric templates
* industry-specific criteria
* criterion weights
* private/public visibility
* score locking
* tie-break rules
* score normalization if needed

## 7.9 Live session module

* session scheduling
* presenter order
* judge tablet view
* moderator notes
* projection control
* dashboard overlays
* real-time updates

## 7.10 Comment and feedback module

* private notes
* internal comments
* presenter-visible comments
* structured feedback summary
* comment archive

## 7.11 AI intelligence module

* file summarization
* transcript extraction
* project comparison
* risk flags
* similarity hints
* social/profile enrichment
* advisory-only summaries

## 7.12 Archive vault

* session records
* score snapshots
* comments history
* video links
* key discussion points
* searchable archive

## 7.13 Public website

* home
* about
* how it works
* apply now
* current season
* past seasons
* winners
* showcase stories
* announcements

## 7.14 Notifications

* email
* in-app
* reminders
* stage transitions
* session reminders
* result notices

## 7.15 Reporting and analytics

* total applications
* category distribution
* shortlist funnel
* judge completion rate
* score distributions
* stage outcomes
* export to Excel/PDF

## 7.16 Governance and audit

* activity logs
* score-change history
* assignment logs
* visibility changes
* finalization logs

These module directions align strongly with the uploaded roadmap’s module structure and real-time / archive / AI direction. 

---

# 8. Recommended domain model

Here is the cleaned master entity structure.

## 8.1 Identity

* users
* roles
* permissions
* user_role_assignments
* user_profiles

## 8.2 Competition structure

* seasons
* stages
* stage_transitions
* industries
* stage_industry_rules
* sessions

## 8.3 Applicant side

* applicants
* organizations
* organization_representatives
* team_members
* social_links
* applicant_contacts

## 8.4 Submission side

* submissions
* submission_versions
* submission_files
* submission_links
* submission_tags
* submission_status_history
* eligibility_checks

## 8.5 Review side

* reviewer_assignments
* screening_reviews
* technical_reviews
* review_decisions
* shortlist_records

## 8.6 Judge side

* judges
* panels
* panel_members
* panel_submission_assignments
* conflict_of_interest_declarations

## 8.7 Rubric and score side

* rubrics
* rubric_criteria
* rubric_stage_bindings
* rubric_industry_bindings
* score_entries
* score_submissions
* score_visibility_events
* score_locks
* ranking_snapshots
* award_records

## 8.8 Comment and feedback

* judge_comments
* reviewer_comments
* internal_notes
* presenter_feedback_packets
* presenter_responses

## 8.9 Live session

* session_presenters
* dashboard_projection_sessions
* session_events
* session_media
* session_highlights
* session_transcripts

## 8.10 AI layer

* ai_analysis_runs
* ai_insights
* ai_source_references
* ai_risk_flags
* ai_comparison_records

## 8.11 Public and archive

* public_pages
* public_showcase_entries
* archive_records
* media_assets

## 8.12 System operations

* notifications
* exports
* audit_logs
* webhooks
* integration_settings

---

# 9. Database blueprint with key fields

## 9.1 seasons

* id
* name
* year
* slug
* status
* registration_open_at
* registration_close_at
* description

## 9.2 stages

* id
* season_id
* name
* code
* type
* order_index
* starts_at
* ends_at
* status
* is_live_stage

## 9.3 industries

* id
* name
* slug
* description
* is_active

## 9.4 applicants

* id
* user_id nullable
* applicant_type (individual, organization_rep)
* full_name
* email
* phone
* bio
* national_id_or_registration_ref nullable

## 9.5 organizations

* id
* legal_name
* display_name
* registration_number nullable
* industry_id nullable
* website nullable
* description
* contact_email
* contact_phone
* logo_path nullable
* address

## 9.6 team_members

* id
* organization_id nullable
* applicant_id nullable
* full_name
* role_title
* email nullable
* phone nullable
* bio nullable
* is_primary_contact

## 9.7 social_links

* id
* applicant_id nullable
* organization_id nullable
* platform
* url
* is_verified

## 9.8 submissions

* id
* season_id
* current_stage_id
* industry_id
* applicant_id
* organization_id nullable
* title
* summary
* problem_statement
* solution_description
* business_model
* status
* submitted_at
* is_public_after_approval
* current_version_id nullable

## 9.9 submission_versions

* id
* submission_id
* version_no
* snapshot_json
* change_note nullable
* created_by
* created_at
* is_locked

## 9.10 submission_files

* id
* submission_id
* submission_version_id nullable
* file_type
* original_name
* file_path
* mime_type
* file_size
* description nullable
* uploaded_by
* uploaded_at
* is_required
* is_verified

## 9.11 reviewer_assignments

* id
* submission_id
* reviewer_id
* stage_id
* assigned_at
* due_at
* status

## 9.12 screening_reviews

* id
* submission_id
* reviewer_assignment_id
* eligibility_status
* recommendation
* score_optional
* notes
* submitted_at

## 9.13 judges

* id
* user_id
* professional_title
* organization
* specialization
* bio
* is_active

## 9.14 panels

* id
* season_id
* stage_id
* name
* description
* status

## 9.15 panel_members

* id
* panel_id
* judge_id
* role_in_panel
* display_order

## 9.16 rubrics

* id
* name
* description
* industry_id nullable
* stage_id nullable
* total_weight
* is_active

## 9.17 rubric_criteria

* id
* rubric_id
* name
* description
* max_score
* weight
* order_index
* is_required
* visibility_rule

## 9.18 panel_submission_assignments

* id
* panel_id
* submission_id
* session_id nullable
* assigned_at
* status

## 9.19 sessions

* id
* season_id
* stage_id
* panel_id nullable
* name
* session_type
* scheduled_at
* broadcasted_at nullable
* location nullable
* status
* etv_video_url nullable

## 9.20 score_entries

* id
* submission_id
* session_id nullable
* panel_id nullable
* judge_id
* rubric_criterion_id
* score_value
* comment nullable
* is_secret
* is_locked
* submitted_at

Unique key:

* submission_id + judge_id + rubric_criterion_id + session_id

## 9.21 judge_comments

* id
* submission_id
* judge_id
* session_id nullable
* comment_type (private, internal, presenter_visible, public)
* content
* is_archived
* created_at

## 9.22 ranking_snapshots

* id
* season_id
* stage_id
* session_id nullable
* submission_id
* aggregate_score
* rank_position
* tie_break_reason nullable
* finalized_at

## 9.23 award_records

* id
* season_id
* submission_id
* award_type
* rank_position
* prize_value nullable
* notes nullable

## 9.24 ai_analysis_runs

* id
* submission_id
* run_type
* triggered_by
* status
* model_name nullable
* started_at
* completed_at
* error_message nullable

## 9.25 ai_insights

* id
* submission_id
* ai_analysis_run_id
* insight_type
* content_text
* content_json nullable
* confidence_label nullable
* is_advisory_only

## 9.26 archive_records

* id
* submission_id
* season_id
* stage_id
* session_id nullable
* archived_at
* archive_status
* public_visibility

## 9.27 audit_logs

* id
* actor_user_id nullable
* action
* entity_type
* entity_id
* before_json nullable
* after_json nullable
* ip_address nullable
* created_at

---

# 10. Relationship map

Here is the simple relationship logic.

* one **season** has many **stages**
* one **season** has many **submissions**
* one **stage** has many **sessions**
* one **industry** can be linked to many **submissions** and **rubrics**
* one **applicant** can create many **submissions**
* one **organization** can have many **team members**
* one **submission** has many **versions**
* one **submission** has many **files**
* one **submission** has many **review assignments**
* one **submission** can be assigned to one or more **panels**
* one **panel** has many **judges**
* one **rubric** has many **criteria**
* one **judge** creates many **score entries**
* one **submission** receives many **score entries**
* one **session** may contain many presenters/submissions
* one **submission** can have many **AI insights**
* one **submission** can have one or more **archive records**
* one **submission** can have many **feedback items**

---

# 11. Status and state machine

This is important. Without status discipline, the app will become messy.

## 11.1 Submission status

* draft
* submitted
* under_intake_check
* incomplete_returned
* eligible
* in_screening
* screening_rejected
* shortlisted
* in_technical_review
* technical_rejected
* scheduled_for_live
* live_reviewed
* finalist
* winner
* not_selected
* archived

## 11.2 Stage status

* planned
* open
* closed
* finalized
* archived

## 11.3 Session status

* scheduled
* ready
* live
* paused
* completed
* finalized
* archived

## 11.4 Score status

* draft
* submitted
* revealed
* locked
* final

---

# 12. Scoring and evaluation logic

This must be one of the strongest parts of the platform.

## 12.1 Scoring model

Each stage uses a rubric.
Each rubric has weighted criteria.

Example:

* innovation
* feasibility
* market potential
* team strength
* impact
* scalability
* financial clarity
* implementation readiness

## 12.2 Score calculation

Per judge:

* each criterion gets a score
* each criterion may have weight
* judge total is calculated

Per submission:

* aggregate all judges’ totals
* apply tie-break logic if equal
* create ranking snapshot

## 12.3 Privacy rules

Default rule:

* a judge can see their own input
* judges cannot see each other’s detailed scores before reveal
* admins/managers may see all
* public dashboard sees only approved aggregate
* presenter sees only policy-approved feedback

This matches your original requirement and the merged source ideas around confidential scoring and reveal logic. 

## 12.4 Tie-break rules

I recommend this order:

1. higher weighted innovation score
2. higher feasibility score
3. higher average across non-outlier judges
4. panel chair review
5. official manual tie-break note

## 12.5 Score locking

Once the judging window closes:

* no judge edits
* only admin with reason can reopen
* all reopen actions logged

## 12.6 Conflict-of-interest rule

A judge must declare if they know or are related to:

* the presenter
* the company
* a competitor
* a partner or funder

If conflict exists:

* recuse from scoring
* system excludes judge from aggregate
* audit note saved

---

# 13. Reviewer workflow versus judge workflow

This distinction was missing in the earlier sources, so here is the clean version.

## 13.1 Reviewer workflow

Used before live judging.

Reviewer actions:

* eligibility screening
* completeness check
* category validation
* technical desk review
* shortlist recommendation
* private notes to manager

Outputs:

* pass
* reject
* return for revision
* escalate to expert
* shortlist candidate

## 13.2 Judge workflow

Used for advanced or live evaluation.

Judge actions:

* review summary pack
* score against rubric
* enter comments
* choose what remains private
* participate in reveal/finalization
* project screen only if allowed

Outputs:

* official score
* comments
* ranking contribution

---

# 14. Presenter experience

The presenter side must feel professional.

## 14.1 Presenter portal

* create/edit profile
* company/team information
* create application
* save draft
* upload files
* track status
* receive notifications
* see non-secret feedback
* resubmit if allowed
* see past seasons/history

## 14.2 Presenter feedback packet

After review or live session, the system can provide:

* stage result
* summary comments
* strengths
* improvement areas
* next-step guidance
* selected score overview if permitted

This feedback packet is much better than exposing raw internal comments directly.

---

# 15. Live session and dashboard design

## 15.1 Judge tablet view

* presenter profile
* project summary
* files/media preview
* rubric scoring panel
* notes section
* submit score button
* reveal status indicator
* projection request button if allowed

## 15.2 Moderator / production dashboard

* current session
* current presenter
* timers
* judge completion status
* aggregate score
* speaker order
* projection source
* session notes
* archive controls

## 15.3 Main public/live dashboard

Only approved items:

* presenter name/company
* project title
* category
* aggregate score
* non-sensitive highlights
* ranking position if policy allows

The real-time scoring and projection concepts are already present in your uploaded roadmap and source summaries.  

---

# 16. AI integration blueprint

AI should help, but not control the competition.

## 16.1 Safe AI uses

* summarize PDF
* summarize pitch deck
* transcribe pitch video
* extract key claims
* detect missing sections
* compare with known market themes
* produce judge briefing notes
* generate draft feedback packet
* produce searchable archive summary

## 16.2 Cautious AI uses

* social/profile summarization
* trend comparison
* similarity detection
* risk flags

These should always be:

* optional
* clearly labeled as machine-generated
* never the sole basis of acceptance or rejection

## 16.3 AI entities to keep

* analysis run
* insight result
* source references
* confidence label
* advisory flag

## 16.4 MVP rule

Do not make AI a blocker for launch.
The platform must work even if AI is disabled.

Your uploaded roadmap strongly supports AI summarization, benchmarking, and structured feedback, but those are best treated as advanced layers rather than foundations.  

---

# 17. Public website requirements

The public side should not expose internal judging complexity.

## Public pages

* Home
* About Negadras
* How It Works
* Current Season
* Apply Now
* Eligibility / Rules
* FAQ
* Past Seasons
* Winners
* Success Stories
* Media / Episodes
* Contact

## Public data rules

Public pages should show only approved information:

* approved contestants
* approved stories
* final results
* archive metadata
* selected media and outcomes

---

# 18. Non-functional requirements

## Security

* 2FA for admins/judges
* strong RBAC
* signed file access where needed
* audit logs
* encryption for sensitive fields where appropriate

## Performance

* queues for heavy jobs
* cache public pages
* optimize file storage
* precompute dashboard aggregates

## Reliability

* backups
* restore testing
* queue monitoring
* failure retries
* stage-based locking

## Usability

* touch-friendly judge UI
* responsive layouts
* keyboard support for admins
* clear status labels

## Compliance and trust

* visible audit trail for sensitive actions
* score reveal log
* conflict declarations
* reason-required overrides

## Localization

* English + Amharic support
* date/time and label formatting suitable for local use

## Accessibility

* contrast
* readable typography
* keyboard nav
* screen-reader basics for public pages

---

# 19. MVP, phase 2, and future scope

## MVP

Build this first:

* auth + RBAC
* seasons/stages
* presenter/applicant profiles
* organization/team basics
* submission form + attachments
* intake validation
* reviewer assignments
* judge assignments
* rubrics + criteria
* scoring engine
* live session scheduling
* real-time aggregate dashboard
* judge comments
* presenter feedback packet
* archive records
* public website basics
* notifications
* audit log essentials

## Phase 2

* richer reporting
* projection controls
* transcript support
* session highlights
* better public archive
* exports
* shortlist engine
* conflict-of-interest workflows

## Future

* AI summarization
* AI comparison tools
* social/profile enrichment
* advanced media integration
* ETV automation hooks
* audience interaction
* mobile companion apps

---

# 20. Recommended development roadmap

## Phase 1 — Foundation

* Laravel starter kit / auth
* roles and permissions
* season, stage, industry setup
* applicant and organization profiles
* submission and file upload
* admin navigation
* public website shell

## Phase 2 — Intake and screening

* intake validation
* reviewer assignments
* screening forms
* shortlist recommendations
* status history
* notifications

## Phase 3 — Judge and rubric engine

* judge profiles
* panels
* rubrics
* criteria weights
* score entries
* judge comments
* reveal and lock logic

## Phase 4 — Live session operations

* sessions
* presenter order
* judge tablet view
* aggregate dashboard
* realtime updates
* moderator controls

## Phase 5 — Archive and feedback

* archive record
* feedback packets
* public archive pages
* search and filters
* export/report basics

## Phase 6 — Governance and hardening

* audit expansion
* conflict declarations
* reopen/override workflow
* test coverage
* security review
* production readiness

## Phase 7 — AI layer

* document summarization
* transcript extraction
* advisory insight panel
* draft feedback suggestions

---

# 21. Suggested Laravel architecture

## Backend

* Laravel 12
* policies + gates
* events/listeners
* queues
* notifications
* form requests
* service classes for scoring, ranking, stage progression

## Frontend

* Vue 3 + Inertia
* role-based layouts
* tablet-optimized judge screens
* public site pages
* admin back-office
* reusable form and file components

## Realtime

Your uploaded roadmap’s technical section recommends Laravel Reverb for live scoring/dashboard behavior, which fits this app very well. 

## Storage

* private storage for submissions
* public storage only for approved showcase files
* S3-compatible object storage for scale, which also aligns with the uploaded roadmap. 

---

# 22. Hosting and deployment recommendation

You asked specifically about Laravel Cloud, Forge, DigitalOcean, and GoDaddy.

## My recommendation in one line

For this kind of app, I would choose:

**Best fast/professional launch:** Laravel Cloud
**Best balance of control + maturity + lower long-term ops cost:** Forge + DigitalOcean
**Least recommended for this project:** shared-style GoDaddy hosting; GoDaddy VPS only if you already have a strong reason

## 22.1 Laravel Cloud

Laravel Cloud is a fully managed Laravel hosting platform with managed deploys, autoscaling, databases, caching, storage, and security, and it is positioned specifically as “deploy Laravel without managing servers.” It also supports usage-based pricing with auto-hibernation and custom domains. ([Laravel Cloud][1])

### Why it fits your project

This project has:

* live scoring
* role-heavy workflows
* queues
* private files
* future AI jobs
* a public website

That means reducing server-management burden is a big advantage.

### Best use case

Choose Laravel Cloud if:

* you want the fastest path to production
* you want to spend more time coding than managing servers
* your team is small
* you want a cleaner Laravel-native deployment story

## 22.2 Forge + DigitalOcean

Forge is not hosting itself; it is a server management/deployment platform. It gives one-click provisioning, zero-downtime deploys, automated SSL, root access, and support for full server stacks including Nginx, PHP, MySQL/Postgres, Redis, Node.js, and Supervisor. Forge also supports multiple server types such as app, web, worker, database, cache, and load balancer servers. ([Laravel Forge][2])

DigitalOcean App Platform is a managed PaaS with CI/CD, autoscaling, workers, cron jobs, scale-to-zero for suitable workloads, managed database connectivity, and pricing that starts low for small deployments. ([DigitalOcean][3])

### My preferred combination

For your app, I would usually choose:

**Forge + DigitalOcean Droplet / managed database / object storage**

Why:

* strong control
* predictable architecture
* easier Laravel ops than raw VPS
* good path for workers, cron, Redis, and DB separation
* easier evolution as the app grows

### Best use case

Choose Forge + DigitalOcean if:

* you want more control than Laravel Cloud
* you are willing to manage infrastructure decisions
* you want to separate app, DB, worker, and cache more explicitly later

## 22.3 DigitalOcean App Platform alone

DigitalOcean App Platform is a solid managed option with Git-based deploys, preview environments, worker components, autoscaling, cron jobs, and managed service integrations. ([DigitalOcean][3])

### Good for

* faster deployment than a raw VPS
* teams that like PaaS workflows
* APIs and worker-heavy apps

### Caution

For a complex Laravel app with real-time broadcasting-style features and nuanced backend processes, I still prefer **Laravel Cloud** if you want managed Laravel specifically, or **Forge + DigitalOcean** if you want more control.

## 22.4 GoDaddy

GoDaddy VPS can provide SSH and root/admin access, so it is technically possible to run this project there. ([GoDaddy][4])

### Why I do not recommend it first

This app is not a simple brochure site. It needs:

* queues
* possibly Redis
* real-time features
* careful deployment
* background jobs
* stricter operational discipline

GoDaddy can host servers, but it is not the platform I would pick first for a Laravel competition system with real-time workflows and future AI jobs. That is a judgment call based on fit, not because GoDaddy cannot technically run PHP.

## 22.5 Final hosting recommendation by stage

### For MVP / pilot

**Laravel Cloud**

### For serious production with more control

**Forge + DigitalOcean**

### For budget experimentation

**DigitalOcean App Platform** or a small **Forge-managed VPS**

### Not my first choice

**GoDaddy**, unless your organization already has a contract, internal policy, or infrastructure reason

---

# 23. My final recommendation for your exact case

If you want this project to look **credible, modern, and pitch-ready**, I would define it like this:

> Negadras is a season-based innovation competition management platform built with Laravel, Vue, and Inertia, designed to manage applicant intake, multi-stage screening, expert review, live judge scoring, broadcast-session support, structured feedback, and public archival showcase, with optional AI-assisted insights for judges and administrators.

And for implementation:

> Build the MVP first around submissions, screening, judging, live scoring, archive, and public showcase. Add AI only after the workflow is stable.

And for hosting:

> Launch on **Laravel Cloud** if speed and simplicity matter most.
> Move or scale on **Forge + DigitalOcean** if you want stronger infrastructure control and long-term operational flexibility. ([Laravel Cloud][1])

---

# 24. The one thing I would change from all previous AI outputs

I would **not** let the project start with AI-first complexity.

The real acceptance of this system will come from:

* strong workflow
* clean scoring rules
* trust and audit
* excellent judge/presenter experience
* reliable live-session behavior

AI should be the enhancer, not the foundation.

---

# 25. Best next step

The smartest next move is to turn this master plan into a **Phase 1 implementation document** with:

* final entity list
* migration order
* module order
* route/module structure
* admin/judge/presenter page list
* MVP database schema in practical Laravel terms

I can do that next as a **day-by-day or phase-by-phase build roadmap**.

[1]: https://cloud.laravel.com/ "Laravel Cloud Hosting - Deploy & Scale Laravel Apps"
[2]: https://forge.laravel.com/ "Forge · Simple server management for PHP and beyond"
[3]: https://www.digitalocean.com/products/app-platform "DigitalOcean App Platform | Build, Deploy, and Scale Apps with Ease"
[4]: https://www.godaddy.com/help/enable-root-or-admin-access-to-my-vps-hosting-24737 "Enable root or admin access to my VPS Hosting | VPS Hosting - GoDaddy Help US"
