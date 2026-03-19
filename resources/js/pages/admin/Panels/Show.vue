<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Plus } from 'lucide-vue-next';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import PageContainer from '@/components/PageContainer.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import { store as storePanelAssignment } from '@/routes/panel-scoring/assignments';
import { edit as editPanel, index as panelsIndex, show as showPanel } from '@/routes/panels';
import { show as showPanelScoring } from '@/routes/panel-scoring';
import type { BreadcrumbItem, ManagedPanel, SelectOption } from '@/types';

type Props = {
    panel: ManagedPanel;
    availableSubmissionOptions: SelectOption[];
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Panels', href: panelsIndex() },
    { title: props.panel.name, href: showPanel(props.panel.id) },
];

const assignForm = useForm({
    submission_id: '',
});

const submit = (): void => {
    assignForm.post(storePanelAssignment(props.panel.id).url, {
        preserveScroll: true,
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="panel.name" />

        <PageContainer>
            <PageHeader :title="panel.name" :description="panel.description || 'Review panel summary, members, and assigned submissions.'">
                <template #actions>
                    <div class="flex gap-2">
                        <Button as-child variant="outline">
                            <Link :href="panelsIndex()">
                                <ArrowLeft class="size-4" />
                                Back
                            </Link>
                        </Button>
                        <Button as-child variant="outline">
                            <Link :href="editPanel(panel.id)">
                                Edit panel
                            </Link>
                        </Button>
                    </div>
                </template>
            </PageHeader>

            <div class="grid gap-6 xl:grid-cols-[0.95fr_1.05fr]">
                <section class="grid gap-6">
                    <div class="rounded-[1.5rem] border border-border/70 bg-card/85 p-5 shadow-sm">
                        <div class="flex flex-wrap items-center gap-2">
                            <StatusBadge :label="panel.statusLabel" :tone="panel.statusTone" />
                            <span class="text-sm text-muted-foreground">Season: {{ panel.seasonName }}</span>
                            <span class="text-sm text-muted-foreground">Stage: {{ panel.stageName }}</span>
                            <span class="text-sm text-muted-foreground">Rubric: {{ panel.rubricName }}</span>
                        </div>

                        <div class="mt-4 grid gap-3">
                            <div class="font-medium">Rubric criteria</div>
                            <article v-for="criterion in panel.criteria ?? []" :key="criterion.id" class="rounded-xl border border-border/70 bg-background/60 p-4 text-sm">
                                <div class="font-medium">{{ criterion.name }}</div>
                                <div class="mt-1 text-muted-foreground">Weight: {{ criterion.weight }} / Max score: {{ criterion.maxScore }}</div>
                            </article>
                        </div>
                    </div>

                    <div class="rounded-[1.5rem] border border-border/70 bg-card/85 p-5 shadow-sm">
                        <h2 class="text-base font-semibold">Panel members</h2>
                        <div class="mt-4 grid gap-3">
                            <article v-for="member in panel.members ?? []" :key="member.id" class="rounded-xl border border-border/70 bg-background/60 p-4">
                                <div class="flex flex-wrap items-center gap-2">
                                    <div class="font-medium">{{ member.name || 'Unknown judge' }}</div>
                                    <StatusBadge :label="member.roleLabel || 'Member'" tone="review" />
                                </div>
                                <div class="mt-2 text-sm text-muted-foreground">{{ member.email || 'No email' }}</div>
                                <div class="text-sm text-muted-foreground">{{ member.specialization || 'No specialization' }}</div>
                            </article>
                        </div>
                    </div>
                </section>

                <aside class="grid gap-6">
                    <div class="rounded-[1.5rem] border border-border/70 bg-card/85 p-5 shadow-sm">
                        <h2 class="text-base font-semibold">Assign submission</h2>
                        <form class="mt-4 grid gap-4" @submit.prevent="submit">
                            <div class="grid gap-2">
                                <Label for="submission_id">Eligible submission</Label>
                                <Select v-model="assignForm.submission_id">
                                    <SelectTrigger id="submission_id">
                                        <SelectValue placeholder="Select submission" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="option in availableSubmissionOptions" :key="option.value" :value="String(option.value)">
                                            {{ option.label }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <Button type="submit" :disabled="assignForm.processing || !assignForm.submission_id">
                                <Plus class="size-4" />
                                Add to panel
                            </Button>
                        </form>
                    </div>

                    <div class="rounded-[1.5rem] border border-border/70 bg-card/85 p-5 shadow-sm">
                        <h2 class="text-base font-semibold">Assigned submissions</h2>
                        <div class="mt-4 grid gap-3">
                            <article v-for="assignment in panel.assignments ?? []" :key="assignment.id" class="rounded-xl border border-border/70 bg-background/60 p-4">
                                <div class="flex flex-wrap items-center gap-2">
                                    <Link :href="showPanelScoring(assignment.id)" class="font-medium hover:underline">{{ assignment.title || 'Untitled submission' }}</Link>
                                    <StatusBadge :label="assignment.statusLabel" :tone="assignment.statusTone" />
                                </div>
                                <div class="mt-2 text-sm text-muted-foreground">
                                    {{ assignment.organizationName || assignment.applicantName || 'Unknown applicant' }}
                                </div>
                                <div class="mt-2 text-sm text-muted-foreground">
                                    Aggregate score: {{ assignment.aggregateScore ?? 'No scoring yet' }}
                                </div>
                            </article>
                        </div>
                    </div>
                </aside>
            </div>
        </PageContainer>
    </AppLayout>
</template>
