<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import PageContainer from '@/components/PageContainer.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as feedbackIndex } from '@/routes/feedback';
import type { BreadcrumbItem } from '@/types';

type Props = {
    packet: {
        id: number;
        submissionTitle: string | null;
        stageName: string | null;
        organizationName: string | null;
        summary: string;
        strengths: string | null;
        improvementAreas: string | null;
        nextStepGuidance: string | null;
        scoreSummaryOptional: number | null;
        sentAtOptional: string | null;
    };
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Feedback',
        href: feedbackIndex(),
    },
    {
        title: props.packet.submissionTitle ?? 'Packet',
        href: feedbackIndex(),
    },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="packet.submissionTitle || 'Feedback packet'" />

        <PageContainer>
            <PageHeader
                :title="packet.submissionTitle || 'Feedback packet'"
                description="Presenter-visible summary prepared from the review pipeline."
            >
                <template #actions>
                    <Button as-child variant="outline">
                        <Link :href="feedbackIndex()">
                            <ArrowLeft class="size-4" />
                            Back to feedback
                        </Link>
                    </Button>
                </template>
            </PageHeader>

            <div class="grid gap-6 lg:grid-cols-[0.75fr_1.25fr]">
                <aside class="rounded-[1.5rem] border border-border/70 bg-card/85 p-5 shadow-sm">
                    <dl class="grid gap-3 text-sm">
                        <div>
                            <dt class="text-muted-foreground">Stage</dt>
                            <dd class="font-medium">{{ packet.stageName || 'No stage' }}</dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">Organization</dt>
                            <dd class="font-medium">{{ packet.organizationName || 'Independent presenter' }}</dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">Released</dt>
                            <dd class="font-medium">{{ packet.sentAtOptional || 'Pending' }}</dd>
                        </div>
                        <div v-if="packet.scoreSummaryOptional !== null">
                            <dt class="text-muted-foreground">Score summary</dt>
                            <dd class="font-medium">{{ Number(packet.scoreSummaryOptional).toFixed(2) }}</dd>
                        </div>
                    </dl>
                </aside>

                <section class="grid gap-4">
                    <article class="rounded-[1.5rem] border border-border/70 bg-card/85 p-5 shadow-sm">
                        <div class="text-sm font-semibold uppercase tracking-[0.18em] text-muted-foreground">Summary</div>
                        <p class="mt-3 whitespace-pre-wrap text-sm leading-7">{{ packet.summary }}</p>
                    </article>

                    <article class="rounded-[1.5rem] border border-border/70 bg-card/85 p-5 shadow-sm">
                        <div class="text-sm font-semibold uppercase tracking-[0.18em] text-muted-foreground">Strengths</div>
                        <p class="mt-3 whitespace-pre-wrap text-sm leading-7">{{ packet.strengths || 'No strengths noted.' }}</p>
                    </article>

                    <article class="rounded-[1.5rem] border border-border/70 bg-card/85 p-5 shadow-sm">
                        <div class="text-sm font-semibold uppercase tracking-[0.18em] text-muted-foreground">Improvement areas</div>
                        <p class="mt-3 whitespace-pre-wrap text-sm leading-7">{{ packet.improvementAreas || 'No improvement areas noted.' }}</p>
                    </article>

                    <article class="rounded-[1.5rem] border border-border/70 bg-card/85 p-5 shadow-sm">
                        <div class="text-sm font-semibold uppercase tracking-[0.18em] text-muted-foreground">Next-step guidance</div>
                        <p class="mt-3 whitespace-pre-wrap text-sm leading-7">{{ packet.nextStepGuidance || 'No next-step guidance provided.' }}</p>
                    </article>
                </section>
            </div>
        </PageContainer>
    </AppLayout>
</template>
