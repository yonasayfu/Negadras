<script setup lang="ts">
import { Plus, Trash2 } from 'lucide-vue-next';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { ManagedApplicantSocialLink } from '@/types';

type SocialLinkForm = ManagedApplicantSocialLink;

type Props = {
    links: SocialLinkForm[];
    errors: Record<string, string | undefined>;
    showVerification?: boolean;
};

withDefaults(defineProps<Props>(), {
    showVerification: false,
});

const emit = defineEmits<{
    add: [];
    remove: [index: number];
    update: [index: number, key: keyof SocialLinkForm, value: string | boolean];
}>();
</script>

<template>
    <div class="space-y-4">
        <div
            v-for="(link, index) in links"
            :key="index"
            class="rounded-2xl border border-border/70 p-4"
        >
            <div class="grid gap-4 md:grid-cols-2">
                <div class="grid gap-2">
                    <Label :for="`social_links_${index}_platform`">Platform</Label>
                    <Input
                        :id="`social_links_${index}_platform`"
                        :model-value="link.platform"
                        placeholder="LinkedIn"
                        @update:model-value="emit('update', index, 'platform', String($event))"
                    />
                    <InputError :message="errors[`social_links.${index}.platform`]" />
                </div>

                <div class="grid gap-2">
                    <Label :for="`social_links_${index}_url`">URL</Label>
                    <Input
                        :id="`social_links_${index}_url`"
                        :model-value="link.url"
                        placeholder="https://example.com/profile"
                        @update:model-value="emit('update', index, 'url', String($event))"
                    />
                    <InputError :message="errors[`social_links.${index}.url`]" />
                </div>
            </div>

            <div class="mt-4 flex items-center justify-between">
                <div v-if="showVerification" class="flex items-center gap-3">
                    <Checkbox
                        :id="`social_links_${index}_verified`"
                        :model-value="link.isVerified ?? false"
                        @update:model-value="emit('update', index, 'isVerified', Boolean($event))"
                    />
                    <Label :for="`social_links_${index}_verified`">Verified</Label>
                </div>
                <div v-else />

                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    class="text-destructive"
                    @click="emit('remove', index)"
                >
                    <Trash2 class="size-4" />
                    Remove
                </Button>
            </div>
        </div>

        <Button type="button" variant="outline" @click="emit('add')">
            <Plus class="size-4" />
            Add social link
        </Button>
    </div>
</template>
