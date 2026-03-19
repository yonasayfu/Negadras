<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import PageContainer from '@/components/PageContainer.vue';
import PageHeader from '@/components/PageHeader.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as awardsIndex, store as storeAward, update as updateAward } from '@/routes/awards';
import type { BreadcrumbItem, ManagedAwardRecord, SelectOption } from '@/types';

type RankingOption = {
    value: number;
    submissionId: number;
    seasonId: number;
    rankPosition: number;
    label: string;
};

type Props = {
    awards: ManagedAwardRecord[];
    rankingOptions: RankingOption[];
    awardTypeOptions: SelectOption[];
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Awards',
        href: awardsIndex(),
    },
];

const createForm = useForm({
    ranking_snapshot_id: '',
    submission_id: '',
    season_id: '',
    award_type: props.awardTypeOptions[0]?.value ?? '',
    rank_position: '',
    prize_value_optional: '',
    notes: '',
});

const rowForms = Object.fromEntries(
    props.awards.map((award) => [
        award.id,
        useForm({
            award_type: award.awardType,
            rank_position: award.rankPosition ? String(award.rankPosition) : '',
            prize_value_optional: award.prizeValueOptional ? String(award.prizeValueOptional) : '',
            notes: award.notes ?? '',
        }),
    ]),
);

const syncSnapshot = (snapshotId: string): void => {
    const selected = props.rankingOptions.find((option) => String(option.value) === snapshotId);

    createForm.ranking_snapshot_id = snapshotId;
    createForm.submission_id = selected ? String(selected.submissionId) : '';
    createForm.season_id = selected ? String(selected.seasonId) : '';
    createForm.rank_position = selected?.rankPosition ? String(selected.rankPosition) : '';
};

const submitCreate = (): void => {
    createForm.transform((data) => ({
        ...data,
        ranking_snapshot_id: Number(data.ranking_snapshot_id),
        submission_id: Number(data.submission_id),
        season_id: Number(data.season_id),
        rank_position: data.rank_position === '' ? null : Number(data.rank_position),
        prize_value_optional: data.prize_value_optional === '' ? null : Number(data.prize_value_optional),
        notes: data.notes === '' ? null : data.notes,
    })).post(storeAward().url, {
        preserveScroll: true,
    });
};

