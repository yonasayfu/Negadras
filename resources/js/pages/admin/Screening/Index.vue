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
import { index as screeningQueueIndex, show as showScreeningSubmission } from '@/routes/screening-queue';
import type { BreadcrumbItem, ManagedSubmission, ResourceFilters, SelectOption } from '@/types';

type Props = {
    submissions: ManagedSubmission[];
    filters: ResourceFilters;
    reviewerOptions: SelectOption[];
    stageOptions: SelectOption[];
    industryOptions: SelectOption[];
    recommendationOptions: SelectOption[];
    queueStateOptions: SelectOption[];
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Screening queue',
        href: screeningQueueIndex(),
    },
];

const search = ref(props.filters.search);
const reviewerId = ref(props.filters.reviewerId ?? '');
const stageId = ref(props.filters.stageId ?? '');
const industryId = ref(props.filters.industryId ?? '');
const recommendation = ref(props.filters.recommendation ?? '');
const queueState = ref(props.filters.queueState ?? '');

const submitSearch = (): void => {
    router.get(
        screeningQueueIndex.url({
            query: {
                search: search.value || undefined,
                reviewer_id: reviewerId.value || undefined,
                stage_id: stageId.value || undefined,
                industry_id: industryId.value || undefined,
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
    stageId.value = '';
    industryId.value = '';
    recommendation.value = '';
    queueState.value = '';
    submitSearch();
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Screening queue" />

        <PageContainer>
            <PageHeader
                title="Screening queue"
                description="Manager-facing queue for reviewed submissions, reviewer workload follow-up, and final shortlist or revision decisions."
            />

            <ResourceToolbar
                v-model:search="search"
                search-placeholder="Search by title, presenter, or organization"
                @submit="submitSearch"
                @reset="resetSearch"
            />

            <div class="grid gap-4 rounded-[1.25rem] border border-border/70 bg-card/85 p-4 shadow-sm md:grid-cols-5">
                <div class="grid gap-2">
                    <Label for="reviewer-filter">Reviewer</Label>
                    <select id="reviewer-filter" v-model="reviewerId" class="flex h-10 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs">
                        <option value="">All reviewers</option>
                        <option v-for="option in reviewerOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                    </select>
                </div>
                <div class="grid gap-2">
                    <Label for="stage-filter">Stage</Label>
                    <select id="stage-filter" v-model="stageId" class="flex h-10 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs">
                        <option value="">All stages</option>
                        <option v-for="option in stageOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                    </select>
                </div>
                <div class="grid gap-2">
                    <Label for="industry-filter">Industry</Label>
                    <select id="industry-filter" v-model="industryId" class="flex h-10 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs">
                        <option value="">All industries</option>
                        <option v-for="option in industryOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
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

                <div class="md:col-span-5 flex items-center gap-2">
                    <Button type="button" variant="secondary" @click="submitSearch">
                        Apply filters
                    </Button>
                    <Button type="button" variant="ghost" @click="resetSearch">
                        Reset filters
                    </Button>
                </div>
            </div>

            <div
                v-if="submissions.length === 0"
                class="rounded-[1.5rem] border border-border/70 bg-card/85 p-6 shadow-sm"
            >
                <EmptyState
                    title="No screening submissions found"
                    description="Eligible submissions with reviewer assignments and submitted screening work will appear here."
                    :icon="ClipboardList"
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
                                <div class="text-lg font-semibold text-foreground">{{ submission.title }}</div>
                                <StatusBadge :label="submission.statusLabel" :tone="submission.statusTone" />
                                <StatusBadge
                                    v-if="submission.screeningStateLabel"
                                    :label="submission.screeningStateLabel"
                                    tone="review"
                                />
                            </div>

                            <div class="grid gap-1 text-sm text-muted-foreground md:grid-cols-2">
                                <div>Presenter: {{ submission.applicantName || 'Unknown presenter' }}</div>
                                <div>Organization: {{ submission.organizationName || 'Independent presenter' }}</div>
                                <div>Season / Stage: {{ submission.seasonName || 'No season' }} / {{ submission.stageName || 'No stage' }}</div>
                                <div>Industry: {{ submission.industryName || 'No industry' }}</div>
                            </div>

                            <div class="grid gap-1 text-sm text-muted-foreground md:grid-cols-3">
                                <div>Assigned reviewers: {{ submission.assignedReviewersCount ?? 0 }}</div>
                                <div>Pending reviews: {{ submission.pendingReviewsCount ?? 0 }}</div>
                                <div>Submitted reviews: {{ submission.submittedReviewsCount ?? 0 }}</div>
                            </div>

                            <p v-if="submission.latestRecommendationLabel" class="text-sm text-muted-foreground">
                                Latest submitted recommendation: {{ submission.latestRecommendationLabel }}
                            </p>
                            <p v-if="submission.latestStatusReason" class="text-sm text-muted-foreground">
                                Latest note: {{ submission.latestStatusReason }}
                            </p>
                        </div>

                        <Button as-child variant="outline">
                            <Link :href="showScreeningSubmission(submission.id)">
                                <Eye class="size-4" />
                                Review screening
                            </Link>
                        </Button>
                    </div>
                </article>
            </div>
        </PageContainer>
    </AppLayout>
</template>
