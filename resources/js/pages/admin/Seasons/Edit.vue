<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save } from 'lucide-vue-next';
import InputError from '@/components/InputError.vue';
import FormSection from '@/components/admin/FormSection.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import PageContainer from '@/components/PageContainer.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import { edit as editSeason, index as seasonsIndex, update as updateSeason } from '@/routes/seasons';
import type { BreadcrumbItem, ManagedSeason, SelectOption } from '@/types';

type Props = {
    season: ManagedSeason;
    statusOptions: SelectOption[];
};

const props = defineProps<Props>();

const formatDateTimeLocal = (value: string | null): string => (value ? value.slice(0, 16) : '');

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Seasons',
        href: seasonsIndex(),
    },
    {
        title: props.season.name,
        href: editSeason(props.season.id),
    },
];

const form = useForm({
    name: props.season.name,
    year: props.season.year,
    slug: props.season.slug,
    status: props.season.status,
    registration_open_at: formatDateTimeLocal(props.season.registrationOpenAt),
    registration_close_at: formatDateTimeLocal(props.season.registrationCloseAt),
    description: props.season.description ?? '',
});

const submit = (): void => {
    form
        .transform((data) => ({
            ...data,
            registration_open_at: data.registration_open_at || null,
            registration_close_at: data.registration_close_at || null,
            description: data.description || null,
        }))
        .put(updateSeason(props.season.id).url);
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="`Edit ${season.name}`" />

        <PageContainer>
            <PageHeader
                :title="`Edit ${season.name}`"
                description="Keep season timing and status accurate because submissions, stages, and reporting will branch from this record."
            >
                <template #actions>
                    <div class="flex items-center gap-2">
                        <StatusBadge :label="season.statusLabel" :tone="season.statusTone" />
                        <Badge variant="outline">{{ season.stagesCount }} stages</Badge>
                        <Button as-child variant="outline">
                            <Link :href="seasonsIndex()">
                                <ArrowLeft class="size-4" />
                                Back to seasons
                            </Link>
                        </Button>
                    </div>
                </template>
            </PageHeader>

            <form class="grid gap-6" @submit.prevent="submit">
                <FormSection>
                    <div class="grid gap-5 md:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="name">Name</Label>
                            <Input id="name" v-model="form.name" />
                            <InputError :message="form.errors.name" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="year">Year</Label>
                            <Input id="year" v-model="form.year" type="number" min="2020" max="2100" />
                            <InputError :message="form.errors.year" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="slug">Slug</Label>
                            <Input id="slug" v-model="form.slug" />
                            <InputError :message="form.errors.slug" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="status">Status</Label>
                            <Select v-model="form.status">
                                <SelectTrigger id="status">
                                    <SelectValue placeholder="Select season status" />
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
                            <Label for="registration_open_at">Registration opens</Label>
                            <Input id="registration_open_at" v-model="form.registration_open_at" type="datetime-local" />
                            <InputError :message="form.errors.registration_open_at" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="registration_close_at">Registration closes</Label>
                            <Input id="registration_close_at" v-model="form.registration_close_at" type="datetime-local" />
                            <InputError :message="form.errors.registration_close_at" />
                        </div>

                        <div class="grid gap-2 md:col-span-2">
                            <Label for="description">Description</Label>
                            <textarea
                                id="description"
                                v-model="form.description"
                                rows="5"
                                class="flex min-h-32 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none ring-offset-background placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
                            />
                            <InputError :message="form.errors.description" />
                        </div>
                    </div>
                </FormSection>

                <div class="flex justify-end">
                    <Button type="submit" :disabled="form.processing || !form.isDirty">
                        <Save class="size-4" />
                        Save season
                    </Button>
                </div>
            </form>
        </PageContainer>
    </AppLayout>
</template>
