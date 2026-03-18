<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { BriefcaseBusiness, Power, Plus, SquarePen, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import ActionIconLink from '@/components/admin/ActionIconLink.vue';
import ConfirmActionDialog from '@/components/admin/ConfirmActionDialog.vue';
import ResourcePagination from '@/components/admin/ResourcePagination.vue';
import ResourceTable from '@/components/admin/ResourceTable.vue';
import ResourceToolbar from '@/components/admin/ResourceToolbar.vue';
import PageContainer from '@/components/PageContainer.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { create as createIndustry, destroy as destroyIndustry, edit as editIndustry, index as industriesIndex, toggle as toggleIndustry } from '@/routes/industries';
import type { Auth, BreadcrumbItem, ManagedIndustry, PaginatedResource } from '@/types';

type Props = {
    industries: PaginatedResource<ManagedIndustry>;
    filters: {
        search: string;
        active: string;
    };
};

const props = defineProps<Props>();
const page = usePage();
const auth = computed(() => page.props.auth as Auth);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Industries',
        href: industriesIndex(),
    },
];

const search = ref(props.filters.search);
const active = ref(props.filters.active);

const submitSearch = (): void => {
    router.get(
        industriesIndex.url({
            query: {
                search: search.value || undefined,
                active: active.value || undefined,
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
    active.value = '';
    submitSearch();
};

const deleteSelectedIndustry = (industry: ManagedIndustry): void => {
    router.delete(destroyIndustry(industry.id).url, {
        preserveScroll: true,
    });
};

const toggleSelectedIndustry = (industry: ManagedIndustry): void => {
    router.post(toggleIndustry(industry.id).url, {}, {
        preserveScroll: true,
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Industries" />

        <PageContainer>
            <PageHeader
                title="Industries"
                description="Industries give Negadras a stable classification layer for submissions, filtering, and reporting."
            >
                <template #eyebrow>
                    <div class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-medium tracking-[0.2em] text-emerald-900 uppercase dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-100">
                        <BriefcaseBusiness class="size-3.5" />
                        Submission classification
                    </div>
                </template>
                <template #actions>
                    <Button v-if="auth.permissions.includes('industries.create')" as-child>
                        <Link :href="createIndustry()">
                            <Plus class="size-4" />
                            Create industry
                        </Link>
                    </Button>
                </template>
            </PageHeader>

            <ResourceToolbar
                v-model:search="search"
                search-placeholder="Search industries by name or slug"
                @submit="submitSearch"
                @reset="resetSearch"
            >
                <template #actions>
                    <div class="flex flex-wrap items-center gap-2">
                        <Button variant="outline" size="sm" :class="active === '' ? 'border-foreground' : ''" @click="active = ''; submitSearch()">
                            All
                        </Button>
                        <Button variant="outline" size="sm" :class="active === 'active' ? 'border-foreground' : ''" @click="active = 'active'; submitSearch()">
                            Active
                        </Button>
                        <Button variant="outline" size="sm" :class="active === 'inactive' ? 'border-foreground' : ''" @click="active = 'inactive'; submitSearch()">
                            Inactive
                        </Button>
                    </div>
                </template>
            </ResourceToolbar>

            <ResourceTable
                :has-results="industries.data.length > 0"
                empty-title="No industries found"
                empty-description="Create the first industry so teams and submissions can be classified properly."
                :empty-icon="BriefcaseBusiness"
            >
                <template #head>
                    <tr class="text-left text-xs tracking-wide text-muted-foreground uppercase">
                        <th class="px-4 py-3 font-medium">Industry</th>
                        <th class="px-4 py-3 font-medium">Slug</th>
                        <th class="px-4 py-3 font-medium">State</th>
                        <th class="px-4 py-3 font-medium text-right">Actions</th>
                    </tr>
                </template>

                <template #body>
                    <tr v-for="industry in industries.data" :key="industry.id" class="align-top">
                        <td class="px-4 py-4">
                            <div class="space-y-1">
                                <div class="font-medium text-foreground">{{ industry.name }}</div>
                                <p class="max-w-xl text-sm text-muted-foreground">
                                    {{ industry.description || 'No industry description added yet.' }}
                                </p>
                            </div>
                        </td>
                        <td class="px-4 py-4 text-sm text-muted-foreground">/{{ industry.slug }}</td>
                        <td class="px-4 py-4">
                            <Badge :variant="industry.isActive ? 'secondary' : 'outline'">
                                {{ industry.isActive ? 'Active' : 'Inactive' }}
                            </Badge>
                        </td>
                        <td class="px-4 py-4">
                            <div class="flex items-center justify-end gap-2">
                                <Button
                                    v-if="auth.permissions.includes('industries.update')"
                                    variant="ghost"
                                    size="icon-sm"
                                    class="rounded-full"
                                    @click="toggleSelectedIndustry(industry)"
                                >
                                    <Power class="size-4" />
                                </Button>
                                <ActionIconLink
                                    v-if="auth.permissions.includes('industries.update')"
                                    :href="editIndustry(industry.id)"
                                    label="Edit industry"
                                    :icon="SquarePen"
                                />
                                <ConfirmActionDialog
                                    v-if="auth.permissions.includes('industries.delete')"
                                    title="Delete industry"
                                    :description="`Delete ${industry.name}? Only do this when no downstream records depend on it.`"
                                    confirm-label="Delete industry"
                                    @confirm="deleteSelectedIndustry(industry)"
                                >
                                    <template #trigger>
                                        <Button variant="ghost" size="icon-sm" class="rounded-full text-destructive">
                                            <Trash2 class="size-4" />
                                        </Button>
                                    </template>
                                </ConfirmActionDialog>
                            </div>
                        </td>
                    </tr>
                </template>
            </ResourceTable>

            <ResourcePagination :resource="industries" />
        </PageContainer>
    </AppLayout>
</template>
