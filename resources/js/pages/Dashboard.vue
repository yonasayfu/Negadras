<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { AlertCircle, ArrowRight, FileText, FolderKanban, Inbox, ShieldCheck } from 'lucide-vue-next';
import { computed } from 'vue';
import RecentActivityPanel from '@/components/admin/RecentActivityPanel.vue';
import StatCard from '@/components/admin/StatCard.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import PageContainer from '@/components/PageContainer.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { edit as editApplicantProfile } from '@/routes/applicant-profile';
import { index as adminSubmissionsIndex } from '@/routes/admin-submissions';
import { create as createSubmission, index as submissionsIndex, show as showSubmission } from '@/routes/submissions';
import type { Auth, BreadcrumbItem } from '@/types';

type Metric = {
    key: string;
    label: string;
    value: number;
    description: string;
    tone: 'amber' | 'sky' | 'emerald' | 'violet';
};

type ActivityItem = {
    id: number;
    event: string;
    description: string;
    createdAt: string | null;
};

type SeasonSummary = {
    id: number;
    name: string;
    year: number;
    description: string | null;
    registrationOpenAt: string | null;
    registrationCloseAt: string | null;
    isOpenForApplications: boolean;
    registrationLabel: string;
    statusLabel: string;
    statusTone: string;
};

type PresenterPortal = {
    hasApplicantProfile: boolean;
    canCreateSubmission: boolean;
    counts: {
        draft: number;
        submitted: number;
        returned: number;
        total: number;
    };
    recentSubmissions: Array<{
        id: number;
        title: string;
        seasonName: string | null;
        statusLabel: string;
        statusTone: string;
        updatedAt: string | null;
    }>;
};

type Operations = {
    metrics: Metric[];
    submissionBreakdown: Array<{
        label: string;
        value: number;
    }>;
    recentSubmissions: Array<{
        id: number;
        title: string;
        presenterName: string | null;
        seasonName: string | null;
        statusLabel: string;
        statusTone: string;
        updatedAt: string | null;
    }>;
};

type Props = {
    currentSeason: SeasonSummary | null;
    presenterPortal: PresenterPortal | null;
    operations: Operations;
    recentActivity: ActivityItem[];
    platformHealth: Metric[];
};

defineProps<Props>();

const page = usePage();
const auth = computed(() => page.props.auth as Auth);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
    },
];

