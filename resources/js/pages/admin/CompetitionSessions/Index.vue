<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import PageContainer from '@/components/PageContainer.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { create as createCompetitionSession, edit as editCompetitionSession, index as competitionSessionsIndex, show as showCompetitionSession } from '@/routes/competition-sessions';
import type { BreadcrumbItem, PaginatedResource } from '@/types';

type SessionRow = {
    id: number;
    name: string;
    seasonName: string | null;
    stageName: string | null;
    panelName: string | null;
    typeLabel: string;
    statusLabel: string;
    statusTone: string;
    scheduledAt: string | null;
    location: string | null;
    presentersCount: number;
};

const props = defineProps<{
    sessions: PaginatedResource<SessionRow>;
    filters: {
        status?: string;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Competition sessions',
        href: competitionSessionsIndex(),
    },
];

const statusFilter = computed({
    get: () => props.filters.status ?? '',
    set: (value: string) => {
        router.get(competitionSessionsIndex().url, { status: value || undefined }, { preserveState: true, replace: true });
    },
});
</script>

<template>
    <Head title="Competition sessions" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <PageContainer>
            <PageHeader
                title="Competition sessions"
                description="Schedule live sessions, connect them to panels, and prepare the presentation-day queue."
            >
                <Button as-child>
                    <Link :href="createCompetitionSession()">
                        Create session
                    </Link>
                </Button>
            </PageHeader>

            <section class="rounded-2xl border border-border/70 bg-card p-6 shadow-sm">
                <div class="mb-4 flex items-center gap-3">
                    <label class="text-sm font-medium" for="status-filter">Status</label>
                    <select
                        id="status-filter"
                        v-model="statusFilter"
                        class="rounded-md border border-input bg-background px-3 py-2 text-sm"
                    >
                        <option value="">All</option>
                        <option value="scheduled">Scheduled</option>
                        <option value="live">Live</option>
                        <option value="paused">Paused</option>
                        <option value="completed">Completed</option>
                        <option value="archived">Archived</option>
                    </select>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead class="text-muted-foreground">
                            <tr>
                                <th class="px-3 py-2">Session</th>
                                <th class="px-3 py-2">Panel</th>
                                <th class="px-3 py-2">Status</th>
                                <th class="px-3 py-2">Schedule</th>
                                <th class="px-3 py-2">Queue</th>
                                <th class="px-3 py-2 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="session in sessions.data" :key="session.id" class="border-t border-border/60">
                                <td class="px-3 py-3">
                                    <div class="font-medium">{{ session.name }}</div>
                                    <div class="text-muted-foreground">
                                        {{ session.seasonName }} · {{ session.stageName }} · {{ session.typeLabel }}
                                    </div>
                                </td>
                                <td class="px-3 py-3">{{ session.panelName || 'No panel linked' }}</td>
                                <td class="px-3 py-3">{{ session.statusLabel }}</td>
                                <td class="px-3 py-3">
                                    <div>{{ session.scheduledAt || 'Unscheduled' }}</div>
                                    <div class="text-muted-foreground">{{ session.location || 'No location' }}</div>
                                </td>
                                <td class="px-3 py-3">{{ session.presentersCount }}</td>
                                <td class="px-3 py-3 text-right">
                                    <div class="flex justify-end gap-2">
                                        <Button as-child size="sm" variant="outline">
                                            <Link :href="showCompetitionSession(session.id)">Open</Link>
                                        </Button>
                                        <Button as-child size="sm" variant="outline">
                                            <Link :href="editCompetitionSession(session.id)">Edit</Link>
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </PageContainer>
    </AppLayout>
</template>
