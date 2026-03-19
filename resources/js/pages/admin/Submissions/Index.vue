<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { CheckCircle2, CircleAlert, FolderKanban } from 'lucide-vue-next';
import { reactive, ref } from 'vue';
import ResourcePagination from '@/components/admin/ResourcePagination.vue';
import ResourceTable from '@/components/admin/ResourceTable.vue';
import ResourceToolbar from '@/components/admin/ResourceToolbar.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import PageContainer from '@/components/PageContainer.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as adminSubmissionsIndex, show as showAdminSubmission, transition as transitionSubmissionStatus } from '@/routes/admin-submissions';
import type { BreadcrumbItem, ManagedSubmission, PaginatedResource, ResourceFilters, SelectOption } from '@/types';

type Props = {
    submissions: PaginatedResource<ManagedSubmission>;
    filters: ResourceFilters;
    seasonOptions: SelectOption[];
    stageOptions: SelectOption[];
    industryOptions: SelectOption[];
    statusOptions: SelectOption[];
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Admin submissions',
        href: adminSubmissionsIndex(),
    },
];

const search = ref(props.filters.search);
const seasonId = ref(props.filters.seasonId ?? '');
const stageId = ref(props.filters.stageId ?? '');
const industryId = ref(props.filters.industryId ?? '');
const status = ref(props.filters.status ?? '');
const reviewState = reactive<Record<number, { status: string; reason: string }>>({});

const stateFor = (submission: ManagedSubmission): { status: string; reason: string } => {
    if (!reviewState[submission.id]) {
        reviewState[submission.id] = {
            status: submission.availableTransitions?.[0]?.value ?? '',
            reason: '',
        };
    }

    return reviewState[submission.id];
};

