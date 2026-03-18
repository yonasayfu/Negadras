<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save } from 'lucide-vue-next';
import InputError from '@/components/InputError.vue';
import FormSection from '@/components/admin/FormSection.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import PageContainer from '@/components/PageContainer.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import { edit as editStage, index as stagesIndex, update as updateStage } from '@/routes/stages';
import type { BreadcrumbItem, ManagedStage, SelectOption } from '@/types';

type Props = {
    stage: ManagedStage;
    seasonOptions: SelectOption[];
    statusOptions: SelectOption[];
    typeOptions: SelectOption[];
};

const props = defineProps<Props>();

const formatDateTimeLocal = (value: string | null): string => (value ? value.slice(0, 16) : '');

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Stages',
        href: stagesIndex(),
    },
    {
        title: props.stage.name,
        href: editStage(props.stage.id),
    },
];

const form = useForm({
    season_id: props.stage.seasonId,
    name: props.stage.name,
    code: props.stage.code,
    type: props.stage.type,
    order_index: props.stage.orderIndex,
    starts_at: formatDateTimeLocal(props.stage.startsAt),
    ends_at: formatDateTimeLocal(props.stage.endsAt),
    status: props.stage.status,
    is_live_stage: props.stage.isLiveStage,
});

const submit = (): void => {
    form
        .transform((data) => ({
            ...data,
            starts_at: data.starts_at || null,
            ends_at: data.ends_at || null,
            is_live_stage: Boolean(data.is_live_stage),
        }))
        .put(updateStage(props.stage.id).url);
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="`Edit ${stage.name}`" />

        <PageContainer>
            <PageHeader
                :title="`Edit ${stage.name}`"
                description="Stage ordering and timing directly affect the operational flow for submissions, reviews, and live sessions."
            >
                <template #actions>
                    <div class="flex items-center gap-2">
                        <StatusBadge :label="stage.statusLabel" :tone="stage.statusTone" />
                        <Badge variant="outline">{{ stage.typeLabel }}</Badge>
                        <Button as-child variant="outline">
                            <Link :href="stagesIndex()">
                                <ArrowLeft class="size-4" />
                                Back to stages
                            </Link>
                        </Button>
                    </div>
                </template>
            </PageHeader>

            <form class="grid gap-6" @submit.prevent="submit">
                <FormSection>
                    <div class="grid gap-5 md:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="season_id">Season</Label>
                            <Select v-model="form.season_id">
                                <SelectTrigger id="season_id">
                                    <SelectValue placeholder="Select season" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="option in seasonOptions" :key="option.value" :value="option.value">
                                        {{ option.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.season_id" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="name">Name</Label>
                            <Input id="name" v-model="form.name" />
                            <InputError :message="form.errors.name" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="code">Code</Label>
                            <Input id="code" v-model="form.code" />
                            <InputError :message="form.errors.code" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="type">Type</Label>
                            <Select v-model="form.type">
                                <SelectTrigger id="type">
                                    <SelectValue placeholder="Select type" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="option in typeOptions" :key="option.value" :value="option.value">
                                        {{ option.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.type" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="order_index">Order</Label>
                            <Input id="order_index" v-model="form.order_index" type="number" min="1" />
                            <InputError :message="form.errors.order_index" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="status">Status</Label>
                            <Select v-model="form.status">
                                <SelectTrigger id="status">
                                    <SelectValue placeholder="Select status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="option in statusOptions" :key="option.value" :value="option.value">
                                        {{ option.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.status" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="starts_at">Starts at</Label>
                            <Input id="starts_at" v-model="form.starts_at" type="datetime-local" />
                            <InputError :message="form.errors.starts_at" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="ends_at">Ends at</Label>
                            <Input id="ends_at" v-model="form.ends_at" type="datetime-local" />
                            <InputError :message="form.errors.ends_at" />
                        </div>

                        <div class="grid gap-3 md:col-span-2">
                            <div class="flex items-center gap-3">
                                <Checkbox id="is_live_stage" :model-value="form.is_live_stage" @update:model-value="form.is_live_stage = Boolean($event)" />
                                <Label for="is_live_stage">This is a live stage</Label>
                            </div>
                            <InputError :message="form.errors.is_live_stage" />
                        </div>
                    </div>
                </FormSection>

                <div class="flex justify-end">
                    <Button type="submit" :disabled="form.processing || !form.isDirty">
                        <Save class="size-4" />
                        Save stage
                    </Button>
                </div>
            </form>
        </PageContainer>
    </AppLayout>
</template>
