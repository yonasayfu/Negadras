Yes. Since you already have **auth, 2FA, and RBAC basics**, we can start the roadmap from the **real Negadras business features**.

I will make this as a **trackable Phase 1 checklist** so you can tick tasks one by one during development.

---

# Phase 1 — Competition Foundation & Submission Core

## Phase 1 Goal

Build the first usable Negadras core so the system can:

* manage seasons, stages, and industries
* register presenters/applicants properly
* create organizations/teams
* accept project submissions
* upload attachments
* track submission statuses
* allow admin/manager/secretary to review basic intake readiness

This phase should end with a working internal system where a presenter can submit an idea and the Negadras team can manage it structurally.

---

# Phase 1 Modules

## 1. Competition Structure Module

## 2. Applicant / Presenter Profile Module

## 3. Organization / Team Module

## 4. Submission Module

## 5. Submission File Upload Module

## 6. Submission Status Tracking Module

## 7. Intake Management Module

## 8. Basic Admin Dashboard for Negadras Operations

---

# Phase 1 Deliverable

At the end of this phase, you should be able to say:

* we can create a season
* we can define competition stages
* we can define industries/categories
* presenters can create profiles
* companies/teams can be registered
* projects can be submitted with files
* submissions can move through basic intake statuses
* admin/manager/secretary can inspect and manage submissions

---

# Phase 1 Suggested Database Scope

Focus only on these main entities first:

* seasons
* stages
* industries
* applicants
* organizations
* team_members
* social_links
* submissions
* submission_versions
* submission_files
* submission_status_history

Do **not** start reviewers, judges, scores, AI, live dashboard, or archives yet.

---

# Phase 1 Checklist

## A. Competition Structure Setup

### A1. Seasons

* [x] Create `seasons` migration
* [x] Add fields: `name`, `year`, `slug`, `status`, `registration_open_at`, `registration_close_at`, `description`
* [x] Create Season model
* [x] Create Season controller
* [x] Create admin season list page
* [x] Create season create form
* [x] Create season edit form
* [x] Add activate/deactivate season action
* [x] Add season validation rules
* [x] Add season policy/permission checks

### A2. Stages

* [x] Create `stages` migration
* [x] Add fields: `season_id`, `name`, `code`, `type`, `order_index`, `starts_at`, `ends_at`, `status`, `is_live_stage`
* [x] Create Stage model
* [x] Define relationship: season has many stages
* [x] Create stage CRUD
* [ ] Add reorder logic for stage order
* [x] Add validation to prevent duplicate stage code in same season
* [x] Add stage open/close UI action
* [x] Add permission checks

### A3. Industries / Categories

* [x] Create `industries` migration
* [x] Add fields: `name`, `slug`, `description`, `is_active`
* [x] Create Industry model
* [x] Create industry CRUD
* [x] Add list/search/filter for industries
* [x] Add enable/disable action
* [ ] Add relationship usage in submission form

---

## B. Applicant / Presenter Foundation

### B1. Applicants

* [x] Create `applicants` migration
* [x] Add fields: `user_id`, `applicant_type`, `full_name`, `email`, `phone`, `bio`, `national_id_or_registration_ref`
* [x] Create Applicant model
* [x] Define relationship with `users`
* [x] Decide whether every presenter must have a user account
* [x] Create presenter profile page
* [x] Create presenter profile edit form
* [x] Add validation for unique email/phone if needed
* [x] Add policy so presenters only manage their own profile

### B2. Social Links

* [x] Create `social_links` migration
* [x] Add fields: `applicant_id`, `organization_id`, `platform`, `url`, `is_verified`
* [x] Create SocialLink model
* [x] Add one-to-many relationship from applicant
* [x] Add repeatable social link fields in form
* [x] Add URL validation
* [x] Support add/remove social links dynamically in Vue form

---

## C. Organization / Team Management

### C1. Organizations

* [x] Create `organizations` migration
* [x] Add fields: `legal_name`, `display_name`, `registration_number`, `industry_id`, `website`, `description`, `contact_email`, `contact_phone`, `logo_path`, `address`
* [x] Create Organization model
* [x] Define relationship with industry
* [x] Create organization create/edit UI
* [x] Add logo upload support
* [x] Add validation rules
* [x] Decide whether organization is optional or required per submission

### C2. Team Members

* [x] Create `team_members` migration
* [x] Add fields: `organization_id`, `applicant_id`, `full_name`, `role_title`, `email`, `phone`, `bio`, `is_primary_contact`
* [x] Create TeamMember model
* [x] Add repeatable team member form section
* [x] Add one primary contact validation rule
* [x] Add edit/delete UI for team members
* [x] Show team members on organization detail page

---

## D. Submission Core

### D1. Submission Main Table

* [x] Create `submissions` migration
* [x] Add fields: `season_id`, `current_stage_id`, `industry_id`, `applicant_id`, `organization_id`, `title`, `summary`, `problem_statement`, `solution_description`, `business_model`, `status`, `submitted_at`, `is_public_after_approval`, `current_version_id`
* [x] Create Submission model
* [x] Define relationships with season, stage, industry, applicant, organization
* [x] Create submission status enum or constants
* [x] Add model scopes like `draft`, `submitted`, `eligible`
* [x] Create submission create page
* [x] Create submission edit page
* [x] Create submission detail/show page
* [x] Add presenter-only ownership policy
* [x] Add admin/manager/secretary access policy

### D2. Submission Draft Flow

