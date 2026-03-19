<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Gavel, ShieldAlert } from 'lucide-vue-next';
import PageContainer from '@/components/PageContainer.vue';
import PageHeader from '@/components/PageHeader.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import { store as storeConflict } from '@/routes/judge-workspace/conflicts';
import { store as storeJudgeScores } from '@/routes/judge-workspace/scores';
import { index as judgeWorkspaceIndex, show as showJudgeWorkspace } from '@/routes/judge-workspace';
import type { BreadcrumbItem, SelectOption } from '@/types';

type Props = {
    assignment: {
        id: number;
        panelName: string | null;
        seasonName: string | null;
        stageName: string | null;
        title: string | null;
        applicantName: string | null;
        organizationName: string | null;
        status: string;
        statusLabel: string;
        statusTone: string;
        assignedAt: string | null;
        isLocked: boolean;
        hasActiveConflict: boolean;
        progress: {
            criteriaCount: number;
            scoredCount: number;
            isComplete: boolean;
            total: number;
        };
        rubric: Array<{
            id: number;
            name: string;
            description: string | null;
            maxScore: number;
            weight: number;
            helpText: string | null;
            existingScore: number | null;
            existingComment: string | null;
        }>;
        privateComment: string | null;
        presenterComment: string | null;
        submissionFiles: Array<{ id: number; fileTypeLabel: string; originalName: string; downloadUrl: string; uploadedAt: string | null }>;
        conflicts: Array<{ id: number; typeLabel: string; description: string; statusLabel: string; declaredAt: string | null }>;
        aggregateScore: number | null;
    };
    conflictTypeOptions: SelectOption[];
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Judge workspace', href: judgeWorkspaceIndex() },
    { title: props.assignment.title || 'Assignment', href: showJudgeWorkspace(props.assignment.id) },
];

const scoreForm = useForm({
    intent: 'draft',
    scores: props.assignment.rubric.map((criterion) => ({
        criterion_id: criterion.id,
        score_value: criterion.existingScore ?? '',
        comment: criterion.existingComment ?? '',
    })),
    private_comment: props.assignment.privateComment ?? '',
    presenter_comment: props.assignment.presenterComment ?? '',
});

const conflictForm = useForm({
    conflict_type: props.conflictTypeOptions[0]?.value ?? 'self_declared',
    description: '',
});

const saveDraft = (): void => {
    scoreForm.intent = 'draft';
    scoreForm.post(storeJudgeScores(props.assignment.id).url);
};

const submitFinal = (): void => {
    scoreForm.intent = 'submit';
    scoreForm.post(storeJudgeScores(props.assignment.id).url);
};

