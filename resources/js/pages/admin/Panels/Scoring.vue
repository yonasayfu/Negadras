<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Eye, Lock, LockOpen } from 'lucide-vue-next';
import PageContainer from '@/components/PageContainer.vue';
import PageHeader from '@/components/PageHeader.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import { update as updateConflict } from '@/routes/panel-scoring/conflicts';
import { update as updatePanelScoreLock } from '@/routes/panel-scoring/lock';
import { show as showPanelScoring } from '@/routes/panel-scoring';
import { store as storePanelScoringVisibility } from '@/routes/panel-scoring/visibility';
import type { BreadcrumbItem, SelectOption } from '@/types';

type Props = {
    assignment: {
        id: number;
        panelName: string | null;
        seasonName: string | null;
        stageName: string | null;
        submissionId: number;
        title: string | null;
        applicantName: string | null;
        organizationName: string | null;
        statusLabel: string;
        statusTone: string;
        assignedAt: string | null;
        isLocked: boolean;
        aggregateScore: number | null;
        criteria: Array<{ id: number; name: string; weight: number; maxScore: number }>;
        judges: Array<{
            judgeId: number;
            name: string | null;
            email: string | null;
            roleLabel: string;
            criteriaCount: number;
            scoredCount: number;
            isComplete: boolean;
            total: number;
            scores: Array<{ criterionName: string | null; scoreValue: number; comment: string | null; submittedAt: string | null }>;
            comments: Array<{ type: string; typeLabel: string; content: string }>;
        }>;
        conflicts: Array<{ id: number; judgeName: string | null; typeLabel: string; description: string; status: string; statusLabel: string; declaredAt: string | null }>;
        locks: Array<{ lockedAt: string | null; lockedBy: string | null; reason: string | null; reopenedAt: string | null; reopenedBy: string | null; reopenReason: string | null }>;
        visibilityEvents: Array<{ action: string; actionLabel: string; note: string | null; changedAt: string | null; changedBy: string | null }>;
    };
    visibilityActionOptions: SelectOption[];
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Panel scoring', href: showPanelScoring(props.assignment.id) },
];

const lockForm = useForm({
    intent: props.assignment.isLocked ? 'unlock' : 'lock',
    reason: '',
});

const visibilityForm = useForm({
    action: props.visibilityActionOptions[0]?.value ?? 'reveal',
    note: '',
});

const conflictForms = Object.fromEntries(
    props.assignment.conflicts.map((conflict) => [
        conflict.id,
        useForm({
            status: conflict.status === 'active' ? 'resolved' : 'active',
            admin_note: '',
        }),
    ]),
);

const submitLock = (): void => {
    lockForm.put(updatePanelScoreLock(props.assignment.id).url, {
        preserveScroll: true,
    });
};

const submitVisibility = (): void => {
    visibilityForm.post(storePanelScoringVisibility(props.assignment.id).url, {
        preserveScroll: true,
    });
};

