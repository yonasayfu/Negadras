<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import PageContainer from '@/components/PageContainer.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { show as showCompetitionSession } from '@/routes/competition-sessions';
import { show as showLiveDashboard } from '@/routes/live-dashboard';
import { update as updateQueue } from '@/routes/live-sessions/queue';
import { update as updateProjection } from '@/routes/live-sessions/projection';
import { store as storePresenter } from '@/routes/live-sessions/presenters';
import { show as showLiveSession, transition as transitionLiveSession } from '@/routes/live-sessions';
import type { BreadcrumbItem } from '@/types';

const props = defineProps<{
    session: {
        id: number;
        name: string;
        seasonName: string | null;
        stageName: string | null;
        status: string;
        statusLabel: string;
        statusTone: string;
        scoresRevealed: boolean;
        queue: Array<{
            id: number;
            submissionId: number;
            title: string | null;
            presenterName: string | null;
            organizationName: string | null;
            orderIndex: number;
            appearanceStatus: string;
            appearanceStatusLabel: string;
        }>;
        snapshot: {
            currentPresenter: {
                id: number;
                title: string | null;
                presenterName: string | null;
                aggregateScore: number | null;
            } | null;
            nextPresenter: {
                id: number;
                title: string | null;
            } | null;
            judgeCompletion: {
                totalJudges: number;
                completedJudges: number;
            };
        } | null;
        projection: {
            id: number;
            statusLabel: string;
            sourceLabel: string | null;
        } | null;
    };
    availableSubmissions: Array<{ value: number; label: string }>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Competition sessions', href: showCompetitionSession(props.session.id) },
    { title: 'Live control', href: showLiveSession(props.session.id) },
];

const addPresenterForm = useForm({ submission_id: '' });
const projectionForm = useForm({ intent: 'request', source_label: '', notes: '' });
const queue = ref(props.session.queue.map((presenter) => ({ ...presenter })));

let timer: number | undefined;

onMounted(() => {
    timer = window.setInterval(() => {
        router.reload({ only: ['session'] });
    }, 5000);
});

onUnmounted(() => {
    if (timer !== undefined) {
        window.clearInterval(timer);
    }
});

watch(
    () => props.session.queue,
    (nextQueue) => {
        queue.value = nextQueue.map((presenter) => ({ ...presenter }));
    },
);

const doTransition = (intent: string, sessionPresenterId?: number): void => {
    router.post(
        transitionLiveSession(props.session.id),
        { intent, session_presenter_id: sessionPresenterId },
        { preserveScroll: true },
    );
};

const visibleQueue = computed(() =>
    queue.value.map((presenter, index) => ({
        ...presenter,
        orderIndex: index + 1,
    })),
);

const movePresenter = (presenterId: number, direction: 'up' | 'down'): void => {
    const index = queue.value.findIndex((presenter) => presenter.id === presenterId);

    if (index === -1) {
        return;
    }

    const targetIndex = direction === 'up' ? index - 1 : index + 1;

    if (targetIndex < 0 || targetIndex >= queue.value.length) {
        return;
    }

    const reorderedQueue = [...queue.value];
    const [presenter] = reorderedQueue.splice(index, 1);

    reorderedQueue.splice(targetIndex, 0, presenter);
    queue.value = reorderedQueue;
};

const saveQueueOrder = (): void => {
    router.put(
        updateQueue(props.session.id),
        {
            presenters: queue.value.map((presenter, index) => ({
                id: presenter.id,
                order_index: index + 1,
            })),
        },
        { preserveScroll: true },
    );
};
</script>

