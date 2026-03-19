<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Send, Trash2 } from 'lucide-vue-next';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import ConfirmActionDialog from '@/components/admin/ConfirmActionDialog.vue';
import FormSection from '@/components/admin/FormSection.vue';
import InputError from '@/components/InputError.vue';
import SubmissionFilesPanel from '@/components/submissions/SubmissionFilesPanel.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import PageContainer from '@/components/PageContainer.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import { autosave as autosaveSubmission, destroy as destroySubmission, edit as editSubmission, index as submissionsIndex, show as showSubmission, update as updateSubmission } from '@/routes/submissions';
import type { BreadcrumbItem, ManagedSubmission, SelectOption, SubmissionFileDefinition, SubmissionStageOption } from '@/types';

type Props = {
    submission: ManagedSubmission;
    seasonOptions: SelectOption[];
    stageOptions: SubmissionStageOption[];
    industryOptions: SelectOption[];
    organizationOptions: SelectOption[];
    submissionFileDefinitions: SubmissionFileDefinition[];
    openSeason: {
        id: number;
        name: string;
        year: number;
        description: string | null;
        registrationOpenAt: string | null;
        registrationCloseAt: string | null;
        isOpenForApplications: boolean;
        registrationLabel: string;
        statusLabel: string;
        statusTone: string;
    } | null;
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Submissions',
        href: submissionsIndex(),
    },
    {
        title: props.submission.title,
        href: editSubmission(props.submission.id),
    },
];

const form = useForm({
    intent: 'draft',
    season_id: String(props.submission.seasonId ?? ''),
    current_stage_id: String(props.submission.currentStageId ?? ''),
    industry_id: String(props.submission.industryId ?? ''),
    organization_id: props.submission.organizationId ? String(props.submission.organizationId) : '__none',
    title: props.submission.title,
    summary: props.submission.summary ?? '',
    problem_statement: props.submission.problemStatement ?? '',
    solution_description: props.submission.solutionDescription ?? '',
    business_model: props.submission.businessModel ?? '',
    is_public_after_approval: Boolean(props.submission.isPublicAfterApproval),
});

const availableStages = computed(() =>
    props.stageOptions.filter((stage) => String(stage.seasonId) === form.season_id),
);

const requiredFileTypes = computed(() =>
    props.submissionFileDefinitions.filter((definition) => definition.required).map((definition) => definition.type),
);

const uploadedRequiredFileTypes = computed(() => {
    const availableFiles = [...(props.submission.draftFiles ?? []), ...(props.submission.currentVersionFiles ?? [])];

    return new Set(
        availableFiles
            .filter((file) => requiredFileTypes.value.includes(file.fileType))
            .map((file) => file.fileType),
    );
});

const progressSections = computed(() => {
    const structureCompleted = [
        form.season_id !== '',
        form.current_stage_id !== '',
        form.industry_id !== '',
        form.title.trim() !== '',
    ].filter(Boolean).length;

    const narrativeCompleted = [
        form.summary.trim() !== '',
        form.problem_statement.trim() !== '',
        form.solution_description.trim() !== '',
        form.business_model.trim() !== '',
    ].filter(Boolean).length;

    return [
        {
            key: 'structure',
            title: 'Structure',
            completed: structureCompleted,
            total: 4,
        },
        {
            key: 'narrative',
            title: 'Narrative',
            completed: narrativeCompleted,
            total: 4,
        },
        {
            key: 'files',
            title: 'Required files',
            completed: uploadedRequiredFileTypes.value.size,
            total: requiredFileTypes.value.length,
        },
    ];
});

const progressPercentage = computed(() => {
    const completed = progressSections.value.reduce((carry, section) => carry + section.completed, 0);
    const total = progressSections.value.reduce((carry, section) => carry + section.total, 0);

    return total === 0 ? 0 : Math.round((completed / total) * 100);
});

const autosaveState = ref<'idle' | 'saving' | 'saved' | 'error'>('idle');
const autosavedAt = ref<string | null>(null);
let autosaveTimeout: ReturnType<typeof setTimeout> | null = null;
let hasInitializedAutosave = false;

const autosavePayload = computed(() => ({
    intent: 'draft',
    season_id: form.season_id,
    current_stage_id: form.current_stage_id,
    industry_id: form.industry_id,
    organization_id: form.organization_id === '__none' ? null : form.organization_id,
    title: form.title,
    summary: form.summary,
    problem_statement: form.problem_statement,
    solution_description: form.solution_description,
    business_model: form.business_model,
    is_public_after_approval: form.is_public_after_approval,
}));

const canAutosave = computed(() =>
    form.title.trim() !== ''
    && form.season_id !== ''
    && form.current_stage_id !== ''
    && form.industry_id !== ''
    && props.submission.canEdit === true,
);

