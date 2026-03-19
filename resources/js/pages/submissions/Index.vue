<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { AlertCircle, FolderKanban, Inbox, Plus, SquarePen } from 'lucide-vue-next';
import ActionIconLink from '@/components/admin/ActionIconLink.vue';
import EmptyState from '@/components/EmptyState.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import PageContainer from '@/components/PageContainer.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { edit as editApplicantProfile } from '@/routes/applicant-profile';
import { create as createSubmission, edit as editSubmission, index as submissionsIndex, show as showSubmission } from '@/routes/submissions';
import type { BreadcrumbItem, ManagedSubmission } from '@/types';

type Props = {
    hasApplicantProfile: boolean;
    openSeason: {
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
    } | null;
    submissionCounts: {
        draft: number;
        submitted: number;
        returned: number;
        total: number;
    };
    submissions: ManagedSubmission[];
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Submissions',
        href: submissionsIndex(),
    },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="My submissions" />

        <PageContainer>
            <PageHeader
                title="My submissions"
                description="Build drafts first, then send a final submission once the problem, solution, and business model sections are complete."
            >
                <template #actions>
                    <Button v-if="hasApplicantProfile && openSeason?.isOpenForApplications" as-child>
                        <Link :href="createSubmission()">
                            <Plus class="size-4" />
                            New submission
                        </Link>
                    </Button>
                    <Button v-else-if="!hasApplicantProfile" as-child>
                        <Link :href="editApplicantProfile()">
                            Complete presenter profile
                        </Link>
                    </Button>
                    <Button v-else type="button" variant="outline" disabled>
                        Open call currently closed
                    </Button>
                </template>
            </PageHeader>

            <section class="grid gap-4 xl:grid-cols-[1.05fr_0.95fr]">
                <div
                    v-if="openSeason"
                    class="rounded-[1.5rem] border border-border/70 bg-card/85 p-5 shadow-sm"
                >
                    <div class="flex flex-wrap items-center gap-2">
                        <StatusBadge :label="openSeason.statusLabel" :tone="openSeason.statusTone" />
                        <StatusBadge
                            :label="openSeason.registrationLabel"
                            :tone="openSeason.isOpenForApplications ? 'published' : 'review'"
                        />
                    </div>

                    <div class="mt-4">
                        <h2 class="text-lg font-semibold">{{ openSeason.name }} {{ openSeason.year }}</h2>
                        <p class="mt-2 text-sm leading-6 text-muted-foreground">
                            {{ openSeason.description || 'The current active season defines whether presenters can start new submissions.' }}
                        </p>
                        <div class="mt-4 grid gap-2 text-sm text-muted-foreground md:grid-cols-2">
                            <div>
                                Opens:
                                {{ openSeason.registrationOpenAt ? new Date(openSeason.registrationOpenAt).toLocaleString() : 'Immediately' }}
                            </div>
                            <div>
                                Closes:
                                {{ openSeason.registrationCloseAt ? new Date(openSeason.registrationCloseAt).toLocaleString() : 'No closing date set' }}
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    v-else
                    class="rounded-[1.5rem] border border-border/70 bg-card/85 p-5 shadow-sm"
                >
                    <div class="flex items-center gap-2 font-medium">
                        <Inbox class="size-4 text-muted-foreground" />
                        No active season
                    </div>
                    <p class="mt-3 text-sm leading-6 text-muted-foreground">
                        Negadras does not currently have an active season configured for presenters. You can still inspect past submissions here.
                    </p>
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="rounded-[1.5rem] border border-border/70 bg-card/85 p-5 shadow-sm">
                        <div class="text-sm text-muted-foreground">Drafts</div>
                        <div class="mt-1 text-3xl font-semibold">{{ submissionCounts.draft }}</div>
                        <p class="mt-2 text-sm text-muted-foreground">Editable records that still need final submission.</p>
                    </div>
                    <div class="rounded-[1.5rem] border border-border/70 bg-card/85 p-5 shadow-sm">
                        <div class="text-sm text-muted-foreground">Submitted</div>
                        <div class="mt-1 text-3xl font-semibold">{{ submissionCounts.submitted }}</div>
                        <p class="mt-2 text-sm text-muted-foreground">Items currently in review or already decided.</p>
                    </div>
                    <div class="rounded-[1.5rem] border border-border/70 bg-card/85 p-5 shadow-sm">
                        <div class="text-sm text-muted-foreground">Returned</div>
                        <div class="mt-1 text-3xl font-semibold">{{ submissionCounts.returned }}</div>
                        <p class="mt-2 text-sm text-muted-foreground">Submissions that need correction before resubmission.</p>
                    </div>
                    <div class="rounded-[1.5rem] border border-border/70 bg-card/85 p-5 shadow-sm">
                        <div class="text-sm text-muted-foreground">Total</div>
                        <div class="mt-1 text-3xl font-semibold">{{ submissionCounts.total }}</div>
                        <p class="mt-2 text-sm text-muted-foreground">All submission records under your presenter account.</p>
                    </div>
                </div>
            </section>

            <div v-if="!hasApplicantProfile" class="rounded-[1.5rem] border border-border/70 bg-card/85 p-6 shadow-sm">
                <EmptyState
                    title="Create your presenter profile first"
                    description="Negadras submissions belong to a presenter profile. Complete that profile before starting a draft."
                    :icon="FolderKanban"
                />

                <div class="mt-5 flex justify-center">
                    <Button as-child>
                        <Link :href="editApplicantProfile()">
                            Complete presenter profile
                        </Link>
                    </Button>
                </div>
            </div>

            <div v-else-if="submissions.length === 0" class="rounded-[1.5rem] border border-border/70 bg-card/85 p-6 shadow-sm">
                <EmptyState
                    title="No submissions yet"
                    description="Create the first draft to start the Negadras intake workflow."
                    :icon="FolderKanban"
                />

                <div
                    v-if="hasApplicantProfile && props.openSeason && !props.openSeason.isOpenForApplications"
                    class="mt-5 rounded-2xl border border-amber-200 bg-amber-50/85 p-4 text-sm text-amber-950"
                >
                    <div class="flex items-center gap-2 font-medium">
                        <AlertCircle class="size-4" />
                        New drafts are paused
                    </div>
                    <p class="mt-2 text-amber-900/90">
                        {{ props.openSeason.registrationLabel }}. Once the current season opens, the submission CTA becomes available again.
                    </p>
                </div>
            </div>

            <div v-else class="grid gap-4">
                <article
                    v-for="submission in submissions"
                    :key="submission.id"
                    class="rounded-[1.5rem] border border-border/70 bg-card/85 p-5 shadow-sm"
                >
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="space-y-3">
                            <div class="flex flex-wrap items-center gap-2">
                                <Link :href="showSubmission(submission.id)" class="text-lg font-semibold text-foreground hover:underline">
                                    {{ submission.title }}
                                </Link>
                                <StatusBadge :label="submission.statusLabel" :tone="submission.statusTone" />
                            </div>

                            <div class="grid gap-1 text-sm text-muted-foreground md:grid-cols-2">
                                <div>Season: {{ submission.seasonName || 'Not set' }}</div>
                                <div>Stage: {{ submission.stageName || 'Not set' }}</div>
                                <div>Industry: {{ submission.industryName || 'Not set' }}</div>
                                <div>Organization: {{ submission.organizationName || 'Independent presenter' }}</div>
                            </div>

                            <div class="text-sm text-muted-foreground">
                                <span>Updated {{ submission.updatedAt ? new Date(submission.updatedAt).toLocaleString() : 'N/A' }}</span>
                                <span v-if="submission.submittedAt"> · Submitted {{ new Date(submission.submittedAt).toLocaleString() }}</span>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-2">
                            <Button as-child variant="outline">
                                <Link :href="showSubmission(submission.id)">
                                    View
                                </Link>
                            </Button>
                            <ActionIconLink
                                v-if="submission.canEdit"
                                :href="editSubmission(submission.id)"
                                label="Edit submission"
                                :icon="SquarePen"
                            />
                        </div>
                    </div>
                </article>
            </div>
        </PageContainer>
    </AppLayout>
</template>
