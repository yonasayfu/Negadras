<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, SquarePen } from 'lucide-vue-next';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import PageContainer from '@/components/PageContainer.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { edit as editSubmission, index as submissionsIndex } from '@/routes/submissions';
import type { BreadcrumbItem, ManagedSubmission } from '@/types';

type Props = {
    submission: ManagedSubmission;
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Submissions',
        href: submissionsIndex(),
    },
    {
        title: props.submission.title,
        href: submissionsIndex(),
    },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="submission.title" />

        <PageContainer>
            <PageHeader
                :title="submission.title"
                description="This detail page shows the current intake state and the submission narrative exactly as Negadras staff will inspect it."
            >
                <template #actions>
                    <div class="flex items-center gap-2">
                        <StatusBadge :label="submission.statusLabel" :tone="submission.statusTone" />
                        <Button v-if="submission.currentVersionNumber" variant="outline" disabled>
                            Current version v{{ submission.currentVersionNumber }}
                        </Button>
                        <Button v-if="submission.canEdit" as-child variant="outline">
                            <Link :href="editSubmission(submission.id)">
                                <SquarePen class="size-4" />
                                Edit draft
                            </Link>
                        </Button>
                        <Button as-child variant="outline">
                            <Link :href="submissionsIndex()">
                                <ArrowLeft class="size-4" />
                                Back to submissions
                            </Link>
                        </Button>
                    </div>
                </template>
            </PageHeader>

            <div class="grid gap-6 lg:grid-cols-[1.2fr_0.8fr]">
                <section class="grid gap-6">
                    <div class="rounded-[1.5rem] border border-border/70 bg-card/85 p-5 shadow-sm">
                        <h2 class="text-base font-semibold">Summary</h2>
                        <p class="mt-3 whitespace-pre-wrap text-sm leading-6 text-muted-foreground">
                            {{ submission.summary || 'No summary provided yet.' }}
                        </p>
                    </div>

                    <div class="rounded-[1.5rem] border border-border/70 bg-card/85 p-5 shadow-sm">
                        <h2 class="text-base font-semibold">Problem statement</h2>
                        <p class="mt-3 whitespace-pre-wrap text-sm leading-6 text-muted-foreground">
                            {{ submission.problemStatement || 'No problem statement provided yet.' }}
                        </p>
                    </div>

                    <div class="rounded-[1.5rem] border border-border/70 bg-card/85 p-5 shadow-sm">
                        <h2 class="text-base font-semibold">Solution description</h2>
                        <p class="mt-3 whitespace-pre-wrap text-sm leading-6 text-muted-foreground">
                            {{ submission.solutionDescription || 'No solution description provided yet.' }}
                        </p>
                    </div>

                    <div class="rounded-[1.5rem] border border-border/70 bg-card/85 p-5 shadow-sm">
                        <h2 class="text-base font-semibold">Business model</h2>
                        <p class="mt-3 whitespace-pre-wrap text-sm leading-6 text-muted-foreground">
                            {{ submission.businessModel || 'No business model provided yet.' }}
                        </p>
                    </div>
                </section>

                <aside class="grid gap-6">
                    <div class="rounded-[1.5rem] border border-border/70 bg-card/85 p-5 shadow-sm">
                        <h2 class="text-base font-semibold">Submission facts</h2>

                        <dl class="mt-4 grid gap-4 text-sm">
                            <div>
                                <dt class="text-muted-foreground">Presenter</dt>
                                <dd class="font-medium">{{ submission.applicantName || 'Unknown presenter' }}</dd>
                                <dd class="text-muted-foreground">{{ submission.applicantEmail || 'No presenter email' }}</dd>
                            </div>
                            <div>
                                <dt class="text-muted-foreground">Season</dt>
                                <dd class="font-medium">{{ submission.seasonName || 'Not set' }}</dd>
                            </div>
                            <div>
                                <dt class="text-muted-foreground">Stage</dt>
                                <dd class="font-medium">{{ submission.stageName || 'Not set' }}</dd>
                            </div>
                            <div>
                                <dt class="text-muted-foreground">Industry</dt>
                                <dd class="font-medium">{{ submission.industryName || 'Not set' }}</dd>
                            </div>
                            <div>
                                <dt class="text-muted-foreground">Organization</dt>
                                <dd class="font-medium">{{ submission.organizationName || 'Independent presenter' }}</dd>
                            </div>
                            <div>
                                <dt class="text-muted-foreground">Public after approval</dt>
                                <dd class="font-medium">{{ submission.isPublicAfterApproval ? 'Yes' : 'No' }}</dd>
                            </div>
                            <div>
                                <dt class="text-muted-foreground">Submitted at</dt>
                                <dd class="font-medium">{{ submission.submittedAt ? new Date(submission.submittedAt).toLocaleString() : 'Draft only' }}</dd>
                            </div>
                            <div>
                                <dt class="text-muted-foreground">Last updated</dt>
                                <dd class="font-medium">{{ submission.updatedAt ? new Date(submission.updatedAt).toLocaleString() : 'N/A' }}</dd>
                            </div>
                        </dl>
                    </div>

                    <div class="rounded-[1.5rem] border border-border/70 bg-card/85 p-5 shadow-sm">
                        <div class="flex items-center justify-between gap-3">
                            <h2 class="text-base font-semibold">Version history</h2>
                            <span class="text-sm text-muted-foreground">{{ submission.versionCount ?? 0 }} version(s)</span>
                        </div>

                        <div v-if="(submission.versionHistory?.length ?? 0) === 0" class="mt-4 text-sm text-muted-foreground">
                            No locked versions yet. A version snapshot is created when the draft is finally submitted.
                        </div>

                        <ol v-else class="mt-4 grid gap-3">
                            <li
                                v-for="version in submission.versionHistory"
                                :key="version.id"
                                class="rounded-xl border border-border/70 bg-background/60 p-4"
                            >
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <div class="flex items-center gap-2">
                                        <div class="font-medium">v{{ version.versionNo }}</div>
                                        <StatusBadge
                                            :label="version.isCurrent ? 'Current version' : 'Locked version'"
                                            :tone="version.isCurrent ? 'published' : 'draft'"
                                        />
                                    </div>
                                    <div class="text-xs text-muted-foreground">
                                        {{ version.createdAt ? new Date(version.createdAt).toLocaleString() : 'Unknown time' }}
                                    </div>
                                </div>

                                <div class="mt-2 text-sm text-muted-foreground">
                                    {{ version.changeNote || 'No change note recorded.' }}
                                </div>

                                <div class="mt-3 grid gap-1 text-sm text-muted-foreground md:grid-cols-2">
                                    <div>Snapshot title: {{ version.snapshotTitle }}</div>
                                    <div>Snapshot status: {{ version.snapshotStatus }}</div>
                                    <div>Created by: {{ version.createdBy || 'System' }}</div>
                                    <div>Locked: {{ version.isLocked ? 'Yes' : 'No' }}</div>
                                </div>
                            </li>
                        </ol>
                    </div>
                </aside>
            </div>
        </PageContainer>
    </AppLayout>
</template>
