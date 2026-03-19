<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import PageContainer from '@/components/PageContainer.vue';
import PageHeader from '@/components/PageHeader.vue';
import ResourceToolbar from '@/components/admin/ResourceToolbar.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as rankingsIndex, store as storeRanking, update as updateRanking } from '@/routes/rankings';
import type { BreadcrumbItem, ManagedRankingSnapshot, SelectOption } from '@/types';

type Props = {
    records: ManagedRankingSnapshot[];
    filters: {
        stageId: string;
        competitionSessionId: string;
    };
    stageOptions: SelectOption[];
    sessionOptions: SelectOption[];
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Rankings',
        href: rankingsIndex(),
    },
];

const allStagesValue = '__all_stages__';
const allSessionsValue = '__all_sessions__';

const generateForm = useForm({
    stage_id: props.filters.stageId,
    competition_session_id: props.filters.competitionSessionId,
    finalize: false,
    scope: 'stage',
});

const rowForms = Object.fromEntries(
    props.records.map((record) => [
        record.id,
        useForm({
            rank_position: String(record.rankPosition),
            override_reason_optional: record.overrideReasonOptional ?? '',
        }),
    ]),
);

const updateFilters = (partial: Record<string, string>): void => {
    const stageId = partial.stageId ?? props.filters.stageId;
    const competitionSessionId = partial.competitionSessionId ?? props.filters.competitionSessionId;

    router.get(rankingsIndex().url, {
        stage_id: stageId || undefined,
        competition_session_id: competitionSessionId || undefined,
    }, {
        preserveScroll: true,
        preserveState: true,
        replace: true,
    });
};

const generate = (): void => {
    generateForm.transform((data) => ({
        ...data,
        competition_session_id: data.competition_session_id === '' ? null : Number(data.competition_session_id),
        stage_id: Number(data.stage_id),
    })).post(storeRanking().url, {
        preserveScroll: true,
    });
};

