<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import PageContainer from '@/components/PageContainer.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { edit as editCompetitionSession, index as competitionSessionsIndex } from '@/routes/competition-sessions';
import { show as showLiveSession } from '@/routes/live-sessions';
import { show as showLiveDashboard } from '@/routes/live-dashboard';
import type { BreadcrumbItem } from '@/types';

const props = defineProps<{
    session: {
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
        scoresRevealed: boolean;
        presenters: Array<{
            id: number;
            title: string | null;
            presenterName: string | null;
            organizationName: string | null;
            orderIndex: number;
            appearanceStatusLabel: string;
        }>;
        events: Array<{
            id: number;
            eventLabel: string;
            actorName: string | null;
            createdAt: string | null;
        }>;
    };
    availableSubmissions: Array<{ value: number; label: string }>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Competition sessions', href: competitionSessionsIndex() },
    { title: props.session.name, href: showLiveSession(props.session.id) },
];
</script>

<template>
    <Head :title="session.name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <PageContainer>
            <PageHeader
                :title="session.name"
                :description="`${session.seasonName || 'Unknown season'} · ${session.stageName || 'Unknown stage'} · ${session.typeLabel}`"
            >
                <div class="flex gap-3">
                    <Button as-child variant="outline">
                        <Link :href="showLiveSession(session.id)">Open live control</Link>
                    </Button>
                    <Button as-child variant="outline">
                        <Link :href="showLiveDashboard(session.id)" target="_blank">Open dashboard</Link>
                    </Button>
                    <Button as-child>
                        <Link :href="editCompetitionSession(session.id)">Edit session</Link>
                    </Button>
                </div>
            </PageHeader>

            <section class="grid gap-6 lg:grid-cols-[1.2fr_0.8fr]">
                <div class="rounded-2xl border border-border/70 bg-card p-6 shadow-sm">
                    <h2 class="text-lg font-semibold">Presenter queue</h2>
                    <div class="mt-4 space-y-3">
                        <div v-for="presenter in session.presenters" :key="presenter.id" class="rounded-xl border border-border/60 p-4">
                            <div class="font-medium">{{ presenter.orderIndex }}. {{ presenter.title }}</div>
                            <div class="text-sm text-muted-foreground">{{ presenter.presenterName }} · {{ presenter.organizationName || 'Independent' }}</div>
                            <div class="mt-2 text-xs uppercase tracking-wide text-muted-foreground">{{ presenter.appearanceStatusLabel }}</div>
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <section class="rounded-2xl border border-border/70 bg-card p-6 shadow-sm">
                        <h2 class="text-lg font-semibold">Live summary</h2>
                        <dl class="mt-4 grid gap-3 text-sm">
                            <div><dt class="text-muted-foreground">Panel</dt><dd>{{ session.panelName || 'No panel linked' }}</dd></div>
                            <div><dt class="text-muted-foreground">Schedule</dt><dd>{{ session.scheduledAt || 'Unscheduled' }}</dd></div>
                            <div><dt class="text-muted-foreground">Location</dt><dd>{{ session.location || 'No location' }}</dd></div>
                            <div><dt class="text-muted-foreground">Score reveal</dt><dd>{{ session.scoresRevealed ? 'Visible' : 'Hidden' }}</dd></div>
                        </dl>
                    </section>

                    <section class="rounded-2xl border border-border/70 bg-card p-6 shadow-sm">
                        <h2 class="text-lg font-semibold">Recent events</h2>
                        <div class="mt-4 space-y-3 text-sm">
                            <div v-for="event in session.events" :key="event.id" class="rounded-xl border border-border/60 p-3">
                                <div class="font-medium">{{ event.eventLabel }}</div>
                                <div class="text-muted-foreground">{{ event.actorName || 'System' }} · {{ event.createdAt }}</div>
                            </div>
                        </div>
                    </section>
                </div>
            </section>
        </PageContainer>
    </AppLayout>
</template>
