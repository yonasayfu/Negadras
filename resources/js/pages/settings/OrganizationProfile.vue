<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import TeamMembersFields from '@/components/organizations/TeamMembersFields.vue';
import FormSection from '@/components/admin/FormSection.vue';
import MediaUploadField from '@/components/admin/MediaUploadField.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { edit as editOrganizationProfile, update as updateOrganizationProfile } from '@/routes/organization-profile';
import { useForm } from '@inertiajs/vue3';
import type { BreadcrumbItem, ManagedOrganization, ManagedTeamMember, SelectOption } from '@/types';

type Props = {
    organization: ManagedOrganization;
    industryOptions: SelectOption[];
};

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Organization profile',
        href: editOrganizationProfile(),
    },
];

const form = useForm({
    legal_name: props.organization.legalName ?? '',
    display_name: props.organization.displayName ?? '',
    registration_number: props.organization.registrationNumber ?? '',
    industry_id: props.organization.industryId ? String(props.organization.industryId) : '',
    website: props.organization.website ?? '',
    description: props.organization.description ?? '',
    contact_email: props.organization.contactEmail ?? '',
    contact_phone: props.organization.contactPhone ?? '',
    address: props.organization.address ?? '',
    logo: null as File | null,
    team_members: props.organization.teamMembers,
});

const addMember = (): void => {
    form.team_members.push({
        id: null,
        applicantId: null,
        fullName: '',
        roleTitle: '',
        email: '',
        phone: '',
        bio: '',
        isPrimaryContact: false,
        linkedApplicantName: null,
    });
};

const removeMember = (index: number): void => {
    form.team_members.splice(index, 1);
};

const updateMember = (index: number, key: keyof ManagedTeamMember, value: string | boolean | number | null): void => {
    if (key === 'isPrimaryContact' && value === true) {
        form.team_members = form.team_members.map((member, memberIndex) => ({
            ...member,
            isPrimaryContact: memberIndex === index,
        }));

        return;
    }

    form.team_members[index] = {
        ...form.team_members[index],
        [key]: value,
    };
};

const onLogoChange = (file: File | null): void => {
    form.logo = file;
};

const submit = (): void => {
    form.transform((data) => ({
        ...data,
        industry_id: data.industry_id || null,
        team_members: data.team_members.map((member) => ({
            id: member.id,
            full_name: member.fullName,
            role_title: member.roleTitle,
            email: member.email,
            phone: member.phone,
            bio: member.bio,
            is_primary_contact: Boolean(member.isPrimaryContact),
        })),
    })).put(updateOrganizationProfile().url, {
        forceFormData: true,
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Organization profile" />

        <SettingsLayout>
            <div class="flex flex-col space-y-6">
                <Heading
                    variant="small"
                    title="Organization profile"
                    description="Create the startup or company profile that your submission can later attach to when Negadras moves into submission workflows."
                />

                <form class="space-y-6" @submit.prevent="submit">
                    <FormSection title="Organization details" description="Keep the public-facing display name, contact details, and legal record consistent from the start.">
                        <div class="grid gap-5 md:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="legal_name">Legal name</Label>
                                <Input id="legal_name" v-model="form.legal_name" />
                                <InputError :message="form.errors.legal_name" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="display_name">Display name</Label>
                                <Input id="display_name" v-model="form.display_name" />
                                <InputError :message="form.errors.display_name" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="registration_number">Registration number</Label>
                                <Input id="registration_number" v-model="form.registration_number" />
                                <InputError :message="form.errors.registration_number" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="industry_id">Industry</Label>
                                <Select v-model="form.industry_id">
                                    <SelectTrigger id="industry_id">
                                        <SelectValue placeholder="Select industry" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="option in industryOptions" :key="option.value" :value="String(option.value)">
                                            {{ option.label }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError :message="form.errors.industry_id" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="contact_email">Contact email</Label>
                                <Input id="contact_email" v-model="form.contact_email" type="email" />
                                <InputError :message="form.errors.contact_email" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="contact_phone">Contact phone</Label>
                                <Input id="contact_phone" v-model="form.contact_phone" />
                                <InputError :message="form.errors.contact_phone" />
                            </div>

                            <div class="grid gap-2 md:col-span-2">
                                <Label for="website">Website</Label>
                                <Input id="website" v-model="form.website" type="url" />
                                <InputError :message="form.errors.website" />
                            </div>

                            <div class="grid gap-2 md:col-span-2">
                                <Label for="address">Address</Label>
                                <textarea
                                    id="address"
                                    v-model="form.address"
                                    rows="3"
                                    class="flex min-h-24 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none ring-offset-background placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
                                />
                                <InputError :message="form.errors.address" />
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

                            <div class="grid gap-2 md:col-span-2">
                                <MediaUploadField
                                    id="organization_logo"
                                    label="Logo"
                                    description="This uses the shared media foundation, so the same file can later be reused in reports or public showcase pages."
                                    accept="image/*"
                                    :filename="form.logo?.name ?? organization.logoFileName ?? null"
                                    @change="onLogoChange"
                                />
                                <InputError :message="form.errors.logo" />
                            </div>
                        </div>
                    </FormSection>

                    <FormSection title="Team members" description="Keep one primary contact and add other visible team members as your competition profile becomes more complete.">
                        <TeamMembersFields
                            :members="form.team_members"
                            :errors="form.errors"
                            @add="addMember"
                            @remove="removeMember"
                            @update="updateMember"
                        />
                    </FormSection>

                    <div class="flex justify-end">
                        <Button type="submit" :disabled="form.processing || !form.isDirty">
                            Save organization profile
                        </Button>
                    </div>
                </form>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