* [x] Support save as draft
* [x] Support final submit
* [x] Prevent final submit if required fields missing
* [x] Lock certain fields after final submit, or define editable rules
* [x] Show clear status badge on submission page
* [x] Add confirmation modal before final submission

---

## E. Submission Versioning

### E1. Submission Versions

* [x] Create `submission_versions` migration
* [x] Add fields: `submission_id`, `version_no`, `snapshot_json`, `change_note`, `created_by`, `created_at`, `is_locked`
* [x] Create SubmissionVersion model
* [x] Create version snapshot service
* [x] Save version snapshot when user submits final version
* [x] Save version snapshot on major edit if needed
* [x] Show version history on submission detail page
* [x] Add "current version" label
* [x] Prevent accidental overwrite of locked version

This part is important because later phases will need revision history.

---

## F. Submission File Uploads

### F1. Submission Files Table

* [x] Create `submission_files` migration
* [x] Add fields: `submission_id`, `submission_version_id`, `file_type`, `original_name`, `file_path`, `mime_type`, `file_size`, `description`, `uploaded_by`, `uploaded_at`, `is_required`, `is_verified`
* [x] Create SubmissionFile model
* [x] Configure storage disk
* [x] Decide file visibility: private or public
* [x] Add file upload component in Vue
* [x] Support PDF upload
* [x] Support image upload
* [ ] Support video link or video upload decision
* [x] Add file delete/replace flow
* [x] Show uploaded file list in submission page
* [x] Add file type validation
* [x] Add file size validation
* [x] Add download/open action with authorization checks

### F2. File Rules

* [x] Define required file types for Negadras submission
* [x] Add required/optional label per file section
* [x] Add secretary verification field later-ready
* [x] Show file verification status placeholder

---

## G. Submission Status Tracking

### G1. Status History

* [x] Create `submission_status_history` migration
* [x] Add fields: `submission_id`, `from_status`, `to_status`, `changed_by`, `reason`, `created_at`
* [x] Create SubmissionStatusHistory model
* [x] Create service to update submission status consistently
* [x] Log every status change automatically
* [x] Show submission timeline/history on detail page

### G2. Phase 1 Statuses to Support

Only implement these now:

* [ ] `draft`
* [x] `submitted`
* [x] `under_intake_check`
* [x] `incomplete_returned`
* [x] `eligible`
* [x] `rejected`

Do not add later complex statuses yet.

### G3. Status Actions

* [x] Presenter can move draft to submitted
* [x] Secretary/admin can move submitted to under intake check
* [x] Secretary/admin can return incomplete submission
* [x] Secretary/admin can mark eligible
* [x] Secretary/admin can reject with reason
* [x] Show reason when returned/rejected

---

## H. Intake Management Module

### H1. Intake Review Screen

* [x] Create submission intake list page for admin/manager/secretary
* [x] Add filters by season
* [x] Add filters by stage
* [x] Add filters by industry
* [x] Add filters by status
* [x] Add search by presenter/project name
* [x] Add table columns for quick review
* [x] Add quick status update action
* [x] Add detail drawer or detail page

### H2. Intake Checklist

* [x] Add simple checklist block in UI:

  * [x] profile complete
  * [x] organization info complete
  * [x] required files uploaded
  * [x] industry selected
  * [x] summary completed
  * [x] contact info valid
* [x] Show checklist pass/fail visually
* [x] Let secretary/admin write intake note
* [x] Allow return for correction with note

### H3. Intake Permissions

* [x] Secretary can review and return incomplete
* [x] Manager can mark eligible/rejected
* [x] Presenter cannot change admin intake decisions directly
* [x] Super admin/admin can override

---

## I. Presenter Portal UI

### I1. Presenter Navigation

* [ ] Add presenter sidebar/menu items
* [ ] My Profile
* [ ] My Organization
* [ ] My Team
* [ ] My Submissions
* [ ] Submission Details

### I2. Submission UX

* [x] Show current season open for applications
* [x] Show draft count
* [x] Show submitted count
* [x] Show returned submissions needing correction
* [x] Add clear CTA: Create New Submission
* [x] Add progress indicator on multi-step form

### I3. Usability

* [x] Add autosave if possible
* [ ] Show validation errors clearly
* [ ] Show uploaded file previews where possible
* [ ] Show strong success messages
* [ ] Show status badge consistently across all pages

---

## J. Admin / Operations Dashboard

### J1. Basic Negadras Metrics

* [x] Total submissions
* [x] Submissions by status
* [ ] Submissions by industry
* [x] Current active season
* [x] Returned incomplete count
* [x] Eligible count

### J2. Quick Actions

* [ ] Create season
* [ ] Create stage
* [ ] Create industry
* [x] View intake queue
* [ ] View recent submissions

### J3. Recent Activity

* [x] Recently submitted projects
* [ ] Recently changed statuses
* [ ] Recently created applicants
* [ ] Recently uploaded files

---

## K. Validation, Policies, and Business Rules

### K1. Form Requests

* [ ] Create request validation for season
* [ ] Create request validation for stage
* [ ] Create request validation for industry
* [x] Create request validation for applicant profile
* [ ] Create request validation for organization
* [ ] Create request validation for submission
* [ ] Create request validation for file upload
* [ ] Create request validation for status update

### K2. Policies

* [ ] ApplicantPolicy
* [ ] OrganizationPolicy
* [ ] SubmissionPolicy
* [ ] SubmissionFilePolicy
* [ ] SeasonPolicy
* [ ] StagePolicy
* [ ] IndustryPolicy

### K3. Business Rules

