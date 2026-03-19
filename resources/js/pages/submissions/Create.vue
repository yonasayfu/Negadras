<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Send, ShieldCheck, TableProperties } from 'lucide-vue-next';
import { computed } from 'vue';
import ConfirmActionDialog from '@/components/admin/ConfirmActionDialog.vue';
import FormSection from '@/components/admin/FormSection.vue';
import InputError from '@/components/InputError.vue';
import PageContainer from '@/components/PageContainer.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import { create as createSubmission, index as submissionsIndex, store as storeSubmission } from '@/routes/submissions';
import type { BreadcrumbItem, SelectOption, SubmissionStageOption } from '@/types';

type Props = {
    seasonOptions: SelectOption[];
    stageOptions: SubmissionStageOption[];
    industryOptions: SelectOption[];
    organizationOptions: SelectOption[];
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
        title: 'Create',
        href: createSubmission(),
    },
];

const form = useForm({
    intent: 'draft',
    season_id: '',
    current_stage_id: '',
    industry_id: '',
    organization_id: '__none',
    title: '',
    summary: '',
    problem_statement: '',
    solution_description: '',
    business_model: '',
    is_public_after_approval: false,
});

const availableStages = computed(() =>
    props.stageOptions.filter((stage) => String(stage.seasonId) === form.season_id),
);

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
            key: 'visibility',
            title: 'Visibility',
            completed: form.is_public_after_approval ? 1 : 0,
            total: 1,
        },
    ];
});

const progressPercentage = computed(() => {
    const completed = progressSections.value.reduce((carry, section) => carry + section.completed, 0);
    const total = progressSections.value.reduce((carry, section) => carry + section.total, 0);

    return Math.round((completed / total) * 100);
});

const submitDraft = (): void => {
    form.transform((data) => ({
        ...data,
        intent: 'draft',
        organization_id: data.organization_id === '__none' ? null : data.organization_id,
    })).post(storeSubmission().url);
};

const submitFinal = (): void => {
    form.transform((data) => ({
        ...data,
        intent: 'submit',
        organization_id: data.organization_id === '__none' ? null : data.organization_id,
    })).post(storeSubmission().url);
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Create submission" />

        <PageContainer>
            <PageHeader
                title="Create submission"
                description="Start with a draft. Final submission should only happen once the intake sections are complete."
            >
                <template #actions>
                    <Button as-child variant="outline">
                        <Link :href="submissionsIndex()">
                            <ArrowLeft class="size-4" />
                            Back to submissions
                        </Link>
                    </Button>
                </template>
            </PageHeader>

            <section class="grid gap-4 xl:grid-cols-[1fr_0.9fr]">
                <div class="rounded-[1.5rem] border border-border/70 bg-card/85 p-5 shadow-sm">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-semibold">Submission progress</h2>
                            <p class="text-sm text-muted-foreground">This is a single-page flow, but the intake sections still need to be completed deliberately.</p>
                        </div>
                        <div class="text-2xl font-semibold">{{ progressPercentage }}%</div>
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
                    <h2 class="text-lg font-semibold">Current open call</h2>
                    <p class="mt-2 text-sm leading-6 text-muted-foreground">
                        {{ openSeason ? `${openSeason.name} ${openSeason.year}` : 'No active season is currently configured.' }}
                    </p>
                    <div v-if="openSeason" class="mt-4 rounded-2xl border border-border/70 bg-background/70 p-4">
                        <div class="font-medium">{{ openSeason.registrationLabel }}</div>
                        <p class="mt-2 text-sm text-muted-foreground">
                            {{ openSeason.description || 'Use this season boundary when deciding whether to start or finalize a draft.' }}
                        </p>
                    </div>
                </div>
            </section>

            <form class="grid gap-6" @submit.prevent="submitDraft">
                <FormSection title="Structure" description="Every submission must point to one season, one stage, one industry, and one presenter profile.">
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
                            <Input id="title" v-model="form.title" placeholder="Project or venture name" />
                            <InputError :message="form.errors.title" />
                        </div>
                    </div>
                </FormSection>

                <FormSection title="Submission narrative" description="These fields can stay partial in draft mode, but final submission requires them all.">
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

                <FormSection title="Visibility preference" description="This only becomes relevant after Negadras approves the submission for public showcase.">
                    <div class="flex items-start gap-3 rounded-xl border border-border/70 p-4">
                        <Checkbox id="is_public_after_approval" v-model:checked="form.is_public_after_approval" />
                        <div class="space-y-1">
                            <Label for="is_public_after_approval">Allow public showcase after approval</Label>
                            <p class="text-sm text-muted-foreground">
                                Keep this off if the team wants to remain private until organizers explicitly approve publication.
                            </p>
                        </div>
                    </div>
                    <InputError :message="form.errors.is_public_after_approval" />
                </FormSection>

                <div class="flex flex-wrap items-center justify-end gap-3">
                    <Button type="button" variant="outline" :disabled="form.processing" @click="submitDraft">
                        <TableProperties class="size-4" />
                        Save draft
                    </Button>

                    <ConfirmActionDialog
                        title="Final submit this draft?"
                        description="After final submission, the draft becomes locked until Negadras returns it for correction."
                        confirm-label="Submit now"
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

                <div class="rounded-[1.25rem] border border-emerald-200 bg-emerald-50/80 p-4 text-sm text-emerald-950 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-50">
                    <div class="flex items-center gap-2 font-medium">
                        <ShieldCheck class="size-4" />
                        Draft-first workflow
                    </div>
                    <p class="mt-2 text-emerald-900/90 dark:text-emerald-100/90">
                        Saving as draft keeps the submission editable. Final submission requires all narrative fields and moves the record into intake review.
                    </p>
                </div>
            </form>
        </PageContainer>
    </AppLayout>
</template>
