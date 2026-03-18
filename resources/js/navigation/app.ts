import {
    Bell,
    BriefcaseBusiness,
    BookOpenText,
    CalendarRange,
    FileOutput,
    FileSpreadsheet,
    FileText,
    FolderOpen,
    LayoutGrid,
    Settings2,
    Shield,
    ScrollText,
    Users,
    Workflow,
} from 'lucide-vue-next';
import { index as activityLogsIndex } from '@/routes/activity-logs';
import { edit as adminSettingsEdit } from '@/routes/admin-settings';
import { dashboard } from '@/routes';
import { index as exportsIndex } from '@/routes/exports';
import { index as handbookIndex } from '@/routes/handbook';
import { index as industriesIndex } from '@/routes/industries';
import { index as mediaIndex } from '@/routes/media';
import { index as notificationsIndex } from '@/routes/notifications';
import { index as pagesIndex } from '@/routes/pages';
import { index as reportsIndex } from '@/routes/reports';
import { index as rolesIndex } from '@/routes/roles';
import { index as seasonsIndex } from '@/routes/seasons';
import { index as stagesIndex } from '@/routes/stages';
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
        ],
    },
    {
        title: 'Management',
        items: [
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
                title: 'Users',
                href: usersIndex(),
                icon: Users,
                permission: 'users.view',
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
