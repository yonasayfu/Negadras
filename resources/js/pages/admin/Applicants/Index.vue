<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { IdCard, SquarePen } from 'lucide-vue-next';
import { ref } from 'vue';
import ActionIconLink from '@/components/admin/ActionIconLink.vue';
import ResourcePagination from '@/components/admin/ResourcePagination.vue';
import ResourceTable from '@/components/admin/ResourceTable.vue';
import ResourceToolbar from '@/components/admin/ResourceToolbar.vue';
import PageContainer from '@/components/PageContainer.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/AppLayout.vue';
import { edit as editApplicant, index as applicantsIndex } from '@/routes/applicants';
import type { BreadcrumbItem, ManagedApplicant, PaginatedResource, ResourceFilters } from '@/types';

type Props = {
    applicants: PaginatedResource<ManagedApplicant>;
    filters: ResourceFilters;
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Applicants',
        href: applicantsIndex(),
    },
];

const search = ref(props.filters.search);

const submitSearch = (): void => {
    router.get(
        applicantsIndex.url({
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
        <Head title="Applicants" />

        <PageContainer>
            <PageHeader
                title="Applicants"
                description="Inspect presenter-facing competition profiles separately from auth users so Negadras can manage real applicant data cleanly."
            />

            <ResourceToolbar
                v-model:search="search"
                search-placeholder="Search applicants by name, email, or phone"
                @submit="submitSearch"
                @reset="resetSearch"
            />

            <ResourceTable
                :has-results="applicants.data.length > 0"
                empty-title="No applicants found"
                empty-description="Applicant profiles will appear here after presenters create their competition-facing profile."
                :empty-icon="IdCard"
            >
                <template #head>
                    <tr class="text-left text-xs tracking-wide text-muted-foreground uppercase">
                        <th class="px-4 py-3 font-medium">Applicant</th>
                        <th class="px-4 py-3 font-medium">Linked user</th>
                        <th class="px-4 py-3 font-medium">Type</th>
                        <th class="px-4 py-3 font-medium">Social links</th>
                        <th class="px-4 py-3 font-medium text-right">Actions</th>
                    </tr>
                </template>

                <template #body>
                    <tr v-for="applicant in applicants.data" :key="applicant.id" class="align-top">
                        <td class="px-4 py-4">
                            <div class="space-y-1">
                                <div class="font-medium text-foreground">{{ applicant.fullName }}</div>
                                <p class="text-sm text-muted-foreground">{{ applicant.email }}</p>
                                <p class="text-sm text-muted-foreground">{{ applicant.phone || 'No phone added' }}</p>
                            </div>
                        </td>
                        <td class="px-4 py-4 text-sm text-muted-foreground">
                            <div>{{ applicant.linkedUserName || 'Unknown user' }}</div>
                            <div>{{ applicant.linkedUserEmail || 'No user email' }}</div>
                        </td>
                        <td class="px-4 py-4">
                            <Badge variant="secondary">{{ applicant.applicantTypeLabel }}</Badge>
                        </td>
                        <td class="px-4 py-4 text-sm text-muted-foreground">
                            {{ applicant.socialLinksCount }}
                        </td>
                        <td class="px-4 py-4">
                            <div class="flex justify-end">
                                <ActionIconLink
                                    :href="editApplicant(applicant.id)"
                                    label="Edit applicant"
                                    :icon="SquarePen"
                                />
                            </div>
                        </td>
                    </tr>
                </template>
            </ResourceTable>

            <ResourcePagination :resource="applicants" />
        </PageContainer>
    </AppLayout>
</template>