const submitUpdate = (awardId: number): void => {
    rowForms[awardId].transform((data) => ({
        ...data,
        rank_position: data.rank_position === '' ? null : Number(data.rank_position),
        prize_value_optional: data.prize_value_optional === '' ? null : Number(data.prize_value_optional),
        notes: data.notes === '' ? null : data.notes,
    })).put(updateAward(awardId).url, {
        preserveScroll: true,
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Awards" />

        <PageContainer>
            <PageHeader
                title="Awards"
                description="Record official awards from finalized ranking outcomes and refine their notes or prize values before publication."
            />

            <div class="grid gap-6 lg:grid-cols-[0.95fr_1.05fr]">
                <section class="rounded-[1.5rem] border border-border/70 bg-card/85 p-5 shadow-sm">
                    <h2 class="text-base font-semibold">Create award</h2>
                    <form class="mt-4 grid gap-4" @submit.prevent="submitCreate">
                        <div class="grid gap-2">
                            <Label for="ranking_snapshot_id">Ranking snapshot</Label>
                            <Select :model-value="createForm.ranking_snapshot_id" @update:model-value="(value) => syncSnapshot(String(value ?? ''))">
                                <SelectTrigger id="ranking_snapshot_id">
                                    <SelectValue placeholder="Select ranked submission" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="option in rankingOptions" :key="option.value" :value="String(option.value)">
                                        {{ option.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="createForm.errors.ranking_snapshot_id" />
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="award_type">Award type</Label>
                                <Select v-model="createForm.award_type">
                                    <SelectTrigger id="award_type">
                                        <SelectValue placeholder="Select award type" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="option in awardTypeOptions" :key="option.value" :value="String(option.value)">
                                            {{ option.label }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError :message="createForm.errors.award_type" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="rank_position">Rank position</Label>
                                <Input id="rank_position" v-model="createForm.rank_position" type="number" min="1" />
                                <InputError :message="createForm.errors.rank_position" />
                            </div>
                        </div>

                        <div class="grid gap-2">
                            <Label for="prize_value_optional">Prize value</Label>
                            <Input id="prize_value_optional" v-model="createForm.prize_value_optional" type="number" min="0" step="0.01" />
                            <InputError :message="createForm.errors.prize_value_optional" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="award_notes">Notes</Label>
                            <textarea id="award_notes" v-model="createForm.notes" rows="4" class="flex min-h-28 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs" />
                            <InputError :message="createForm.errors.notes" />
                        </div>

                        <div class="flex justify-end">
                            <Button type="submit" :disabled="createForm.processing || createForm.ranking_snapshot_id === ''">
                                Save award
                            </Button>
                        </div>
                    </form>
                </section>

                <section class="grid gap-4">
                    <div v-if="awards.length === 0" class="rounded-[1.5rem] border border-dashed border-border bg-card/70 p-10 text-sm text-muted-foreground">
                        No awards recorded yet.
                    </div>

                    <article
                        v-for="award in awards"
                        :key="award.id"
                        class="rounded-[1.5rem] border border-border/70 bg-card/85 p-5 shadow-sm"
                    >
                        <div class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
                            <div>
                                <div class="text-lg font-semibold">{{ award.awardTypeLabel }}</div>
                                <div class="mt-1 text-sm text-muted-foreground">{{ award.submissionTitle || 'Untitled submission' }}</div>
                                <div class="mt-2 grid gap-1 text-sm text-muted-foreground md:grid-cols-2">
                                    <div>Presenter: {{ award.applicantName || 'Unknown presenter' }}</div>
                                    <div>Organization: {{ award.organizationName || 'Independent presenter' }}</div>
                                    <div>Season: {{ award.seasonName || 'No season' }}</div>
                                    <div>Granted: {{ award.grantedAt || 'Pending' }}</div>
                                </div>
                            </div>
                        </div>

                        <form class="mt-4 grid gap-4 rounded-xl border border-border/70 bg-background/60 p-4 md:grid-cols-2" @submit.prevent="submitUpdate(award.id)">
                            <div class="grid gap-2">
                                <Label :for="`type-${award.id}`">Award type</Label>
                                <Select v-model="rowForms[award.id].award_type">
                                    <SelectTrigger :id="`type-${award.id}`">
                                        <SelectValue placeholder="Select award type" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="option in awardTypeOptions" :key="option.value" :value="String(option.value)">
                                            {{ option.label }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError :message="rowForms[award.id].errors.award_type" />
                            </div>

                            <div class="grid gap-2">
                                <Label :for="`award-rank-${award.id}`">Rank position</Label>
                                <Input :id="`award-rank-${award.id}`" v-model="rowForms[award.id].rank_position" type="number" min="1" />
                                <InputError :message="rowForms[award.id].errors.rank_position" />
                            </div>

                            <div class="grid gap-2">
                                <Label :for="`award-prize-${award.id}`">Prize value</Label>
                                <Input :id="`award-prize-${award.id}`" v-model="rowForms[award.id].prize_value_optional" type="number" min="0" step="0.01" />
                                <InputError :message="rowForms[award.id].errors.prize_value_optional" />
                            </div>

                            <div class="grid gap-2 md:col-span-2">
                                <Label :for="`award-notes-${award.id}`">Notes</Label>
                                <textarea :id="`award-notes-${award.id}`" v-model="rowForms[award.id].notes" rows="4" class="flex min-h-28 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs" />
                                <InputError :message="rowForms[award.id].errors.notes" />
                            </div>

                            <div class="md:col-span-2 flex justify-end">
                                <Button type="submit" :disabled="rowForms[award.id].processing">
                                    Update award
                                </Button>
                            </div>
                        </form>
                    </article>
                </section>
            </div>
        </PageContainer>
    </AppLayout>
</template>
