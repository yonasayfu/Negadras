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
import { create as createJudge, index as judgesIndex, store as storeJudge } from '@/routes/judges';
import type { BreadcrumbItem, SelectOption } from '@/types';

type Props = {
    userOptions: SelectOption[];
};

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Judges', href: judgesIndex() },
    { title: 'Create', href: createJudge() },
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
    form.post(storeJudge().url);
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Create judge" />

        <PageContainer>
            <PageHeader title="Create judge profile" description="Attach a judge profile to an existing user account and activate judging access.">
                <template #actions>
                    <Button as-child variant="outline">
                        <Link :href="judgesIndex()">
                            <ArrowLeft class="size-4" />
                            Back
                        </Link>
                    </Button>
                </template>
            </PageHeader>

            <form class="grid gap-6" @submit.prevent="submit">
                <FormSection title="Identity" description="Judge profiles are tied to authenticated user accounts.">
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
                            <Input id="specialization" v-model="form.specialization" placeholder="Strategy, product, finance, legal, operations..." />
                            <InputError :message="form.errors.specialization" />
                        </div>

                        <div class="grid gap-2 md:col-span-2">
                            <Label for="bio">Bio</Label>
                            <textarea id="bio" v-model="form.bio" rows="5" class="flex min-h-32 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs" />
                            <InputError :message="form.errors.bio" />
                        </div>
                    </div>
                </FormSection>

                <FormSection title="Access state" description="Inactive judges remain on record but should not receive panel work.">
                    <div class="flex items-start gap-3 rounded-xl border border-border/70 p-4">
                        <Checkbox id="is_active" v-model:checked="form.is_active" />
                        <div class="space-y-1">
                            <Label for="is_active">Active judge</Label>
                            <p class="text-sm text-muted-foreground">Only active judges appear in panel member selection.</p>
                        </div>
                    </div>
                    <InputError :message="form.errors.is_active" />
                </FormSection>

                <div class="flex justify-end">
                    <Button type="submit" :disabled="form.processing || !form.user_id">
                        <UserPlus class="size-4" />
                        Create judge
                    </Button>
                </div>
            </form>
        </PageContainer>
    </AppLayout>
</template>
