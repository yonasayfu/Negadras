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
import { create as createPanel, index as panelsIndex, store as storePanel } from '@/routes/panels';
import type { BreadcrumbItem, SelectOption } from '@/types';

type OptionWithSeason = SelectOption & { seasonId?: string };

type Props = {
    seasonOptions: SelectOption[];
    stageOptions: OptionWithSeason[];
    rubricOptions: SelectOption[];
    judgeOptions: SelectOption[];
    statusOptions: SelectOption[];
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Panels', href: panelsIndex() },
    { title: 'Create', href: createPanel() },
];

const form = useForm({
    season_id: '',
    stage_id: '',
    rubric_id: '',
    name: '',
    description: '',
    status: 'draft',
    members: [
        {
            judge_id: '',
            role_in_panel: 'chair',
            display_order: 1,
        },
    ],
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
    form.post(storePanel().url);
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Create panel" />

        <PageContainer>
            <PageHeader title="Create panel" description="Build a stage-specific judging panel and attach the correct rubric and members.">
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
                <FormSection title="Panel setup" description="Panel stage and season must stay aligned with the submissions it will judge.">
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
                            <Input id="name" v-model="form.name" placeholder="Agritech Technical Panel A" />
                            <InputError :message="form.errors.name" />
                        </div>

                        <div class="grid gap-2 md:col-span-2">
                            <Label for="description">Description</Label>
                            <textarea id="description" v-model="form.description" rows="4" class="flex min-h-24 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs" />
                            <InputError :message="form.errors.description" />
                        </div>
                    </div>
                </FormSection>

                <FormSection title="Panel members" description="Every panel must include exactly one chair.">
                    <InputError :message="form.errors.members" />
                    <PanelMembersEditor :members="form.members" :judge-options="judgeOptions" :role-options="roleOptions" :errors="form.errors" @add="addMember" @remove="removeMember" />
                </FormSection>

                <div class="flex justify-end">
                    <Button type="submit" :disabled="form.processing">
                        <Save class="size-4" />
                        Create panel
                    </Button>
                </div>
            </form>
        </PageContainer>
    </AppLayout>
</template>