const submitConflict = (): void => {
    conflictForm.post(storeConflict(props.assignment.id).url, {
        preserveScroll: true,
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="assignment.title || 'Judge assignment'" />

        <PageContainer>
            <PageHeader :title="assignment.title || 'Judge assignment'" :description="assignment.panelName || 'Judge scoring workspace'">
                <template #actions>
                    <div class="flex items-center gap-2">
                        <StatusBadge :label="assignment.statusLabel" :tone="assignment.statusTone" />
                        <Button variant="outline" as-child>
                            <a href="javascript:history.back()">
                                <ArrowLeft class="size-4" />
                                Back
                            </a>
                        </Button>
                    </div>
                </template>
            </PageHeader>

            <div class="grid gap-6 xl:grid-cols-[1.05fr_0.95fr]">
                <section class="grid gap-6">
                    <div class="rounded-[1.5rem] border border-border/70 bg-card/85 p-5 shadow-sm">
                        <div class="grid gap-2 text-sm text-muted-foreground md:grid-cols-2">
                            <div>Season: {{ assignment.seasonName || 'Unknown' }}</div>
                            <div>Stage: {{ assignment.stageName || 'Unknown' }}</div>
                            <div>Submission owner: {{ assignment.organizationName || assignment.applicantName || 'Unknown' }}</div>
                            <div>Aggregate score: {{ assignment.aggregateScore ?? 'Not enough data yet' }}</div>
                        </div>
                    </div>

                    <form class="grid gap-6" @submit.prevent>
                        <div v-for="(criterion, index) in assignment.rubric" :key="criterion.id" class="rounded-[1.5rem] border border-border/70 bg-card/85 p-5 shadow-sm">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div>
                                    <div class="text-base font-semibold">{{ criterion.name }}</div>
                                    <div class="text-sm text-muted-foreground">{{ criterion.description || 'No criterion description.' }}</div>
                                </div>
                                <div class="text-sm text-muted-foreground">Weight {{ criterion.weight }} / Max {{ criterion.maxScore }}</div>
                            </div>

                            <div class="mt-4 grid gap-4 md:grid-cols-[180px_1fr]">
                                <div class="grid gap-2">
                                    <Label :for="`criterion_score_${criterion.id}`">Score</Label>
                                    <Input :id="`criterion_score_${criterion.id}`" v-model="scoreForm.scores[index].score_value" type="number" min="0" :max="criterion.maxScore" step="0.01" :disabled="assignment.isLocked || assignment.hasActiveConflict" />
                                    <InputError :message="scoreForm.errors[`scores.${index}.score_value`]" />
                                </div>

                                <div class="grid gap-2">
                                    <Label :for="`criterion_comment_${criterion.id}`">Comment</Label>
                                    <textarea :id="`criterion_comment_${criterion.id}`" v-model="scoreForm.scores[index].comment" rows="3" class="flex min-h-24 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs" :disabled="assignment.isLocked || assignment.hasActiveConflict" />
                                    <InputError :message="scoreForm.errors[`scores.${index}.comment`]" />
                                </div>
                            </div>
                        </div>

                        <div class="rounded-[1.5rem] border border-border/70 bg-card/85 p-5 shadow-sm">
                            <div class="grid gap-4">
                                <div class="grid gap-2">
                                    <Label for="private_comment">Private comment</Label>
                                    <textarea id="private_comment" v-model="scoreForm.private_comment" rows="4" class="flex min-h-24 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs" :disabled="assignment.isLocked || assignment.hasActiveConflict" />
                                    <InputError :message="scoreForm.errors.private_comment" />
                                </div>

                                <div class="grid gap-2">
                                    <Label for="presenter_comment">Presenter-visible comment</Label>
                                    <textarea id="presenter_comment" v-model="scoreForm.presenter_comment" rows="4" class="flex min-h-24 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs" :disabled="assignment.isLocked || assignment.hasActiveConflict" />
                                    <InputError :message="scoreForm.errors.presenter_comment" />
                                </div>

                                <div class="flex justify-end gap-2">
                                    <Button type="button" variant="outline" :disabled="scoreForm.processing || assignment.isLocked || assignment.hasActiveConflict" @click="saveDraft">
                                        Save draft
                                    </Button>
                                    <Button type="button" :disabled="scoreForm.processing || assignment.isLocked || assignment.hasActiveConflict" @click="submitFinal">
                                        <Gavel class="size-4" />
                                        Submit final scores
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </form>
                </section>

                <aside class="grid gap-6">
                    <div class="rounded-[1.5rem] border border-border/70 bg-card/85 p-5 shadow-sm">
                        <h2 class="text-base font-semibold">Progress</h2>
                        <div class="mt-3 text-sm text-muted-foreground">
                            {{ assignment.progress.scoredCount }} / {{ assignment.progress.criteriaCount }} criteria scored
                        </div>
                        <div class="mt-2 text-2xl font-semibold">{{ assignment.progress.total }}</div>
                    </div>

                    <div class="rounded-[1.5rem] border border-border/70 bg-card/85 p-5 shadow-sm">
                        <h2 class="text-base font-semibold">Submission files</h2>
                        <div class="mt-4 grid gap-2 text-sm">
                            <a v-for="file in assignment.submissionFiles" :key="file.id" :href="file.downloadUrl" class="rounded-lg border border-border/60 p-3 hover:bg-muted/40">
                                <div class="font-medium">{{ file.fileTypeLabel }}</div>
                                <div class="text-muted-foreground">{{ file.originalName }}</div>
                            </a>
                        </div>
                    </div>

                    <div class="rounded-[1.5rem] border border-border/70 bg-card/85 p-5 shadow-sm">
                        <h2 class="text-base font-semibold">Conflict of interest</h2>
                        <div v-if="assignment.conflicts.length > 0" class="mt-4 grid gap-3">
                            <article v-for="conflict in assignment.conflicts" :key="conflict.id" class="rounded-xl border border-border/70 bg-background/60 p-4 text-sm">
                                <div class="font-medium">{{ conflict.typeLabel }}</div>
                                <div class="mt-1 text-muted-foreground">{{ conflict.description }}</div>
                                <div class="mt-1 text-muted-foreground">{{ conflict.statusLabel }}</div>
                            </article>
                        </div>

                        <form class="mt-4 grid gap-3" @submit.prevent="submitConflict">
                            <div class="grid gap-2">
                                <Label for="conflict_type">Conflict type</Label>
                                <Select v-model="conflictForm.conflict_type">
                                    <SelectTrigger id="conflict_type">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="option in conflictTypeOptions" :key="option.value" :value="String(option.value)">
                                            {{ option.label }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div class="grid gap-2">
                                <Label for="conflict_description">Description</Label>
                                <textarea id="conflict_description" v-model="conflictForm.description" rows="3" class="flex min-h-24 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs" />
                                <InputError :message="conflictForm.errors.description" />
                            </div>
                            <Button type="submit" variant="outline" :disabled="conflictForm.processing">
                                <ShieldAlert class="size-4" />
                                Declare conflict
                            </Button>
                        </form>
                    </div>
                </aside>
            </div>
        </PageContainer>
    </AppLayout>
</template>
