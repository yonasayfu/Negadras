<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Trophy } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import PublicLayout from '@/layouts/public/PublicLayout.vue';
import { index as showcaseIndex } from '@/routes/showcase';

type Props = {
    entry: {
        title: string;
        subtitle: string | null;
        summary: string;
        winnerLabel: string | null;
        seasonName: string | null;
        industryName: string | null;
        presenterName: string | null;
        organizationName: string | null;
        awards: Array<{
            label: string;
            notes: string | null;
        }>;
        highlights: Array<{
            title: string;
            summary: string;
            quoteOptional: string | null;
            quoteSourceOptional: string | null;
        }>;
    };
};

defineProps<Props>();
</script>

<template>
    <PublicLayout :title="entry.title">
        <Head :title="entry.title" />

        <section class="mx-auto max-w-6xl px-5 py-16 lg:px-8 lg:py-24">
            <Button as-child variant="outline" class="rounded-full border-stone-900/15 bg-white/70">
                <Link :href="showcaseIndex()">
                    <ArrowLeft class="size-4" />
                    Back to showcase
                </Link>
            </Button>

            <article class="mt-8 rounded-[2rem] border border-stone-900/10 bg-white/80 p-8 shadow-sm backdrop-blur lg:p-10">
                <div class="flex flex-wrap items-center gap-2 text-xs font-semibold uppercase tracking-[0.2em] text-stone-500">
                    <span>{{ entry.seasonName || 'Season' }}</span>
                    <span v-if="entry.industryName">• {{ entry.industryName }}</span>
                    <span v-if="entry.winnerLabel" class="inline-flex items-center gap-1">
                        <Trophy class="size-3.5" />
                        {{ entry.winnerLabel }}
                    </span>
                </div>

                <h1 class="mt-6 text-4xl font-semibold tracking-[-0.04em] text-stone-950 lg:text-5xl">
                    {{ entry.title }}
                </h1>

                <p v-if="entry.subtitle" class="mt-4 text-lg text-stone-700">
                    {{ entry.subtitle }}
                </p>

                <div class="mt-6 grid gap-2 text-sm text-stone-600 md:grid-cols-2">
                    <div>Presenter: {{ entry.presenterName || 'Unknown presenter' }}</div>
                    <div>Organization: {{ entry.organizationName || 'Independent presenter' }}</div>
                </div>

                <div class="mt-10 h-px bg-stone-900/10" />

                <div class="mt-10 whitespace-pre-wrap text-base leading-8 text-stone-800">
                    {{ entry.summary }}
                </div>
            </article>

            <div class="mt-8 grid gap-6 lg:grid-cols-[0.8fr_1.2fr]">
                <section class="rounded-[2rem] border border-stone-900/10 bg-white/80 p-6 shadow-sm backdrop-blur">
                    <h2 class="text-base font-semibold text-stone-950">Awards</h2>
                    <div v-if="entry.awards.length === 0" class="mt-4 text-sm text-stone-600">
                        No awards were attached to this archive record.
                    </div>
                    <div class="mt-4 grid gap-3">
                        <article v-for="(award, index) in entry.awards" :key="`${award.label}-${index}`" class="rounded-xl border border-stone-900/10 bg-stone-50/80 p-4">
                            <div class="font-medium text-stone-900">{{ award.label }}</div>
                            <p class="mt-2 text-sm leading-6 text-stone-700">{{ award.notes || 'No additional notes.' }}</p>
                        </article>
                    </div>
                </section>

                <section class="rounded-[2rem] border border-stone-900/10 bg-white/80 p-6 shadow-sm backdrop-blur">
                    <h2 class="text-base font-semibold text-stone-950">Session highlights</h2>
                    <div v-if="entry.highlights.length === 0" class="mt-4 text-sm text-stone-600">
                        No public session highlights are attached yet.
                    </div>
                    <div class="mt-4 grid gap-4">
                        <article
                            v-for="(highlight, index) in entry.highlights"
                            :key="`${highlight.title}-${index}`"
                            class="rounded-xl border border-stone-900/10 bg-stone-50/80 p-4"
                        >
                            <div class="font-medium text-stone-900">{{ highlight.title }}</div>
                            <p class="mt-2 text-sm leading-6 text-stone-700">{{ highlight.summary }}</p>
                            <blockquote v-if="highlight.quoteOptional" class="mt-3 border-l-2 border-stone-300 pl-4 text-sm italic text-stone-700">
                                “{{ highlight.quoteOptional }}”
                                <span v-if="highlight.quoteSourceOptional" class="not-italic text-stone-500"> — {{ highlight.quoteSourceOptional }}</span>
                            </blockquote>
                        </article>
                    </div>
                </section>
            </div>
        </section>
    </PublicLayout>
</template>
