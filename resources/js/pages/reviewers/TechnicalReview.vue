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
import { store as storeTechnicalReview } from '@/routes/technical-reviews';
import { index as technicalReviewerQueueIndex, show as showTechnicalAssignment } from '@/routes/technical-reviewer-queue';
import type { BreadcrumbItem, ManagedReviewerAssignment, ManagedSubmission, SelectOption, SubmissionFileDefinition } from '@/types';

type Props = {
    assignment: ManagedReviewerAssignment;
    submission: ManagedSubmission;
    recommendationOptions: SelectOption[];
    submissionFileDefinitions: SubmissionFileDefinition[];
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Technical review queue',
        href: technicalReviewerQueueIndex(),
    },
    {
        title: props.submission.title,
        href: showTechnicalAssignment(props.assignment.id),
    },
];

const reviewForm = useForm({
    intent: 'draft',
    innovation_score_optional: props.assignment.review?.innovationScoreOptional ? String(props.assignment.review.innovationScoreOptional) : '',
    feasibility_score_optional: props.assignment.review?.feasibilityScoreOptional ? String(props.assignment.review.feasibilityScoreOptional) : '',
    execution_score_optional: props.assignment.review?.executionScoreOptional ? String(props.assignment.review.executionScoreOptional) : '',
    market_score_optional: props.assignment.review?.marketScoreOptional ? String(props.assignment.review.marketScoreOptional) : '',
    strengths: props.assignment.review?.strengths ?? '',
    weaknesses: props.assignment.review?.weaknesses ?? '',
    risk_note: props.assignment.review?.riskNote ?? '',
    recommendation: props.assignment.review?.recommendation ?? '',
});

const transformPayload = (intent: 'draft' | 'submit') => ({
    ...reviewForm.data(),
    intent,
    innovation_score_optional: reviewForm.innovation_score_optional === '' ? null : Number(reviewForm.innovation_score_optional),
    feasibility_score_optional: reviewForm.feasibility_score_optional === '' ? null : Number(reviewForm.feasibility_score_optional),
    execution_score_optional: reviewForm.execution_score_optional === '' ? null : Number(reviewForm.execution_score_optional),
    market_score_optional: reviewForm.market_score_optional === '' ? null : Number(reviewForm.market_score_optional),
});

const saveDraft = (): void => {
    reviewForm.transform(() => transformPayload('draft')).post(storeTechnicalReview(props.assignment.id).url);
};

