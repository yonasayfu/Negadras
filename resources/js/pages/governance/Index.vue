<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import PageContainer from '@/components/PageContainer.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as governanceIndex } from '@/routes/governance';
import { send as governanceSendReminders } from '@/routes/governance/reminders';
import type { BreadcrumbItem } from '@/types';

defineProps<{
    summary: {
        overrideEvents: number;
        exportJobs: number;
        notificationLogs: number;
        activityLogs: number;
    };
    overrideEvents: Array<{
        id: number;
        eventType: string;
        eventLabel: string;
        actor: string | null;
        submissionTitle: string | null;
        sessionName: string | null;
        reason: string | null;
        beforeState: Record<string, unknown> | null;
        afterState: Record<string, unknown> | null;
        createdAt: string | null;
    }>;
    exportJobs: Array<{
        id: number;
        type: string;
        statusLabel: string;
        rowCount: number | null;
        requestedBy: string | null;
        completedAt: string | null;
    }>;
    notificationLogs: Array<{
        id: number;
        category: string;
        title: string;
        recipient: string | null;
        sender: string | null;
        level: string;
        sentAt: string | null;
    }>;
    recentActivity: Array<{
        id: number;
        event: string;
        description: string;
        actor: string | null;
        createdAt: string | null;
    }>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Governance',
        href: governanceIndex(),
    },
];

const sendReminders = (): void => {
    router.post(governanceSendReminders().url, {}, { preserveScroll: true });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Governance" />

        <PageContainer>
            <PageHeader
                title="Governance"
                description="Track override actions, notification delivery, and export activity from one operational control surface."
            >
                <template #actions>
                    <Button @click="sendReminders">
                        Trigger reminders
                    </Button>
                </template>
            </PageHeader>

            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <article class="rounded-2xl border border-border/70 bg-card/85 p-5">
                    <div class="text-sm text-muted-foreground">Override events</div>
                    <div class="mt-2 text-3xl font-semibold">{{ summary.overrideEvents }}</div>
                </article>
                <article class="rounded-2xl border border-border/70 bg-card/85 p-5">
                    <div class="text-sm text-muted-foreground">Export jobs</div>
                    <div class="mt-2 text-3xl font-semibold">{{ summary.exportJobs }}</div>
                </article>
                <article class="rounded-2xl border border-border/70 bg-card/85 p-5">
                    <div class="text-sm text-muted-foreground">Notification logs</div>
                    <div class="mt-2 text-3xl font-semibold">{{ summary.notificationLogs }}</div>
                </article>
                <article class="rounded-2xl border border-border/70 bg-card/85 p-5">
                    <div class="text-sm text-muted-foreground">Activity logs</div>
                    <div class="mt-2 text-3xl font-semibold">{{ summary.activityLogs }}</div>
                </article>
            </section>

            <section class="grid gap-6 xl:grid-cols-2">
                <article class="rounded-2xl border border-border/70 bg-card/85 p-5">
                    <h2 class="text-lg font-semibold">Override events</h2>
                    <div class="mt-4 space-y-3">
                        <div v-for="event in overrideEvents" :key="event.id" class="rounded-xl border border-border/60 px-4 py-3">
                            <div class="flex items-center justify-between gap-4">
                                <div class="font-medium">{{ event.eventLabel }}</div>
                                <div class="text-sm text-muted-foreground">{{ event.createdAt || 'N/A' }}</div>
                            </div>
                            <div class="mt-1 text-sm text-muted-foreground">
                                {{ event.submissionTitle || event.sessionName || 'No linked record' }} · {{ event.actor || 'System' }}
                            </div>
                            <div v-if="event.reason" class="mt-2 text-sm">{{ event.reason }}</div>
                        </div>
                    </div>
                </article>

                <article class="rounded-2xl border border-border/70 bg-card/85 p-5">
                    <h2 class="text-lg font-semibold">Notification delivery</h2>
                    <div class="mt-4 space-y-3">
                        <div v-for="log in notificationLogs" :key="log.id" class="rounded-xl border border-border/60 px-4 py-3">
                            <div class="flex items-center justify-between gap-4">
                                <div class="font-medium">{{ log.title }}</div>
                                <div class="text-sm text-muted-foreground">{{ log.sentAt || 'N/A' }}</div>
                            </div>
                            <div class="mt-1 text-sm text-muted-foreground">
                                {{ log.category }} · {{ log.recipient || 'Unknown recipient' }} · {{ log.sender || 'System' }}
                            </div>
                        </div>
                    </div>
                </article>
            </section>

            <section class="grid gap-6 xl:grid-cols-2">
                <article class="rounded-2xl border border-border/70 bg-card/85 p-5">
                    <h2 class="text-lg font-semibold">Recent export jobs</h2>
                    <div class="mt-4 space-y-3">
                        <div v-for="job in exportJobs" :key="job.id" class="rounded-xl border border-border/60 px-4 py-3">
                            <div class="flex items-center justify-between gap-4">
                                <div class="font-medium">{{ job.type }}</div>
                                <div class="text-sm text-muted-foreground">{{ job.completedAt || 'Pending' }}</div>
                            </div>
                            <div class="mt-1 text-sm text-muted-foreground">
                                {{ job.requestedBy || 'System' }} · {{ job.rowCount || 0 }} rows · {{ job.statusLabel }}
                            </div>
                        </div>
                    </div>
                </article>

                <article class="rounded-2xl border border-border/70 bg-card/85 p-5">
                    <h2 class="text-lg font-semibold">Recent activity</h2>
                    <div class="mt-4 space-y-3">
                        <div v-for="log in recentActivity" :key="log.id" class="rounded-xl border border-border/60 px-4 py-3">
                            <div class="font-medium">{{ log.event }}</div>
                            <div class="mt-1 text-sm text-muted-foreground">{{ log.description }}</div>
                            <div class="mt-2 text-xs text-muted-foreground">
                                {{ log.actor || 'System' }} · {{ log.createdAt || 'N/A' }}
                            </div>
                        </div>
                    </div>
                </article>
            </section>
        </PageContainer>
    </AppLayout>
</template>
