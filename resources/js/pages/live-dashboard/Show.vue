<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { onMounted, onUnmounted } from 'vue';
import PublicLayout from '@/layouts/public/PublicLayout.vue';

defineProps<{
    snapshot: {
        session: {
            name: string;
            typeLabel: string;
            statusLabel: string;
            location: string | null;
            scoresRevealed: boolean;
        };
        currentPresenter: {
            title: string | null;
            presenterName: string | null;
            organizationName: string | null;
            aggregateScore: number | null;
            isScoreVisible: boolean;
        } | null;
        nextPresenter: {
            title: string | null;
        } | null;
        judgeCompletion: {
            totalJudges: number;
            completedJudges: number;
        };
    };
}>();

let timer: number | undefined;

onMounted(() => {
    timer = window.setInterval(() => {
        router.reload({ only: ['snapshot'] });
    }, 5000);
});

onUnmounted(() => {
    if (timer !== undefined) {
        window.clearInterval(timer);
    }
});
</script>

<template>
    <Head :title="snapshot.session.name" />

    <PublicLayout>
        <div class="min-h-screen bg-[radial-gradient(circle_at_top,#dbeafe,transparent_30%),radial-gradient(circle_at_bottom,#fef3c7,transparent_35%)] px-6 py-10">
            <div class="mx-auto max-w-6xl space-y-8">
                <header class="rounded-[2.5rem] border border-white/60 bg-white/80 p-8 shadow-sm backdrop-blur">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                        <div>
                            <p class="text-sm uppercase tracking-[0.2em] text-muted-foreground">{{ snapshot.session.typeLabel }}</p>
                            <h1 class="mt-3 text-4xl font-semibold tracking-tight">{{ snapshot.session.name }}</h1>
                        </div>
                        <div class="text-right text-sm text-muted-foreground">
                            <div>{{ snapshot.session.statusLabel }}</div>
                            <div>{{ snapshot.session.location || 'Studio location pending' }}</div>
                        </div>
                    </div>
                </header>

                <section class="grid gap-6 lg:grid-cols-[1.2fr_0.8fr]">
                    <div class="rounded-[2.5rem] border border-white/60 bg-white/80 p-10 shadow-sm backdrop-blur">
                        <p class="text-sm uppercase tracking-[0.2em] text-muted-foreground">Current presenter</p>
                        <h2 class="mt-4 text-5xl font-semibold tracking-tight">{{ snapshot.currentPresenter?.title || 'Waiting for next presenter' }}</h2>
                        <p class="mt-4 text-xl text-muted-foreground">{{ snapshot.currentPresenter?.presenterName || 'Moderator has not started a presenter yet.' }}</p>
                        <p class="mt-2 text-lg text-muted-foreground">{{ snapshot.currentPresenter?.organizationName }}</p>
                    </div>

                    <div class="space-y-6">
                        <section class="rounded-[2rem] border border-white/60 bg-white/80 p-6 shadow-sm backdrop-blur">
                            <p class="text-sm uppercase tracking-[0.2em] text-muted-foreground">Next presenter</p>
                            <div class="mt-4 text-2xl font-semibold">{{ snapshot.nextPresenter?.title || 'Queue not ready' }}</div>
                        </section>

                        <section class="rounded-[2rem] border border-white/60 bg-white/80 p-6 shadow-sm backdrop-blur">
                            <p class="text-sm uppercase tracking-[0.2em] text-muted-foreground">Judge completion</p>
                            <div class="mt-4 text-4xl font-semibold">{{ snapshot.judgeCompletion.completedJudges }}/{{ snapshot.judgeCompletion.totalJudges }}</div>
                        </section>

                        <section class="rounded-[2rem] border border-white/60 bg-white/80 p-6 shadow-sm backdrop-blur">
                            <p class="text-sm uppercase tracking-[0.2em] text-muted-foreground">Aggregate score</p>
                            <div class="mt-4 text-4xl font-semibold">
                                {{
                                    snapshot.currentPresenter?.isScoreVisible
                                        ? (snapshot.currentPresenter?.aggregateScore ?? 'Pending')
                                        : 'Hidden until reveal'
                                }}
                            </div>
                        </section>
                    </div>
                </section>
            </div>
        </div>
    </PublicLayout>
</template>
