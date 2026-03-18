<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { CircleStop, Plus, SquarePen, Trash2, Workflow } from 'lucide-vue-next';
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
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import { close as closeStage, create as createStage, destroy as destroyStage, edit as editStage, index as stagesIndex, open as openStage } from '@/routes/stages';
import type { Auth, BreadcrumbItem, ManagedStage, PaginatedResource, SelectOption } from '@/types';

type Props = {
    stages: PaginatedResource<ManagedStage>;
    filters: {
        search: string;
        seasonId: string;
        status: string;
    };
    seasonOptions: SelectOption[];
    statusOptions: SelectOption[];
};

const props = defineProps<Props>();
const page = usePage();
const auth = computed(() => page.props.auth as Auth);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Stages',
        href: stagesIndex(),
    },
];

const search = ref(props.filters.search);
const seasonId = ref(props.filters.seasonId || 'all');
const status = ref(props.filters.status || 'all');

const submitSearch = (): void => {
    router.get(
        stagesIndex.url({
            query: {
                search: search.value || undefined,
                season_id: seasonId.value === 'all' ? undefined : seasonId.value,
                status: status.value === 'all' ? undefined : status.value,
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
    seasonId.value = 'all';
    status.value = 'all';
    submitSearch();
};

const deleteSelectedStage = (stage: ManagedStage): void => {
    router.delete(destroyStage(stage.id).url, {
        preserveScroll: true,
    });
};

const openSelectedStage = (stage: ManagedStage): void => {
    router.post(openStage(stage.id).url, {}, {
        preserveScroll: true,
    });
};

const closeSelectedStage = (stage: ManagedStage): void => {
    router.post(closeStage(stage.id).url, {}, {
        preserveScroll: true,
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Stages" />

        <PageContainer>
            <PageHeader
                title="Stages"
                description="Stages define the real competition flow inside a season, including order, timing, and whether the stage is live."
            >
                <template #eyebrow>
                    <div class="inline-flex items-center gap-2 rounded-full border border-violet-200 bg-violet-50 px-3 py-1 text-xs font-medium tracking-[0.2em] text-violet-900 uppercase dark:border-violet-500/30 dark:bg-violet-500/10 dark:text-violet-100">
                        <Workflow class="size-3.5" />
                        Season workflow
                    </div>
                </template>
                <template #actions>
                    <Button v-if="auth.permissions.includes('stages.create')" as-child>
                        <Link :href="createStage()">
                            <Plus class="size-4" />
                            Create stage
                        </Link>
                    </Button>
                </template>
            </PageHeader>

            <ResourceToolbar
                v-model:search="search"
                search-placeholder="Search stages by name or code"
                @submit="submitSearch"
                @reset="resetSearch"
            >
                <template #actions>
                    <div class="flex flex-wrap items-center gap-2">
                        <Select v-model="seasonId">
                            <SelectTrigger class="w-52">
                                <SelectValue placeholder="All seasons" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All seasons</SelectItem>
                                <SelectItem v-for="option in seasonOptions" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <Select v-model="status">
                            <SelectTrigger class="w-44">
                                <SelectValue placeholder="All statuses" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All statuses</SelectItem>
                                <SelectItem v-for="option in statusOptions" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <Button variant="secondary" size="sm" @click="submitSearch()">
                            Apply
                        </Button>
                    </div>
                </template>
            </ResourceToolbar>

            <ResourceTable
                :has-results="stages.data.length > 0"
                empty-title="No stages found"
                empty-description="Create the stage sequence for a season so submissions can move through a real workflow."
                :empty-icon="Workflow"
            >
                <template #head>
                    <tr class="text-left text-xs tracking-wide text-muted-foreground uppercase">
                        <th class="px-4 py-3 font-medium">Stage</th>
                        <th class="px-4 py-3 font-medium">Season</th>
                        <th class="px-4 py-3 font-medium">Window</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 font-medium text-right">Actions</th>
                    </tr>
                </template>

                <template #body>
                    <tr v-for="stage in stages.data" :key="stage.id" class="align-top">
                        <td class="px-4 py-4">
                            <div class="space-y-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-medium text-foreground">{{ stage.name }}</span>
                                    <Badge variant="secondary">{{ stage.code }}</Badge>
                                    <Badge variant="outline">#{{ stage.orderIndex }}</Badge>
                                    <Badge v-if="stage.isLiveStage" variant="outline">Live</Badge>
                                </div>
                                <p class="text-sm text-muted-foreground">{{ stage.typeLabel }}</p>
                            </div>
                        </td>
                        <td class="px-4 py-4 text-sm text-muted-foreground">
                            {{ stage.seasonName || 'Unknown season' }}
                        </td>
                        <td class="px-4 py-4 text-sm text-muted-foreground">
                            <div>{{ stage.startsAt ? new Date(stage.startsAt).toLocaleString() : 'Not set' }}</div>
                            <div>{{ stage.endsAt ? new Date(stage.endsAt).toLocaleString() : 'Not set' }}</div>
                        </td>
                        <td class="px-4 py-4">
                            <StatusBadge :label="stage.statusLabel" :tone="stage.statusTone" />
                        </td>
                        <td class="px-4 py-4">
                            <div class="flex items-center justify-end gap-2">
                                <Button
                                    v-if="auth.permissions.includes('stages.update') && stage.status !== 'open'"
                                    variant="ghost"
                                    size="sm"
                                    @click="openSelectedStage(stage)"
                                >
                                    Open
                                </Button>
                                <Button
                                    v-if="auth.permissions.includes('stages.update') && stage.status === 'open'"
                                    variant="ghost"
                                    size="sm"
                                    @click="closeSelectedStage(stage)"
                                >
                                    <CircleStop class="size-4" />
                                    Close
                                </Button>
                                <ActionIconLink
                                    v-if="auth.permissions.includes('stages.update')"
                                    :href="editStage(stage.id)"
                                    label="Edit stage"
                                    :icon="SquarePen"
                                />
                                <ConfirmActionDialog
                                    v-if="auth.permissions.includes('stages.delete')"
                                    title="Delete stage"
                                    :description="`Delete ${stage.name}? This should only be done before real submissions depend on it.`"
                                    confirm-label="Delete stage"
                                    @confirm="deleteSelectedStage(stage)"
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

            <ResourcePagination :resource="stages" />
        </PageContainer>
    </AppLayout>
</template>