* [ ] Only active/open season can accept submissions
* [ ] Submission must belong to exactly one season
* [ ] Submission must belong to one industry
* [ ] Final submission requires minimum mandatory fields
* [ ] Returned incomplete submission can be edited and re-submitted
* [ ] Eligible/rejected submissions require authorized staff action

---

## L. Testing Checklist

### L1. Feature Tests

* [ ] Presenter can create profile
* [ ] Presenter can create organization
* [ ] Presenter can create draft submission
* [ ] Presenter can submit final submission
* [ ] Unauthorized user cannot access another presenter’s submission
* [ ] Secretary can update intake status
* [ ] Manager can mark eligible
* [ ] Status history is created on status change
* [ ] File upload validation works
* [ ] Closed season blocks new submission

### L2. Browser / UI Tests

* [ ] Submission form works on desktop
* [ ] Submission form works on tablet
* [ ] File upload UX is acceptable
* [ ] Intake list filters work
* [ ] Status badges appear correctly

---

# Phase 1 Suggested Build Order

This is the order I recommend so you do not get blocked:

## Step 1

* [ ] seasons
* [ ] stages
* [ ] industries

## Step 2

* [x] applicants
* [x] organizations
* [x] team_members
* [x] social_links

## Step 3

* [ ] submissions
* [ ] submission create/edit/show
* [ ] draft/final submit flow

## Step 4

* [ ] submission_files
* [ ] upload component
* [ ] storage and authorization

## Step 5

* [ ] submission_versions
* [ ] status_history
* [ ] status update service

## Step 6

* [ ] intake review screens
* [ ] filters
* [ ] checklist
* [ ] role-based actions

## Step 7

* [ ] presenter portal polish
* [ ] operations dashboard
* [ ] testing and cleanup

---

# Suggested Git Milestones for Phase 1

You said before you like roadmap-style development, so here are useful commit milestone ideas:

* [ ] `init negadras competition structure module`
* [x] `add applicant organization and team management`
* [ ] `build negadras submission draft and final submit flow`
* [ ] `implement submission file upload and secure access`
* [ ] `add submission versioning and status history`
* [ ] `build intake management workflow for secretary and manager`
* [ ] `add presenter portal and operations dashboard summaries`
* [ ] `test and refine phase 1 submission foundation`

---

# Phase 1 Done Definition

You can mark **Phase 1 complete** only when all of these are true:

* [ ] active season can be created
* [ ] stages and industries are manageable
* [x] presenter/applicant profile works
* [x] organization and team data works
* [ ] submission draft and final submit flow works
* [ ] file upload works securely
* [ ] status history is tracked
* [ ] intake staff can review and update basic submission readiness
* [ ] presenter can see returned/rejected/eligible result clearly
* [ ] permissions are correctly enforced
* [ ] core feature tests pass

---

# Recommendation for your next immediate development action

Start by implementing only this first block:

* [ ] seasons
* [ ] stages
* [ ] industries
* [ ] admin CRUD pages for these three

Because these three become the foundation for almost every later Negadras feature.

In the next message, I can turn **Phase 1** into a more practical **day-by-day implementation plan** with:

* database first
* backend first
* frontend pages
* routes
* controllers
* policies
* test sequence




Absolutely. Below is the **full phase-by-phase tracked roadmap** from **Phase 2 onward**, in the same checkbox style so you can develop and tick tasks one by one.

You already have:

* auth
* 2FA
* RBAC basics
* and now Phase 1 scope

So these next phases start from the **actual Negadras business features**.

---

# Phase 2 — Screening & Reviewer Workflow

## Phase 2 Goal

Build the layer between simple submission intake and official judging.

At the end of this phase, the system should support:

* reviewer assignment
* screening workflow
* reviewer notes and decisions
* shortlist recommendations
* handoff from screening to judging preparation

This is the phase that makes the app feel like a **real competition platform** rather than just a submission portal.

---

## Phase 2 Main Modules

* Reviewer Assignment Module
* Screening Review Module
* Technical Review Module
* Shortlist Recommendation Module
* Review Decision Tracking Module
* Review Queue Dashboard

---

## Phase 2 Database Scope

Add these entities:

* reviewer_profiles
* reviewer_assignments
* screening_reviews
* technical_reviews
* review_decisions
* shortlist_records
* submission_review_notes

---

## Phase 2 Checklist

## A. Reviewer Foundation

### A1. Reviewer Profile

* [x] Create `reviewers` table or reviewer profile extension
* [x] Add fields: `user_id`, `professional_title`, `organization`, `specialization`, `bio`, `is_active`
* [x] Create Reviewer model
* [x] Link reviewer to user
* [x] Create reviewer list page for admin
* [x] Create reviewer detail page
* [x] Add reviewer activation/deactivation
* [ ] Add reviewer specialization filters

### A2. Reviewer Permissions

* [x] Define reviewer permissions
* [x] Restrict reviewer visibility to assigned submissions only
* [x] Prevent reviewer from accessing judge-only interfaces
* [x] Add reviewer policy rules

---

## B. Reviewer Assignment

### B1. Assignment Table

* [x] Create `reviewer_assignments` migration
* [x] Add fields: `submission_id`, `reviewer_id`, `stage_id`, `assigned_at`, `due_at`, `status`
* [x] Create ReviewerAssignment model
* [x] Add assignment statuses: `assigned`, `in_progress`, `submitted`, `expired`, `cancelled`
* [x] Create assignment service
* [x] Create assignment UI for admin/manager
* [x] Add bulk assignment support
* [x] Add due date support
* [x] Add reviewer workload count display

