<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save, Send } from 'lucide-vue-next';
import ConfirmActionDialog from '@/components/admin/ConfirmActionDialog.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import SubmissionFilesPanel from '@/components/submissions/SubmissionFilesPanel.vue';
import PageContainer from '@/components/PageContainer.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import { store as storeScreeningReview } from '@/routes/screening-reviews';
import { index as reviewerQueueIndex, show as showReviewerAssignment } from '@/routes/reviewer-queue';
import type { BreadcrumbItem, ManagedReviewerAssignment, ManagedSubmission, SelectOption, SubmissionFileDefinition } from '@/types';

type Props = {
    assignment: ManagedReviewerAssignment;
    submission: ManagedSubmission;
    recommendationOptions: SelectOption[];
    eligibilityOptions: SelectOption[];
    submissionFileDefinitions: SubmissionFileDefinition[];
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Reviewer queue',
        href: reviewerQueueIndex(),
    },
    {
        title: props.submission.title,
        href: showReviewerAssignment(props.assignment.id),
    },
];

const reviewForm = useForm({
    intent: 'draft',
    eligibility_status: props.assignment.review?.eligibilityStatus ?? '',
    recommendation: props.assignment.review?.recommendation ?? '',
    score_optional: props.assignment.review?.scoreOptional ? String(props.assignment.review.scoreOptional) : '',
    notes: props.assignment.review?.notes ?? '',
});

const saveDraft = (): void => {
    reviewForm.transform((data) => ({
        ...data,
        intent: 'draft',
        score_optional: data.score_optional === '' ? null : Number(data.score_optional),
    })).post(storeScreeningReview(props.assignment.id).url);
};

