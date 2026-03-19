<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, UserPlus } from 'lucide-vue-next';
import FormSection from '@/components/admin/FormSection.vue';
import InputError from '@/components/InputError.vue';
import PageContainer from '@/components/PageContainer.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import { create as createReviewer, index as reviewersIndex, store as storeReviewer } from '@/routes/reviewers';
import type { BreadcrumbItem, SelectOption } from '@/types';

type Props = {
    userOptions: SelectOption[];
};

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Reviewers',
        href: reviewersIndex(),
    },
    {
        title: 'Create',
        href: createReviewer(),
    },
];

const form = useForm({
    user_id: '',
    professional_title: '',
    organization: '',
    specialization: '',
    bio: '',
    is_active: true,
});

const submit = (): void => {
    form.post(storeReviewer().url);
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Create reviewer" />

        <PageContainer>
            <PageHeader
                title="Create reviewer profile"
                description="Attach a reviewer profile to an existing user account and activate reviewer-specific workflow access."
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
                <FormSection title="Identity" description="Reviewer profiles are linked to existing user accounts so the same person can authenticate and receive assignments.">
                    <div class="grid gap-5 md:grid-cols-2">
                        <div class="grid gap-2 md:col-span-2">
                            <Label for="user_id">User account</Label>
                            <Select v-model="form.user_id">
                                <SelectTrigger id="user_id">
                                    <SelectValue placeholder="Select a user" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="option in userOptions" :key="option.value" :value="String(option.value)">
                                        {{ option.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.user_id" />
                        </div>

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
                            <Input id="specialization" v-model="form.specialization" placeholder="Innovation, agriculture, climate, fintech..." />
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

                <FormSection title="Access state" description="Inactive reviewers remain in history but should not receive new assignments.">
                    <div class="flex items-start gap-3 rounded-xl border border-border/70 p-4">
                        <Checkbox id="is_active" v-model:checked="form.is_active" />
                        <div class="space-y-1">
                            <Label for="is_active">Active reviewer</Label>
                            <p class="text-sm text-muted-foreground">The system only offers active reviewers during assignment.</p>
                        </div>
                    </div>
                    <InputError :message="form.errors.is_active" />
                </FormSection>

                <div class="flex justify-end">
                    <Button type="submit" :disabled="form.processing || !form.user_id">
                        <UserPlus class="size-4" />
                        Create reviewer
                    </Button>
                </div>
            </form>
        </PageContainer>
    </AppLayout>
</template>
