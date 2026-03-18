<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save } from 'lucide-vue-next';
import InputError from '@/components/InputError.vue';
import FormSection from '@/components/admin/FormSection.vue';
import PageContainer from '@/components/PageContainer.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import { create as createSeason, index as seasonsIndex, store as storeSeason } from '@/routes/seasons';
import type { BreadcrumbItem, SelectOption } from '@/types';

type Props = {
    statusOptions: SelectOption[];
};

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Seasons',
        href: seasonsIndex(),
    },
    {
        title: 'Create',
        href: createSeason(),
    },
];

const form = useForm({
    name: '',
    year: new Date().getFullYear(),
    slug: '',
    status: 'draft',
    registration_open_at: '',
    registration_close_at: '',
    description: '',
});

const submit = (): void => {
    form
        .transform((data) => ({
            ...data,
            registration_open_at: data.registration_open_at || null,
            registration_close_at: data.registration_close_at || null,
            description: data.description || null,
        }))
        .post(storeSeason().url);
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Create season" />

        <PageContainer>
            <PageHeader
                title="Create season"
                description="A season becomes the top-level container for the full Negadras competition workflow."
            >
                <template #actions>
                    <Button as-child variant="outline">
                        <Link :href="seasonsIndex()">
                            <ArrowLeft class="size-4" />
                            Back to seasons
                        </Link>
                    </Button>
                </template>
            </PageHeader>

            <form class="grid gap-6" @submit.prevent="submit">
                <FormSection>
                    <div class="grid gap-5 md:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="name">Name</Label>
                            <Input id="name" v-model="form.name" placeholder="Negadras 2026" />
                            <InputError :message="form.errors.name" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="year">Year</Label>
                            <Input id="year" v-model="form.year" type="number" min="2020" max="2100" />
                            <InputError :message="form.errors.year" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="slug">Slug</Label>
                            <Input id="slug" v-model="form.slug" placeholder="negadras-2026" />
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
                                placeholder="Describe the scope, timing, or public framing for this season."
                            />
                            <InputError :message="form.errors.description" />
                        </div>
                    </div>
                </FormSection>

                <div class="flex justify-end">
                    <Button type="submit" :disabled="form.processing">
                        <Save class="size-4" />
                        Create season
                    </Button>
                </div>
            </form>
        </PageContainer>
    </AppLayout>
</template>