### B2. Assignment Rules

* [x] Prevent duplicate active reviewer assignment for same submission/reviewer/stage
* [x] Allow reassignment by manager/admin
* [x] Log assignment changes
* [x] Notify reviewer on assignment

---

## C. Screening Review

### C1. Screening Review Table

* [x] Create `screening_reviews` migration
* [x] Add fields: `submission_id`, `reviewer_assignment_id`, `eligibility_status`, `recommendation`, `score_optional`, `notes`, `submitted_at`
* [x] Create ScreeningReview model
* [x] Create screening review form
* [x] Add structured eligibility checklist
* [x] Add recommendation choices:

  * [x] pass
  * [x] reject
  * [x] return_for_revision
  * [x] escalate
* [x] Add private reviewer note section
* [x] Add submit review action

### C2. Screening Form Logic

* [x] Prevent final submission without required checklist items
* [x] Allow save as draft
* [x] Mark reviewer assignment `in_progress` on first edit
* [x] Mark reviewer assignment `submitted` on final submit
* [x] Lock submitted review unless manager reopens

---

## D. Technical Review

### D1. Technical Review Table

* [x] Create `technical_reviews` migration
* [x] Add fields: `submission_id`, `reviewer_id`, `stage_id`, `innovation_score_optional`, `feasibility_score_optional`, `risk_note`, `strengths`, `weaknesses`, `recommendation`, `submitted_at`
* [x] Create TechnicalReview model
* [x] Build technical review form
* [x] Add structured fields for deeper evaluation
* [x] Add recommendation outcome
* [x] Allow manager to assign technical expert separately from screening reviewer

### D2. Technical Review Rules

* [x] Allow multiple technical reviews if needed
* [x] Show comparison of multiple review recommendations to manager
* [x] Add review summary view for manager/admin

---

## E. Review Decisions & Shortlisting

### E1. Review Decisions

* [x] Create `review_decisions` migration
* [x] Add fields: `submission_id`, `stage_id`, `decision_type`, `decision_reason`, `decided_by`, `decided_at`
* [x] Create ReviewDecision model
* [x] Add decision options:

  * [x] shortlisted
  * [x] rejected
  * [x] returned_for_revision
  * [x] needs_more_review
* [x] Add decision service to update submission status
* [x] Log decision in status history

### E2. Shortlist Records

* [x] Create `shortlist_records` migration
* [x] Add fields: `submission_id`, `stage_id`, `rank_order_optional`, `notes`, `created_by`
* [x] Create ShortlistRecord model
* [x] Create shortlist list page
* [x] Add shortlist export support placeholder
* [x] Add manager approval flow for shortlist

---

## F. Reviewer Workspace

### F1. Reviewer Dashboard

* [x] Create reviewer dashboard
* [x] Show assigned submissions
* [x] Show due reviews
* [x] Show completed reviews
* [x] Show overdue assignments
* [x] Show quick filters by stage and status

### F2. Reviewer Submission Detail View

* [x] Show presenter/applicant information
* [x] Show organization/team information
* [x] Show submission summary
* [x] Show uploaded files
* [x] Show version information
* [x] Show prior intake notes if allowed
* [x] Show review form entry point

---

## G. Manager Review Queue

### G1. Queue Page

* [x] Create screening queue page for manager/admin
* [x] Show pending screening reviews
* [x] Show technical review status
* [x] Show submissions waiting for decision
* [x] Add filters by reviewer, stage, industry, recommendation

### G2. Queue Actions

* [x] assign reviewer
* [x] reassign reviewer
* [x] view review details
* [x] make final screening decision
* [x] shortlist submission
* [x] reject submission
* [x] request revision

---

## H. Notifications

* [x] Notify reviewer on assignment
* [x] Notify manager on submitted review
* [x] Notify presenter when revision requested
* [x] Notify presenter on rejection or shortlist result
* [x] Add in-app notifications for overdue reviews

---

## I. Testing

* [x] Reviewer can only see assigned submissions
* [x] Reviewer can submit screening review
* [x] Reviewer can save draft review
* [x] Manager can shortlist based on reviews
* [x] Decision updates submission status
* [x] Duplicate assignment prevention works
* [x] Reviewer overdue state behaves correctly

---

## Phase 2 Done Definition

* [x] reviewers exist as real users with workflow
* [x] submissions can be assigned for screening
* [x] reviews can be submitted and tracked
* [x] manager can make shortlist decisions
* [x] revision/rejection/shortlist outcomes are functional
* [x] reviewer dashboard works
* [x] review queue works
* [x] tests pass
* [x] Phase 2 screening and reviewer workflow is complete

---

# Phase 3 — Judge, Panel & Rubric Engine

## Phase 3 Goal

Build the official evaluation engine used by judges.

At the end of this phase, the system should support:

* judge profiles
* panels
* rubric templates
* criteria and weights
* submission-to-panel assignment
* private judge scoring
* judge comments
* score visibility and locking rules

---

## Phase 3 Main Modules

* Judge Management
* Panel Management
* Rubric & Criteria Module
* Panel Assignment Module
* Judge Scoring Workspace
* Score Visibility/Lock Module

---

## Phase 3 Database Scope

Add:

* judges
* panels
* panel_members
* panel_submission_assignments
* rubrics
* rubric_criteria
* rubric_stage_bindings
* rubric_industry_bindings
* score_entries
* judge_comments
* score_locks
* score_visibility_events
* conflict_of_interest_declarations