const submitConflict = (conflictId: number): void => {
    conflictForms[conflictId].put(updateConflict(conflictId).url, {
        preserveScroll: true,
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="assignment.title || 'Panel scoring'" />

        <PageContainer>
            <PageHeader :title="assignment.title || 'Scoring detail'" :description="assignment.panelName || 'Panel scoring workspace'">
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
                        <h2 class="text-base font-semibold">Judge comparison</h2>
                        <div class="mt-4 grid gap-4">
                            <article v-for="judge in assignment.judges" :key="judge.judgeId" class="rounded-xl border border-border/70 bg-background/60 p-4">
                                <div class="flex flex-wrap items-center gap-2">
                                    <div class="font-medium">{{ judge.name || 'Unknown judge' }}</div>
                                    <StatusBadge :label="judge.roleLabel" tone="review" />
                                </div>
                                <div class="mt-2 text-sm text-muted-foreground">
                                    {{ judge.scoredCount }} / {{ judge.criteriaCount }} criteria scored
                                </div>
                                <div class="text-sm text-muted-foreground">Weighted total: {{ judge.total }}</div>
                                <div class="mt-3 grid gap-2">
                                    <div v-for="score in judge.scores" :key="`${judge.judgeId}-${score.criterionName}`" class="rounded-lg border border-border/60 p-3 text-sm">
                                        <div class="font-medium">{{ score.criterionName }}</div>
                                        <div class="text-muted-foreground">Score: {{ score.scoreValue }}</div>
                                        <div class="text-muted-foreground">{{ score.comment || 'No comment' }}</div>
                                    </div>
                                </div>
                            </article>
                        </div>
                    </div>

                    <div class="rounded-[1.5rem] border border-border/70 bg-card/85 p-5 shadow-sm">
                        <h2 class="text-base font-semibold">Conflict declarations</h2>
                        <div class="mt-4 grid gap-3">
                            <article v-for="conflict in assignment.conflicts" :key="conflict.id" class="rounded-xl border border-border/70 bg-background/60 p-4">
                                <div class="font-medium">{{ conflict.judgeName || 'Unknown judge' }}</div>
                                <div class="mt-1 text-sm text-muted-foreground">{{ conflict.typeLabel }}</div>
                                <p class="mt-2 text-sm text-muted-foreground">{{ conflict.description }}</p>
                                <form class="mt-4 grid gap-3" @submit.prevent="submitConflict(conflict.id)">
                                    <div class="grid gap-2">
                                        <Label :for="`conflict_status_${conflict.id}`">Status</Label>
                                        <Select v-model="conflictForms[conflict.id].status">
                                            <SelectTrigger :id="`conflict_status_${conflict.id}`">
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="active">Active</SelectItem>
                                                <SelectItem value="resolved">Resolved</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div class="grid gap-2">
                                        <Label :for="`conflict_note_${conflict.id}`">Admin note</Label>
                                        <textarea :id="`conflict_note_${conflict.id}`" v-model="conflictForms[conflict.id].admin_note" rows="2" class="flex min-h-20 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs" />
                                    </div>
                                    <Button type="submit" size="sm" :disabled="conflictForms[conflict.id].processing">Update conflict</Button>
                                </form>
                            </article>
                        </div>
                    </div>
                </section>

                <aside class="grid gap-6">
                    <div class="rounded-[1.5rem] border border-border/70 bg-card/85 p-5 shadow-sm">
                        <h2 class="text-base font-semibold">Aggregate</h2>
                        <div class="mt-3 text-2xl font-semibold">{{ assignment.aggregateScore ?? 'No score yet' }}</div>
                    </div>

                    <div class="rounded-[1.5rem] border border-border/70 bg-card/85 p-5 shadow-sm">
                        <h2 class="text-base font-semibold">Lock scoring</h2>
                        <form class="mt-4 grid gap-3" @submit.prevent="submitLock">
                            <div class="grid gap-2">
                                <Label for="lock_intent">Action</Label>
                                <Select v-model="lockForm.intent">
                                    <SelectTrigger id="lock_intent">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="lock">Lock</SelectItem>
                                        <SelectItem value="unlock">Unlock</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div class="grid gap-2">
                                <Label for="lock_reason">Reason</Label>
                                <Input id="lock_reason" v-model="lockForm.reason" />
                            </div>
                            <Button type="submit" :disabled="lockForm.processing">
                                <component :is="lockForm.intent === 'lock' ? Lock : LockOpen" class="size-4" />
                                Apply
                            </Button>
                        </form>
                    </div>

                    <div class="rounded-[1.5rem] border border-border/70 bg-card/85 p-5 shadow-sm">
                        <h2 class="text-base font-semibold">Visibility events</h2>
                        <form class="mt-4 grid gap-3" @submit.prevent="submitVisibility">
                            <div class="grid gap-2">
                                <Label for="visibility_action">Action</Label>
                                <Select v-model="visibilityForm.action">
                                    <SelectTrigger id="visibility_action">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="option in visibilityActionOptions" :key="option.value" :value="String(option.value)">
                                            {{ option.label }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div class="grid gap-2">
                                <Label for="visibility_note">Note</Label>
                                <Input id="visibility_note" v-model="visibilityForm.note" />
                            </div>
                            <Button type="submit" :disabled="visibilityForm.processing">
                                <Eye class="size-4" />
                                Record event
                            </Button>
                        </form>

                        <div class="mt-4 grid gap-2 text-sm text-muted-foreground">
                            <div v-for="(event, index) in assignment.visibilityEvents" :key="`${event.action}-${index}`" class="rounded-lg border border-border/60 p-3">
                                <div class="font-medium text-foreground">{{ event.actionLabel }}</div>
                                <div>{{ event.note || 'No note provided' }}</div>
                            </div>
                        </div>
                    </div>
                </aside>
            </div>
        </PageContainer>
    </AppLayout>
</template>
