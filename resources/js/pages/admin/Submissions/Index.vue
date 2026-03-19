<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { FolderKanban } from 'lucide-vue-next';
import { ref } from 'vue';
import ResourcePagination from '@/components/admin/ResourcePagination.vue';
import ResourceTable from '@/components/admin/ResourceTable.vue';
import ResourceToolbar from '@/components/admin/ResourceToolbar.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import PageContainer from '@/components/PageContainer.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as adminSubmissionsIndex, show as showAdminSubmission } from '@/routes/admin-submissions';
import type { BreadcrumbItem, ManagedSubmission, PaginatedResource, ResourceFilters } from '@/types';

type Props = {
    submissions: PaginatedResource<ManagedSubmission>;
    filters: ResourceFilters;
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Admin submissions',
        href: adminSubmissionsIndex(),
    },
];

const search = ref(props.filters.search);

const submitSearch = (): void => {
    router.get(
        adminSubmissionsIndex.url({
            query: {
                search: search.value || undefined,
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
    submitSearch();
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
                        <th class="px-4 py-3 font-medium text-right">Actions</th>
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
                        </td>
                        <td class="px-4 py-4">
                            <div class="flex justify-end">
                                <Button as-child variant="outline" size="sm">
                                    <Link :href="showAdminSubmission(submission.id)">
                                        View
                                    </Link>
                                </Button>
                            </div>
                        </td>
                    </tr>
                </template>
            </ResourceTable>

            <ResourcePagination :resource="submissions" />
        </PageContainer>
    </AppLayout>
</template>