const submitSearch = (): void => {
    router.get(
        adminSubmissionsIndex.url({
            query: {
                search: search.value || undefined,
                season_id: seasonId.value || undefined,
                stage_id: stageId.value || undefined,
                industry_id: industryId.value || undefined,
                status: status.value || undefined,
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
    seasonId.value = '';
    stageId.value = '';
    industryId.value = '';
    status.value = '';
    submitSearch();
};

const submitTransition = (submission: ManagedSubmission): void => {
    const state = stateFor(submission);

    router.post(
        transitionSubmissionStatus(submission.id).url,
        {
            status: state.status,
            reason: state.reason,
        },
        {
            preserveScroll: true,
        },
    );
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Admin submissions" />

        <PageContainer>
            <PageHeader
                title="Admin submissions"
                description="Operational staff can inspect intake records here without using the presenter-facing editing surface."
            />

            <ResourceToolbar
                v-model:search="search"
                search-placeholder="Search by title, presenter, or organization"
                @submit="submitSearch"
                @reset="resetSearch"
            />

            <div class="grid gap-4 rounded-[1.25rem] border border-border/70 bg-card/85 p-4 shadow-sm md:grid-cols-4">
                <div class="grid gap-2">
                    <Label for="season-filter">Season</Label>
                    <select
                        id="season-filter"
                        v-model="seasonId"
                        class="flex h-10 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none ring-offset-background focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
                    >
                        <option value="">All seasons</option>
                        <option v-for="option in seasonOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                    </select>
                </div>
                <div class="grid gap-2">
                    <Label for="stage-filter">Stage</Label>
                    <select
                        id="stage-filter"
                        v-model="stageId"
                        class="flex h-10 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none ring-offset-background focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
                    >
                        <option value="">All stages</option>
                        <option v-for="option in stageOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                    </select>
                </div>
                <div class="grid gap-2">
                    <Label for="industry-filter">Industry</Label>
                    <select
                        id="industry-filter"
                        v-model="industryId"
                        class="flex h-10 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none ring-offset-background focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
                    >
                        <option value="">All industries</option>
                        <option v-for="option in industryOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                    </select>
                </div>
                <div class="grid gap-2">
                    <Label for="status-filter">Status</Label>
                    <select
                        id="status-filter"
                        v-model="status"
                        class="flex h-10 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none ring-offset-background focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
                    >
                        <option value="">All statuses</option>
                        <option v-for="option in statusOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                    </select>
                </div>

                <div class="md:col-span-4 flex items-center gap-2">
                    <Button type="button" variant="secondary" @click="submitSearch">
                        Apply filters
                    </Button>
                    <Button type="button" variant="ghost" @click="resetSearch">
                        Reset filters
                    </Button>
                </div>
            </div>

            <ResourceTable
                :has-results="submissions.data.length > 0"
                empty-title="No submissions found"
                empty-description="Presenter drafts and final submissions will appear here once intake starts."
                :empty-icon="FolderKanban"
            >
                <template #head>
                    <tr class="text-left text-xs tracking-wide text-muted-foreground uppercase">
                        <th class="px-4 py-3 font-medium">Submission</th>
                        <th class="px-4 py-3 font-medium">Presenter</th>
                        <th class="px-4 py-3 font-medium">Season</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 font-medium">Checklist</th>
                        <th class="px-4 py-3 font-medium">Operational review</th>
                    </tr>
                </template>

                <template #body>
                    <tr v-for="submission in submissions.data" :key="submission.id" class="align-top">
                        <td class="px-4 py-4">
                            <div class="space-y-1">
                                <div class="font-medium text-foreground">{{ submission.title }}</div>
                                <p class="text-sm text-muted-foreground">{{ submission.organizationName || 'Independent presenter' }}</p>
                                <p class="text-sm text-muted-foreground">{{ submission.industryName || 'No industry' }}</p>
                            </div>
                        </td>
                        <td class="px-4 py-4 text-sm text-muted-foreground">
                            <div>{{ submission.applicantName || 'Unknown presenter' }}</div>
                            <div>{{ submission.applicantEmail || 'No email' }}</div>
                        </td>
                        <td class="px-4 py-4 text-sm text-muted-foreground">
                            <div>{{ submission.seasonName || 'No season' }}</div>
                            <div>{{ submission.stageName || 'No stage' }}</div>
                        </td>
                        <td class="px-4 py-4">
                            <StatusBadge :label="submission.statusLabel" :tone="submission.statusTone" />
                            <p v-if="submission.latestStatusReason" class="mt-2 text-xs text-muted-foreground">
                                Latest note: {{ submission.latestStatusReason }}
                            </p>
                        </td>
                        <td class="px-4 py-4">
                            <div v-if="submission.intakeChecklist" class="space-y-2">
                                <div class="flex items-center gap-2 text-sm font-medium">
                                    <component :is="submission.intakeChecklist.isReady ? CheckCircle2 : CircleAlert" class="size-4" />
                                    {{ submission.intakeChecklist.passedCount }}/{{ submission.intakeChecklist.totalCount }} checks passed
                                </div>
                                <ul class="space-y-1 text-xs text-muted-foreground">
                                    <li
                                        v-for="item in submission.intakeChecklist.items"
                                        :key="item.key"
                                        class="flex items-center gap-2"
                                    >
                                        <span :class="item.passed ? 'text-emerald-600' : 'text-amber-600'">
                                            {{ item.passed ? 'Pass' : 'Fail' }}
                                        </span>
                                        <span>{{ item.label }}</span>
                                    </li>
                                </ul>
                            </div>
                        </td>
                        <td class="px-4 py-4">
                            <div class="space-y-3">
                                <div class="flex items-center gap-2">
                                    <Button as-child variant="outline" size="sm">
                                        <Link :href="showAdminSubmission(submission.id)">
                                            View
                                        </Link>
                                    </Button>
                                </div>

                                <div v-if="(submission.availableTransitions?.length ?? 0) > 0" class="space-y-2 rounded-xl border border-border/70 bg-background/60 p-3">
                                    <div class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Quick review action</div>
                                    <select
                                        v-model="stateFor(submission).status"
                                        class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none ring-offset-background focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
                                    >
                                        <option v-for="option in submission.availableTransitions" :key="option.value" :value="option.value">
                                            {{ option.label }}
                                        </option>
                                    </select>
                                    <textarea
                                        v-model="stateFor(submission).reason"
                                        rows="3"
                                        class="flex min-h-24 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none ring-offset-background placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
                                        placeholder="Return/reject notes or optional internal review note."
                                    />
                                    <Button type="button" size="sm" @click="submitTransition(submission)">
                                        Apply review action
                                    </Button>
                                </div>
                            </div>
                        </td>
                    </tr>
                </template>
            </ResourceTable>

            <ResourcePagination :resource="submissions" />
        </PageContainer>
    </AppLayout>
</template>
