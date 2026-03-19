<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save } from 'lucide-vue-next';
import FormSection from '@/components/admin/FormSection.vue';
import InputError from '@/components/InputError.vue';
import PageContainer from '@/components/PageContainer.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { edit as editReviewer, index as reviewersIndex, update as updateReviewer } from '@/routes/reviewers';
import type { BreadcrumbItem, ManagedReviewer } from '@/types';

type Props = {
    reviewer: ManagedReviewer;
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Reviewers',
        href: reviewersIndex(),
    },
    {
        title: props.reviewer.name || 'Reviewer',
        href: editReviewer(props.reviewer.id),
    },
];

const form = useForm({
    professional_title: props.reviewer.professionalTitle ?? '',
    organization: props.reviewer.organization ?? '',
    specialization: props.reviewer.specialization ?? '',
    bio: props.reviewer.bio ?? '',
    is_active: props.reviewer.isActive,
});

const submit = (): void => {
    form.put(updateReviewer(props.reviewer.id).url);
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="`Edit ${reviewer.name ?? 'Reviewer'}`" />

        <PageContainer>
            <PageHeader
                :title="reviewer.name || 'Reviewer profile'"
                description="Refine reviewer specialization and activation so assignments match the real screening workload."
            >
                <template #actions>
                    <Button as-child variant="outline">
                        <Link :href="reviewersIndex()">
                            <ArrowLeft class="size-4" />
                            Back
                        </Link>
                    </Button>
                </template>
            </PageHeader>

            <form class="grid gap-6" @submit.prevent="submit">
                <FormSection title="Linked account" description="Reviewer profiles remain attached to real user accounts and inherit reviewer role access from that identity.">
                    <div class="grid gap-2 text-sm text-muted-foreground">
                        <div>Name: {{ reviewer.name || 'Unknown reviewer' }}</div>
                        <div>Email: {{ reviewer.email || 'No email' }}</div>
                        <div>Active assignments: {{ reviewer.activeAssignmentsCount }}</div>
                    </div>
                </FormSection>

                <FormSection title="Reviewer profile" description="Keep the reviewer context specific enough to support later specialization-based assignments.">
                    <div class="grid gap-5 md:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="professional_title">Professional title</Label>
                            <Input id="professional_title" v-model="form.professional_title" />
                            <InputError :message="form.errors.professional_title" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="organization">Organization</Label>
                            <Input id="organization" v-model="form.organization" />
                            <InputError :message="form.errors.organization" />
                        </div>

                        <div class="grid gap-2 md:col-span-2">
                            <Label for="specialization">Specialization</Label>
                            <Input id="specialization" v-model="form.specialization" />
                            <InputError :message="form.errors.specialization" />
                        </div>

                        <div class="grid gap-2 md:col-span-2">
                            <Label for="bio">Bio</Label>
                            <textarea
                                id="bio"
                                v-model="form.bio"
                                rows="5"
                                class="flex min-h-32 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none ring-offset-background placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
                            />
                            <InputError :message="form.errors.bio" />
                        </div>
                    </div>
                </FormSection>

                <FormSection title="Activation" description="Inactive reviewers remain visible in records but stop appearing in assignment options.">
                    <div class="flex items-start gap-3 rounded-xl border border-border/70 p-4">
                        <Checkbox id="is_active" v-model:checked="form.is_active" />
                        <div class="space-y-1">
                            <Label for="is_active">Active reviewer</Label>
                            <p class="text-sm text-muted-foreground">Turn this off if the reviewer should stop receiving new screening assignments.</p>
                        </div>
                    </div>
                    <InputError :message="form.errors.is_active" />
                </FormSection>

                <div class="flex justify-end">
                    <Button type="submit" :disabled="form.processing">
                        <Save class="size-4" />
                        Save reviewer
                    </Button>
                </div>
            </form>
        </PageContainer>
    </AppLayout>
</template>
