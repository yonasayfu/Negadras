<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save, Send } from 'lucide-vue-next';
import ConfirmActionDialog from '@/components/admin/ConfirmActionDialog.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import InputError from '@/components/InputError.vue';
import PageContainer from '@/components/PageContainer.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as screeningQueueIndex, decision as decideScreening } from '@/routes/screening-queue';
import { update as updateReviewerAssignment } from '@/routes/reviewer-assignments';
import type { BreadcrumbItem, ManagedSubmission, SelectOption, SubmissionTransitionOption } from '@/types';

type Props = {
    submission: ManagedSubmission;
    reviewerOptions: SelectOption[];
    decisionOptions: SubmissionTransitionOption[];
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Screening queue',
        href: screeningQueueIndex(),
    },
    {
        title: props.submission.title,
        href: screeningQueueIndex(),
    },
];

const decisionForm = useForm({
    status: props.decisionOptions[0]?.value ?? '',
    reason: '',
});

const reassignmentForms = Object.fromEntries(
    (props.submission.reviewerAssignments ?? []).map((assignment) => [
        assignment.id,
        useForm({
            reviewer_id: '',
            due_at: assignment.dueAt ? assignment.dueAt.slice(0, 16) : '',
            reason: '',
        }),
    ]),
);

const submitDecision = (): void => {
    decisionForm.post(decideScreening(props.submission.id).url, {
        preserveScroll: true,
    });
};

