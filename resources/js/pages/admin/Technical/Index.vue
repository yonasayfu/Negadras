<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ClipboardList, Eye } from 'lucide-vue-next';
import { ref } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import ResourceToolbar from '@/components/admin/ResourceToolbar.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import PageContainer from '@/components/PageContainer.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as technicalQueueIndex, show as showTechnicalSubmission } from '@/routes/technical-queue';
import type { BreadcrumbItem, ManagedSubmission, ResourceFilters, SelectOption } from '@/types';

type Props = {
    submissions: ManagedSubmission[];
    filters: ResourceFilters;
    reviewerOptions: SelectOption[];
    recommendationOptions: SelectOption[];
    queueStateOptions: SelectOption[];
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Technical queue',
        href: technicalQueueIndex(),
    },
];

const search = ref(props.filters.search);
const reviewerId = ref(props.filters.reviewerId ?? '');
const recommendation = ref(props.filters.recommendation ?? '');
const queueState = ref(props.filters.queueState ?? '');

const submitSearch = (): void => {
    router.get(
        technicalQueueIndex.url({
            query: {
                search: search.value || undefined,
                reviewer_id: reviewerId.value || undefined,
                recommendation: recommendation.value || undefined,
                queue_state: queueState.value || undefined,
            },
        }),
        {},
        {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        },
    );
};

const resetSearch = (): void => {
    search.value = '';
    reviewerId.value = '';
    recommendation.value = '';
    queueState.value = '';
    submitSearch();
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Technical queue" />

        <PageContainer>
            <PageHeader
                title="Technical queue"
                description="Manager-facing queue for shortlisted submissions, technical expert assignment, and comparison of submitted technical output."
            />

            <ResourceToolbar
                v-model:search="search"
                search-placeholder="Search by title, presenter, or organization"
                @submit="submitSearch"
                @reset="resetSearch"
            />

            <div class="grid gap-4 rounded-[1.25rem] border border-border/70 bg-card/85 p-4 shadow-sm md:grid-cols-3">
                <div class="grid gap-2">
                    <Label for="reviewer-filter">Technical reviewer</Label>
                    <select id="reviewer-filter" v-model="reviewerId" class="flex h-10 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs">
                        <option value="">All reviewers</option>
                        <option v-for="option in reviewerOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                    </select>
                </div>
                <div class="grid gap-2">
                    <Label for="recommendation-filter">Recommendation</Label>
                    <select id="recommendation-filter" v-model="recommendation" class="flex h-10 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs">
                        <option value="">All recommendations</option>
                        <option v-for="option in recommendationOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                    </select>
                </div>
                <div class="grid gap-2">
                    <Label for="queue-state-filter">Queue state</Label>
                    <select id="queue-state-filter" v-model="queueState" class="flex h-10 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs">
                        <option value="">All queue states</option>
                        <option v-for="option in queueStateOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                    </select>
                </div>

                <div class="md:col-span-3 flex items-center gap-2">
                    <Button type="button" variant="secondary" @click="submitSearch">Apply filters</Button>
                    <Button type="button" variant="ghost" @click="resetSearch">Reset filters</Button>
                </div>
            </div>

            <div v-if="submissions.length === 0" class="rounded-[1.5rem] border border-border/70 bg-card/85 p-6 shadow-sm">
                <EmptyState
                    title="No technical review submissions found"
                    description="Shortlisted submissions will appear here once they are ready for technical review."
                    :icon="ClipboardList"
                />
            </div>

            <div v-else class="grid gap-4">
                <article v-for="submission in submissions" :key="submission.id" class="rounded-[1.5rem] border border-border/70 bg-card/85 p-5 shadow-sm">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="space-y-3">
                            <div class="flex flex-wrap items-center gap-2">
                                <div class="text-lg font-semibold text-foreground">{{ submission.title }}</div>
                                <StatusBadge :label="submission.statusLabel" :tone="submission.statusTone" />
                                <StatusBadge v-if="submission.technicalStateLabel" :label="submission.technicalStateLabel" tone="review" />
                            </div>

                            <div class="grid gap-1 text-sm text-muted-foreground md:grid-cols-2">
                                <div>Presenter: {{ submission.applicantName || 'Unknown presenter' }}</div>
                                <div>Organization: {{ submission.organizationName || 'Independent presenter' }}</div>
                                <div>Season / Stage: {{ submission.seasonName || 'No season' }} / {{ submission.stageName || 'No stage' }}</div>
                                <div>Industry: {{ submission.industryName || 'No industry' }}</div>
                            </div>

                            <div class="grid gap-1 text-sm text-muted-foreground md:grid-cols-3">
                                <div>Assigned experts: {{ submission.assignedTechnicalReviewersCount ?? 0 }}</div>
                                <div>Submitted technical reviews: {{ submission.submittedTechnicalReviewsCount ?? 0 }}</div>
                                <div>Screening reviews available: {{ submission.screeningReviewsCount ?? 0 }}</div>
                            </div>

                            <p v-if="submission.latestTechnicalRecommendationLabel" class="text-sm text-muted-foreground">
                                Latest technical recommendation: {{ submission.latestTechnicalRecommendationLabel }}
                            </p>
                            <p v-if="submission.averageTechnicalScore !== null && submission.averageTechnicalScore !== undefined" class="text-sm text-muted-foreground">
                                Average technical score: {{ submission.averageTechnicalScore }}
                            </p>
                        </div>

                        <Button as-child variant="outline">
                            <Link :href="showTechnicalSubmission(submission.id)">
                                <Eye class="size-4" />
                                Open technical review
                            </Link>
                        </Button>
                    </div>
                </article>
            </div>
        </PageContainer>
    </AppLayout>
</template>
