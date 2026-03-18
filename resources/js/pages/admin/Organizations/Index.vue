<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Building2, Plus, SquarePen } from 'lucide-vue-next';
import { ref } from 'vue';
import ActionIconLink from '@/components/admin/ActionIconLink.vue';
import ResourcePagination from '@/components/admin/ResourcePagination.vue';
import ResourceTable from '@/components/admin/ResourceTable.vue';
import ResourceToolbar from '@/components/admin/ResourceToolbar.vue';
import PageContainer from '@/components/PageContainer.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { create as createOrganization, edit as editOrganization, index as organizationsIndex } from '@/routes/organizations';
import type { BreadcrumbItem, ManagedOrganization, PaginatedResource, ResourceFilters } from '@/types';

type Props = {
    organizations: PaginatedResource<ManagedOrganization>;
    filters: ResourceFilters;
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Organizations',
        href: organizationsIndex(),
    },
];

const search = ref(props.filters.search);

const submitSearch = (): void => {
    router.get(
        organizationsIndex.url({
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
        <Head title="Organizations" />

        <PageContainer>
            <PageHeader
                title="Organizations"
                description="Track startup or company profiles separately from presenter accounts so submissions can later attach to real teams."
            >
                <template #actions>
                    <Button as-child>
                        <Link :href="createOrganization()">
                            <Plus class="size-4" />
                            New organization
                        </Link>
                    </Button>
                </template>
            </PageHeader>

            <ResourceToolbar
                v-model:search="search"
                search-placeholder="Search organizations by name, registration number, or contact email"
                @submit="submitSearch"
                @reset="resetSearch"
            />

            <ResourceTable
                :has-results="organizations.data.length > 0"
                empty-title="No organizations found"
                empty-description="Organization and team profiles will appear here once presenters or admins create them."
                :empty-icon="Building2"
            >
                <template #head>
                    <tr class="text-left text-xs tracking-wide text-muted-foreground uppercase">
                        <th class="px-4 py-3 font-medium">Organization</th>
                        <th class="px-4 py-3 font-medium">Industry</th>
                        <th class="px-4 py-3 font-medium">Primary contact</th>
                        <th class="px-4 py-3 font-medium">Team size</th>
                        <th class="px-4 py-3 font-medium text-right">Actions</th>
                    </tr>
                </template>

                <template #body>
                    <tr v-for="organization in organizations.data" :key="organization.id" class="align-top">
                        <td class="px-4 py-4">
                            <div class="space-y-1">
                                <div class="font-medium text-foreground">{{ organization.displayName }}</div>
                                <p class="text-sm text-muted-foreground">{{ organization.legalName }}</p>
                                <p class="text-sm text-muted-foreground">
                                    {{ organization.registrationNumber || 'No registration number' }}
                                </p>
                            </div>
                        </td>
                        <td class="px-4 py-4 text-sm text-muted-foreground">
                            {{ organization.industryName || 'No industry set' }}
                        </td>
                        <td class="px-4 py-4 text-sm text-muted-foreground">
                            {{ organization.primaryContactName || 'No primary contact' }}
                        </td>
                        <td class="px-4 py-4 text-sm text-muted-foreground">
                            {{ organization.teamMembersCount ?? 0 }}
                        </td>
                        <td class="px-4 py-4">
                            <div class="flex justify-end">
                                <ActionIconLink
                                    :href="editOrganization(organization.id!)"
                                    label="Edit organization"
                                    :icon="SquarePen"
                                />
                            </div>
                        </td>
                    </tr>
                </template>
            </ResourceTable>

            <ResourcePagination :resource="organizations" />
        </PageContainer>
    </AppLayout>
</template>
