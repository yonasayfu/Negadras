<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save } from 'lucide-vue-next';
import { computed } from 'vue';
import FormSection from '@/components/admin/FormSection.vue';
import InputError from '@/components/InputError.vue';
import RubricCriteriaEditor from '@/components/judging/RubricCriteriaEditor.vue';
import PageContainer from '@/components/PageContainer.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { edit as editRubric, index as rubricsIndex, update as updateRubric } from '@/routes/rubrics';
import type { BreadcrumbItem, ManagedRubric, SelectOption } from '@/types';

type Props = {
    rubric: ManagedRubric;
    stageOptions: SelectOption[];
    industryOptions: SelectOption[];
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Rubrics', href: rubricsIndex() },
    { title: props.rubric.name, href: editRubric(props.rubric.id) },
];

const form = useForm({
    name: props.rubric.name,
    description: props.rubric.description ?? '',
    is_active: props.rubric.isActive,
    stage_ids: props.rubric.stageIds ?? [],
    industry_ids: props.rubric.industryIds ?? [],
    criteria: (props.rubric.criteria ?? []).map((criterion) => ({
        id: criterion.id,
        name: criterion.name,
        description: criterion.description ?? '',
        max_score: criterion.maxScore,
        weight: criterion.weight,
        order_index: criterion.orderIndex,
        is_required: criterion.isRequired,
        visibility_rule: criterion.visibilityRule ?? '',
        help_text: criterion.helpText ?? '',
    })),
});

const totalWeight = computed(() =>
    form.criteria.reduce((carry, criterion) => carry + Number(criterion.weight || 0), 0),
);

const addCriterion = (): void => {
    form.criteria.push({
        id: undefined,
        name: '',
        description: '',
        max_score: 10,
        weight: 0,
        order_index: form.criteria.length + 1,
        is_required: true,
        visibility_rule: '',
        help_text: '',
    });
};

const removeCriterion = (index: number): void => {
    if (form.criteria.length === 1) {
        return;
    }

    form.criteria.splice(index, 1);
};

const submit = (): void => {
    form.put(updateRubric(props.rubric.id).url);
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="`Edit ${rubric.name}`" />

        <PageContainer>
            <PageHeader :title="rubric.name" description="Update scoring criteria, weight distribution, and bindings.">
                <template #actions>
                    <Button as-child variant="outline">
                        <Link :href="rubricsIndex()">
                            <ArrowLeft class="size-4" />
                            Back
                        </Link>
                    </Button>
                </template>
            </PageHeader>

            <form class="grid gap-6" @submit.prevent="submit">
                <FormSection title="Rubric settings" description="Bindings narrow where the rubric should be used in the competition workflow.">
                    <div class="grid gap-5 md:grid-cols-2">
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

                        <div class="grid gap-2">
                            <Label>Stage bindings</Label>
                            <div class="grid gap-2 rounded-xl border border-border/70 p-4">
                                <label v-for="option in stageOptions" :key="option.value" class="flex items-center gap-3 text-sm">
                                    <input v-model="form.stage_ids" type="checkbox" :value="String(option.value)" class="size-4 rounded border-border" />
                                    <span>{{ option.label }}</span>
                                </label>
                            </div>
                        </div>

                        <div class="grid gap-2">
                            <Label>Industry bindings</Label>
                            <div class="grid gap-2 rounded-xl border border-border/70 p-4">
                                <label v-for="option in industryOptions" :key="option.value" class="flex items-center gap-3 text-sm">
                                    <input v-model="form.industry_ids" type="checkbox" :value="String(option.value)" class="size-4 rounded border-border" />
                                    <span>{{ option.label }}</span>
                                </label>
                            </div>
                        </div>

                        <div class="md:col-span-2">
                            <label class="flex items-start gap-3 rounded-xl border border-border/70 p-4">
                                <Checkbox v-model:checked="form.is_active" />
                                <div>
                                    <div class="text-sm font-medium">Active rubric</div>
                                    <p class="text-sm text-muted-foreground">Rubrics can be retired without removing historical scoring data.</p>
                                </div>
                            </label>
                        </div>
                    </div>
                </FormSection>

                <FormSection :title="`Criteria (${totalWeight} total weight)`" description="Weights should reflect how much each scoring dimension contributes to the final result.">
                    <InputError :message="form.errors.criteria" />
                    <RubricCriteriaEditor :criteria="form.criteria" :errors="form.errors" @add="addCriterion" @remove="removeCriterion" />
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
