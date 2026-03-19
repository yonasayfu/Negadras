<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Plus, Scale, SquarePen } from 'lucide-vue-next';
import EmptyState from '@/components/EmptyState.vue';
import PageContainer from '@/components/PageContainer.vue';
import PageHeader from '@/components/PageHeader.vue';
import ResourcePagination from '@/components/admin/ResourcePagination.vue';
import ResourceToolbar from '@/components/admin/ResourceToolbar.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import { create as createPanel, edit as editPanel, index as panelsIndex, show as showPanel } from '@/routes/panels';
import type { BreadcrumbItem, ManagedPanel, PaginatedResource, SelectOption } from '@/types';

type Props = {
    panels: PaginatedResource<ManagedPanel>;
    filters: {
        search: string;
        seasonId: string;
        status: string;
    };
    seasonOptions: SelectOption[];
    statusOptions: SelectOption[];
};

const props = defineProps<Props>();
const breadcrumbs: BreadcrumbItem[] = [{ title: 'Panels', href: panelsIndex() }];
const allSeasonsValue = '__all_seasons__';
const allStatusesValue = '__all_statuses__';

const updateFilters = (search: string, seasonId: string, status: string): void => {
    router.get(panelsIndex().url, {
        search,
        season_id: seasonId || undefined,
        status: status || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Panels" />

        <PageContainer>
            <PageHeader title="Panels" description="Create stage-specific judging groups and bind them to the correct rubric.">
                <template #actions>
                    <Button as-child>
                        <Link :href="createPanel()">
                            <Plus class="size-4" />
                            Create panel
                        </Link>
                    </Button>
                </template>
            </PageHeader>

            <ResourceToolbar :search="filters.search" search-placeholder="Search panel names" @update:search="(value) => updateFilters(value, filters.seasonId, filters.status)">
                <template #actions>
                    <div class="flex gap-2">
                        <Select :model-value="filters.seasonId || allSeasonsValue" @update:model-value="(value) => updateFilters(filters.search, String(value) === allSeasonsValue ? '' : String(value ?? ''), filters.status)">
                            <SelectTrigger class="w-[200px]">
                                <SelectValue placeholder="All seasons" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="allSeasonsValue">All seasons</SelectItem>
                                <SelectItem v-for="option in seasonOptions" :key="option.value" :value="String(option.value)">
                                    {{ option.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>

                        <Select :model-value="filters.status || allStatusesValue" @update:model-value="(value) => updateFilters(filters.search, filters.seasonId, String(value) === allStatusesValue ? '' : String(value ?? ''))">
                            <SelectTrigger class="w-[180px]">
                                <SelectValue placeholder="All statuses" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="allStatusesValue">All statuses</SelectItem>
                                <SelectItem v-for="option in statusOptions" :key="option.value" :value="String(option.value)">
                                    {{ option.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </template>
            </ResourceToolbar>

            <div v-if="panels.data.length === 0" class="rounded-[1.5rem] border border-border/70 bg-card/85 p-6 shadow-sm">
                <EmptyState title="No panels yet" description="Create the first panel once your rubric and judges are ready." :icon="Scale" />
            </div>

            <div v-else class="grid gap-4">
                <article v-for="panel in panels.data" :key="panel.id" class="rounded-[1.5rem] border border-border/70 bg-card/85 p-5 shadow-sm">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="space-y-3">
                            <div class="flex flex-wrap items-center gap-2">
                                <Link :href="showPanel(panel.id)" class="text-lg font-semibold text-foreground hover:underline">{{ panel.name }}</Link>
                                <StatusBadge :label="panel.statusLabel" :tone="panel.statusTone" />
                            </div>

                            <div class="grid gap-1 text-sm text-muted-foreground md:grid-cols-2">
                                <div>Season: {{ panel.seasonName || 'Unassigned' }}</div>
                                <div>Stage: {{ panel.stageName || 'Unassigned' }}</div>
                                <div>Rubric: {{ panel.rubricName || 'Missing rubric' }}</div>
                                <div>Members: {{ panel.membersCount }}</div>
                                <div>Submission assignments: {{ panel.submissionAssignmentsCount }}</div>
                            </div>
                        </div>

                        <Button as-child variant="outline">
                            <Link :href="editPanel(panel.id)">
                                <SquarePen class="size-4" />
                                Edit
                            </Link>
                        </Button>
                    </div>
                </article>
            </div>

            <ResourcePagination :resource="panels" />
        </PageContainer>
    </AppLayout>
</template>
