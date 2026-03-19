<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import ConfirmActionDialog from '@/components/admin/ConfirmActionDialog.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import InputError from '@/components/InputError.vue';
import SubmissionFilesPanel from '@/components/submissions/SubmissionFilesPanel.vue';
import PageContainer from '@/components/PageContainer.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import { destroy as destroyReviewerAssignment, store as storeReviewerAssignment } from '@/routes/admin-submissions/reviewer-assignments';
import { index as adminSubmissionsIndex, transition as transitionSubmissionStatus } from '@/routes/admin-submissions';
import type { BreadcrumbItem, ManagedReviewerAssignment, ManagedSubmission, SelectOption, SubmissionFileDefinition, SubmissionTransitionOption } from '@/types';

type Props = {
    submission: ManagedSubmission;
    submissionFileDefinitions?: SubmissionFileDefinition[];
    availableTransitions: SubmissionTransitionOption[];
    canTransitionStatus: boolean;
    reviewerOptions: SelectOption[];
};

const props = defineProps<Props>();

const transitionForm = useForm({
    status: props.availableTransitions[0]?.value ?? '',
    reason: '',
});

const selectedTransitionRequiresReason = (): boolean => {
    return props.availableTransitions.find((option) => option.value === transitionForm.status)?.requiresReason ?? false;
};

const assignmentForm = useForm({
    reviewer_id: props.reviewerOptions[0]?.value ?? '',
    due_at: '',
});

const submitStatusTransition = (): void => {
    transitionForm.post(transitionSubmissionStatus(props.submission.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            transitionForm.reset('reason');
        },
    });
};

const submitReviewerAssignment = (): void => {
    assignmentForm.post(storeReviewerAssignment(props.submission.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            assignmentForm.reset('due_at');
        },
    });
};

