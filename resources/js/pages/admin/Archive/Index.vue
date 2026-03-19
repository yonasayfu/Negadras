<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import PageContainer from '@/components/PageContainer.vue';
import PageHeader from '@/components/PageHeader.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as archiveIndex, store as storeArchive, update as updateArchive } from '@/routes/archive';
import { store as storeArchiveHighlight, update as updateArchiveHighlight } from '@/routes/archive/highlights';
import type { BreadcrumbItem, ManagedArchiveRecord, ManagedSessionHighlight, SelectOption } from '@/types';

type RankingOption = {
    value: number;
    submissionId: number;
    competitionSessionId: number | null;
    label: string;
};

type MediaOption = {
    value: number;
    label: string;
};

type Props = {
    records: ManagedArchiveRecord[];
    rankingOptions: RankingOption[];
    sessionOptions: SelectOption[];
    mediaOptions: MediaOption[];
    highlights: ManagedSessionHighlight[];
    archiveStatusOptions: SelectOption[];
    publicVisibilityOptions: SelectOption[];
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Archive',
        href: archiveIndex(),
    },
];

const createForm = useForm({
    ranking_snapshot_id: '',
    submission_id: '',
    competition_session_id: '',
    archive_status: props.archiveStatusOptions[0]?.value ?? '',
    public_visibility: props.publicVisibilityOptions[0]?.value ?? '',
    notes: '',
    showcase_title: '',
    showcase_subtitle: '',
    showcase_summary: '',
    showcase_winner_label: '',
    showcase_media_id_optional: '',
});

const highlightForm = useForm({
    competition_session_id: '',
    title: '',
    summary: '',
    quote_optional: '',
    quote_source_optional: '',
    display_order: '1',
    is_public: true,
});

const rowForms = Object.fromEntries(
    props.records.map((record) => [
        record.id,
        useForm({
            archive_status: record.archiveStatus,
            public_visibility: record.publicVisibility,
            notes: record.notes ?? '',
            showcase_title: record.showcase?.title ?? '',
            showcase_subtitle: record.showcase?.subtitle ?? '',
            showcase_summary: record.showcase?.summary ?? '',
            showcase_winner_label: record.showcase?.winnerLabel ?? '',
            showcase_media_id_optional: '',
        }),
    ]),
);

const highlightForms = Object.fromEntries(
    props.highlights.map((highlight) => [
        highlight.id,
        useForm({
            title: highlight.title,
            summary: highlight.summary,
            quote_optional: highlight.quoteOptional ?? '',
            quote_source_optional: highlight.quoteSourceOptional ?? '',
            display_order: String(highlight.displayOrder),
            is_public: highlight.isPublic,
        }),
    ]),
);

const syncRankingChoice = (rankingSnapshotId: string): void => {
    const selected = props.rankingOptions.find((option) => String(option.value) === rankingSnapshotId);
    createForm.ranking_snapshot_id = rankingSnapshotId;
    createForm.submission_id = selected ? String(selected.submissionId) : '';
    createForm.competition_session_id = selected?.competitionSessionId ? String(selected.competitionSessionId) : '';
};

const submitCreate = (): void => {
    createForm.transform((data) => ({
        ...data,
        ranking_snapshot_id: data.ranking_snapshot_id === '' ? null : Number(data.ranking_snapshot_id),
        submission_id: Number(data.submission_id),
        competition_session_id: data.competition_session_id === '' ? null : Number(data.competition_session_id),
        notes: data.notes === '' ? null : data.notes,
        showcase_title: data.showcase_title === '' ? null : data.showcase_title,
        showcase_subtitle: data.showcase_subtitle === '' ? null : data.showcase_subtitle,
        showcase_summary: data.showcase_summary === '' ? null : data.showcase_summary,
        showcase_winner_label: data.showcase_winner_label === '' ? null : data.showcase_winner_label,
        showcase_media_id_optional: data.showcase_media_id_optional === '' ? null : Number(data.showcase_media_id_optional),
    })).post(storeArchive().url, {
        preserveScroll: true,
    });
};

const submitRecord = (recordId: number): void => {
    rowForms[recordId].transform((data) => ({
        ...data,
        notes: data.notes === '' ? null : data.notes,
        showcase_title: data.showcase_title === '' ? null : data.showcase_title,
        showcase_subtitle: data.showcase_subtitle === '' ? null : data.showcase_subtitle,
        showcase_summary: data.showcase_summary === '' ? null : data.showcase_summary,
        showcase_winner_label: data.showcase_winner_label === '' ? null : data.showcase_winner_label,
        showcase_media_id_optional: data.showcase_media_id_optional === '' ? null : Number(data.showcase_media_id_optional),
    })).put(updateArchive(recordId).url, {
        preserveScroll: true,
    });
};

