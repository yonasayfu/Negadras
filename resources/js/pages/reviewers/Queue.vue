<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ClipboardCheck, Eye } from 'lucide-vue-next';
import EmptyState from '@/components/EmptyState.vue';
import PageContainer from '@/components/PageContainer.vue';
import PageHeader from '@/components/PageHeader.vue';
import ResourceToolbar from '@/components/admin/ResourceToolbar.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as reviewerQueueIndex, show as showReviewerAssignment } from '@/routes/reviewer-queue';
import type { BreadcrumbItem, ManagedReviewerAssignment, SelectOption } from '@/types';

type Props = {
    assignments: ManagedReviewerAssignment[];
    filters: {
        search: string;
        status: string;
        stageId: string;
    };
    statusOptions: SelectOption[];
    stageOptions: SelectOption[];
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Reviewer queue',
        href: reviewerQueueIndex(),
    },
];

const updateSearch = (search: string): void => {
    router.get(reviewerQueueIndex().url, { ...props.filters, search }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Reviewer queue" />

        <PageContainer>
            <PageHeader
                title="Reviewer queue"
                description="This is the reviewer-facing workload surface. Only assigned submissions appear here."
            />

            <ResourceToolbar
                :search="filters.search"
                search-placeholder="Search submission title or presenter"
                @update:search="updateSearch"
            />

            <div
                v-if="assignments.length === 0"
                class="rounded-[1.5rem] border border-border/70 bg-card/85 p-6 shadow-sm"
            >
                <EmptyState
                    title="No reviewer assignments"
                    description="Once a manager assigns screening work, the assigned submissions will appear here."
                    :icon="ClipboardCheck"
                />
            </div>

            <div v-else class="grid gap-4">
                <article
                    v-for="assignment in assignments"
                    :key="assignment.id"
                    class="rounded-[1.5rem] border border-border/70 bg-card/85 p-5 shadow-sm"
                >
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="space-y-3">
                            <div class="flex flex-wrap items-center gap-2">
                                <div class="text-lg font-semibold text-foreground">{{ assignment.title || 'Untitled submission' }}</div>
                                <StatusBadge :label="assignment.statusLabel" :tone="assignment.statusTone" />
                                <StatusBadge
                                    v-if="assignment.isOverdue"
                                    label="Overdue"
                                    tone="archived"
                                />
                            </div>

                            <div class="grid gap-1 text-sm text-muted-foreground md:grid-cols-2">
                                <div>Presenter: {{ assignment.applicantName || 'Unknown presenter' }}</div>
                                <div>Season: {{ assignment.seasonName || 'Not set' }}</div>
                                <div>Stage: {{ assignment.stageName || 'Not set' }}</div>
                                <div>Industry: {{ assignment.industryName || 'Not set' }}</div>
                            </div>

                            <div class="text-sm text-muted-foreground">
                                Due {{ assignment.dueAt ? new Date(assignment.dueAt).toLocaleString() : 'No due date' }}
                                <span v-if="assignment.recommendationLabel"> · Last recommendation: {{ assignment.recommendationLabel }}</span>
                            </div>
                        </div>

                        <Button as-child variant="outline">
                            <Link :href="showReviewerAssignment(assignment.id)">
                                <Eye class="size-4" />
                                Open review
                            </Link>
                        </Button>
                    </div>
                </article>
            </div>
        </PageContainer>
    </AppLayout>
</template>