const submitReassignment = (assignmentId: number): void => {
    reassignmentForms[assignmentId].transform((data) => ({
        ...data,
        due_at: data.due_at === '' ? null : data.due_at,
    })).put(updateReviewerAssignment(assignmentId).url, {
        preserveScroll: true,
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="`Screening ${submission.title}`" />

        <PageContainer>
            <PageHeader
                :title="submission.title"
                description="Inspect reviewer output, reassign work when needed, and record the manager-level screening decision."
            >
                <template #actions>
                    <div class="flex items-center gap-2">
                        <StatusBadge :label="submission.statusLabel" :tone="submission.statusTone" />
                        <Button as-child variant="outline">
                            <Link :href="screeningQueueIndex()">
                                <ArrowLeft class="size-4" />
                                Back to screening queue
                            </Link>
                        </Button>
                    </div>
                </template>
            </PageHeader>

            <div class="grid gap-6 lg:grid-cols-[1.15fr_0.85fr]">
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
                        <h2 class="text-base font-semibold">Reviewer assignments and submitted reviews</h2>
                        <div class="mt-4 grid gap-4">
                            <article
                                v-for="assignment in submission.reviewerAssignments ?? []"
                                :key="assignment.id"
                                class="rounded-xl border border-border/70 bg-background/60 p-4"
                            >
                                <div class="flex flex-wrap items-center gap-2">
                                    <div class="font-medium">{{ assignment.reviewerName || 'Reviewer' }}</div>
                                    <StatusBadge :label="assignment.statusLabel" :tone="assignment.statusTone" />
                                    <StatusBadge
                                        v-if="assignment.recommendationLabel"
                                        :label="assignment.recommendationLabel"
                                        tone="review"
                                    />
                                </div>

                                <div class="mt-3 grid gap-1 text-sm text-muted-foreground md:grid-cols-2">
                                    <div>Email: {{ assignment.reviewerEmail || 'No email' }}</div>
                                    <div>Stage: {{ assignment.stageName || 'No stage' }}</div>
                                    <div>Assigned at: {{ assignment.assignedAt ? new Date(assignment.assignedAt).toLocaleString() : 'N/A' }}</div>
                                    <div>Due at: {{ assignment.dueAt ? new Date(assignment.dueAt).toLocaleString() : 'No due date' }}</div>
                                </div>

                                <div v-if="assignment.review" class="mt-4 grid gap-2 rounded-xl border border-border/70 bg-card/80 p-4 text-sm">
                                    <div>Eligibility: {{ assignment.review.eligibilityStatus || 'Not set' }}</div>
                                    <div>Recommendation: {{ assignment.review.recommendation || 'Not set' }}</div>
                                    <div>Optional score: {{ assignment.review.scoreOptional ?? 'Not scored' }}</div>
                                    <div>Submitted at: {{ assignment.review.submittedAt ? new Date(assignment.review.submittedAt).toLocaleString() : 'Draft only' }}</div>
                                    <div class="whitespace-pre-wrap text-muted-foreground">{{ assignment.review.notes || 'No reviewer notes.' }}</div>
                                </div>

                                <form class="mt-4 grid gap-3 rounded-xl border border-border/70 bg-card/80 p-4" @submit.prevent="submitReassignment(assignment.id)">
                                    <div class="text-sm font-medium">Reassign reviewer</div>
                                    <div class="grid gap-3 md:grid-cols-2">
                                        <div class="grid gap-2">
                                            <Label :for="`reviewer-${assignment.id}`">New reviewer</Label>
                                            <Select v-model="reassignmentForms[assignment.id].reviewer_id">
                                                <SelectTrigger :id="`reviewer-${assignment.id}`">
                                                    <SelectValue placeholder="Select reviewer" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem v-for="option in reviewerOptions" :key="option.value" :value="String(option.value)">
                                                        {{ option.label }}
                                                    </SelectItem>
                                                </SelectContent>
                                            </Select>
                                            <InputError :message="reassignmentForms[assignment.id].errors.reviewer_id" />
                                        </div>

                                        <div class="grid gap-2">
                                            <Label :for="`due-${assignment.id}`">New due date</Label>
                                            <Input :id="`due-${assignment.id}`" v-model="reassignmentForms[assignment.id].due_at" type="datetime-local" />
                                            <InputError :message="reassignmentForms[assignment.id].errors.due_at" />
                                        </div>
                                    </div>

                                    <div class="grid gap-2">
                                        <Label :for="`reason-${assignment.id}`">Reason</Label>
                                        <textarea
                                            :id="`reason-${assignment.id}`"
                                            v-model="reassignmentForms[assignment.id].reason"
                                            rows="3"
                                            class="flex min-h-24 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs"
                                            placeholder="Why this reassignment is needed."
                                        />
                                        <InputError :message="reassignmentForms[assignment.id].errors.reason" />
                                    </div>

                                    <div class="flex justify-end">
                                        <Button type="submit" variant="outline" :disabled="reassignmentForms[assignment.id].processing">
                                            <Save class="size-4" />
                                            Reassign reviewer
                                        </Button>
                                    </div>
                                </form>
                            </article>
                        </div>
                    </div>
                </section>

                <aside class="grid gap-6">
                    <div class="rounded-[1.5rem] border border-border/70 bg-card/85 p-5 shadow-sm">
                        <h2 class="text-base font-semibold">Decision context</h2>
                        <dl class="mt-4 grid gap-3 text-sm">
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
                                <dt class="text-muted-foreground">Screening state</dt>
                                <dd class="font-medium">{{ submission.screeningStateLabel || 'Not derived' }}</dd>
                            </div>
                            <div>
                                <dt class="text-muted-foreground">Latest recommendation</dt>
                                <dd class="font-medium">{{ submission.latestRecommendationLabel || 'No submitted review yet' }}</dd>
                            </div>
                            <div v-if="submission.latestStatusReason">
                                <dt class="text-muted-foreground">Latest note</dt>
                                <dd class="font-medium">{{ submission.latestStatusReason }}</dd>
                            </div>
                        </dl>
                    </div>

                    <div class="rounded-[1.5rem] border border-border/70 bg-card/85 p-5 shadow-sm">
                        <h2 class="text-base font-semibold">Manager screening decision</h2>
                        <p class="mt-2 text-sm text-muted-foreground">
                            Use this only after at least one submitted screening review exists. Revision requests return the submission to the presenter for correction.
                        </p>

                        <form class="mt-4 grid gap-4" @submit.prevent="submitDecision">
                            <div class="grid gap-2">
                                <Label for="screening-status">Decision</Label>
                                <Select v-model="decisionForm.status">
                                    <SelectTrigger id="screening-status">
                                        <SelectValue placeholder="Select decision" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="option in decisionOptions" :key="option.value" :value="String(option.value)">
                                            {{ option.label }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError :message="decisionForm.errors.status" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="screening-reason">Reason</Label>
                                <textarea
                                    id="screening-reason"
                                    v-model="decisionForm.reason"
                                    rows="5"
                                    class="flex min-h-32 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs"
                                    placeholder="Required for revision and rejection. Recommended for shortlist rationale too."
                                />
                                <InputError :message="decisionForm.errors.reason" />
                            </div>

                            <ConfirmActionDialog
                                title="Record screening decision?"
                                description="This will update the submission status and notify the presenter."
                                confirm-label="Record decision"
                                :processing="decisionForm.processing"
                                @confirm="submitDecision"
                            >
                                <template #trigger>
                                    <Button type="button" :disabled="decisionForm.processing || decisionOptions.length === 0">
                                        <Send class="size-4" />
                                        Apply decision
                                    </Button>
                                </template>
                            </ConfirmActionDialog>
                        </form>
                    </div>
                </aside>
            </div>
        </PageContainer>
    </AppLayout>
</template>
