<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PageContainer from '@/components/PageContainer.vue';
import PageHeader from '@/components/PageHeader.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as reportsIndex } from '@/routes/reports';
import type { BreadcrumbItem } from '@/types';

type MetricSummary = {
    totalSubmissions: number;
    draftSubmissions: number;
    submittedSubmissions: number;
    eligibleSubmissions: number;
    shortlistedSubmissions: number;
    rejectedSubmissions: number;
    activeReviewAssignments: number;
    submittedReviews: number;
    finalizedSessions: number;
    rankingSnapshots: number;
    awardsGranted: number;
    feedbackReleased: number;
};

defineProps<{
    summary: MetricSummary;
    seasonBreakdown: Array<{ id: number; name: string; year: number; submissionsCount: number }>;
    stageBreakdown: Array<{ id: number; name: string; seasonName: string | null; submissionsCount: number }>;
    decisionBreakdown: Array<{ decisionType: string; decisionLabel: string; total: number }>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Reports',
        href: reportsIndex(),
    },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Reports" />

        <PageContainer>
            <PageHeader
                title="Reports"
                description="Negadras operational reporting now summarizes intake, review throughput, ranking outputs, and delivery progress from one page."
            />

            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <article class="rounded-2xl border border-border/70 bg-card/85 p-5">
                    <div class="text-sm text-muted-foreground">Submissions</div>
                    <div class="mt-2 text-3xl font-semibold">{{ summary.totalSubmissions }}</div>
                    <div class="mt-3 text-sm text-muted-foreground">
                        Draft {{ summary.draftSubmissions }} · Submitted {{ summary.submittedSubmissions }}
                    </div>
                </article>
                <article class="rounded-2xl border border-border/70 bg-card/85 p-5">
                    <div class="text-sm text-muted-foreground">Review throughput</div>
                    <div class="mt-2 text-3xl font-semibold">{{ summary.submittedReviews }}</div>
                    <div class="mt-3 text-sm text-muted-foreground">
                        Active assignments {{ summary.activeReviewAssignments }}
                    </div>
                </article>
                <article class="rounded-2xl border border-border/70 bg-card/85 p-5">
                    <div class="text-sm text-muted-foreground">Decision funnel</div>
                    <div class="mt-2 text-3xl font-semibold">{{ summary.shortlistedSubmissions }}</div>
                    <div class="mt-3 text-sm text-muted-foreground">
                        Eligible {{ summary.eligibleSubmissions }} · Rejected {{ summary.rejectedSubmissions }}
                    </div>
                </article>
                <article class="rounded-2xl border border-border/70 bg-card/85 p-5">
                    <div class="text-sm text-muted-foreground">Outcomes</div>
                    <div class="mt-2 text-3xl font-semibold">{{ summary.awardsGranted }}</div>
                    <div class="mt-3 text-sm text-muted-foreground">
                        Rankings {{ summary.rankingSnapshots }} · Feedback {{ summary.feedbackReleased }}
                    </div>
                </article>
            </section>

            <section class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
                <article class="rounded-2xl border border-border/70 bg-card/85 p-5">
                    <h2 class="text-lg font-semibold">Season breakdown</h2>
                    <div class="mt-4 space-y-3">
                        <div
                            v-for="season in seasonBreakdown"
                            :key="season.id"
                            class="flex items-center justify-between rounded-xl border border-border/60 px-4 py-3"
                        >
                            <div>
                                <div class="font-medium">{{ season.name }}</div>
                                <div class="text-sm text-muted-foreground">{{ season.year }}</div>
                            </div>
                            <div class="text-sm font-medium text-muted-foreground">
                                {{ season.submissionsCount }} submissions
                            </div>
                        </div>
                    </div>
                </article>

                <article class="rounded-2xl border border-border/70 bg-card/85 p-5">
                    <h2 class="text-lg font-semibold">Decision distribution</h2>
                    <div class="mt-4 space-y-3">
                        <div
                            v-for="decision in decisionBreakdown"
                            :key="decision.decisionType"
                            class="flex items-center justify-between rounded-xl border border-border/60 px-4 py-3"
                        >
                            <span class="font-medium">{{ decision.decisionLabel }}</span>
                            <span class="text-sm text-muted-foreground">{{ decision.total }}</span>
                        </div>
                    </div>
                </article>
            </section>

            <section class="rounded-2xl border border-border/70 bg-card/85 p-5">
                <h2 class="text-lg font-semibold">Stage workload</h2>
                <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                    <div
                        v-for="stage in stageBreakdown"
                        :key="stage.id"
                        class="rounded-xl border border-border/60 px-4 py-3"
                    >
                        <div class="font-medium">{{ stage.name }}</div>
                        <div class="text-sm text-muted-foreground">{{ stage.seasonName || 'No season' }}</div>
                        <div class="mt-2 text-sm font-medium">{{ stage.submissionsCount }} submissions</div>
                    </div>
                </div>
            </section>
        </PageContainer>
    </AppLayout>
</template>
