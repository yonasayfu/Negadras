<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ClipboardCheck, SquarePen } from 'lucide-vue-next';
import EmptyState from '@/components/EmptyState.vue';
import PageContainer from '@/components/PageContainer.vue';
import PageHeader from '@/components/PageHeader.vue';
import ResourcePagination from '@/components/admin/ResourcePagination.vue';
import ResourceToolbar from '@/components/admin/ResourceToolbar.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import { create as createRubric, edit as editRubric, index as rubricsIndex } from '@/routes/rubrics';
import type { BreadcrumbItem, ManagedRubric, PaginatedResource, SelectOption } from '@/types';

type Props = {
    rubrics: PaginatedResource<ManagedRubric>;
    filters: {
        search: string;
        status: string;
    };
    statusOptions: SelectOption[];
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Rubrics', href: rubricsIndex() }];
const allStatusValue = '__all_statuses__';

const updateFilters = (search: string, status: string): void => {
    router.get(rubricsIndex().url, { search, status: status || undefined }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Rubrics" />

        <PageContainer>
            <PageHeader title="Rubrics" description="Define weighted scoring structures that panels will use consistently across submissions.">
                <template #actions>
                    <Button as-child>
                        <Link :href="createRubric()">
                            <ClipboardCheck class="size-4" />
                            Create rubric
                        </Link>
                    </Button>
                </template>
            </PageHeader>

            <ResourceToolbar :search="filters.search" search-placeholder="Search rubrics" @update:search="(value) => updateFilters(value, filters.status)">
                <template #actions>
                    <Select :model-value="filters.status || allStatusValue" @update:model-value="(value) => updateFilters(filters.search, String(value) === allStatusValue ? '' : String(value ?? ''))">
                        <SelectTrigger class="w-[180px]">
                            <SelectValue placeholder="All statuses" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem :value="allStatusValue">All statuses</SelectItem>
                            <SelectItem v-for="option in statusOptions" :key="option.value" :value="String(option.value)">
                                {{ option.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </template>
            </ResourceToolbar>

            <div v-if="rubrics.data.length === 0" class="rounded-[1.5rem] border border-border/70 bg-card/85 p-6 shadow-sm">
                <EmptyState title="No rubrics yet" description="Create the first rubric before forming judging panels." :icon="ClipboardCheck" />
            </div>

            <div v-else class="grid gap-4">
                <article v-for="rubric in rubrics.data" :key="rubric.id" class="rounded-[1.5rem] border border-border/70 bg-card/85 p-5 shadow-sm">
                    <div class="flex flex-col gap-4 lg:flex-row lg:justify-between">
                        <div class="space-y-3">
                            <div class="flex flex-wrap items-center gap-2">
                                <div class="text-lg font-semibold text-foreground">{{ rubric.name }}</div>
                                <StatusBadge :label="rubric.isActive ? 'Active' : 'Inactive'" :tone="rubric.isActive ? 'published' : 'archived'" />
                            </div>
                            <p class="text-sm text-muted-foreground">{{ rubric.description || 'No rubric description yet.' }}</p>
                            <div class="grid gap-1 text-sm text-muted-foreground md:grid-cols-2">
                                <div>Total weight: {{ rubric.totalWeight }}</div>
                                <div>Criteria: {{ rubric.criteriaCount }}</div>
                                <div>Stages: {{ rubric.stageNames.join(', ') || 'All / not bound' }}</div>
                                <div>Industries: {{ rubric.industryNames.join(', ') || 'All / not bound' }}</div>
                            </div>
                        </div>

                        <Button as-child variant="outline">
                            <Link :href="editRubric(rubric.id)">
                                <SquarePen class="size-4" />
                                Edit
                            </Link>
                        </Button>
                    </div>
                </article>
            </div>

            <ResourcePagination :resource="rubrics" />
        </PageContainer>
    </AppLayout>
</template>
