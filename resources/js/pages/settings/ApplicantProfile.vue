<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import FormSection from '@/components/admin/FormSection.vue';
import SocialLinksFields from '@/components/applicants/SocialLinksFields.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { edit as editApplicantProfile, update as updateApplicantProfile } from '@/routes/applicant-profile';
import type { BreadcrumbItem, ManagedApplicantSocialLink, SelectOption } from '@/types';

type Props = {
    applicant: {
        applicantType: string;
        fullName: string;
        email: string;
        phone: string | null;
        bio: string | null;
        nationalIdOrRegistrationRef: string | null;
        socialLinks: ManagedApplicantSocialLink[];
    };
    typeOptions: SelectOption[];
};

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Presenter profile',
        href: editApplicantProfile(),
    },
];

const form = useForm({
    applicant_type: props.applicant.applicantType,
    full_name: props.applicant.fullName,
    email: props.applicant.email,
    phone: props.applicant.phone ?? '',
    bio: props.applicant.bio ?? '',
    national_id_or_registration_ref: props.applicant.nationalIdOrRegistrationRef ?? '',
    social_links: props.applicant.socialLinks.length > 0 ? [...props.applicant.socialLinks] : [],
});

const addLink = (): void => {
    form.social_links.push({
        platform: '',
        url: '',
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
    form.put(updateApplicantProfile().url);
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Presenter profile" />

        <SettingsLayout>
            <div class="flex flex-col space-y-6">
                <Heading
                    variant="small"
                    title="Presenter profile"
                    description="Maintain the competition-facing profile that Negadras will use for submissions, contact details, and later assignment workflows."
                />

                <form class="space-y-6" @submit.prevent="submit">
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
                                    placeholder="Describe the presenter, team, or organization context for the competition."
                                />
                                <InputError :message="form.errors.bio" />
                            </div>
                        </div>
                    </FormSection>

                    <FormSection
                        title="Social links"
                        description="Keep public references current so reviewers and organizers can verify the presenter context later."
                    >
                        <SocialLinksFields
                            :links="form.social_links"
                            :errors="form.errors"
                            @add="addLink"
                            @remove="removeLink"
                            @update="updateLink"
                        />
                    </FormSection>

                    <div class="flex justify-end">
                        <Button type="submit" :disabled="form.processing || !form.isDirty">
                            Save presenter profile
                        </Button>
                    </div>
                </form>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
