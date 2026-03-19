<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Gavel } from 'lucide-vue-next';
import EmptyState from '@/components/EmptyState.vue';
import PageContainer from '@/components/PageContainer.vue';
import PageHeader from '@/components/PageHeader.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as judgeWorkspaceIndex, show as showJudgeWorkspace } from '@/routes/judge-workspace';
import type { BreadcrumbItem } from '@/types';

type Props = {
    assignments: Array<{
        id: number;
        panelName: string | null;
        seasonName: string | null;
        stageName: string | null;
        title: string | null;
        applicantName: string | null;
        organizationName: string | null;
        statusLabel: string;
        statusTone: string;
        assignedAt: string | null;
        isLocked: boolean;
        hasActiveConflict: boolean;
        progress: {
            criteriaCount: number;
            scoredCount: number;
            isComplete: boolean;
            total: number;
        };
    }>;
};

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Judge workspace', href: judgeWorkspaceIndex() }];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Judge workspace" />

        <PageContainer>
            <PageHeader title="Judge workspace" description="Review your panel allocations, complete scoring, and declare conflicts before submitting final marks." />

            <div v-if="assignments.length === 0" class="rounded-[1.5rem] border border-border/70 bg-card/85 p-6 shadow-sm">
                <EmptyState title="No judging assignments yet" description="Assignments will appear here once a manager places you on a panel." :icon="Gavel" />
            </div>

            <div v-else class="grid gap-4">
                <article v-for="assignment in assignments" :key="assignment.id" class="rounded-[1.5rem] border border-border/70 bg-card/85 p-5 shadow-sm">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="space-y-3">
                            <div class="flex flex-wrap items-center gap-2">
                                <Link :href="showJudgeWorkspace(assignment.id)" class="text-lg font-semibold text-foreground hover:underline">
                                    {{ assignment.title || 'Untitled submission' }}
                                </Link>
                                <StatusBadge :label="assignment.statusLabel" :tone="assignment.statusTone" />
                                <StatusBadge v-if="assignment.isLocked" label="Locked" tone="archived" />
                                <StatusBadge v-if="assignment.hasActiveConflict" label="Conflict active" tone="review" />
                            </div>

                            <div class="grid gap-1 text-sm text-muted-foreground md:grid-cols-2">
                                <div>Panel: {{ assignment.panelName || 'Unknown panel' }}</div>
                                <div>Season: {{ assignment.seasonName || 'Unknown season' }}</div>
                                <div>Stage: {{ assignment.stageName || 'Unknown stage' }}</div>
                                <div>Assigned: {{ assignment.assignedAt || 'Unknown' }}</div>
                                <div>Submission owner: {{ assignment.organizationName || assignment.applicantName || 'Unknown' }}</div>
                                <div>Progress: {{ assignment.progress.scoredCount }} / {{ assignment.progress.criteriaCount }}</div>
                            </div>
                        </div>

                        <div class="text-right text-sm text-muted-foreground">
                            <div>Weighted total</div>
                            <div class="text-2xl font-semibold text-foreground">{{ assignment.progress.total }}</div>
                        </div>
                    </div>
                </article>
            </div>
        </PageContainer>
    </AppLayout>
</template>
