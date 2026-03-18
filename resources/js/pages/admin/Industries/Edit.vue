<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save } from 'lucide-vue-next';
import InputError from '@/components/InputError.vue';
import FormSection from '@/components/admin/FormSection.vue';
import PageContainer from '@/components/PageContainer.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { edit as editIndustry, index as industriesIndex, update as updateIndustry } from '@/routes/industries';
import type { BreadcrumbItem, ManagedIndustry } from '@/types';

type Props = {
    industry: ManagedIndustry;
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Industries',
        href: industriesIndex(),
    },
    {
        title: props.industry.name,
        href: editIndustry(props.industry.id),
    },
];

const form = useForm({
    name: props.industry.name,
    slug: props.industry.slug,
    description: props.industry.description ?? '',
    is_active: props.industry.isActive,
});

const submit = (): void => {
    form
        .transform((data) => ({
            ...data,
            description: data.description || null,
            is_active: Boolean(data.is_active),
        }))
        .put(updateIndustry(props.industry.id).url);
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="`Edit ${industry.name}`" />

        <PageContainer>
            <PageHeader
                :title="`Edit ${industry.name}`"
                description="Use industries consistently because submissions, filters, and analytics will all depend on this list."
            >
                <template #actions>
                    <div class="flex items-center gap-2">
                        <Badge :variant="industry.isActive ? 'secondary' : 'outline'">
                            {{ industry.isActive ? 'Active' : 'Inactive' }}
                        </Badge>
                        <Button as-child variant="outline">
                            <Link :href="industriesIndex()">
                                <ArrowLeft class="size-4" />
                                Back to industries
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
                            <Label for="slug">Slug</Label>
                            <Input id="slug" v-model="form.slug" />
                            <InputError :message="form.errors.slug" />
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

                        <div class="grid gap-3 md:col-span-2">
                            <div class="flex items-center gap-3">
                                <Checkbox id="is_active" :model-value="form.is_active" @update:model-value="form.is_active = Boolean($event)" />
                                <Label for="is_active">Industry is active</Label>
                            </div>
                            <InputError :message="form.errors.is_active" />
                        </div>
                    </div>
                </FormSection>

                <div class="flex justify-end">
                    <Button type="submit" :disabled="form.processing || !form.isDirty">
                        <Save class="size-4" />
                        Save industry
                    </Button>
                </div>
            </form>
        </PageContainer>
    </AppLayout>
</template>