const saveDraft = (): void => {
    if (autosaveTimeout) {
        clearTimeout(autosaveTimeout);
    }

    form.transform((data) => ({
        ...data,
        intent: 'draft',
        organization_id: data.organization_id === '__none' ? null : data.organization_id,
    })).put(updateSubmission(props.submission.id).url);
};

const submitFinal = (): void => {
    if (autosaveTimeout) {
        clearTimeout(autosaveTimeout);
    }

    form.transform((data) => ({
        ...data,
        intent: 'submit',
        organization_id: data.organization_id === '__none' ? null : data.organization_id,
    })).put(updateSubmission(props.submission.id).url);
};

const deleteDraft = (): void => {
    router.delete(destroySubmission(props.submission.id).url);
};

const saveDraftSilently = async (): Promise<void> => {
    if (! canAutosave.value || form.processing) {
        return;
    }

    autosaveState.value = 'saving';

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
        const response = await fetch(autosaveSubmission(props.submission.id).url, {
            method: 'PUT',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(autosavePayload.value),
            credentials: 'same-origin',
        });

        if (! response.ok) {
            autosaveState.value = 'error';

            return;
        }

        const payload = await response.json() as { savedAt?: string | null };
        autosaveState.value = 'saved';
        autosavedAt.value = payload.savedAt ?? null;
    } catch {
        autosaveState.value = 'error';
    }
};

watch(autosavePayload, () => {
    if (! hasInitializedAutosave) {
        hasInitializedAutosave = true;

        return;
    }

    if (autosaveTimeout) {
        clearTimeout(autosaveTimeout);
    }

    if (! canAutosave.value) {
        autosaveState.value = 'idle';

        return;
    }

    autosaveTimeout = setTimeout(() => {
        void saveDraftSilently();
    }, 1200);
}, { deep: true });