---

## Phase 3 Checklist

## A. Judge Foundation

### A1. Judge Profile

* [x] Create `judges` migration
* [x] Add fields: `user_id`, `professional_title`, `organization`, `specialization`, `bio`, `is_active`
* [x] Create Judge model
* [x] Create judge CRUD for admin
* [x] Add judge expertise tags or specialization fields
* [x] Add judge activation/deactivation

### A2. Judge Permissions

* [x] Define judge-specific permissions
* [x] Restrict judges to assigned submissions/panels
* [x] Prevent judges from seeing admin-only review decisions unless allowed

---

## B. Panels

### B1. Panel Table

* [x] Create `panels` migration
* [x] Add fields: `season_id`, `stage_id`, `name`, `description`, `status`
* [x] Create Panel model
* [x] Create panel CRUD
* [x] Add season/stage relationships

### B2. Panel Members

* [x] Create `panel_members` migration
* [x] Add fields: `panel_id`, `judge_id`, `role_in_panel`, `display_order`
* [x] Create PanelMember model
* [x] Add judge-to-panel assignment UI
* [x] Prevent duplicate judge in same panel
* [x] Add panel chair support

---

## C. Conflict of Interest

### C1. Declaration Table

* [x] Create `conflict_of_interest_declarations` migration
* [x] Add fields: `judge_id`, `submission_id`, `session_id_optional`, `conflict_type`, `description`, `declared_at`, `status`
* [x] Create model
* [x] Add declaration UI
* [x] Allow manager/admin review
* [x] Exclude conflicted judge from scoring if required

---

## D. Rubrics

### D1. Rubric Main Table

* [x] Create `rubrics` migration
* [x] Add fields: `name`, `description`, `industry_id_optional`, `stage_id_optional`, `total_weight`, `is_active`
* [x] Create Rubric model
* [x] Create rubric CRUD
* [x] Allow stage-level or industry-level rubric use

### D2. Rubric Criteria

* [x] Create `rubric_criteria` migration
* [x] Add fields: `rubric_id`, `name`, `description`, `max_score`, `weight`, `order_index`, `is_required`, `visibility_rule`
* [x] Create RubricCriterion model
* [x] Build add/remove/reorder criteria UI
* [x] Validate total weight logic
* [x] Add criterion examples/help text support

---

## E. Panel Submission Assignment

### E1. Assignment Table

* [x] Create `panel_submission_assignments` migration
* [x] Add fields: `panel_id`, `submission_id`, `session_id_optional`, `assigned_at`, `status`
* [x] Create model
* [x] Build assignment UI
* [ ] Allow bulk assign shortlisted submissions to panel
* [x] Prevent duplicate same-panel assignment

### E2. Assignment Rules

* [ ] Only shortlisted or approved submissions can move here
* [ ] Assignment should update submission stage/status appropriately
* [ ] Show assignment timeline in submission detail

---

## F. Score Engine

### F1. Score Entry Table

* [x] Create `score_entries` migration
* [x] Add fields: `submission_id`, `session_id_optional`, `panel_id_optional`, `judge_id`, `rubric_criterion_id`, `score_value`, `comment`, `is_secret`, `is_locked`, `submitted_at`
* [x] Create ScoreEntry model
* [x] Add unique index to avoid duplicate score per criterion/judge/submission/session
* [x] Build score calculation service
* [x] Add per-judge score total calculation
* [x] Add aggregate score calculation

### F2. Judge Comments

* [x] Create `judge_comments` migration
* [x] Add fields: `submission_id`, `judge_id`, `session_id_optional`, `comment_type`, `content`, `is_archived`, `created_at`
* [x] Create model
* [ ] Support comment types:

  * [x] private
  * [ ] internal
  * [x] presenter_visible
  * [ ] public
* [x] Build comment entry UI

### F3. Score Locking

* [x] Create `score_locks` migration
* [x] Add fields for lock event
* [x] Build lock scores action
* [x] Prevent editing locked scores
* [x] Allow admin reopen with reason
* [x] Log reopen event

### F4. Score Visibility Events

* [x] Create `score_visibility_events` migration
* [x] Log reveal action
* [x] Log hide action
* [x] Track who changed visibility

---

## G. Judge Workspace

### G1. Judge Dashboard

* [x] Show assigned panels
* [x] Show assigned submissions
* [x] Show pending scores
* [x] Show completed scores
* [ ] Show schedule if session linked

### G2. Judge Submission Detail

* [x] Show presenter/applicant summary
* [x] Show organization/team info
* [x] Show project summary
* [x] Show files and links
* [ ] Show technical review summary if allowed
* [x] Show rubric criteria
* [x] Add score input fields
* [x] Add judge note/comment fields
* [x] Add save draft and submit score actions

---

## H. Testing

* [x] Judge can only score assigned submission
* [x] Conflict declaration blocks scoring if configured
* [x] Rubric criteria weights behave correctly
* [x] Aggregate scoring works
* [x] Locked score cannot be edited
* [x] Reveal action is logged
* [x] Presenter-visible comments are stored properly

---

## Phase 3 Done Definition

* [x] judges exist with profiles
* [x] panels can be created
* [x] shortlisted submissions can be assigned to panels
* [x] rubrics and criteria work
* [x] judges can score privately
* [x] score calculation works
* [ ] comments work by visibility type
* [x] locking and visibility events work
* [x] tests pass

---

# Phase 4 — Live Session, Real-Time Dashboard & Broadcast Support

## Phase 4 Goal