<template>
    <Head :title="`${session.name} live control`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <PageContainer>
            <PageHeader
                :title="`${session.name} live control`"
                :description="`${session.seasonName || 'Unknown season'} · ${session.stageName || 'Unknown stage'} · ${session.statusLabel}`"
            >
                <div class="flex gap-3">
                    <Button as-child variant="outline">
                        <Link :href="showLiveDashboard(session.id)" target="_blank">Open studio dashboard</Link>
                    </Button>
                </div>
            </PageHeader>

            <section class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
                <div class="space-y-6">
                    <section class="rounded-2xl border border-border/70 bg-card p-6 shadow-sm">
                        <div class="flex flex-wrap gap-3">
                            <Button @click="doTransition('start')">Start</Button>
                            <Button variant="outline" @click="doTransition('pause')">Pause</Button>
                            <Button variant="outline" @click="doTransition('resume')">Resume</Button>
                            <Button variant="outline" @click="doTransition('advance_presenter')">Next presenter</Button>
                            <Button variant="outline" @click="doTransition(session.scoresRevealed ? 'hide_scores' : 'reveal_scores')">
                                {{ session.scoresRevealed ? 'Hide scores' : 'Reveal scores' }}
                            </Button>
                            <Button variant="outline" @click="doTransition('complete')">Complete session</Button>
                        </div>

                        <div class="mt-6 grid gap-4 md:grid-cols-3">
                            <div class="rounded-xl border border-border/60 p-4">
                                <div class="text-xs uppercase tracking-wide text-muted-foreground">Current presenter</div>
                                <div class="mt-2 font-medium">{{ session.snapshot?.currentPresenter?.title || 'Waiting' }}</div>
                                <div class="text-sm text-muted-foreground">{{ session.snapshot?.currentPresenter?.presenterName || 'No active presenter' }}</div>
                            </div>
                            <div class="rounded-xl border border-border/60 p-4">
                                <div class="text-xs uppercase tracking-wide text-muted-foreground">Judge completion</div>
                                <div class="mt-2 text-2xl font-semibold">{{ session.snapshot?.judgeCompletion.completedJudges || 0 }}/{{ session.snapshot?.judgeCompletion.totalJudges || 0 }}</div>
                            </div>
                            <div class="rounded-xl border border-border/60 p-4">
                                <div class="text-xs uppercase tracking-wide text-muted-foreground">Aggregate score</div>
                                <div class="mt-2 text-2xl font-semibold">{{ session.snapshot?.currentPresenter?.aggregateScore ?? 'Hidden' }}</div>
                            </div>
                        </div>
                    </section>

                    <section class="rounded-2xl border border-border/70 bg-card p-6 shadow-sm">
                        <div class="flex items-center justify-between">
                            <h2 class="text-lg font-semibold">Presenter queue</h2>
                            <Button variant="outline" @click="saveQueueOrder">Save order</Button>
                        </div>
                        <div class="mt-4 space-y-3">
                            <div v-for="(presenter, index) in visibleQueue" :key="presenter.id" class="rounded-xl border border-border/60 p-4">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <div class="font-medium">{{ presenter.orderIndex }}. {{ presenter.title }}</div>
                                        <div class="text-sm text-muted-foreground">{{ presenter.presenterName }} · {{ presenter.organizationName || 'Independent' }}</div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <Button size="sm" variant="outline" :disabled="index === 0" @click="movePresenter(presenter.id, 'up')">Up</Button>
                                        <Button size="sm" variant="outline" :disabled="index === visibleQueue.length - 1" @click="movePresenter(presenter.id, 'down')">Down</Button>
                                        <Button size="sm" variant="outline" @click="doTransition('activate_presenter', presenter.id)">Go live</Button>
                                    </div>
                                </div>
                                <div class="mt-2 text-xs uppercase tracking-wide text-muted-foreground">{{ presenter.appearanceStatusLabel }}</div>
                            </div>
                        </div>
                    </section>
                </div>

                <div class="space-y-6">
                    <section class="rounded-2xl border border-border/70 bg-card p-6 shadow-sm">
                        <h2 class="text-lg font-semibold">Add presenter</h2>
                        <form class="mt-4 space-y-3" @submit.prevent="addPresenterForm.post(storePresenter(session.id).url)">
                            <select v-model="addPresenterForm.submission_id" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                                <option value="">Select submission</option>
                                <option v-for="option in availableSubmissions" :key="option.value" :value="option.value">{{ option.label }}</option>
                            </select>
                            <Button :disabled="addPresenterForm.processing" type="submit">Add to queue</Button>
                        </form>
                    </section>

                    <section class="rounded-2xl border border-border/70 bg-card p-6 shadow-sm">
                        <h2 class="text-lg font-semibold">Projection control</h2>
                        <p class="mt-2 text-sm text-muted-foreground">This is the MVP-safe placeholder for studio projection approval and shutdown.</p>
                        <div v-if="session.projection" class="mt-4 rounded-xl border border-border/60 p-4 text-sm">
                            {{ session.projection.statusLabel }} · {{ session.projection.sourceLabel || 'No source label' }}
                        </div>
                        <form class="mt-4 space-y-3" @submit.prevent="projectionForm.put(updateProjection(session.id).url)">
                            <select v-model="projectionForm.intent" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                                <option value="request">Request projection</option>
                                <option value="approve">Approve/start projection</option>
                                <option value="end">End projection</option>
                            </select>
                            <input v-model="projectionForm.source_label" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm" placeholder="Source label" />
                            <textarea v-model="projectionForm.notes" class="min-h-24 w-full rounded-md border border-input bg-background px-3 py-2 text-sm" placeholder="Notes"></textarea>
                            <Button :disabled="projectionForm.processing" type="submit">Update projection</Button>
                        </form>
                    </section>
                </div>
            </section>
        </PageContainer>
    </AppLayout>
</template>
