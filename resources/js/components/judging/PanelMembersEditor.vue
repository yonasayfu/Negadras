<script setup lang="ts">
import { CirclePlus, Trash2 } from 'lucide-vue-next';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import type { SelectOption } from '@/types';

type MemberForm = {
    id?: number;
    judge_id: string;
    role_in_panel: string;
    display_order: number | string;
};

type Props = {
    members: MemberForm[];
    judgeOptions: SelectOption[];
    roleOptions: SelectOption[];
    errors: Record<string, string | undefined>;
};

defineProps<Props>();

const emit = defineEmits<{
    add: [];
    remove: [index: number];
}>();
</script>

<template>
    <div class="grid gap-4">
        <article
            v-for="(member, index) in members"
            :key="member.id ?? `member-${index}`"
            class="rounded-xl border border-border/70 bg-background/60 p-4"
        >
            <div class="mb-4 flex items-center justify-between">
                <div class="text-sm font-medium">Panel member {{ index + 1 }}</div>
                <Button type="button" variant="ghost" size="sm" @click="emit('remove', index)">
                    <Trash2 class="size-4" />
                </Button>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <div class="grid gap-2 md:col-span-2">
                    <Label :for="`panel_member_judge_${index}`">Judge</Label>
                    <Select v-model="member.judge_id">
                        <SelectTrigger :id="`panel_member_judge_${index}`">
                            <SelectValue placeholder="Select judge" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="option in judgeOptions" :key="option.value" :value="String(option.value)">
                                {{ option.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="errors[`members.${index}.judge_id`]" />
                </div>

                <div class="grid gap-2">
                    <Label :for="`panel_member_role_${index}`">Role</Label>
                    <Select v-model="member.role_in_panel">
                        <SelectTrigger :id="`panel_member_role_${index}`">
                            <SelectValue placeholder="Select role" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="option in roleOptions" :key="option.value" :value="String(option.value)">
                                {{ option.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="errors[`members.${index}.role_in_panel`]" />
                </div>

                <div class="grid gap-2">
                    <Label :for="`panel_member_order_${index}`">Display order</Label>
                    <Input :id="`panel_member_order_${index}`" v-model="member.display_order" type="number" min="1" step="1" />
                    <InputError :message="errors[`members.${index}.display_order`]" />
                </div>
            </div>
        </article>

        <div>
            <Button type="button" variant="outline" @click="emit('add')">
                <CirclePlus class="size-4" />
                Add panel member
            </Button>
        </div>
    </div>
</template>