Build the live operational layer used during presentation day.

At the end of this phase, the system should support:

* sessions
* presenter order
* judge tablet experience
* real-time score aggregation
* live dashboard
* moderator/production controls
* optional projection control

---

## Phase 4 Main Modules

* Session Scheduling
* Session Presenter Queue
* Judge Live Tablet View
* Live Aggregation Engine
* Moderator Dashboard
* Public/Studio Dashboard
* Projection Support

---

## Phase 4 Database Scope

Add:

* sessions
* session_presenters
* session_events
* dashboard_projection_sessions
* session_media
* live_status_snapshots

---

## Phase 4 Checklist

## A. Sessions

### A1. Session Table

* [x] Create session migration using `competition_sessions` to avoid collision with Laravel auth sessions
* [x] Add fields: `season_id`, `stage_id`, `panel_id_optional`, `name`, `session_type`, `scheduled_at`, `broadcasted_at`, `location`, `status`, `etv_video_url_optional`
* [x] Create CompetitionSession model
* [x] Create session CRUD
* [x] Add schedule management UI

### A2. Session Presenter Queue

* [x] Create `session_presenters` migration
* [x] Add fields: `session_id`, `submission_id`, `order_index`, `appearance_status`
* [x] Create model
* [x] Build reorder queue
* [x] Add current presenter indicator
* [x] Add next presenter preview

---

## B. Session Event Logging

* [x] Create `session_events` migration
* [x] Add fields: `session_id`, `event_type`, `payload_json`, `created_by`, `created_at`
* [x] Log actions such as:

  * [x] session started
  * [x] presenter started
  * [x] presenter ended
  * [x] scoring revealed
  * [x] session paused
  * [x] session completed

---

## C. Real-Time Engine

* [ ] Configure Laravel Reverb or equivalent
* [ ] Broadcast score updates
* [ ] Broadcast current presenter changes
* [ ] Broadcast reveal actions
* [ ] Broadcast judge submission status
* [ ] Create frontend listeners
* [x] Add fallback non-live polling plan if needed

Current implementation note:

* [x] Build `live_status_snapshots` + polling-first live engine as the MVP-safe transport layer

---

## D. Judge Live Tablet View

* [x] Create live scoring layout for tablets
* [x] Show current presenter details
* [ ] Show timer if needed
* [x] Show rubric scoring panel
* [x] Show current submission progress
* [x] Show save draft / submit buttons
* [x] Show lock/reveal status
* [x] Optimize touch-friendly UI

---

## E. Moderator / Production Dashboard

* [x] Create moderator dashboard
* [x] Show live session status
* [x] Show current presenter
* [x] Show judge completion indicators
* [x] Show aggregate score
* [x] Add start/pause/resume/end controls
* [x] Add presenter queue controls
* [x] Add reveal control
* [x] Add projection control placeholder

---

## F. Main Dashboard View

* [x] Create audience/studio dashboard
* [x] Show presenter/project title
* [x] Show category
* [x] Show aggregate score
* [ ] Show non-sensitive comments/highlights if allowed
* [x] Hide private judge details unless reveal policy allows

---

## G. Projection Support

* [x] Create `dashboard_projection_sessions` migration
* [x] Add fields for active projector/source
* [x] Build project request button placeholder
* [x] Add manager approval before projection
* [x] Log projection start/end events

This can be MVP-light at first.

---

## H. Testing

* [x] Live score updates appear correctly through polling snapshots
* [x] Judge tablet UI works on tablet resolution
* [x] Moderator controls change session state
* [x] Presenter order updates live
* [x] Reveal behavior respects privacy rules
* [x] Non-authorized users cannot control session

---

## Phase 4 Done Definition

* [x] sessions can be created and scheduled
* [x] presenters can be queued per session
* [x] judges can score in live mode
* [x] live dashboard updates work
* [x] moderator can control session flow
* [x] reveal flow works
* [x] tests pass

---

# Phase 5 — Feedback Packets, Ranking, Awards & Archive

## Phase 5 Goal

Build the post-judging layer.

At the end of this phase, the system should support:

* ranking snapshots
* finalization
* awards
* presenter feedback packets
* archive records
* public past-session pages

---

## Phase 5 Main Modules

* Ranking Engine
* Award Management
* Feedback Packet Builder
* Archive Vault
* Public Showcase / Past Season Pages

---

## Phase 5 Database Scope

Add:

* ranking_snapshots
* award_records
* presenter_feedback_packets
* archive_records
* session_highlights
* public_showcase_entries

---

## Phase 5 Checklist

## A. Ranking

### A1. Ranking Snapshot Table

* [x] Create `ranking_snapshots` migration
* [x] Add fields: `season_id`, `stage_id`, `session_id_optional`, `submission_id`, `aggregate_score`, `rank_position`, `tie_break_reason_optional`, `finalized_at`
* [x] Create model
* [x] Build ranking calculation service
* [x] Add stage-specific ranking generation
* [x] Add ranking review page for manager/admin

### A2. Tie-Break Rules

* [x] Implement tie-break rule service
* [x] Log tie-break reason
* [x] Allow manager final override with reason

---

## B. Awards

### B1. Award Records

* [x] Create `award_records` migration
* [x] Add fields: `season_id`, `submission_id`, `award_type`, `rank_position`, `prize_value_optional`, `notes`
* [x] Create model
* [x] Build award creation/finalization page
* [x] Link awards to public showcase

---

## C. Presenter Feedback Packets

### C1. Feedback Table

