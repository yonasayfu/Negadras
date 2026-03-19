<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import PageContainer from '@/components/PageContainer.vue';
import PageHeader from '@/components/PageHeader.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as judgeLiveIndex, show as showJudgeLive } from '@/routes/judge-live';
import type { BreadcrumbItem } from '@/types';

defineProps<{
    sessions: Array<{
        id: number;
        name: string;
        seasonName: string | null;
        stageName: string | null;
        statusLabel: string;
        statusTone: string;
        scheduledAt: string | null;
    }>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Judge live sessions', href: judgeLiveIndex() },
];
</script>

<template>
    <Head title="Judge live sessions" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <PageContainer>
            <PageHeader
                title="Judge live sessions"
                description="Touch-friendly session view for live presentation scoring."
            />

            <section class="grid gap-4 md:grid-cols-2">
                <Link
                    v-for="session in sessions"
                    :key="session.id"
                    :href="showJudgeLive(session.id)"
                    class="rounded-2xl border border-border/70 bg-card p-6 shadow-sm transition hover:border-primary/40"
                >
                    <div class="font-semibold">{{ session.name }}</div>
                    <div class="mt-2 text-sm text-muted-foreground">{{ session.seasonName }} · {{ session.stageName }}</div>
                    <div class="mt-3 text-sm">{{ session.statusLabel }} · {{ session.scheduledAt || 'Unscheduled' }}</div>
                </Link>
            </section>
        </PageContainer>
    </AppLayout>
</template>
