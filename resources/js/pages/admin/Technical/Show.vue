<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save } from 'lucide-vue-next';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import InputError from '@/components/InputError.vue';
import PageContainer from '@/components/PageContainer.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import { store as storeTechnicalAssignment } from '@/routes/admin-submissions/technical-reviewer-assignments';
import { index as technicalQueueIndex } from '@/routes/technical-queue';
import type { BreadcrumbItem, ManagedSubmission, SelectOption } from '@/types';

type Props = {
    submission: ManagedSubmission;
    reviewerOptions: SelectOption[];
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Technical queue',
        href: technicalQueueIndex(),
    },
    {
        title: props.submission.title,
        href: technicalQueueIndex(),
    },
];

const assignForm = useForm({
    reviewer_id: '',
    due_at: '',
});

const assignReviewer = (): void => {
    assignForm.transform((data) => ({
        ...data,
        due_at: data.due_at === '' ? null : data.due_at,
    })).post(storeTechnicalAssignment(props.submission.id).url, {
        preserveScroll: true,
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="`Technical ${submission.title}`" />

        <PageContainer>
            <PageHeader
                :title="submission.title"
                description="Assign technical experts and compare screening output against deeper technical reviews."
            >
                <template #actions>
                    <div class="flex items-center gap-2">
                        <StatusBadge :label="submission.statusLabel" :tone="submission.statusTone" />
                        <Button as-child variant="outline">
                            <Link :href="technicalQueueIndex()">
                                <ArrowLeft class="size-4" />
                                Back to technical queue
                            </Link>
                        </Button>
                    </div>
                </template>
            </PageHeader>

            <div class="grid gap-6 lg:grid-cols-[0.95fr_1.05fr]">
                <section class="grid gap-6">
                    <div class="rounded-[1.5rem] border border-border/70 bg-card/85 p-5 shadow-sm">
                        <h2 class="text-base font-semibold">Submission summary</h2>
                        <p class="mt-3 whitespace-pre-wrap text-sm leading-6 text-muted-foreground">{{ submission.summary || 'No summary provided.' }}</p>
                    </div>

                    <div class="rounded-[1.5rem] border border-border/70 bg-card/85 p-5 shadow-sm">
                        <h2 class="text-base font-semibold">Screening output</h2>
                        <div class="mt-4 grid gap-3">
                            <article
                                v-for="(review, index) in submission.screeningReviews ?? []"
                                :key="`${review.reviewerName}-${index}`"
                                class="rounded-xl border border-border/70 bg-background/60 p-4"
                            >
                                <div class="font-medium">{{ review.reviewerName || 'Reviewer' }}</div>
                                <div class="mt-2 text-sm text-muted-foreground">
                                    {{ review.recommendationLabel || review.recommendation || 'No recommendation' }}
                                </div>
                                <div class="mt-2 text-sm text-muted-foreground">{{ review.eligibilityStatus || 'No eligibility status' }}</div>
                                <p class="mt-2 whitespace-pre-wrap text-sm text-muted-foreground">{{ review.notes || 'No notes.' }}</p>
                            </article>
                        </div>
                    </div>

                    <div class="rounded-[1.5rem] border border-border/70 bg-card/85 p-5 shadow-sm">
                        <h2 class="text-base font-semibold">Assign technical reviewer</h2>
                        <form class="mt-4 grid gap-4" @submit.prevent="assignReviewer">
                            <div class="grid gap-2">
                                <Label for="reviewer_id">Reviewer</Label>
                                <Select v-model="assignForm.reviewer_id">
                                    <SelectTrigger id="reviewer_id">
                                        <SelectValue placeholder="Select reviewer" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="option in reviewerOptions" :key="option.value" :value="String(option.value)">
                                            {{ option.label }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError :message="assignForm.errors.reviewer_id" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="due_at">Due date</Label>
                                <Input id="due_at" v-model="assignForm.due_at" type="datetime-local" />
                                <InputError :message="assignForm.errors.due_at" />
                            </div>

                            <div class="flex justify-end">
                                <Button type="submit" :disabled="assignForm.processing">
                                    <Save class="size-4" />
                                    Assign technical reviewer
                                </Button>
                            </div>
                        </form>
                    </div>
                </section>

                <aside class="grid gap-6">
                    <div class="rounded-[1.5rem] border border-border/70 bg-card/85 p-5 shadow-sm">
                        <h2 class="text-base font-semibold">Comparison summary</h2>
                        <dl class="mt-4 grid gap-3 text-sm">
                            <div>
                                <dt class="text-muted-foreground">Technical state</dt>
                                <dd class="font-medium">{{ submission.technicalStateLabel || 'Not derived' }}</dd>
                            </div>
                            <div>
                                <dt class="text-muted-foreground">Assigned experts</dt>
                                <dd class="font-medium">{{ submission.assignedTechnicalReviewersCount ?? 0 }}</dd>
                            </div>
                            <div>
                                <dt class="text-muted-foreground">Submitted technical reviews</dt>
                                <dd class="font-medium">{{ submission.submittedTechnicalReviewsCount ?? 0 }}</dd>
                            </div>
                            <div>
                                <dt class="text-muted-foreground">Average technical score</dt>
                                <dd class="font-medium">{{ submission.averageTechnicalScore ?? 'Not enough scoring yet' }}</dd>
                            </div>
                        </dl>
                    </div>

                    <div class="rounded-[1.5rem] border border-border/70 bg-card/85 p-5 shadow-sm">
                        <h2 class="text-base font-semibold">Technical review outputs</h2>
                        <div class="mt-4 grid gap-4">
                            <article
                                v-for="assignment in submission.technicalAssignments ?? []"
                                :key="assignment.id"
                                class="rounded-xl border border-border/70 bg-background/60 p-4"
                            >
                                <div class="flex flex-wrap items-center gap-2">
                                    <div class="font-medium">{{ assignment.reviewerName || 'Reviewer' }}</div>
                                    <StatusBadge :label="assignment.statusLabel" :tone="assignment.statusTone" />
                                    <StatusBadge v-if="assignment.review?.recommendationLabel" :label="assignment.review.recommendationLabel" tone="review" />
                                </div>

                                <div class="mt-3 grid gap-1 text-sm text-muted-foreground md:grid-cols-2">
                                    <div>Innovation: {{ assignment.review?.innovationScoreOptional ?? 'N/A' }}</div>
                                    <div>Feasibility: {{ assignment.review?.feasibilityScoreOptional ?? 'N/A' }}</div>
                                    <div>Execution: {{ assignment.review?.executionScoreOptional ?? 'N/A' }}</div>
                                    <div>Market: {{ assignment.review?.marketScoreOptional ?? 'N/A' }}</div>
                                </div>

                                <div v-if="assignment.review" class="mt-3 grid gap-2 text-sm text-muted-foreground">
                                    <div><span class="font-medium text-foreground">Strengths:</span> {{ assignment.review.strengths || 'No strengths recorded.' }}</div>
                                    <div><span class="font-medium text-foreground">Weaknesses:</span> {{ assignment.review.weaknesses || 'No weaknesses recorded.' }}</div>
                                    <div><span class="font-medium text-foreground">Risk note:</span> {{ assignment.review.riskNote || 'No risk note recorded.' }}</div>
                                </div>
                            </article>
                        </div>
                    </div>
                </aside>
            </div>
        </PageContainer>
    </AppLayout>
</template>
