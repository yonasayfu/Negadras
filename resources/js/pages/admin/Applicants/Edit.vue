<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import SocialLinksFields from '@/components/applicants/SocialLinksFields.vue';
import FormSection from '@/components/admin/FormSection.vue';
import InputError from '@/components/InputError.vue';
import PageContainer from '@/components/PageContainer.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import { edit as editApplicant, index as applicantsIndex, update as updateApplicant } from '@/routes/applicants';
import type { BreadcrumbItem, ManagedApplicant, ManagedApplicantSocialLink, SelectOption } from '@/types';

type Props = {
    applicant: ManagedApplicant;
    typeOptions: SelectOption[];
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Applicants',
        href: applicantsIndex(),
    },
    {
        title: props.applicant.fullName,
        href: editApplicant(props.applicant.id),
    },
];

const form = useForm({
    applicant_type: props.applicant.applicantType,
    full_name: props.applicant.fullName,
    email: props.applicant.email,
    phone: props.applicant.phone ?? '',
    bio: props.applicant.bio ?? '',
    national_id_or_registration_ref: props.applicant.nationalIdOrRegistrationRef ?? '',
    social_links: props.applicant.socialLinks ?? [],
});

const addLink = (): void => {
    form.social_links.push({
        platform: '',
        url: '',
        isVerified: false,
    });
};

const removeLink = (index: number): void => {
    form.social_links.splice(index, 1);
};

const updateLink = (index: number, key: keyof ManagedApplicantSocialLink, value: string | boolean): void => {
    form.social_links[index] = {
        ...form.social_links[index],
        [key]: value,
    };
};

const submit = (): void => {
    form.transform((data) => ({
        ...data,
        social_links: data.social_links.map((socialLink) => ({
            platform: socialLink.platform,
            url: socialLink.url,
            is_verified: Boolean(socialLink.isVerified),
        })),
    })).put(updateApplicant(props.applicant.id).url);
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="`Edit ${applicant.fullName}`" />

        <PageContainer>
            <PageHeader
                :title="`Edit ${applicant.fullName}`"
                description="Admin edits should correct competition-facing applicant data without overwriting the linked auth user blindly."
            >
                <template #actions>
                    <div class="flex items-center gap-2">
                        <Badge variant="outline">{{ applicant.linkedUserEmail }}</Badge>
                        <Button as-child variant="outline">
                            <Link :href="applicantsIndex()">
                                <ArrowLeft class="size-4" />
                                Back to applicants
                            </Link>
                        </Button>
                    </div>
                </template>
            </PageHeader>

            <form class="grid gap-6" @submit.prevent="submit">
                <FormSection>
                    <div class="grid gap-5 md:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="applicant_type">Profile type</Label>
                            <Select v-model="form.applicant_type">
                                <SelectTrigger id="applicant_type">
                                    <SelectValue placeholder="Select profile type" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="option in typeOptions" :key="option.value" :value="option.value">
                                        {{ option.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.applicant_type" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="full_name">Full name</Label>
                            <Input id="full_name" v-model="form.full_name" />
                            <InputError :message="form.errors.full_name" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="email">Contact email</Label>
                            <Input id="email" v-model="form.email" type="email" />
                            <InputError :message="form.errors.email" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="phone">Phone</Label>
                            <Input id="phone" v-model="form.phone" />
                            <InputError :message="form.errors.phone" />
                        </div>

                        <div class="grid gap-2 md:col-span-2">
                            <Label for="national_id_or_registration_ref">National ID or registration reference</Label>
                            <Input id="national_id_or_registration_ref" v-model="form.national_id_or_registration_ref" />
                            <InputError :message="form.errors.national_id_or_registration_ref" />
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

                <FormSection
                    title="Social links"
                    description="Admin can also mark verified links once intake checks or review workflows confirm them."
                >
                    <SocialLinksFields
                        :links="form.social_links"
                        :errors="form.errors"
                        show-verification
                        @add="addLink"
                        @remove="removeLink"
                        @update="updateLink"
                    />
                </FormSection>

                <div class="flex justify-end">
                    <Button type="submit" :disabled="form.processing || !form.isDirty">
                        Save applicant
                    </Button>
                </div>
            </form>
        </PageContainer>
    </AppLayout>
</template>
