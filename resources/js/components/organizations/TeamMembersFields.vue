<script setup lang="ts">
import { Trash2 } from 'lucide-vue-next';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { ManagedTeamMember } from '@/types/admin';

defineProps<{
    members: ManagedTeamMember[];
    errors: Record<string, string | undefined>;
}>();

const emit = defineEmits<{
    add: [];
    remove: [index: number];
    update: [index: number, key: keyof ManagedTeamMember, value: string | boolean | number | null];
}>();
</script>

<template>
    <div class="grid gap-4">
        <div
            v-for="(member, index) in members"
            :key="member.id ?? `team-member-${index}`"
            class="rounded-xl border border-border/70 bg-background p-4"
        >
            <div class="mb-4 flex items-center justify-between gap-3">
                <div>
                    <h4 class="font-medium text-foreground">Team member {{ index + 1 }}</h4>
                    <p v-if="member.linkedApplicantName" class="text-sm text-muted-foreground">
                        Linked applicant: {{ member.linkedApplicantName }}
                    </p>
                </div>

                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    :disabled="members.length === 1"
                    @click="emit('remove', index)"
                >
                    <Trash2 class="size-4" />
                    <span class="sr-only">Remove team member</span>
                </Button>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div class="grid gap-2">
                    <Label :for="`team_member_full_name_${index}`">Full name</Label>
                    <Input
                        :id="`team_member_full_name_${index}`"
                        :model-value="member.fullName"
                        @update:model-value="emit('update', index, 'fullName', $event)"
                    />
                    <InputError :message="errors[`team_members.${index}.full_name`]" />
                </div>

                <div class="grid gap-2">
                    <Label :for="`team_member_role_title_${index}`">Role title</Label>
                    <Input
                        :id="`team_member_role_title_${index}`"
                        :model-value="member.roleTitle"
                        @update:model-value="emit('update', index, 'roleTitle', $event)"
                    />
                    <InputError :message="errors[`team_members.${index}.role_title`]" />
                </div>

                <div class="grid gap-2">
                    <Label :for="`team_member_email_${index}`">Email</Label>
                    <Input
                        :id="`team_member_email_${index}`"
                        type="email"
                        :model-value="member.email ?? ''"
                        @update:model-value="emit('update', index, 'email', $event)"
                    />
                    <InputError :message="errors[`team_members.${index}.email`]" />
                </div>

                <div class="grid gap-2">
                    <Label :for="`team_member_phone_${index}`">Phone</Label>
                    <Input
                        :id="`team_member_phone_${index}`"
                        :model-value="member.phone ?? ''"
                        @update:model-value="emit('update', index, 'phone', $event)"
                    />
                    <InputError :message="errors[`team_members.${index}.phone`]" />
                </div>

                <div class="grid gap-2 md:col-span-2">
                    <Label :for="`team_member_bio_${index}`">Bio</Label>
                    <textarea
                        :id="`team_member_bio_${index}`"
                        :value="member.bio ?? ''"
                        rows="3"
                        class="flex min-h-24 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none ring-offset-background placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
                        @input="emit('update', index, 'bio', ($event.target as HTMLTextAreaElement).value)"
                    />
                    <InputError :message="errors[`team_members.${index}.bio`]" />
                </div>

                <div class="flex items-start gap-3 md:col-span-2">
                    <Checkbox
                        :id="`team_member_primary_${index}`"
                        :model-value="member.isPrimaryContact"
                        @update:model-value="emit('update', index, 'isPrimaryContact', Boolean($event))"
                    />
                    <div class="grid gap-1.5">
                        <Label :for="`team_member_primary_${index}`">Primary contact</Label>
                        <p class="text-sm text-muted-foreground">
                            Exactly one team member must be marked as the primary contact.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <InputError :message="errors.team_members" />

        <div>
            <Button type="button" variant="outline" @click="emit('add')">
                Add team member
            </Button>
        </div>
    </div>
</template>
