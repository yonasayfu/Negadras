import {
    Bell,
    BriefcaseBusiness,
    BookOpenText,
    Building2,
    CalendarRange,
    Cast,
    ClipboardCheck,
    FileOutput,
    Gem,
    FileSpreadsheet,
    FolderKanban,
    FileText,
    FolderOpen,
    Gavel,
    MessageSquareQuote,
    Trophy,
    IdCard,
    LayoutGrid,
    ListChecks,
    Scale,
    Settings2,
    Shield,
    ScrollText,
    Users,
    Workflow,
    ShieldAlert,
} from 'lucide-vue-next';
import { index as activityLogsIndex } from '@/routes/activity-logs';
import { index as adminSubmissionsIndex } from '@/routes/admin-submissions';
import { edit as adminSettingsEdit } from '@/routes/admin-settings';
import { dashboard } from '@/routes';
import { index as applicantsIndex } from '@/routes/applicants';
import { index as archiveIndex } from '@/routes/archive';
import { index as awardsIndex } from '@/routes/awards';
import { index as exportsIndex } from '@/routes/exports';
import { index as feedbackIndex } from '@/routes/feedback';
import { index as feedbackPacketsIndex } from '@/routes/feedback-packets';
import { index as governanceIndex } from '@/routes/governance';
import { index as handbookIndex } from '@/routes/handbook';
import { index as industriesIndex } from '@/routes/industries';
import { index as judgeLiveIndex } from '@/routes/judge-live';
import { index as judgeWorkspaceIndex } from '@/routes/judge-workspace';
import { index as judgesIndex } from '@/routes/judges';
import { index as mediaIndex } from '@/routes/media';
import { index as notificationsIndex } from '@/routes/notifications';
import { index as organizationsIndex } from '@/routes/organizations';
import { index as pagesIndex } from '@/routes/pages';
import { index as panelsIndex } from '@/routes/panels';
import { index as reportsIndex } from '@/routes/reports';
import { index as reviewerQueueIndex } from '@/routes/reviewer-queue';
import { index as reviewersIndex } from '@/routes/reviewers';
import { index as rankingsIndex } from '@/routes/rankings';
import { index as rolesIndex } from '@/routes/roles';
import { index as competitionSessionsIndex } from '@/routes/competition-sessions';
import { index as screeningQueueIndex } from '@/routes/screening-queue';
import { index as seasonsIndex } from '@/routes/seasons';
import { index as shortlistIndex } from '@/routes/shortlist';
import { index as stagesIndex } from '@/routes/stages';
import { index as submissionsIndex } from '@/routes/submissions';
import { index as technicalQueueIndex } from '@/routes/technical-queue';
import { index as technicalReviewerQueueIndex } from '@/routes/technical-reviewer-queue';
import { index as rubricsIndex } from '@/routes/rubrics';
import { index as usersIndex } from '@/routes/users';
import type { NavGroup } from '@/types';

