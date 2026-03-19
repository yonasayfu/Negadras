<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ShieldCheck, SquarePen, UserPlus } from 'lucide-vue-next';
import EmptyState from '@/components/EmptyState.vue';
import PageContainer from '@/components/PageContainer.vue';
import PageHeader from '@/components/PageHeader.vue';
import ResourcePagination from '@/components/admin/ResourcePagination.vue';
import ResourceToolbar from '@/components/admin/ResourceToolbar.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { create as createReviewer, edit as editReviewer, index as reviewersIndex } from '@/routes/reviewers';
import type { BreadcrumbItem, ManagedReviewer, PaginatedResource } from '@/types';

type Props = {
    reviewers: PaginatedResource<ManagedReviewer>;
    filters: {
        search: string;
    };
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Reviewers',
        href: reviewersIndex(),
    },
];

const updateSearch = (search: string): void => {
    router.get(reviewersIndex().url, { search }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Reviewers" />

        <PageContainer>
            <PageHeader
                title="Reviewers"
                description="Manage Negadras reviewer profiles, specialization context, and current assignment workload."
            >
                <template #actions>
                    <Button as-child>
                        <Link :href="createReviewer()">
                            <UserPlus class="size-4" />
                            Create reviewer profile
                        </Link>
                    </Button>
                </template>
            </PageHeader>

            <ResourceToolbar
                :search="filters.search"
                search-placeholder="Search reviewers, emails, organization, or specialization"
                @update:search="updateSearch"
            />

            <div
                v-if="reviewers.data.length === 0"
                class="rounded-[1.5rem] border border-border/70 bg-card/85 p-6 shadow-sm"
            >
                <EmptyState
                    title="No reviewer profiles yet"
                    description="Create the first reviewer profile before assigning screening work."
                    :icon="ShieldCheck"
                />
            </div>

            <div v-else class="grid gap-4">
                <article
                    v-for="reviewer in reviewers.data"
                    :key="reviewer.id"
                    class="rounded-[1.5rem] border border-border/70 bg-card/85 p-5 shadow-sm"
                >
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="space-y-3">
                            <div class="flex flex-wrap items-center gap-2">
                                <div class="text-lg font-semibold text-foreground">{{ reviewer.name || 'Unknown reviewer' }}</div>
                                <StatusBadge :label="reviewer.isActive ? 'Active' : 'Inactive'" :tone="reviewer.isActive ? 'published' : 'archived'" />
                            </div>

                            <div class="grid gap-1 text-sm text-muted-foreground md:grid-cols-2">
                                <div>Email: {{ reviewer.email || 'No email' }}</div>
                                <div>Professional title: {{ reviewer.professionalTitle || 'Not set' }}</div>
                                <div>Organization: {{ reviewer.organization || 'Not set' }}</div>
                                <div>Specialization: {{ reviewer.specialization || 'Not set' }}</div>
                            </div>

                            <div class="text-sm text-muted-foreground">
                                Active assignments: {{ reviewer.activeAssignmentsCount }}
                            </div>
                        </div>

                        <Button as-child variant="outline">
                            <Link :href="editReviewer(reviewer.id)">
                                <SquarePen class="size-4" />
                                Edit
                            </Link>
                        </Button>
                    </div>
                </article>
            </div>

            <ResourcePagination :resource="reviewers" />
        </PageContainer>
    </AppLayout>
</template>
