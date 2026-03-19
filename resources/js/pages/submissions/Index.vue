<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { FolderKanban, Plus, SquarePen } from 'lucide-vue-next';
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
    submissions: ManagedSubmission[];
};

defineProps<Props>();

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
                    <Button as-child>
                        <Link :href="createSubmission()">
                            <Plus class="size-4" />
                            New submission
                        </Link>
                    </Button>
                </template>
            </PageHeader>

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
