<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import { onMounted, onUnmounted } from 'vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { store as storeJudgeScores } from '@/routes/judge-workspace/scores';
import { index as judgeLiveIndex } from '@/routes/judge-live';
import type { BreadcrumbItem } from '@/types';

const props = defineProps<{
    session: {
        id: number;
        name: string;
        seasonName: string | null;
        stageName: string | null;
        statusLabel: string;
        statusTone: string;
        scoresRevealed: boolean;
    };
    currentPresenter: {
        assignmentId: number;
        title: string | null;
        presenterName: string | null;
        organizationName: string | null;
        progress: { criteriaCount: number; scoredCount: number; isComplete: boolean };
        isLocked: boolean;
        rubric: Array<{
            id: number;
            name: string;
            description: string | null;
            maxScore: number;
            weight: number;
            existingScore: number | null;
            existingComment: string | null;
        }>;
        privateComment: string | null;
        presenterComment: string | null;
        files: Array<{ id: number; label: string; originalName: string; downloadUrl: string }>;
    } | null;
    snapshot: {
        nextPresenter: { title: string | null } | null;
    } | null;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Judge live sessions', href: judgeLiveIndex() },
];

const form = useForm({
    scores: (props.currentPresenter?.rubric ?? []).map((criterion) => ({
        rubric_criterion_id: criterion.id,
        score_value: criterion.existingScore ?? '',
        comment: criterion.existingComment ?? '',
    })),
    private_comment: props.currentPresenter?.privateComment ?? '',
    presenter_comment: props.currentPresenter?.presenterComment ?? '',
    intent: 'save',
});

let timer: number | undefined;

onMounted(() => {
    timer = window.setInterval(() => {
        router.reload({ only: ['currentPresenter', 'snapshot'] });
    }, 5000);
});

onUnmounted(() => {
    if (timer !== undefined) {
        window.clearInterval(timer);
    }
});

const submit = (intent: 'save' | 'submit'): void => {
    if (!props.currentPresenter) {
        return;
    }

    form.intent = intent;
    form.post(storeJudgeScores(props.currentPresenter.assignmentId).url, { preserveScroll: true });
};
</script>

<template>
    <Head :title="`${session.name} live scoring`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-background px-4 py-6 md:px-8">
            <div class="mx-auto max-w-5xl space-y-6">
                <header class="rounded-[2rem] border border-border/70 bg-card p-6 shadow-sm">
                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div>
                            <h1 class="text-3xl font-semibold">{{ session.name }}</h1>
                            <p class="text-sm text-muted-foreground">{{ session.seasonName }} · {{ session.stageName }} · {{ session.statusLabel }}</p>
                        </div>
                        <div class="text-sm text-muted-foreground">
                            Next presenter: {{ snapshot?.nextPresenter?.title || 'Not queued yet' }}
                        </div>
                    </div>
                </header>

                <section v-if="currentPresenter" class="grid gap-6 lg:grid-cols-[1.15fr_0.85fr]">
                    <div class="rounded-[2rem] border border-border/70 bg-card p-6 shadow-sm">
                        <div class="mb-6">
                            <h2 class="text-2xl font-semibold">{{ currentPresenter.title }}</h2>
                            <p class="text-sm text-muted-foreground">{{ currentPresenter.presenterName }} · {{ currentPresenter.organizationName || 'Independent' }}</p>
                        </div>

                        <div class="space-y-4">
                            <div v-for="(criterion, index) in currentPresenter.rubric" :key="criterion.id" class="rounded-xl border border-border/60 p-4">
                                <div class="mb-3">
                                    <div class="font-medium">{{ criterion.name }}</div>
                                    <div class="text-sm text-muted-foreground">{{ criterion.description }}</div>
                                </div>
                                <div class="grid gap-3 md:grid-cols-[140px_1fr]">
                                    <input v-model="form.scores[index].score_value" :max="criterion.maxScore" min="0" step="0.1" type="number" class="rounded-md border border-input bg-background px-3 py-2 text-sm" />
                                    <textarea v-model="form.scores[index].comment" class="min-h-20 rounded-md border border-input bg-background px-3 py-2 text-sm" placeholder="Criterion note"></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 grid gap-4 md:grid-cols-2">
                            <textarea v-model="form.private_comment" class="min-h-28 rounded-md border border-input bg-background px-3 py-2 text-sm" placeholder="Private note"></textarea>
                            <textarea v-model="form.presenter_comment" class="min-h-28 rounded-md border border-input bg-background px-3 py-2 text-sm" placeholder="Presenter-visible note"></textarea>
                        </div>

                        <div class="mt-6 flex flex-wrap justify-end gap-3">
                            <Button :disabled="form.processing || currentPresenter.isLocked" variant="outline" @click="submit('save')">Save draft</Button>
                            <Button :disabled="form.processing || currentPresenter.isLocked" @click="submit('submit')">Submit score</Button>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <section class="rounded-[2rem] border border-border/70 bg-card p-6 shadow-sm">
                            <h2 class="text-lg font-semibold">Scoring progress</h2>
                            <div class="mt-4 text-4xl font-semibold">{{ currentPresenter.progress.scoredCount }}/{{ currentPresenter.progress.criteriaCount }}</div>
                            <p class="mt-2 text-sm text-muted-foreground">{{ currentPresenter.isLocked ? 'Scoring is locked.' : 'Touch-friendly draft save is enabled.' }}</p>
                        </section>

                        <section class="rounded-[2rem] border border-border/70 bg-card p-6 shadow-sm">
                            <h2 class="text-lg font-semibold">Submission files</h2>
                            <div class="mt-4 space-y-3 text-sm">
                                <a v-for="file in currentPresenter.files" :key="file.id" :href="file.downloadUrl" class="block rounded-xl border border-border/60 p-3 hover:border-primary/40">
                                    {{ file.label }} · {{ file.originalName }}
                                </a>
                            </div>
                        </section>
                    </div>
                </section>

                <section v-else class="rounded-[2rem] border border-border/70 bg-card p-10 text-center shadow-sm">
                    <h2 class="text-xl font-semibold">Waiting for the moderator to activate a presenter</h2>
                    <p class="mt-2 text-sm text-muted-foreground">This screen polls the live session snapshot and updates automatically.</p>
                </section>
            </div>
        </div>
    </AppLayout>
</template>