onBeforeUnmount(() => {
    if (autosaveTimeout) {
        clearTimeout(autosaveTimeout);
    }
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="`Edit ${submission.title}`" />

        <PageContainer>
            <PageHeader
                :title="`Edit ${submission.title}`"
                description="Only draft or returned submissions remain editable by the presenter."
            >
                <template #actions>
                    <div class="flex items-center gap-2">
                        <StatusBadge :label="submission.statusLabel" :tone="submission.statusTone" />
                        <Button as-child variant="outline">
                            <Link :href="showSubmission(submission.id)">
                                View details
                            </Link>
                        </Button>
                        <Button as-child variant="outline">
                            <Link :href="submissionsIndex()">
                                <ArrowLeft class="size-4" />
                                Back
                            </Link>
                        </Button>
                    </div>
                </template>
            </PageHeader>

            <section class="grid gap-4 xl:grid-cols-[1fr_0.9fr]">
                <div class="rounded-[1.5rem] border border-border/70 bg-card/85 p-5 shadow-sm">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-semibold">Submission progress</h2>
                            <p class="text-sm text-muted-foreground">Negadras intake is smoother when structure, narrative, and required files stay complete together.</p>
                        </div>
                        <div class="text-right">
                            <div class="text-2xl font-semibold">{{ progressPercentage }}%</div>
                            <div class="text-xs text-muted-foreground">
                                <span v-if="autosaveState === 'saving'">Autosaving draft...</span>
                                <span v-else-if="autosaveState === 'saved'">Autosaved {{ autosavedAt ? new Date(autosavedAt).toLocaleTimeString() : '' }}</span>
                                <span v-else-if="autosaveState === 'error'">Autosave paused</span>
                                <span v-else>Autosave enabled for draft edits</span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 h-3 overflow-hidden rounded-full bg-muted">
                        <div class="h-full rounded-full bg-primary transition-all" :style="{ width: `${progressPercentage}%` }" />
                    </div>

                    <div class="mt-5 grid gap-3 md:grid-cols-3">
                        <div
                            v-for="section in progressSections"
                            :key="section.key"
                            class="rounded-2xl border border-border/70 bg-background/70 p-4"
                        >
                            <div class="text-sm text-muted-foreground">{{ section.title }}</div>
                            <div class="mt-1 text-lg font-semibold">{{ section.completed }}/{{ section.total }}</div>
                        </div>
                    </div>
                </div>

                <div class="rounded-[1.5rem] border border-border/70 bg-card/85 p-5 shadow-sm">
                    <h2 class="text-lg font-semibold">Open call boundary</h2>
                    <p class="mt-2 text-sm leading-6 text-muted-foreground">
                        {{ openSeason ? `${openSeason.name} ${openSeason.year}` : 'No active season is currently configured.' }}
                    </p>
                    <div v-if="openSeason" class="mt-4 rounded-2xl border border-border/70 bg-background/70 p-4">
                        <div class="font-medium">{{ openSeason.registrationLabel }}</div>
                        <p class="mt-2 text-sm text-muted-foreground">
                            {{ openSeason.description || 'Use the open-call window to decide whether to keep editing or finalize the draft.' }}
                        </p>
                    </div>
                </div>
            </section>

            <form class="grid gap-6" @submit.prevent="saveDraft">
                <FormSection title="Structure" description="Season, stage, and industry stay attached to the submission record from the beginning.">
                    <div class="grid gap-5 md:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="season_id">Season</Label>
                            <Select v-model="form.season_id">
                                <SelectTrigger id="season_id">
                                    <SelectValue placeholder="Select a season" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="option in seasonOptions" :key="option.value" :value="String(option.value)">
                                        {{ option.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.season_id" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="current_stage_id">Current stage</Label>
                            <Select v-model="form.current_stage_id" :disabled="form.season_id === ''">
                                <SelectTrigger id="current_stage_id">
                                    <SelectValue placeholder="Select a stage" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="option in availableStages" :key="option.value" :value="String(option.value)">
                                        {{ option.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.current_stage_id" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="industry_id">Industry</Label>
                            <Select v-model="form.industry_id">
                                <SelectTrigger id="industry_id">
                                    <SelectValue placeholder="Select an industry" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="option in industryOptions" :key="option.value" :value="String(option.value)">
                                        {{ option.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.industry_id" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="organization_id">Organization</Label>
                            <Select v-model="form.organization_id">
                                <SelectTrigger id="organization_id">
                                    <SelectValue placeholder="Optional organization" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="__none">Independent presenter</SelectItem>
                                    <SelectItem v-for="option in organizationOptions" :key="option.value" :value="String(option.value)">
                                        {{ option.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.organization_id" />
                        </div>

                        <div class="grid gap-2 md:col-span-2">
                            <Label for="title">Submission title</Label>
                            <Input id="title" v-model="form.title" />
                            <InputError :message="form.errors.title" />
                        </div>
                    </div>
                </FormSection>

                <FormSection title="Submission narrative" description="Returned submissions can be corrected here before they are submitted again.">
                    <div class="grid gap-5">
                        <div class="grid gap-2">
                            <Label for="summary">Summary</Label>
                            <textarea id="summary" v-model="form.summary" rows="4" class="flex min-h-28 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none ring-offset-background placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]" />
                            <InputError :message="form.errors.summary" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="problem_statement">Problem statement</Label>
                            <textarea id="problem_statement" v-model="form.problem_statement" rows="6" class="flex min-h-36 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none ring-offset-background placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]" />
                            <InputError :message="form.errors.problem_statement" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="solution_description">Solution description</Label>
                            <textarea id="solution_description" v-model="form.solution_description" rows="6" class="flex min-h-36 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none ring-offset-background placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]" />
                            <InputError :message="form.errors.solution_description" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="business_model">Business model</Label>
                            <textarea id="business_model" v-model="form.business_model" rows="6" class="flex min-h-36 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none ring-offset-background placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]" />
                            <InputError :message="form.errors.business_model" />
                        </div>
                    </div>
                </FormSection>

                <FormSection title="Visibility preference" description="This preference only matters if the submission becomes publicly showcase-ready.">
                    <div class="flex items-start gap-3 rounded-xl border border-border/70 p-4">
                        <Checkbox id="is_public_after_approval" v-model:checked="form.is_public_after_approval" />
                        <div class="space-y-1">
                            <Label for="is_public_after_approval">Allow public showcase after approval</Label>
                            <p class="text-sm text-muted-foreground">Negadras keeps the submission private until approval is granted.</p>
                        </div>
                    </div>
                    <InputError :message="form.errors.is_public_after_approval" />
                </FormSection>

                <div class="flex flex-wrap items-center justify-between gap-3">
                    <ConfirmActionDialog
                        title="Delete this draft?"
                        description="This removes the current submission draft permanently."
                        confirm-label="Delete draft"
                        :processing="form.processing"
                        @confirm="deleteDraft"
                    >
                        <template #trigger>
                            <Button type="button" variant="outline" class="text-destructive">
                                <Trash2 class="size-4" />
                                Delete draft
                            </Button>
                        </template>
                    </ConfirmActionDialog>

                    <div class="flex flex-wrap items-center gap-3">
                        <Button type="button" variant="outline" :disabled="form.processing" @click="saveDraft">
                            Save draft
                        </Button>

                        <ConfirmActionDialog
                            title="Submit this revision?"
                            description="Once submitted, the record becomes locked until staff returns it for correction."
                            confirm-label="Submit revision"
                            :processing="form.processing"
                            @confirm="submitFinal"
                        >
                            <template #trigger>
                                <Button type="button" :disabled="form.processing">
                                    <Send class="size-4" />
                                    Final submit
                                </Button>
                            </template>
                        </ConfirmActionDialog>
                    </div>
                </div>
            </form>

            <SubmissionFilesPanel
                :submission-id="submission.id"
                :definitions="submissionFileDefinitions"
                :draft-files="submission.draftFiles ?? []"
                :current-version-files="submission.currentVersionFiles ?? []"
                can-manage
            />
        </PageContainer>
    </AppLayout>
</template>