* [x] Create `presenter_feedback_packets` migration
* [x] Add fields: `submission_id`, `stage_id`, `summary`, `strengths`, `improvement_areas`, `next_step_guidance`, `generated_by`, `visibility_status`, `sent_at_optional`
* [x] Create model
* [x] Build feedback packet generation service
* [x] Pull in presenter-visible comments only
* [x] Pull in approved score summary if policy allows
* [x] Build feedback detail page for presenter
* [x] Add send notification action

---

## D. Archive

### D1. Archive Records

* [x] Create `archive_records` migration
* [x] Add fields: `submission_id`, `season_id`, `stage_id`, `session_id_optional`, `archived_at`, `archive_status`, `public_visibility`
* [x] Create model
* [x] Build archive generation action
* [x] Link scores/comments/session metadata
* [x] Create admin archive view

### D2. Session Highlights

* [x] Create `session_highlights` migration
* [x] Add highlight notes
* [x] Add summary blocks
* [x] Attach approved quotes if needed

---

## E. Public Showcase

### E1. Showcase Entries

* [x] Create `public_showcase_entries` migration
* [x] Add fields for title, subtitle, visibility, image, summary
* [x] Create model
* [x] Build public showcase page
* [x] Filter by season/industry/winner type
* [x] Add winner pages
* [x] Add archive detail pages

---

## F. Testing

* [x] ranking calculation works
* [x] tie-break logging works
* [x] feedback packet only includes allowed content
* [x] archive records are created
* [x] public visibility rules are respected

---

## Phase 5 Done Definition

* [x] rankings can be finalized
* [x] awards can be recorded
* [x] presenters can receive structured feedback
* [x] archive records exist
* [x] public showcase pages work
* [x] tests pass

---

# Phase 6 — Reporting, Exports, Notifications & Governance

## Phase 6 Goal

Make the app operationally strong and board-acceptable.

At the end of this phase, the system should support:

* reports
* exports
* stronger notifications
* audit logs
* override history
* governance visibility

---

## Phase 6 Main Modules

* Reports Dashboard
* Export Center
* Notifications Center
* Audit & Governance
* Override / Reopen Controls

---

## Phase 6 Database Scope

Add or expand:

* audit_logs
* export_jobs
* notification_logs
* override_events

---

## Phase 6 Checklist

## A. Reporting

* [x] Create reports dashboard
* [x] Total submissions by season
* [x] Submissions by stage
* [x] Submissions by industry
* [x] Reviewer completion rates
* [x] Judge completion rates
* [x] Score distribution reports
* [x] Shortlist funnel report
* [x] Winner summary report

## B. Exports

* [x] Export submissions list to Excel/CSV
* [x] Export shortlisted projects
* [x] Export scoring summary
* [x] Export feedback summary
* [x] Export awards report
* [x] Export archive summary

## C. Notifications

* [x] Strengthen notification templates
* [x] Add notification log tracking
* [x] Add scheduled reminders for judges/reviewers
* [x] Add session reminder notices
* [x] Add result publication notifications

## D. Audit & Governance

* [x] Expand `audit_logs`
* [x] Log score reopen
* [x] Log status overrides
* [x] Log assignment changes
* [x] Log visibility changes
* [x] Log archive/publication actions
* [x] Build audit log viewer page

## E. Override Controls

* [x] Build reopen score flow
* [x] Build override decision flow
* [x] Require reason for override
* [x] Restrict override permissions to senior roles
* [x] Show override history per submission/session

## F. Testing

* [x] exports produce correct data
* [x] audit entries are created for sensitive actions
* [x] notifications are logged
* [x] override permissions are enforced

---

## Phase 6 Done Definition

* [x] reports are useful
* [x] exports work
* [x] governance trail exists
* [x] override logic is controlled
* [x] tests pass

---

# Phase 7 — AI Judge Copilot & Advisory Layer

## Phase 7 Goal

Add an in-app AI copilot for judges and staff after the workflow is stable.

At the end of this phase, the system should support:

* grounded chat on a specific competitor submission
* PDF, document, and media review support
* transcript-aware video and audio analysis
* judge-side follow-up questions in real time during evaluation
* latest external research and news lookups with source labeling
* advisory summaries, risks, and draft feedback assistance

AI should remain advisory, not authoritative.

Primary implementation direction:

* use Laravel AI SDK for the in-app experience
* use conversation storage and agent patterns from the SDK
* keep MCP out of the core Phase 7 path unless external AI client access is explicitly needed later

---

## Phase 7 Main Modules

* AI Foundation & Agent Layer
* Submission AI Asset Ingestion
* Judge Copilot Chat
* Retrieval & Web Research
* Advisory Insights & Risk Flags
* Draft Feedback Assistant
* AI Governance

---

## Phase 7 Database Scope

Add:

* published Laravel AI SDK tables:
  * agent_conversations
  * agent_conversation_messages
* submission_ai_assets
* submission_ai_analyses
* submission_ai_source_references
* submission_ai_risk_flags

---

## Phase 7 Checklist

## A. AI Foundation

* [ ] Install `laravel/ai`
* [ ] Publish AI SDK config and migrations
* [ ] Configure provider and model defaults
* [ ] Define provider failover strategy
* [ ] Add Phase 7 env/config guidance
* [ ] Create base `JudgeSubmissionCopilot` agent
* [ ] Add explicit advisory-only instructions

## B. Submission AI Asset Ingestion

* [ ] Create `submission_ai_assets` migration
* [ ] Store source asset metadata:
  * [ ] file type
  * [ ] provider file id
  * [ ] transcript status
  * [ ] processing status
  * [ ] source hash / version marker
