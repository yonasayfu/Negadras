<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight, Trophy } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import PublicLayout from '@/layouts/public/PublicLayout.vue';
import { show as showShowcase } from '@/routes/showcase';

type Props = {
    entries: Array<{
        slug: string;
        title: string;
        subtitle: string | null;
        summary: string;
        winnerLabel: string | null;
        seasonName: string | null;
        industryName: string | null;
        presenterName: string | null;
    }>;
};

defineProps<Props>();
</script>

<template>
    <PublicLayout title="Showcase">
        <Head title="Showcase" />

        <section class="mx-auto max-w-7xl px-5 py-16 lg:px-8 lg:py-24">
            <div class="max-w-3xl">
                <div class="inline-flex items-center gap-2 rounded-full border border-stone-900/10 bg-white/70 px-4 py-2 text-xs font-semibold uppercase tracking-[0.2em] text-stone-600">
                    <Trophy class="size-3.5" />
                    Showcase
                </div>
                <h1 class="mt-6 text-5xl font-semibold tracking-[-0.05em] text-stone-950 md:text-6xl">
                    Finalists, winners, and featured stories from Negadras.
                </h1>
                <p class="mt-6 text-base leading-8 text-stone-700 md:text-lg">
                    The showcase highlights archived outcomes, standout sessions, and public-facing project summaries.
                </p>
            </div>

            <div v-if="entries.length === 0" class="mt-12 rounded-[2rem] border border-dashed border-stone-900/10 bg-white/65 p-10 text-sm text-stone-600">
                No public showcase entries are published yet.
            </div>

            <div class="mt-12 grid gap-6 lg:grid-cols-2">
                <article
                    v-for="entry in entries"
                    :key="entry.slug"
                    class="rounded-[2rem] border border-stone-900/10 bg-white/75 p-6 shadow-sm backdrop-blur"
                >
                    <div class="flex flex-wrap items-center gap-2 text-xs font-semibold uppercase tracking-[0.18em] text-stone-500">
                        <span>{{ entry.seasonName || 'Season' }}</span>
                        <span v-if="entry.industryName">• {{ entry.industryName }}</span>
                        <span v-if="entry.winnerLabel">• {{ entry.winnerLabel }}</span>
                    </div>

                    <h2 class="mt-4 text-2xl font-semibold tracking-[-0.03em] text-stone-950">
                        {{ entry.title }}
                    </h2>

                    <p v-if="entry.subtitle" class="mt-2 text-sm font-medium text-stone-700">
                        {{ entry.subtitle }}
                    </p>

                    <p class="mt-4 line-clamp-4 text-sm leading-7 text-stone-700">
                        {{ entry.summary }}
                    </p>

                    <div class="mt-5 text-sm text-stone-600">
                        Presenter: {{ entry.presenterName || 'Unknown presenter' }}
                    </div>

                    <div class="mt-6">
                        <Button as-child class="rounded-full bg-stone-900 text-stone-50 hover:bg-stone-800">
                            <Link :href="showShowcase(entry.slug)">
                                Open story
                                <ArrowRight class="size-4" />
                            </Link>
                        </Button>
                    </div>
                </article>
            </div>
        </section>
    </PublicLayout>
</template>