const submitReview = (): void => {
    reviewForm.transform((data) => ({
        ...data,
        intent: 'submit',
        score_optional: data.score_optional === '' ? null : Number(data.score_optional),
    })).post(storeScreeningReview(props.assignment.id).url);
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="`Review ${submission.title}`" />

        <PageContainer>
            <PageHeader
                :title="submission.title"
                description="Review only the submissions explicitly assigned to you and record the first screening recommendation here."
            >
                <template #actions>
                    <div class="flex items-center gap-2">
                        <StatusBadge :label="assignment.statusLabel" :tone="assignment.statusTone" />
                        <Button as-child variant="outline">
                            <Link :href="reviewerQueueIndex()">
                                <ArrowLeft class="size-4" />
                                Back to queue
                            </Link>
                        </Button>
                    </div>
                </template>
            </PageHeader>

            <div class="grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
                <section class="grid gap-6">
                    <div class="rounded-[1.5rem] border border-border/70 bg-card/85 p-5 shadow-sm">
                        <h2 class="text-base font-semibold">Submission summary</h2>
                        <p class="mt-3 whitespace-pre-wrap text-sm leading-6 text-muted-foreground">{{ submission.summary || 'No summary provided.' }}</p>
                    </div>

                    <div class="rounded-[1.5rem] border border-border/70 bg-card/85 p-5 shadow-sm">
                        <h2 class="text-base font-semibold">Problem statement</h2>
                        <p class="mt-3 whitespace-pre-wrap text-sm leading-6 text-muted-foreground">{{ submission.problemStatement || 'No problem statement provided.' }}</p>
                    </div>

                    <div class="rounded-[1.5rem] border border-border/70 bg-card/85 p-5 shadow-sm">
                        <h2 class="text-base font-semibold">Solution description</h2>
                        <p class="mt-3 whitespace-pre-wrap text-sm leading-6 text-muted-foreground">{{ submission.solutionDescription || 'No solution description provided.' }}</p>
                    </div>

                    <div class="rounded-[1.5rem] border border-border/70 bg-card/85 p-5 shadow-sm">
                        <h2 class="text-base font-semibold">Business model</h2>
                        <p class="mt-3 whitespace-pre-wrap text-sm leading-6 text-muted-foreground">{{ submission.businessModel || 'No business model provided.' }}</p>
                    </div>
                </section>

                <aside class="grid gap-6">
                    <div class="rounded-[1.5rem] border border-border/70 bg-card/85 p-5 shadow-sm">
                        <h2 class="text-base font-semibold">Reviewer context</h2>

                        <dl class="mt-4 grid gap-4 text-sm">
                            <div>
                                <dt class="text-muted-foreground">Presenter</dt>
                                <dd class="font-medium">{{ submission.applicantName || 'Unknown presenter' }}</dd>
                                <dd class="text-muted-foreground">{{ submission.applicantEmail || 'No email' }}</dd>
                            </div>
                            <div>
                                <dt class="text-muted-foreground">Organization</dt>
                                <dd class="font-medium">{{ submission.organizationName || 'Independent presenter' }}</dd>
                            </div>
                            <div>
                                <dt class="text-muted-foreground">Season / stage</dt>
                                <dd class="font-medium">{{ submission.seasonName || 'No season' }}</dd>
                                <dd class="text-muted-foreground">{{ submission.stageName || 'No stage' }}</dd>
                            </div>
                            <div>
                                <dt class="text-muted-foreground">Industry</dt>
                                <dd class="font-medium">{{ submission.industryName || 'No industry' }}</dd>
                            </div>
                            <div v-if="submission.latestStatusReason">
                                <dt class="text-muted-foreground">Latest intake note</dt>
                                <dd class="font-medium">{{ submission.latestStatusReason }}</dd>
                            </div>
                        </dl>
                    </div>

                    <div class="rounded-[1.5rem] border border-border/70 bg-card/85 p-5 shadow-sm">
                        <h2 class="text-base font-semibold">Screening review</h2>
                        <p class="mt-2 text-sm text-muted-foreground">
                            Save a draft while you are still reading the submission, then submit once the recommendation and notes are ready.
                        </p>

                        <form class="mt-4 grid gap-4" @submit.prevent="saveDraft">
                            <div class="grid gap-2">
                                <Label for="eligibility_status">Eligibility status</Label>
                                <Select v-model="reviewForm.eligibility_status">
                                    <SelectTrigger id="eligibility_status">
                                        <SelectValue placeholder="Select eligibility status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="option in eligibilityOptions" :key="option.value" :value="String(option.value)">
                                            {{ option.label }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div class="grid gap-2">
                                <Label for="recommendation">Recommendation</Label>
                                <Select v-model="reviewForm.recommendation">
                                    <SelectTrigger id="recommendation">
                                        <SelectValue placeholder="Select recommendation" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="option in recommendationOptions" :key="option.value" :value="String(option.value)">
                                            {{ option.label }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div class="grid gap-2">
                                <Label for="score_optional">Optional score</Label>
                                <Input id="score_optional" v-model="reviewForm.score_optional" type="number" min="1" max="100" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="notes">Private reviewer notes</Label>
                                <textarea
                                    id="notes"
                                    v-model="reviewForm.notes"
                                    rows="8"
                                    class="flex min-h-40 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none ring-offset-background placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
                                    placeholder="Record the reasoning behind your recommendation."
                                />
                            </div>

                            <div class="flex flex-wrap justify-end gap-3">
                                <Button type="button" variant="outline" :disabled="reviewForm.processing" @click="saveDraft">
                                    <Save class="size-4" />
                                    Save draft review
                                </Button>

                                <ConfirmActionDialog
                                    title="Submit screening review?"
                                    description="Submitted screening reviews become locked until a manager explicitly reopens them in a later phase."
                                    confirm-label="Submit review"
                                    :processing="reviewForm.processing"
                                    @confirm="submitReview"
                                >
                                    <template #trigger>
                                        <Button type="button" :disabled="reviewForm.processing">
                                            <Send class="size-4" />
                                            Submit review
                                        </Button>
                                    </template>
                                </ConfirmActionDialog>
                            </div>
                        </form>
                    </div>
                </aside>
            </div>

            <SubmissionFilesPanel
                :submission-id="submission.id"
                :definitions="submissionFileDefinitions"
                :draft-files="[]"
                :current-version-files="submission.currentVersionFiles ?? []"
                :can-manage="false"
            />
        </PageContainer>
    </AppLayout>
</template>
