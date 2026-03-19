<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Gavel, SquarePen, UserPlus } from 'lucide-vue-next';
import EmptyState from '@/components/EmptyState.vue';
import PageContainer from '@/components/PageContainer.vue';
import PageHeader from '@/components/PageHeader.vue';
import ResourcePagination from '@/components/admin/ResourcePagination.vue';
import ResourceToolbar from '@/components/admin/ResourceToolbar.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import { create as createJudge, edit as editJudge, index as judgesIndex } from '@/routes/judges';
import type { BreadcrumbItem, ManagedJudge, PaginatedResource, SelectOption } from '@/types';

type Props = {
    judges: PaginatedResource<ManagedJudge>;
    filters: {
        search: string;
        specialization: string;
    };
    specializationOptions: SelectOption[];
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Judges',
        href: judgesIndex(),
    },
];

const allSpecializationsValue = '__all_specializations__';

const updateFilters = (search: string, specialization: string): void => {
    router.get(judgesIndex().url, { search, specialization: specialization || undefined }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Judges" />

        <PageContainer>
            <PageHeader title="Judges" description="Manage Negadras judge profiles, specialization context, and panel capacity.">
                <template #actions>
                    <Button as-child>
                        <Link :href="createJudge()">
                            <UserPlus class="size-4" />
                            Create judge profile
                        </Link>
                    </Button>
                </template>
            </PageHeader>

            <ResourceToolbar
                :search="filters.search"
                search-placeholder="Search judges, specialization, or organization"
                @update:search="(value) => updateFilters(value, filters.specialization)"
            >
                <template #actions>
                    <Select :model-value="filters.specialization || allSpecializationsValue" @update:model-value="(value) => updateFilters(filters.search, String(value) === allSpecializationsValue ? '' : String(value ?? ''))">
                        <SelectTrigger class="w-[220px]">
                            <SelectValue placeholder="All specializations" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem :value="allSpecializationsValue">All specializations</SelectItem>
                            <SelectItem v-for="option in specializationOptions" :key="option.value" :value="String(option.value)">
                                {{ option.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </template>
            </ResourceToolbar>

            <div v-if="judges.data.length === 0" class="rounded-[1.5rem] border border-border/70 bg-card/85 p-6 shadow-sm">
                <EmptyState title="No judge profiles yet" description="Create the first judge profile before building panels." :icon="Gavel" />
            </div>

            <div v-else class="grid gap-4">
                <article v-for="judge in judges.data" :key="judge.id" class="rounded-[1.5rem] border border-border/70 bg-card/85 p-5 shadow-sm">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="space-y-3">
                            <div class="flex flex-wrap items-center gap-2">
                                <div class="text-lg font-semibold text-foreground">{{ judge.name || 'Unknown judge' }}</div>
                                <StatusBadge :label="judge.isActive ? 'Active' : 'Inactive'" :tone="judge.isActive ? 'published' : 'archived'" />
                            </div>

                            <div class="grid gap-1 text-sm text-muted-foreground md:grid-cols-2">
                                <div>Email: {{ judge.email || 'No email' }}</div>
                                <div>Professional title: {{ judge.professionalTitle || 'Not set' }}</div>
                                <div>Organization: {{ judge.organization || 'Not set' }}</div>
                                <div>Specialization: {{ judge.specialization || 'Not set' }}</div>
                            </div>

                            <div class="text-sm text-muted-foreground">
                                Panel memberships: {{ judge.panelMembershipsCount }}
                            </div>
                        </div>

                        <Button as-child variant="outline">
                            <Link :href="editJudge(judge.id)">
                                <SquarePen class="size-4" />
                                Edit
                            </Link>
                        </Button>
                    </div>
                </article>
            </div>

            <ResourcePagination :resource="judges" />
        </PageContainer>
    </AppLayout>
</template>