const cancelReviewerAssignment = (assignment: ManagedReviewerAssignment): void => {
    router.delete(destroyReviewerAssignment({
        submission: props.submission.id,
        reviewerAssignment: assignment.id,
    }).url, {
        preserveScroll: true,
    });
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Admin submissions',
        href: adminSubmissionsIndex(),
    },
    {
        title: props.submission.title,
        href: adminSubmissionsIndex(),
    },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="submission.title" />

        <PageContainer>
            <PageHeader
                :title="submission.title"
                description="This is the staff-facing read model for intake inspection before reviewer assignment and later screening workflows."
            >
                <template #actions>
                    <div class="flex items-center gap-2">
                        <StatusBadge :label="submission.statusLabel" :tone="submission.statusTone" />
                        <Button v-if="submission.currentVersionNumber" variant="outline" disabled>
                            Current version v{{ submission.currentVersionNumber }}
                        </Button>
                        <Button as-child variant="outline">
                            <Link :href="adminSubmissionsIndex()">
                                <ArrowLeft class="size-4" />
                                Back to admin submissions
                            </Link>
                        </Button>
                    </div>
                </template>
            </PageHeader>

            <div class="grid gap-6 lg:grid-cols-[1.2fr_0.8fr]">
                <section class="grid gap-6">
                    <div class="rounded-[1.5rem] border border-border/70 bg-card/85 p-5 shadow-sm">
                        <h2 class="text-base font-semibold">Summary</h2>
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
                        <h2 class="text-base font-semibold">Operational context</h2>

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
                            <div>
                                <dt class="text-muted-foreground">Public after approval</dt>
                                <dd class="font-medium">{{ submission.isPublicAfterApproval ? 'Yes' : 'No' }}</dd>
                            </div>
                            <div>
                                <dt class="text-muted-foreground">Submitted at</dt>
                                <dd class="font-medium">{{ submission.submittedAt ? new Date(submission.submittedAt).toLocaleString() : 'Draft only' }}</dd>
                            </div>
                            <div>
                                <dt class="text-muted-foreground">Last updated</dt>
                                <dd class="font-medium">{{ submission.updatedAt ? new Date(submission.updatedAt).toLocaleString() : 'N/A' }}</dd>
                            </div>
                            <div v-if="submission.latestStatusReason">
                                <dt class="text-muted-foreground">Latest status note</dt>
                                <dd class="font-medium">{{ submission.latestStatusReason }}</dd>
                            </div>
                        </dl>
                    </div>

                    <div v-if="submission.intakeChecklist" class="rounded-[1.5rem] border border-border/70 bg-card/85 p-5 shadow-sm">
                        <div class="flex items-center justify-between gap-3">
                            <h2 class="text-base font-semibold">Intake checklist</h2>
                            <span class="text-sm text-muted-foreground">
                                {{ submission.intakeChecklist.passedCount }}/{{ submission.intakeChecklist.totalCount }} passed
                            </span>
                        </div>

                        <ul class="mt-4 grid gap-3 text-sm">
                            <li
                                v-for="item in submission.intakeChecklist.items"
                                :key="item.key"
                                class="flex items-start justify-between gap-3 rounded-xl border border-border/70 bg-background/60 px-3 py-2"
                            >
                                <span>{{ item.label }}</span>
                                <span :class="item.passed ? 'text-emerald-600' : 'text-amber-600'">
                                    {{ item.passed ? 'Pass' : 'Fail' }}
                                </span>
                            </li>
                        </ul>
                    </div>

                    <div v-if="canTransitionStatus && availableTransitions.length > 0" class="rounded-[1.5rem] border border-border/70 bg-card/85 p-5 shadow-sm">
                        <h2 class="text-base font-semibold">Status transition</h2>
                        <p class="mt-2 text-sm text-muted-foreground">
                            Move the submission through intake review. Return and rejection actions require a reason.
                        </p>

                        <form class="mt-4 grid gap-4" @submit.prevent="submitStatusTransition">
                            <div class="grid gap-2">
                                <Label for="status">Next status</Label>
                                <select
                                    id="status"
                                    v-model="transitionForm.status"
                                    class="flex h-10 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none ring-offset-background focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
                                >
                                    <option v-for="option in availableTransitions" :key="option.value" :value="option.value">
                                        {{ option.label }}
                                    </option>
                                </select>
                                <InputError :message="transitionForm.errors.status" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="reason">Reason</Label>
                                <textarea
                                    id="reason"
                                    v-model="transitionForm.reason"
                                    rows="4"
                                    class="flex min-h-28 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none ring-offset-background placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
                                    :placeholder="selectedTransitionRequiresReason() ? 'Reason is required for this transition.' : 'Optional internal note.'"
                                />
                                <InputError :message="transitionForm.errors.reason" />
                            </div>

                            <Button type="submit" :disabled="transitionForm.processing || !transitionForm.status">
                                Update status
                            </Button>
                        </form>
                    </div>

                    <div class="rounded-[1.5rem] border border-border/70 bg-card/85 p-5 shadow-sm">
                        <h2 class="text-base font-semibold">Reviewer assignments</h2>
                        <p class="mt-2 text-sm text-muted-foreground">
                            Assign eligible submissions to active reviewers and track whether their screening review is still pending or already submitted.
                        </p>

                        <form
                            v-if="reviewerOptions.length > 0"
                            class="mt-4 grid gap-4"
                            @submit.prevent="submitReviewerAssignment"
                        >
                            <div class="grid gap-2">
                                <Label for="reviewer_id">Reviewer</Label>
                                <Select v-model="assignmentForm.reviewer_id">
                                    <SelectTrigger id="reviewer_id">
                                        <SelectValue placeholder="Select reviewer" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="option in reviewerOptions" :key="option.value" :value="String(option.value)">
                                            {{ option.label }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError :message="assignmentForm.errors.reviewer_id" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="due_at">Due at</Label>
                                <Input id="due_at" v-model="assignmentForm.due_at" type="datetime-local" />
                                <InputError :message="assignmentForm.errors.due_at" />
                            </div>

                            <Button type="submit" :disabled="assignmentForm.processing || !assignmentForm.reviewer_id">
                                Assign reviewer
                            </Button>
                        </form>

                        <div
                            v-else
                            class="mt-4 rounded-xl border border-dashed border-border/70 bg-background/60 p-4 text-sm text-muted-foreground"
                        >
                            No active reviewer profiles are available yet.
                        </div>

                        <div class="mt-5 grid gap-3">
                            <article
                                v-for="assignment in submission.reviewerAssignments ?? []"
                                :key="assignment.id"
                                class="rounded-xl border border-border/70 bg-background/60 p-4"
                            >
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <div class="font-medium">{{ assignment.reviewerName || 'Unknown reviewer' }}</div>
                                        <div class="mt-1 text-sm text-muted-foreground">{{ assignment.reviewerEmail || 'No email' }}</div>
                                    </div>
                                    <StatusBadge :label="assignment.statusLabel" :tone="assignment.statusTone" />
                                </div>

                                <div class="mt-3 grid gap-1 text-sm text-muted-foreground">
                                    <div>Stage: {{ assignment.stageName || 'N/A' }}</div>
                                    <div>Assigned: {{ assignment.assignedAt ? new Date(assignment.assignedAt).toLocaleString() : 'N/A' }}</div>
                                    <div>Due: {{ assignment.dueAt ? new Date(assignment.dueAt).toLocaleString() : 'No due date' }}</div>
                                    <div v-if="assignment.recommendationLabel">Recommendation: {{ assignment.recommendationLabel }}</div>
                                </div>

                                <div class="mt-4 flex justify-end">
                                    <ConfirmActionDialog
                                        title="Cancel reviewer assignment"
                                        description="Cancel this assignment so a manager can reassign the submission if needed."
                                        confirm-label="Cancel assignment"
                                        @confirm="cancelReviewerAssignment(assignment)"
                                    >
                                        <template #trigger>
                                            <Button variant="outline">Cancel assignment</Button>
                                        </template>
                                    </ConfirmActionDialog>
                                </div>
                            </article>

                            <div
                                v-if="(submission.reviewerAssignments?.length ?? 0) === 0"
                                class="rounded-xl border border-dashed border-border/70 bg-background/60 p-4 text-sm text-muted-foreground"
                            >
                                No reviewer assignments exist yet for this submission.
                            </div>
                        </div>
                    </div>

                    <div class="rounded-[1.5rem] border border-border/70 bg-card/85 p-5 shadow-sm">
                        <div class="flex items-center justify-between gap-3">
                            <h2 class="text-base font-semibold">Status timeline</h2>
                            <span class="text-sm text-muted-foreground">{{ submission.statusTimeline?.length ?? 0 }} event(s)</span>
                        </div>

                        <div v-if="(submission.statusTimeline?.length ?? 0) === 0" class="mt-4 text-sm text-muted-foreground">
                            No status events have been recorded yet.
                        </div>

                        <ol v-else class="mt-4 grid gap-3">
                            <li
                                v-for="entry in submission.statusTimeline"
                                :key="entry.id"
                                class="rounded-xl border border-border/70 bg-background/60 p-4"
                            >
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <div class="flex items-center gap-2">
                                        <StatusBadge :label="entry.toStatusLabel" :tone="entry.toStatusTone" />
                                        <span class="text-sm text-muted-foreground">
                                            {{ entry.fromStatusLabel ? `${entry.fromStatusLabel} -> ${entry.toStatusLabel}` : `Initial status: ${entry.toStatusLabel}` }}
                                        </span>
                                    </div>
                                    <div class="text-xs text-muted-foreground">
                                        {{ entry.changedAt ? new Date(entry.changedAt).toLocaleString() : 'Unknown time' }}
                                    </div>
                                </div>
                                <div class="mt-2 text-sm text-muted-foreground">
                                    {{ entry.reason || 'No note recorded.' }}
                                </div>
                                <div class="mt-2 text-xs text-muted-foreground">
                                    Changed by {{ entry.changedBy || 'System' }}
                                </div>
                            </li>
                        </ol>
                    </div>

                    <div class="rounded-[1.5rem] border border-border/70 bg-card/85 p-5 shadow-sm">
                        <div class="flex items-center justify-between gap-3">
                            <h2 class="text-base font-semibold">Version history</h2>
                            <span class="text-sm text-muted-foreground">{{ submission.versionCount ?? 0 }} version(s)</span>
                        </div>

                        <div v-if="(submission.versionHistory?.length ?? 0) === 0" class="mt-4 text-sm text-muted-foreground">
                            No locked versions exist yet for this submission.
                        </div>

                        <ol v-else class="mt-4 grid gap-3">
                            <li
                                v-for="version in submission.versionHistory"
                                :key="version.id"
                                class="rounded-xl border border-border/70 bg-background/60 p-4"
                            >
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <div class="flex items-center gap-2">
                                        <div class="font-medium">v{{ version.versionNo }}</div>
                                        <StatusBadge
                                            :label="version.isCurrent ? 'Current version' : 'Locked version'"
                                            :tone="version.isCurrent ? 'published' : 'draft'"
                                        />
                                    </div>
                                    <div class="text-xs text-muted-foreground">
                                        {{ version.createdAt ? new Date(version.createdAt).toLocaleString() : 'Unknown time' }}
                                    </div>
                                </div>

                                <div class="mt-2 text-sm text-muted-foreground">
                                    {{ version.changeNote || 'No change note recorded.' }}
                                </div>

                                <div class="mt-3 grid gap-1 text-sm text-muted-foreground">
                                    <div>Snapshot title: {{ version.snapshotTitle }}</div>
                                    <div>Snapshot status: {{ version.snapshotStatus }}</div>
                                    <div>Created by: {{ version.createdBy || 'System' }}</div>
                                    <div>Locked: {{ version.isLocked ? 'Yes' : 'No' }}</div>
                                </div>
                            </li>
                        </ol>
                    </div>
                </aside>
            </div>

            <SubmissionFilesPanel
                :submission-id="submission.id"
                :definitions="submissionFileDefinitions ?? []"
                :draft-files="submission.draftFiles ?? []"
                :current-version-files="submission.currentVersionFiles ?? []"
            />
        </PageContainer>
    </AppLayout>
</template>