* [ ] Support PDF and office-style document ingestion
* [ ] Support image asset ingestion
* [ ] Support audio/video transcription pipeline
* [ ] Attach assets to a specific submission and version
* [ ] Restrict retrieval to authorized judges and staff

## C. Judge Copilot Chat

* [ ] Add judge copilot panel in the judging workspace
* [ ] Start a conversation per judge and submission
* [ ] Use AI SDK conversation storage
* [ ] Stream assistant responses in the UI
* [ ] Show source citations with each answer
* [ ] Keep conversation context submission-scoped
* [ ] Keep chat separate from official scoring inputs

## D. Retrieval & Web Research

* [ ] Add internal submission retrieval tool
* [ ] Ground answers on uploaded documents and transcripts
* [ ] Add optional external web research tool
* [ ] Mark external findings separately from internal facts
* [ ] Log all external-research lookups
* [ ] Add source reference records to persisted output
* [ ] Enforce allowlist / guardrails for external research

## E. Advisory Insights

* [ ] Create `submission_ai_analyses` migration
* [ ] Add insight types:
  * [ ] executive summary
  * [ ] missing information
  * [ ] claim checklist
  * [ ] market / competition note
  * [ ] legal / compliance caution note
  * [ ] financial-risk note
  * [ ] judge prep question suggestions
* [ ] Build judge insight sidebar on the submission
* [ ] Add regenerate / refresh action
* [ ] Label all insights as advisory

## F. AI Risk Flags

* [ ] Create `submission_ai_risk_flags` migration
* [ ] Add incomplete-submission warning
* [ ] Add unsupported-claim placeholder
* [ ] Add high-risk assumption placeholder
* [ ] Add duplicate / overlap warning placeholder
* [ ] Show flags without blocking official workflow

## G. Draft Feedback Assistance

* [ ] Use AI to propose draft presenter feedback
* [ ] Keep drafts editable by humans
* [ ] Require human review before sending
* [ ] Log who approved the final packet
* [ ] Keep original AI draft distinct from the sent version

## H. Governance & Testing

* [ ] Log AI conversation/session ownership
* [ ] Log AI generation requests and failures
* [ ] Log external research usage
* [ ] Ensure AI failures never block manual workflow
* [ ] Add tests for submission-scoped retrieval
* [ ] Add tests for conversation persistence
* [ ] Add tests for advisory labels
* [ ] Add tests for human approval requirement
* [ ] Add tests for role access boundaries

---

## Phase 7 Done Definition

* [ ] judges can chat with a grounded submission copilot
* [ ] uploaded documents and transcripts are actually used as context
* [ ] external research is clearly labeled and logged
* [ ] AI never replaces official score logic
* [ ] failed AI operations are safe
* [ ] human approval remains required where needed
* [ ] tests pass

---

# Phase 8 — Production Hardening & Deployment Readiness

## Phase 8 Goal

Prepare the app for real launch and board confidence.

---

## Phase 8 Checklist

## A. Security

* [ ] enforce 2FA for admin/judge/reviewer
* [ ] tighten policies
* [ ] signed/private file access
* [ ] sanitize uploads
* [ ] validate all file types

## B. Performance

* [ ] optimize queries
* [ ] add indexes
* [ ] queue heavy jobs
* [ ] cache safe reports/pages
* [ ] optimize real-time broadcasting usage

## C. Backup & Recovery

* [ ] database backup plan
* [ ] storage backup plan
* [ ] recovery test
* [ ] deployment rollback plan

## D. QA

* [ ] end-to-end testing
* [ ] tablet testing
* [ ] real session dry run
* [ ] pilot season simulation
* [ ] permissions regression tests

## E. Documentation

* [ ] admin usage guide
* [ ] judge quick guide
* [ ] reviewer quick guide
* [ ] secretary workflow guide
* [ ] deployment notes
* [ ] maintenance checklist

---

## Phase 8 Done Definition

* [ ] system is tested for real use
* [ ] pilot simulation passed
* [ ] backup/recovery plan exists
* [ ] operations docs exist
* [ ] production deployment is ready

---

# Full High-Level Sequence

Here is the simplest overall order:

* [x] Phase 1 — Competition Foundation & Submission Core
* [x] Phase 2 — Screening & Reviewer Workflow
* [x] Phase 3 — Judge, Panel & Rubric Engine
* [x] Phase 4 — Live Session & Real-Time Dashboard
* [x] Phase 5 — Feedback, Ranking, Awards & Archive
* [x] Phase 6 — Reporting, Exports & Governance
* [ ] Phase 7 — AI Intelligence Layer
* [ ] Phase 8 — Production Hardening & Deployment

---

# Recommended Practical Milestone Names

* [x] milestone 1: submission foundation complete
* [x] milestone 2: reviewer screening workflow complete
* [x] milestone 3: judge scoring engine complete
* [x] milestone 4: live session operations complete
* [x] milestone 5: archive and feedback complete
* [x] milestone 6: governance and reporting complete
* [ ] milestone 7: AI advisory layer complete
* [ ] milestone 8: production readiness complete

---

# My recommendation

For real development, do not open all phases at once.

Work in this order:

1. finish Phase 1 fully
2. build Phase 2 fully
3. then Phase 3

Because once reviewer workflow and judging engine are solid, the rest becomes much easier.

The best next thing after this is for me to convert all these phases into a **single master tracking document** you can use like a project checklist.
