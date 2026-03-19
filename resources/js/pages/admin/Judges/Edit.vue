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
import { edit as editJudge, index as judgesIndex, update as updateJudge } from '@/routes/judges';
import type { BreadcrumbItem, ManagedJudge } from '@/types';

type Props = {
    judge: ManagedJudge;
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Judges', href: judgesIndex() },
    { title: props.judge.name || 'Edit', href: editJudge(props.judge.id) },
];

const form = useForm({
    professional_title: props.judge.professionalTitle ?? '',
    organization: props.judge.organization ?? '',
    specialization: props.judge.specialization ?? '',
    bio: props.judge.bio ?? '',
    is_active: props.judge.isActive,
});

const submit = (): void => {
    form.put(updateJudge(props.judge.id).url);
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="judge.name ? `Edit ${judge.name}` : 'Edit judge'" />

        <PageContainer>
            <PageHeader :title="judge.name || 'Edit judge'" :description="judge.email || 'Update specialization, organization, and activation state.'">
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
                <FormSection title="Judge profile" description="Update the expertise context used when building panels.">
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
                            <textarea id="bio" v-model="form.bio" rows="5" class="flex min-h-32 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs" />
                            <InputError :message="form.errors.bio" />
                        </div>
                    </div>
                </FormSection>

                <FormSection title="Activation" description="Inactive judges remain visible historically but should not be assigned to new panels.">
                    <div class="flex items-start gap-3 rounded-xl border border-border/70 p-4">
                        <Checkbox id="is_active" v-model:checked="form.is_active" />
                        <div class="space-y-1">
                            <Label for="is_active">Active judge</Label>
                            <p class="text-sm text-muted-foreground">Current panel memberships: {{ judge.panelMembershipsCount }}</p>
                        </div>
                    </div>
                    <InputError :message="form.errors.is_active" />
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
