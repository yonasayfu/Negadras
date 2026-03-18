<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { CalendarRange, CircleStop, Play, Plus, SquarePen, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import ActionIconLink from '@/components/admin/ActionIconLink.vue';
import ConfirmActionDialog from '@/components/admin/ConfirmActionDialog.vue';
import ResourcePagination from '@/components/admin/ResourcePagination.vue';
import ResourceTable from '@/components/admin/ResourceTable.vue';
import ResourceToolbar from '@/components/admin/ResourceToolbar.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import PageContainer from '@/components/PageContainer.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { activate as activateSeason, close as closeSeason, create as createSeason, destroy as destroySeason, edit as editSeason, index as seasonsIndex } from '@/routes/seasons';
import type { Auth, BreadcrumbItem, ManagedSeason, PaginatedResource, SelectOption } from '@/types';

type Props = {
    seasons: PaginatedResource<ManagedSeason>;
    filters: {
        search: string;
        status: string;
    };
    statusOptions: SelectOption[];
};

const props = defineProps<Props>();
const page = usePage();
const auth = computed(() => page.props.auth as Auth);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Seasons',
        href: seasonsIndex(),
    },
];

const search = ref(props.filters.search);
const status = ref(props.filters.status);

const submitSearch = (): void => {
    router.get(
        seasonsIndex.url({
            query: {
                search: search.value || undefined,
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
    status.value = '';
    submitSearch();
};

const deleteSelectedSeason = (season: ManagedSeason): void => {
    router.delete(destroySeason(season.id).url, {
        preserveScroll: true,
    });
};

const activateSelectedSeason = (season: ManagedSeason): void => {
    router.post(activateSeason(season.id).url, {}, {
        preserveScroll: true,
    });
};

const closeSelectedSeason = (season: ManagedSeason): void => {
    router.post(closeSeason(season.id).url, {}, {
        preserveScroll: true,
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Seasons" />

        <PageContainer>
            <PageHeader
                title="Seasons"
                description="Define the yearly competition containers, their registration window, and the operational status the rest of Negadras will attach to."
            >
                <template #eyebrow>
                    <div class="inline-flex items-center gap-2 rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-xs font-medium tracking-[0.2em] text-sky-900 uppercase dark:border-sky-500/30 dark:bg-sky-500/10 dark:text-sky-100">
                        <CalendarRange class="size-3.5" />
                        Competition structure
                    </div>
                </template>
                <template #actions>
                    <Button v-if="auth.permissions.includes('seasons.create')" as-child>
                        <Link :href="createSeason()">
                            <Plus class="size-4" />
                            Create season
                        </Link>
                    </Button>
                </template>
            </PageHeader>

            <ResourceToolbar
                v-model:search="search"
                search-placeholder="Search seasons by name or slug"
                @submit="submitSearch"
                @reset="resetSearch"
            >
                <template #actions>
                    <div class="flex flex-wrap items-center gap-2">
                        <Button variant="outline" size="sm" :class="status === '' ? 'border-foreground' : ''" @click="status = ''; submitSearch()">
                            All
                        </Button>
                        <Button
                            v-for="option in statusOptions"
                            :key="option.value"
                            variant="outline"
                            size="sm"
                            :class="status === option.value ? 'border-foreground' : ''"
                            @click="status = option.value; submitSearch()"
                        >
                            {{ option.label }}
                        </Button>
                    </div>
                </template>
            </ResourceToolbar>

            <ResourceTable
                :has-results="seasons.data.length > 0"
                empty-title="No seasons found"
                empty-description="Create the first Negadras season so later modules have a real competition container."
                :empty-icon="CalendarRange"
            >
                <template #head>
                    <tr class="text-left text-xs tracking-wide text-muted-foreground uppercase">
                        <th class="px-4 py-3 font-medium">Season</th>
                        <th class="px-4 py-3 font-medium">Registration</th>
                        <th class="px-4 py-3 font-medium">Stages</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 font-medium text-right">Actions</th>
                    </tr>
                </template>

                <template #body>
                    <tr v-for="season in seasons.data" :key="season.id" class="align-top">
                        <td class="px-4 py-4">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <span class="font-medium text-foreground">{{ season.name }}</span>
                                    <Badge variant="secondary">{{ season.year }}</Badge>
                                </div>
                                <p class="text-sm text-muted-foreground">/{{ season.slug }}</p>
                                <p class="max-w-xl text-sm text-muted-foreground">
                                    {{ season.description || 'No season description added yet.' }}
                                </p>
                            </div>
                        </td>
                        <td class="px-4 py-4 text-sm text-muted-foreground">
                            <div>{{ season.registrationOpenAt ? new Date(season.registrationOpenAt).toLocaleString() : 'Not set' }}</div>
                            <div>{{ season.registrationCloseAt ? new Date(season.registrationCloseAt).toLocaleString() : 'Not set' }}</div>
                        </td>
                        <td class="px-4 py-4">
                            <Badge variant="outline">{{ season.stagesCount }} stages</Badge>
                        </td>
                        <td class="px-4 py-4">
                            <StatusBadge :label="season.statusLabel" :tone="season.statusTone" />
                        </td>
                        <td class="px-4 py-4">
                            <div class="flex items-center justify-end gap-2">
                                <Button
                                    v-if="auth.permissions.includes('seasons.update') && season.status !== 'active'"
                                    variant="ghost"
                                    size="icon-sm"
                                    class="rounded-full"
                                    @click="activateSelectedSeason(season)"
                                >
                                    <Play class="size-4" />
                                </Button>
                                <Button
                                    v-if="auth.permissions.includes('seasons.update') && season.status === 'active'"
                                    variant="ghost"
                                    size="icon-sm"
                                    class="rounded-full"
                                    @click="closeSelectedSeason(season)"
                                >
                                    <CircleStop class="size-4" />
                                </Button>
                                <ActionIconLink
                                    v-if="auth.permissions.includes('seasons.update')"
                                    :href="editSeason(season.id)"
                                    label="Edit season"
                                    :icon="SquarePen"
                                />
                                <ConfirmActionDialog
                                    v-if="auth.permissions.includes('seasons.delete')"
                                    title="Delete season"
                                    :description="`Delete ${season.name}? This is only safe before submissions depend on it.`"
                                    confirm-label="Delete season"
                                    @confirm="deleteSelectedSeason(season)"
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

            <ResourcePagination :resource="seasons" />
        </PageContainer>
    </AppLayout>
</template>