const submitHighlight = (): void => {
    highlightForm.transform((data) => ({
        ...data,
        competition_session_id: Number(data.competition_session_id),
        display_order: Number(data.display_order),
        quote_optional: data.quote_optional === '' ? null : data.quote_optional,
        quote_source_optional: data.quote_source_optional === '' ? null : data.quote_source_optional,
    })).post(storeArchiveHighlight().url, {
        preserveScroll: true,
    });
};

const saveHighlight = (highlightId: number): void => {
    highlightForms[highlightId].transform((data) => ({
        ...data,
        display_order: Number(data.display_order),
        quote_optional: data.quote_optional === '' ? null : data.quote_optional,
        quote_source_optional: data.quote_source_optional === '' ? null : data.quote_source_optional,
    })).put(updateArchiveHighlight(highlightId).url, {
        preserveScroll: true,
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Archive" />

        <PageContainer>
            <PageHeader
                title="Archive and showcase"
                description="Freeze final outcomes into archive records, attach public showcase data, and curate session highlights for public storytelling."
            />

            <div class="grid gap-6 xl:grid-cols-[0.9fr_1.1fr]">
                <section class="space-y-6">
                    <div class="rounded-[1.5rem] border border-border/70 bg-card/85 p-5 shadow-sm">
                        <h2 class="text-base font-semibold">Create archive record</h2>
                        <form class="mt-4 grid gap-4" @submit.prevent="submitCreate">
                            <div class="grid gap-2">
                                <Label for="archive_ranking">Ranking snapshot</Label>
                                <Select :model-value="createForm.ranking_snapshot_id" @update:model-value="(value) => syncRankingChoice(String(value ?? ''))">
                                    <SelectTrigger id="archive_ranking">
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
                                    <Label for="archive_status">Archive status</Label>
                                    <Select v-model="createForm.archive_status">
                                        <SelectTrigger id="archive_status">
                                            <SelectValue placeholder="Select archive status" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem v-for="option in archiveStatusOptions" :key="option.value" :value="String(option.value)">
                                                {{ option.label }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <InputError :message="createForm.errors.archive_status" />
                                </div>

                                <div class="grid gap-2">
                                    <Label for="public_visibility">Public visibility</Label>
                                    <Select v-model="createForm.public_visibility">
                                        <SelectTrigger id="public_visibility">
                                            <SelectValue placeholder="Select visibility" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem v-for="option in publicVisibilityOptions" :key="option.value" :value="String(option.value)">
                                                {{ option.label }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <InputError :message="createForm.errors.public_visibility" />
                                </div>
                            </div>

                            <div class="grid gap-2">
                                <Label for="archive_notes">Archive notes</Label>
                                <textarea id="archive_notes" v-model="createForm.notes" rows="4" class="flex min-h-28 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs" />
                                <InputError :message="createForm.errors.notes" />
                            </div>

                            <div class="rounded-xl border border-border/70 bg-background/60 p-4">
                                <div class="text-sm font-semibold">Showcase details</div>
                                <div class="mt-4 grid gap-4">
                                    <div class="grid gap-2">
                                        <Label for="showcase_title">Title</Label>
                                        <Input id="showcase_title" v-model="createForm.showcase_title" />
                                    </div>
                                    <div class="grid gap-2">
                                        <Label for="showcase_subtitle">Subtitle</Label>
                                        <Input id="showcase_subtitle" v-model="createForm.showcase_subtitle" />
                                    </div>
                                    <div class="grid gap-2">
                                        <Label for="showcase_summary">Summary</Label>
                                        <textarea id="showcase_summary" v-model="createForm.showcase_summary" rows="4" class="flex min-h-28 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs" />
                                    </div>
                                    <div class="grid gap-4 md:grid-cols-2">
                                        <div class="grid gap-2">
                                            <Label for="showcase_winner_label">Winner label</Label>
                                            <Input id="showcase_winner_label" v-model="createForm.showcase_winner_label" />
                                        </div>
                                        <div class="grid gap-2">
                                            <Label for="showcase_media">Media</Label>
                                            <Select v-model="createForm.showcase_media_id_optional">
                                                <SelectTrigger id="showcase_media">
                                                    <SelectValue placeholder="Optional showcase media" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="">No media</SelectItem>
                                                    <SelectItem v-for="option in mediaOptions" :key="option.value" :value="String(option.value)">
                                                        {{ option.label }}
                                                    </SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex justify-end">
                                <Button type="submit" :disabled="createForm.processing || createForm.submission_id === ''">
                                    Archive result
                                </Button>
                            </div>
                        </form>
                    </div>

                    <div class="rounded-[1.5rem] border border-border/70 bg-card/85 p-5 shadow-sm">
                        <h2 class="text-base font-semibold">Session highlights</h2>
                        <form class="mt-4 grid gap-4" @submit.prevent="submitHighlight">
                            <div class="grid gap-2">
                                <Label for="highlight_session">Competition session</Label>
                                <Select v-model="highlightForm.competition_session_id">
                                    <SelectTrigger id="highlight_session">
                                        <SelectValue placeholder="Select session" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="option in sessionOptions" :key="option.value" :value="String(option.value)">
                                            {{ option.label }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError :message="highlightForm.errors.competition_session_id" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="highlight_title">Title</Label>
                                <Input id="highlight_title" v-model="highlightForm.title" />
                                <InputError :message="highlightForm.errors.title" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="highlight_summary">Summary</Label>
                                <textarea id="highlight_summary" v-model="highlightForm.summary" rows="4" class="flex min-h-28 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs" />
                                <InputError :message="highlightForm.errors.summary" />
                            </div>

                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="grid gap-2">
                                    <Label for="highlight_quote">Quote</Label>
                                    <Input id="highlight_quote" v-model="highlightForm.quote_optional" />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="highlight_quote_source">Quote source</Label>
                                    <Input id="highlight_quote_source" v-model="highlightForm.quote_source_optional" />
                                </div>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="grid gap-2">
                                    <Label for="highlight_order">Display order</Label>
                                    <Input id="highlight_order" v-model="highlightForm.display_order" type="number" min="1" />
                                </div>
                                <label class="flex items-center gap-3 rounded-xl border border-border/70 bg-background/60 px-4 py-3 text-sm">
                                    <input v-model="highlightForm.is_public" type="checkbox" class="size-4 rounded border-border" />
                                    <span>Visible on public showcase pages</span>
                                </label>
                            </div>

                            <div class="flex justify-end">
                                <Button type="submit" :disabled="highlightForm.processing || highlightForm.competition_session_id === ''">
                                    Save highlight
                                </Button>
                            </div>
                        </form>
                    </div>
                </section>

                <section class="space-y-4">
                    <article
                        v-for="record in records"
                        :key="record.id"
                        class="rounded-[1.5rem] border border-border/70 bg-card/85 p-5 shadow-sm"
                    >
                        <div class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
                            <div>
                                <div class="text-lg font-semibold">{{ record.submissionTitle || 'Untitled submission' }}</div>
                                <div class="mt-2 flex flex-wrap items-center gap-2">
                                    <StatusBadge :label="record.archiveStatusLabel" :tone="record.archiveStatusTone" />
                                    <span class="text-sm text-muted-foreground">{{ record.publicVisibilityLabel }}</span>
                                </div>
                                <div class="mt-2 grid gap-1 text-sm text-muted-foreground md:grid-cols-2">
                                    <div>Presenter: {{ record.applicantName || 'Unknown presenter' }}</div>
                                    <div>Organization: {{ record.organizationName || 'Independent presenter' }}</div>
                                    <div>Season: {{ record.seasonName || 'No season' }}</div>
                                    <div>Stage: {{ record.stageName || 'No stage' }}</div>
                                    <div v-if="record.sessionName">Session: {{ record.sessionName }}</div>
                                    <div>Archived: {{ record.archivedAt || 'Pending' }}</div>
                                </div>
                            </div>
                        </div>

                        <form class="mt-4 grid gap-4 rounded-xl border border-border/70 bg-background/60 p-4" @submit.prevent="submitRecord(record.id)">
                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="grid gap-2">
                                    <Label :for="`archive-status-${record.id}`">Archive status</Label>
                                    <Select v-model="rowForms[record.id].archive_status">
                                        <SelectTrigger :id="`archive-status-${record.id}`">
                                            <SelectValue placeholder="Select archive status" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem v-for="option in archiveStatusOptions" :key="option.value" :value="String(option.value)">
                                                {{ option.label }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div class="grid gap-2">
                                    <Label :for="`archive-visibility-${record.id}`">Public visibility</Label>
                                    <Select v-model="rowForms[record.id].public_visibility">
                                        <SelectTrigger :id="`archive-visibility-${record.id}`">
                                            <SelectValue placeholder="Select visibility" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem v-for="option in publicVisibilityOptions" :key="option.value" :value="String(option.value)">
                                                {{ option.label }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>

                            <div class="grid gap-2">
                                <Label :for="`archive-notes-${record.id}`">Notes</Label>
                                <textarea :id="`archive-notes-${record.id}`" v-model="rowForms[record.id].notes" rows="4" class="flex min-h-28 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs" />
                            </div>

                            <div class="rounded-xl border border-border/70 bg-card/70 p-4">
                                <div class="text-sm font-semibold">Showcase entry</div>
                                <div class="mt-4 grid gap-4">
                                    <div class="grid gap-2">
                                        <Label :for="`showcase-title-${record.id}`">Title</Label>
                                        <Input :id="`showcase-title-${record.id}`" v-model="rowForms[record.id].showcase_title" />
                                    </div>
                                    <div class="grid gap-2">
                                        <Label :for="`showcase-subtitle-${record.id}`">Subtitle</Label>
                                        <Input :id="`showcase-subtitle-${record.id}`" v-model="rowForms[record.id].showcase_subtitle" />
                                    </div>
                                    <div class="grid gap-2">
                                        <Label :for="`showcase-summary-${record.id}`">Summary</Label>
                                        <textarea :id="`showcase-summary-${record.id}`" v-model="rowForms[record.id].showcase_summary" rows="4" class="flex min-h-28 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs" />
                                    </div>
                                    <div class="grid gap-4 md:grid-cols-2">
                                        <div class="grid gap-2">
                                            <Label :for="`winner-label-${record.id}`">Winner label</Label>
                                            <Input :id="`winner-label-${record.id}`" v-model="rowForms[record.id].showcase_winner_label" />
                                        </div>
                                        <div class="grid gap-2">
                                            <Label :for="`showcase-media-${record.id}`">Media</Label>
                                            <Select v-model="rowForms[record.id].showcase_media_id_optional">
                                                <SelectTrigger :id="`showcase-media-${record.id}`">
                                                    <SelectValue placeholder="Optional showcase media" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="">No media</SelectItem>
                                                    <SelectItem v-for="option in mediaOptions" :key="option.value" :value="String(option.value)">
                                                        {{ option.label }}
                                                    </SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex justify-end">
                                <Button type="submit" :disabled="rowForms[record.id].processing">
                                    Update archive record
                                </Button>
                            </div>
                        </form>
                    </article>

                    <div class="rounded-[1.5rem] border border-border/70 bg-card/85 p-5 shadow-sm">
                        <div class="text-base font-semibold">Existing highlights</div>
                        <div v-if="highlights.length === 0" class="mt-4 text-sm text-muted-foreground">
                            No session highlights recorded yet.
                        </div>
                        <div class="mt-4 grid gap-4">
                            <form
                                v-for="highlight in highlights"
                                :key="highlight.id"
                                class="grid gap-4 rounded-xl border border-border/70 bg-background/60 p-4"
                                @submit.prevent="saveHighlight(highlight.id)"
                            >
                                <div class="text-sm font-medium">{{ highlight.competitionSessionName || 'Session highlight' }}</div>
                                <div class="grid gap-2">
                                    <Label :for="`highlight-title-${highlight.id}`">Title</Label>
                                    <Input :id="`highlight-title-${highlight.id}`" v-model="highlightForms[highlight.id].title" />
                                </div>
                                <div class="grid gap-2">
                                    <Label :for="`highlight-summary-${highlight.id}`">Summary</Label>
                                    <textarea :id="`highlight-summary-${highlight.id}`" v-model="highlightForms[highlight.id].summary" rows="4" class="flex min-h-28 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs" />
                                </div>
                                <div class="grid gap-4 md:grid-cols-2">
                                    <div class="grid gap-2">
                                        <Label :for="`highlight-quote-${highlight.id}`">Quote</Label>
                                        <Input :id="`highlight-quote-${highlight.id}`" v-model="highlightForms[highlight.id].quote_optional" />
                                    </div>
                                    <div class="grid gap-2">
                                        <Label :for="`highlight-source-${highlight.id}`">Quote source</Label>
                                        <Input :id="`highlight-source-${highlight.id}`" v-model="highlightForms[highlight.id].quote_source_optional" />
                                    </div>
                                </div>
                                <div class="grid gap-4 md:grid-cols-2">
                                    <div class="grid gap-2">
                                        <Label :for="`highlight-order-${highlight.id}`">Display order</Label>
                                        <Input :id="`highlight-order-${highlight.id}`" v-model="highlightForms[highlight.id].display_order" type="number" min="1" />
                                    </div>
                                    <label class="flex items-center gap-3 rounded-xl border border-border/70 bg-card/70 px-4 py-3 text-sm">
                                        <input v-model="highlightForms[highlight.id].is_public" type="checkbox" class="size-4 rounded border-border" />
                                        <span>Public highlight</span>
                                    </label>
                                </div>
                                <div class="flex justify-end">
                                    <Button type="submit" variant="outline" :disabled="highlightForms[highlight.id].processing">
                                        Update highlight
                                    </Button>
                                </div>
                            </form>
                        </div>
                    </div>
                </section>
            </div>
        </PageContainer>
    </AppLayout>
</template>