export const appNavigation: NavGroup[] = [
    {
        title: 'Core',
        items: [
            {
                title: 'Dashboard',
                href: dashboard(),
                icon: LayoutGrid,
                permission: 'dashboard.view',
            },
            {
                title: 'Handbook',
                href: handbookIndex(),
                icon: BookOpenText,
                permission: 'handbook.view',
            },
        ],
    },
    {
        title: 'Insight',
        items: [
            {
                title: 'Notifications',
                href: notificationsIndex(),
                icon: Bell,
                permission: 'notifications.view',
            },
            {
                title: 'Activity logs',
                href: activityLogsIndex(),
                icon: ScrollText,
                permission: 'activity-logs.view',
            },
            {
                title: 'Reports',
                href: reportsIndex(),
                icon: FileSpreadsheet,
                permission: 'reports.view',
            },
            {
                title: 'Governance',
                href: governanceIndex(),
                icon: ShieldAlert,
                permission: 'governance.view',
            },
        ],
    },
    {
        title: 'Management',
        items: [
            {
                title: 'Applicants',
                href: applicantsIndex(),
                icon: IdCard,
                permission: 'applicants.view',
            },
            {
                title: 'Organizations',
                href: organizationsIndex(),
                icon: Building2,
                permission: 'organizations.view',
            },
            {
                title: 'Submissions',
                href: submissionsIndex(),
                icon: FolderKanban,
            },
            {
                title: 'Reviewer queue',
                href: reviewerQueueIndex(),
                icon: ListChecks,
                permission: 'reviewer-queue.view',
            },
            {
                title: 'Technical review queue',
                href: technicalReviewerQueueIndex(),
                icon: ListChecks,
                permission: 'technical-reviewer-queue.view',
            },
            {
                title: 'Judge workspace',
                href: judgeWorkspaceIndex(),
                icon: Gavel,
                permission: 'judge-workspace.view',
            },
            {
                title: 'Feedback packets',
                href: feedbackIndex(),
                icon: MessageSquareQuote,
            },
            {
                title: 'Judge live view',
                href: judgeLiveIndex(),
                icon: Cast,
                permission: 'judge-live.view',
            },
            {
                title: 'Seasons',
                href: seasonsIndex(),
                icon: CalendarRange,
                permission: 'seasons.view',
            },
            {
                title: 'Stages',
                href: stagesIndex(),
                icon: Workflow,
                permission: 'stages.view',
            },
            {
                title: 'Industries',
                href: industriesIndex(),
                icon: BriefcaseBusiness,
                permission: 'industries.view',
            },
            {
                title: 'Pages',
                href: pagesIndex(),
                icon: FileText,
                permission: 'pages.view',
            },
        ],
    },
    {
        title: 'Administration',
        items: [
            {
                title: 'Export center',
                href: exportsIndex(),
                icon: FileOutput,
                permission: 'exports.view',
            },
            {
                title: 'Settings',
                href: adminSettingsEdit(),
                icon: Settings2,
                permission: 'settings.view',
            },
            {
                title: 'Media',
                href: mediaIndex(),
                icon: FolderOpen,
                permission: 'media.view',
            },
            {
                title: 'Submission intake',
                href: adminSubmissionsIndex(),
                icon: FolderKanban,
                permission: 'submissions.view',
            },
            {
                title: 'Screening queue',
                href: screeningQueueIndex(),
                icon: ListChecks,
                permission: 'screening-queue.view',
            },
            {
                title: 'Technical queue',
                href: technicalQueueIndex(),
                icon: ListChecks,
                permission: 'technical-queue.view',
            },
            {
                title: 'Shortlist',
                href: shortlistIndex(),
                icon: FileSpreadsheet,
                permission: 'submissions.view',
            },
            {
                title: 'Rankings',
                href: rankingsIndex(),
                icon: Trophy,
                permission: 'rankings.view',
            },
            {
                title: 'Awards',
                href: awardsIndex(),
                icon: Gem,
                permission: 'awards.view',
            },
            {
                title: 'Feedback packets',
                href: feedbackPacketsIndex(),
                icon: MessageSquareQuote,
                permission: 'feedback-packets.view',
            },
            {
                title: 'Archive',
                href: archiveIndex(),
                icon: FileText,
                permission: 'archive.view',
            },
            {
                title: 'Users',
                href: usersIndex(),
                icon: Users,
                permission: 'users.view',
            },
            {
                title: 'Reviewers',
                href: reviewersIndex(),
                icon: ListChecks,
                permission: 'reviewers.view',
            },
            {
                title: 'Judges',
                href: judgesIndex(),
                icon: Gavel,
                permission: 'judges.view',
            },
            {
                title: 'Rubrics',
                href: rubricsIndex(),
                icon: ClipboardCheck,
                permission: 'rubrics.view',
            },
            {
                title: 'Panels',
                href: panelsIndex(),
                icon: Scale,
                permission: 'panels.view',
            },
            {
                title: 'Competition sessions',
                href: competitionSessionsIndex(),
                icon: CalendarRange,
                permission: 'competition-sessions.view',
            },
            {
                title: 'Roles',
                href: rolesIndex(),
                icon: Shield,
                permission: 'roles.view',
            },
        ],
    },
];
