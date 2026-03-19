<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save } from 'lucide-vue-next';
import { computed } from 'vue';
import FormSection from '@/components/admin/FormSection.vue';
import InputError from '@/components/InputError.vue';
import PanelMembersEditor from '@/components/judging/PanelMembersEditor.vue';
import PageContainer from '@/components/PageContainer.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import { edit as editPanel, index as panelsIndex, update as updatePanel } from '@/routes/panels';
import type { BreadcrumbItem, ManagedPanel, SelectOption } from '@/types';

type OptionWithSeason = SelectOption & { seasonId?: string };

type Props = {
    panel: ManagedPanel;
    seasonOptions: SelectOption[];
    stageOptions: OptionWithSeason[];
    rubricOptions: SelectOption[];
    judgeOptions: SelectOption[];
    statusOptions: SelectOption[];
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Panels', href: panelsIndex() },
    { title: props.panel.name, href: editPanel(props.panel.id) },
];

const form = useForm({
    season_id: props.panel.seasonId ?? '',
    stage_id: props.panel.stageId ?? '',
    rubric_id: props.panel.rubricId ?? '',
    name: props.panel.name,
    description: props.panel.description ?? '',
    status: props.panel.status,
    members: (props.panel.members ?? []).map((member) => ({
        id: member.id,
        judge_id: String(member.judgeId),
        role_in_panel: member.roleInPanel ?? 'member',
        display_order: member.displayOrder,
    })),
});

const availableStages = computed(() =>
    props.stageOptions.filter((stage) => String(stage.seasonId) === form.season_id),
);

const roleOptions: SelectOption[] = [
    { value: 'chair', label: 'Chair' },
    { value: 'member', label: 'Member' },
];

const addMember = (): void => {
    form.members.push({
        id: undefined,
        judge_id: '',
        role_in_panel: 'member',
        display_order: form.members.length + 1,
    });
};

const removeMember = (index: number): void => {
    if (form.members.length === 1) {
        return;
    }

    form.members.splice(index, 1);
};

const submit = (): void => {
    form.put(updatePanel(props.panel.id).url);
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="`Edit ${panel.name}`" />

        <PageContainer>
            <PageHeader :title="panel.name" description="Adjust membership, rubric, and panel state before or during scoring.">
                <template #actions>
                    <Button as-child variant="outline">
                        <Link :href="panelsIndex()">
                            <ArrowLeft class="size-4" />
                            Back
                        </Link>
                    </Button>
                </template>
            </PageHeader>

            <form class="grid gap-6" @submit.prevent="submit">
                <FormSection title="Panel setup" description="Use draft while assembling the panel, then activate it once ready.">
                    <div class="grid gap-5 md:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="season_id">Season</Label>
                            <Select v-model="form.season_id">
                                <SelectTrigger id="season_id"><SelectValue placeholder="Select season" /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="option in seasonOptions" :key="option.value" :value="String(option.value)">{{ option.label }}</SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.season_id" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="stage_id">Stage</Label>
                            <Select v-model="form.stage_id">
                                <SelectTrigger id="stage_id"><SelectValue placeholder="Select stage" /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="option in availableStages" :key="option.value" :value="String(option.value)">{{ option.label }}</SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.stage_id" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="rubric_id">Rubric</Label>
                            <Select v-model="form.rubric_id">
                                <SelectTrigger id="rubric_id"><SelectValue placeholder="Select rubric" /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="option in rubricOptions" :key="option.value" :value="String(option.value)">{{ option.label }}</SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.rubric_id" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="status">Status</Label>
                            <Select v-model="form.status">
                                <SelectTrigger id="status"><SelectValue placeholder="Select status" /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="option in statusOptions" :key="option.value" :value="String(option.value)">{{ option.label }}</SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.status" />
                        </div>

                        <div class="grid gap-2 md:col-span-2">
                            <Label for="name">Name</Label>
                            <Input id="name" v-model="form.name" />
                            <InputError :message="form.errors.name" />
                        </div>

                        <div class="grid gap-2 md:col-span-2">
                            <Label for="description">Description</Label>
                            <textarea id="description" v-model="form.description" rows="4" class="flex min-h-24 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs" />
                            <InputError :message="form.errors.description" />
                        </div>
                    </div>
                </FormSection>

                <FormSection title="Panel members" description="Keep at least one chair assigned while scoring is active.">
                    <InputError :message="form.errors.members" />
                    <PanelMembersEditor :members="form.members" :judge-options="judgeOptions" :role-options="roleOptions" :errors="form.errors" @add="addMember" @remove="removeMember" />
                </FormSection>

                <div class="flex justify-end">
                    <Button type="submit" :disabled="form.processing">
                        <Save class="size-4" />
                        Save changes
                    </Button>
                </div>
            </form>
        </PageContainer>
    </AppLayout>
</template>
