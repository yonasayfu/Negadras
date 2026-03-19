<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Send, Trash2 } from 'lucide-vue-next';
import { computed } from 'vue';
import ConfirmActionDialog from '@/components/admin/ConfirmActionDialog.vue';
import FormSection from '@/components/admin/FormSection.vue';
import InputError from '@/components/InputError.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import PageContainer from '@/components/PageContainer.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import { destroy as destroySubmission, edit as editSubmission, index as submissionsIndex, show as showSubmission, update as updateSubmission } from '@/routes/submissions';
import type { BreadcrumbItem, ManagedSubmission, SelectOption, SubmissionStageOption } from '@/types';

type Props = {
    submission: ManagedSubmission;
    seasonOptions: SelectOption[];
    stageOptions: SubmissionStageOption[];
    industryOptions: SelectOption[];
    organizationOptions: SelectOption[];
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

const saveDraft = (): void => {
    form.transform((data) => ({
        ...data,
        intent: 'draft',
        organization_id: data.organization_id === '__none' ? null : data.organization_id,
    })).put(updateSubmission(props.submission.id).url);
};

const submitFinal = (): void => {
    form.transform((data) => ({
        ...data,
        intent: 'submit',
        organization_id: data.organization_id === '__none' ? null : data.organization_id,
    })).put(updateSubmission(props.submission.id).url);
};

const deleteDraft = (): void => {
    router.delete(destroySubmission(props.submission.id).url);
};
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
        </PageContainer>
    </AppLayout>
</template>