const submitReview = (): void => {
    reviewForm.transform(() => transformPayload('submit')).post(storeTechnicalReview(props.assignment.id).url);
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="`Technical review ${submission.title}`" />

        <PageContainer>
            <PageHeader
                :title="submission.title"
                description="Review the shortlisted submission in more depth and record technical strengths, risks, and recommendation."
            >
                <template #actions>
                    <div class="flex items-center gap-2">
                        <StatusBadge :label="assignment.statusLabel" :tone="assignment.statusTone" />
                        <Button as-child variant="outline">
                            <Link :href="technicalReviewerQueueIndex()">
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
                        <h2 class="text-base font-semibold">Solution description</h2>
                        <p class="mt-3 whitespace-pre-wrap text-sm leading-6 text-muted-foreground">{{ submission.solutionDescription || 'No solution description provided.' }}</p>
                    </div>

                    <div class="rounded-[1.5rem] border border-border/70 bg-card/85 p-5 shadow-sm">
                        <h2 class="text-base font-semibold">Business model</h2>
                        <p class="mt-3 whitespace-pre-wrap text-sm leading-6 text-muted-foreground">{{ submission.businessModel || 'No business model provided.' }}</p>
                    </div>

                    <div class="rounded-[1.5rem] border border-border/70 bg-card/85 p-5 shadow-sm">
                        <h2 class="text-base font-semibold">Submitted screening context</h2>
                        <div class="mt-4 grid gap-3">
                            <article
                                v-for="(review, index) in submission.screeningReviews ?? []"
                                :key="`${review.reviewerName}-${index}`"
                                class="rounded-xl border border-border/70 bg-background/60 p-4"
                            >
                                <div class="font-medium">{{ review.reviewerName || 'Reviewer' }}</div>
                                <div class="mt-2 text-sm text-muted-foreground">
                                    {{ review.recommendation || 'No recommendation' }} · {{ review.eligibilityStatus || 'No eligibility status' }}
                                </div>
                                <p class="mt-2 whitespace-pre-wrap text-sm text-muted-foreground">{{ review.notes || 'No notes.' }}</p>
                            </article>
                        </div>
                    </div>
                </section>

                <aside class="grid gap-6">
                    <div class="rounded-[1.5rem] border border-border/70 bg-card/85 p-5 shadow-sm">
                        <h2 class="text-base font-semibold">Technical review</h2>
                        <p class="mt-2 text-sm text-muted-foreground">
                            Save a draft while you assess the submission, then submit once the technical recommendation is ready.
                        </p>

                        <form class="mt-4 grid gap-4" @submit.prevent="saveDraft">
                            <div class="grid gap-3 md:grid-cols-2">
                                <div class="grid gap-2">
                                    <Label for="innovation_score_optional">Innovation score</Label>
                                    <Input id="innovation_score_optional" v-model="reviewForm.innovation_score_optional" type="number" min="1" max="100" />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="feasibility_score_optional">Feasibility score</Label>
                                    <Input id="feasibility_score_optional" v-model="reviewForm.feasibility_score_optional" type="number" min="1" max="100" />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="execution_score_optional">Execution score</Label>
                                    <Input id="execution_score_optional" v-model="reviewForm.execution_score_optional" type="number" min="1" max="100" />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="market_score_optional">Market score</Label>
                                    <Input id="market_score_optional" v-model="reviewForm.market_score_optional" type="number" min="1" max="100" />
                                </div>
                            </div>

                            <div class="grid gap-2">
                                <Label for="strengths">Strengths</Label>
                                <textarea id="strengths" v-model="reviewForm.strengths" rows="4" class="flex min-h-24 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="weaknesses">Weaknesses</Label>
                                <textarea id="weaknesses" v-model="reviewForm.weaknesses" rows="4" class="flex min-h-24 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="risk_note">Risk note</Label>
                                <textarea id="risk_note" v-model="reviewForm.risk_note" rows="3" class="flex min-h-20 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs" />
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

                            <div class="flex flex-wrap justify-end gap-3">
                                <Button type="button" variant="outline" :disabled="reviewForm.processing" @click="saveDraft">
                                    <Save class="size-4" />
                                    Save draft review
                                </Button>

                                <ConfirmActionDialog
                                    title="Submit technical review?"
                                    description="Submitted technical reviews become locked until a later manager reopen flow exists."
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

                    <div v-if="submission.intakeNotes?.length" class="rounded-[1.5rem] border border-border/70 bg-card/85 p-5 shadow-sm">
                        <h2 class="text-base font-semibold">Prior intake and manager notes</h2>
                        <div class="mt-4 grid gap-3">
                            <article
                                v-for="(entry, index) in submission.intakeNotes"
                                :key="`${entry.changedAt}-${index}`"
                                class="rounded-xl border border-border/70 bg-background/60 p-4"
                            >
                                <div class="text-sm font-medium">{{ entry.statusLabel }}</div>
                                <p class="mt-2 whitespace-pre-wrap text-sm text-muted-foreground">{{ entry.reason || 'No note recorded.' }}</p>
                                <div class="mt-2 text-xs text-muted-foreground">
                                    {{ entry.changedBy || 'System' }} · {{ entry.changedAt ? new Date(entry.changedAt).toLocaleString() : 'Unknown time' }}
                                </div>
                            </article>
                        </div>
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