const canViewIntakeQueue = computed(() => auth.value.permissions.includes('submissions.view'));
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <PageContainer class="bg-[radial-gradient(circle_at_top_left,#fef3c7,transparent_28%),radial-gradient(circle_at_top_right,#dbeafe,transparent_24%)]">
            <PageHeader
                title="Negadras workspace"
                description="Track the current open call, keep presenter drafts moving, and monitor intake operations from one dashboard."
            />

            <section
                v-if="currentSeason"
                class="rounded-[1.75rem] border border-border/70 bg-card/90 p-6 shadow-sm backdrop-blur"
            >
                <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                    <div class="space-y-3">
                        <div class="flex flex-wrap items-center gap-2">
                            <StatusBadge :label="currentSeason.statusLabel" :tone="currentSeason.statusTone" />
                            <StatusBadge
                                :label="currentSeason.registrationLabel"
                                :tone="currentSeason.isOpenForApplications ? 'published' : 'review'"
                            />
                        </div>
                        <div>
                            <h2 class="text-2xl font-semibold">{{ currentSeason.name }} {{ currentSeason.year }}</h2>
                            <p class="mt-2 max-w-3xl text-sm leading-6 text-muted-foreground">
                                {{ currentSeason.description || 'The current active season is ready for presenter and intake operations.' }}
                            </p>
                        </div>
                    </div>

                    <dl class="grid gap-3 rounded-2xl border border-border/70 bg-background/70 p-4 text-sm md:grid-cols-2">
                        <div>
                            <dt class="text-muted-foreground">Applications open</dt>
                            <dd class="font-medium">
                                {{ currentSeason.registrationOpenAt ? new Date(currentSeason.registrationOpenAt).toLocaleString() : 'Immediately' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">Applications close</dt>
                            <dd class="font-medium">
                                {{ currentSeason.registrationCloseAt ? new Date(currentSeason.registrationCloseAt).toLocaleString() : 'No closing date set' }}
                            </dd>
                        </div>
                    </dl>
                </div>
            </section>

            <section v-if="presenterPortal" class="grid gap-4 xl:grid-cols-[1.1fr_0.9fr]">
                <div class="space-y-4">
                    <div class="rounded-[1.75rem] border border-border/70 bg-card/90 p-6 shadow-sm backdrop-blur">
                        <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                            <div>
                                <h2 class="text-xl font-semibold">Presenter portal</h2>
                                <p class="mt-2 text-sm leading-6 text-muted-foreground">
                                    Keep drafts moving, act on returned submissions quickly, and use the current open call as your submission boundary.
                                </p>
                            </div>

                            <div class="flex flex-wrap items-center gap-3">
                                <Button
                                    v-if="presenterPortal.hasApplicantProfile && presenterPortal.canCreateSubmission"
                                    as-child
                                >
                                    <Link :href="createSubmission()">
                                        <FolderKanban class="size-4" />
                                        Create new submission
                                    </Link>
                                </Button>

                                <Button v-else-if="!presenterPortal.hasApplicantProfile" as-child>
                                    <Link :href="editApplicantProfile()">
                                        Complete presenter profile
                                    </Link>
                                </Button>

                                <Button
                                    v-else
                                    type="button"
                                    variant="outline"
                                    disabled
                                >
                                    Open call currently closed
                                </Button>
                            </div>
                        </div>

                        <div class="mt-6 grid gap-4 md:grid-cols-4">
                            <StatCard
                                label="Total submissions"
                                :value="presenterPortal.counts.total"
                                description="All drafts and submitted records tied to your presenter profile."
                                tone="amber"
                            />
                            <StatCard
                                label="Drafts"
                                :value="presenterPortal.counts.draft"
                                description="Editable drafts that still need final submission."
                                tone="sky"
                            />
                            <StatCard
                                label="Submitted"
                                :value="presenterPortal.counts.submitted"
                                description="Items currently in organizer hands or already decided."
                                tone="emerald"
                            />
                            <StatCard
                                label="Returned"
                                :value="presenterPortal.counts.returned"
                                description="Submissions that need your correction before resubmission."
                                tone="violet"
                            />
                        </div>
                    </div>

                    <div
                        v-if="!presenterPortal.hasApplicantProfile"
                        class="rounded-[1.5rem] border border-amber-200 bg-amber-50/85 p-5 text-sm text-amber-950 shadow-sm"
                    >
                        <div class="flex items-center gap-2 font-medium">
                            <AlertCircle class="size-4" />
                            Presenter profile required
                        </div>
                        <p class="mt-2 leading-6 text-amber-900/90">
                            Negadras cannot attach a submission to your account until the presenter profile exists. Complete it first, then start a draft.
                        </p>
                    </div>

                    <div
                        v-else-if="currentSeason && !currentSeason.isOpenForApplications"
                        class="rounded-[1.5rem] border border-sky-200 bg-sky-50/85 p-5 text-sm text-sky-950 shadow-sm"
                    >
                        <div class="flex items-center gap-2 font-medium">
                            <Inbox class="size-4" />
                            Open call visibility
                        </div>
                        <p class="mt-2 leading-6 text-sky-900/90">
                            {{ currentSeason.registrationLabel }}. You can still review existing records, but new submission CTA stays disabled until the call opens again.
                        </p>
                    </div>
                </div>

                <div class="rounded-[1.75rem] border border-border/70 bg-card/90 p-6 shadow-sm backdrop-blur">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-semibold">My recent submissions</h2>
                            <p class="text-sm text-muted-foreground">The latest records tied to your presenter profile.</p>
                        </div>

                        <Button as-child variant="outline">
                            <Link :href="submissionsIndex()">
                                View all
                            </Link>
                        </Button>
                    </div>

                    <div
                        v-if="presenterPortal.recentSubmissions.length === 0"
                        class="mt-5 rounded-2xl border border-dashed border-border/70 bg-background/50 p-5 text-sm text-muted-foreground"
                    >
                        No presenter submissions yet. Use the CTA above once the open call and your profile are ready.
                    </div>

                    <div v-else class="mt-5 grid gap-3">
                        <Link
                            v-for="submission in presenterPortal.recentSubmissions"
                            :key="submission.id"
                            :href="showSubmission(submission.id)"
                            class="rounded-2xl border border-border/70 bg-background/70 p-4 transition hover:border-foreground/15"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="font-medium">{{ submission.title }}</div>
                                    <div class="mt-1 text-sm text-muted-foreground">{{ submission.seasonName || 'No season attached' }}</div>
                                </div>
                                <StatusBadge :label="submission.statusLabel" :tone="submission.statusTone" />
                            </div>
                            <div class="mt-3 text-xs text-muted-foreground">
                                Updated {{ submission.updatedAt ? new Date(submission.updatedAt).toLocaleString() : 'N/A' }}
                            </div>
                        </Link>
                    </div>
                </div>
            </section>

            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <StatCard
                    v-for="metric in operations.metrics"
                    :key="metric.key"
                    :label="metric.label"
                    :value="metric.value"
                    :description="metric.description"
                    :tone="metric.tone"
                />
            </section>

            <section class="grid gap-4 xl:grid-cols-[1.15fr_0.85fr]">
                <div class="space-y-4">
                    <section class="rounded-[1.5rem] border border-border/70 bg-card/90 p-6 shadow-sm backdrop-blur">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <h2 class="text-lg font-semibold">Intake and review breakdown</h2>
                                <p class="text-sm text-muted-foreground">Operational status totals across all submissions.</p>
                            </div>

                            <Button v-if="canViewIntakeQueue" as-child variant="outline">
                                <Link :href="adminSubmissionsIndex()">
                                    View intake queue
                                </Link>
                            </Button>
                        </div>

                        <div class="mt-5 grid gap-3 md:grid-cols-2">
                            <div
                                v-for="item in operations.submissionBreakdown"
                                :key="item.label"
                                class="rounded-2xl border border-border/70 bg-background/70 px-4 py-4"
                            >
                                <div class="text-sm text-muted-foreground">{{ item.label }}</div>
                                <div class="mt-1 text-2xl font-semibold">{{ item.value }}</div>
                            </div>
                        </div>
                    </section>

                    <section class="rounded-[1.5rem] border border-border/70 bg-card/90 p-6 shadow-sm backdrop-blur">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <h2 class="text-lg font-semibold">Recent submission activity</h2>
                                <p class="text-sm text-muted-foreground">Operationally relevant changes across the intake queue.</p>
                            </div>
                        </div>

                        <div
                            v-if="operations.recentSubmissions.length === 0"
                            class="mt-5 rounded-2xl border border-dashed border-border/70 bg-background/50 p-5 text-sm text-muted-foreground"
                        >
                            No submissions have been recorded yet.
                        </div>

                        <div v-else class="mt-5 grid gap-3">
                            <div
                                v-for="submission in operations.recentSubmissions"
                                :key="submission.id"
                                class="rounded-2xl border border-border/70 bg-background/70 p-4"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <div class="font-medium">{{ submission.title }}</div>
                                        <div class="mt-1 text-sm text-muted-foreground">
                                            {{ submission.presenterName || 'Unknown presenter' }}
                                            <span v-if="submission.seasonName"> · {{ submission.seasonName }}</span>
                                        </div>
                                    </div>
                                    <StatusBadge :label="submission.statusLabel" :tone="submission.statusTone" />
                                </div>
                                <div class="mt-3 text-xs text-muted-foreground">
                                    Updated {{ submission.updatedAt ? new Date(submission.updatedAt).toLocaleString() : 'N/A' }}
                                </div>
                            </div>
                        </div>
                    </section>
                </div>

                <div class="space-y-4">
                    <RecentActivityPanel :items="recentActivity" />

                    <section class="rounded-[1.5rem] border border-border/70 bg-card/90 p-6 shadow-sm backdrop-blur">
                        <div class="flex items-center gap-2">
                            <ShieldCheck class="size-4 text-muted-foreground" />
                            <h2 class="text-lg font-semibold">Platform baseline</h2>
                        </div>

                        <div class="mt-5 grid gap-3">
                            <div
                                v-for="item in platformHealth"
                                :key="item.key"
                                class="rounded-2xl border border-border/70 bg-background/70 px-4 py-4"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <div class="text-sm text-muted-foreground">{{ item.label }}</div>
                                        <div class="mt-1 text-2xl font-semibold">{{ item.value }}</div>
                                    </div>
                                    <FileText class="size-4 text-muted-foreground" />
                                </div>
                                <p class="mt-2 text-sm text-muted-foreground">{{ item.description }}</p>
                            </div>
                        </div>

                        <div class="mt-5">
                            <Button as-child variant="outline">
                                <Link :href="submissionsIndex()">
                                    Open submissions workspace
                                    <ArrowRight class="size-4" />
                                </Link>
                            </Button>
                        </div>
                    </section>
                </div>
            </section>
        </PageContainer>
    </AppLayout>
</template>
