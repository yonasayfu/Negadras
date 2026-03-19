<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight } from 'lucide-vue-next';
import PageContainer from '@/components/PageContainer.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { show as showFeedback } from '@/routes/feedback';
import type { BreadcrumbItem } from '@/types';

type Props = {
    packets: Array<{
        id: number;
        submissionTitle: string | null;
        stageName: string | null;
        organizationName: string | null;
        sentAtOptional: string | null;
        scoreSummaryOptional: number | null;
        summary: string;
    }>;
};

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Feedback',
        href: '#',
    },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Feedback" />

        <PageContainer>
            <PageHeader
                title="Feedback"
                description="Review presenter-visible guidance, score summaries, and next-step recommendations released from the evaluation flow."
            />

            <div v-if="packets.length === 0" class="rounded-[1.5rem] border border-dashed border-border bg-card/70 p-10 text-sm text-muted-foreground">
                No feedback packets are visible to your account yet.
            </div>

            <div class="grid gap-4">
                <article
                    v-for="packet in packets"
                    :key="packet.id"
                    class="rounded-[1.5rem] border border-border/70 bg-card/85 p-5 shadow-sm"
                >
                    <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                        <div>
                            <div class="text-lg font-semibold">{{ packet.submissionTitle || 'Untitled submission' }}</div>
                            <div class="mt-1 grid gap-1 text-sm text-muted-foreground md:grid-cols-2">
                                <div>Stage: {{ packet.stageName || 'No stage' }}</div>
                                <div>Organization: {{ packet.organizationName || 'Independent presenter' }}</div>
                                <div>Released: {{ packet.sentAtOptional || 'Pending' }}</div>
                                <div v-if="packet.scoreSummaryOptional !== null">Score summary: {{ Number(packet.scoreSummaryOptional).toFixed(2) }}</div>
                            </div>
                            <p class="mt-3 max-w-3xl text-sm leading-6 text-muted-foreground">{{ packet.summary }}</p>
                        </div>
                        <Button as-child>
                            <Link :href="showFeedback(packet.id)">
                                Open packet
                                <ArrowRight class="size-4" />
                            </Link>
                        </Button>
                    </div>
                </article>
            </div>
        </PageContainer>
    </AppLayout>
</template>