const saveRow = (recordId: number): void => {
    rowForms[recordId].transform((data) => ({
        ...data,
        rank_position: Number(data.rank_position),
        override_reason_optional: data.override_reason_optional === '' ? null : data.override_reason_optional,
    })).put(updateRanking(recordId).url, {
        preserveScroll: true,
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Rankings" />

        <PageContainer>
            <PageHeader
                title="Rankings"
                description="Generate stage or session ranking snapshots from locked scoring results, then apply rank overrides when operations require them."
            />

            <div class="grid gap-6 lg:grid-cols-[0.9fr_1.1fr]">
                <section class="rounded-[1.5rem] border border-border/70 bg-card/85 p-5 shadow-sm">
                    <h2 class="text-base font-semibold">Generate snapshot</h2>
                    <form class="mt-4 grid gap-4" @submit.prevent="generate">
                        <div class="grid gap-2">
                            <Label for="stage_id">Stage</Label>
                            <Select v-model="generateForm.stage_id">
                                <SelectTrigger id="stage_id">
                                    <SelectValue placeholder="Select stage" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="option in stageOptions" :key="option.value" :value="String(option.value)">
                                        {{ option.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="generateForm.errors.stage_id" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="competition_session_id">Competition session</Label>
                            <Select v-model="generateForm.competition_session_id">
                                <SelectTrigger id="competition_session_id">
                                    <SelectValue placeholder="Whole stage ranking" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="">Whole stage ranking</SelectItem>
                                    <SelectItem v-for="option in sessionOptions" :key="option.value" :value="String(option.value)">
                                        {{ option.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="generateForm.errors.competition_session_id" />
                        </div>

                        <label class="flex items-center gap-3 rounded-xl border border-border/70 bg-background/60 px-4 py-3 text-sm">
                            <input v-model="generateForm.finalize" type="checkbox" class="size-4 rounded border-border" />
                            <span>Finalize this snapshot immediately</span>
                        </label>

                        <div class="flex justify-end">
                            <Button type="submit" :disabled="generateForm.processing || generateForm.stage_id === ''">
                                Generate ranking snapshot
                            </Button>
                        </div>
                    </form>
                </section>

                <section class="space-y-4">
                    <ResourceToolbar
                        search=""
                        search-placeholder="Filtering is stage/session based in this view"
                    >
                        <template #actions>
                            <Select :model-value="filters.stageId || allStagesValue" @update:model-value="(value) => updateFilters({ stageId: String(value) === allStagesValue ? '' : String(value ?? '') })">
                                <SelectTrigger class="w-[190px]">
                                    <SelectValue placeholder="All stages" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem :value="allStagesValue">All stages</SelectItem>
                                    <SelectItem v-for="option in stageOptions" :key="option.value" :value="String(option.value)">
                                        {{ option.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>

                            <Select :model-value="filters.competitionSessionId || allSessionsValue" @update:model-value="(value) => updateFilters({ competitionSessionId: String(value) === allSessionsValue ? '' : String(value ?? '') })">
                                <SelectTrigger class="w-[220px]">
                                    <SelectValue placeholder="All sessions" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem :value="allSessionsValue">All sessions</SelectItem>
                                    <SelectItem v-for="option in sessionOptions" :key="option.value" :value="String(option.value)">
                                        {{ option.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </template>
                    </ResourceToolbar>

                    <div v-if="records.length === 0" class="rounded-[1.5rem] border border-dashed border-border bg-card/70 p-10 text-sm text-muted-foreground">
                        No ranking snapshots generated yet for the selected scope.
                    </div>

                    <article
                        v-for="record in records"
                        :key="record.id"
                        class="rounded-[1.5rem] border border-border/70 bg-card/85 p-5 shadow-sm"
                    >
                        <div class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
                            <div>
                                <div class="text-lg font-semibold">{{ record.rankPosition }}. {{ record.submissionTitle || 'Untitled submission' }}</div>
                                <div class="mt-1 grid gap-1 text-sm text-muted-foreground md:grid-cols-2">
                                    <div>Presenter: {{ record.applicantName || 'Unknown presenter' }}</div>
                                    <div>Organization: {{ record.organizationName || 'Independent presenter' }}</div>
                                    <div>Season: {{ record.seasonName || 'No season' }}</div>
                                    <div>Stage: {{ record.stageName || 'No stage' }}</div>
                                    <div v-if="record.competitionSessionName">Session: {{ record.competitionSessionName }}</div>
                                </div>
                            </div>
                            <div class="text-right text-sm">
                                <div class="text-muted-foreground">Aggregate score</div>
                                <div class="text-2xl font-semibold">{{ record.aggregateScore.toFixed(2) }}</div>
                            </div>
                        </div>

                        <div v-if="record.tieBreakReasonOptional" class="mt-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                            Tie-break: {{ record.tieBreakReasonOptional }}
                        </div>

                        <form class="mt-4 grid gap-4 rounded-xl border border-border/70 bg-background/60 p-4 md:grid-cols-[180px_1fr_auto]" @submit.prevent="saveRow(record.id)">
                            <div class="grid gap-2">
                                <Label :for="`rank-${record.id}`">Manual rank</Label>
                                <Input :id="`rank-${record.id}`" v-model="rowForms[record.id].rank_position" type="number" min="1" />
                                <InputError :message="rowForms[record.id].errors.rank_position" />
                            </div>

                            <div class="grid gap-2">
                                <Label :for="`override-${record.id}`">Override reason</Label>
                                <Input :id="`override-${record.id}`" v-model="rowForms[record.id].override_reason_optional" placeholder="Why was this rank adjusted?" />
                                <InputError :message="rowForms[record.id].errors.override_reason_optional" />
                            </div>

                            <div class="flex items-end justify-end">
                                <Button type="submit" :disabled="rowForms[record.id].processing">
                                    Save override
                                </Button>
                            </div>
                        </form>
                    </article>
                </section>
            </div>
        </PageContainer>
    </AppLayout>
</template>
