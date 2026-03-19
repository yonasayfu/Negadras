<script setup lang="ts">
import { CirclePlus, Trash2 } from 'lucide-vue-next';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type CriterionForm = {
    id?: number;
    name: string;
    description: string;
    max_score: number | string;
    weight: number | string;
    order_index: number | string;
    is_required: boolean;
    visibility_rule: string;
    help_text: string;
};

type Props = {
    criteria: CriterionForm[];
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
            v-for="(criterion, index) in criteria"
            :key="criterion.id ?? `criterion-${index}`"
            class="rounded-xl border border-border/70 bg-background/60 p-4"
        >
            <div class="mb-4 flex items-center justify-between">
                <div class="text-sm font-medium">Criterion {{ index + 1 }}</div>
                <Button type="button" variant="ghost" size="sm" @click="emit('remove', index)">
                    <Trash2 class="size-4" />
                </Button>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div class="grid gap-2 md:col-span-2">
                    <Label :for="`criterion_name_${index}`">Name</Label>
                    <Input :id="`criterion_name_${index}`" v-model="criterion.name" />
                    <InputError :message="errors[`criteria.${index}.name`]" />
                </div>

                <div class="grid gap-2">
                    <Label :for="`criterion_max_score_${index}`">Max score</Label>
                    <Input :id="`criterion_max_score_${index}`" v-model="criterion.max_score" type="number" min="1" max="100" step="0.01" />
                    <InputError :message="errors[`criteria.${index}.max_score`]" />
                </div>

                <div class="grid gap-2">
                    <Label :for="`criterion_weight_${index}`">Weight</Label>
                    <Input :id="`criterion_weight_${index}`" v-model="criterion.weight" type="number" min="1" max="100" step="0.01" />
                    <InputError :message="errors[`criteria.${index}.weight`]" />
                </div>

                <div class="grid gap-2">
                    <Label :for="`criterion_order_${index}`">Display order</Label>
                    <Input :id="`criterion_order_${index}`" v-model="criterion.order_index" type="number" min="1" step="1" />
                    <InputError :message="errors[`criteria.${index}.order_index`]" />
                </div>

                <div class="grid gap-2">
                    <Label :for="`criterion_visibility_${index}`">Visibility rule</Label>
                    <Input :id="`criterion_visibility_${index}`" v-model="criterion.visibility_rule" placeholder="optional" />
                    <InputError :message="errors[`criteria.${index}.visibility_rule`]" />
                </div>

                <div class="grid gap-2 md:col-span-2">
                    <Label :for="`criterion_description_${index}`">Description</Label>
                    <textarea
                        :id="`criterion_description_${index}`"
                        v-model="criterion.description"
                        rows="3"
                        class="flex min-h-24 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs"
                    />
                    <InputError :message="errors[`criteria.${index}.description`]" />
                </div>

                <div class="grid gap-2 md:col-span-2">
                    <Label :for="`criterion_help_${index}`">Help text</Label>
                    <textarea
                        :id="`criterion_help_${index}`"
                        v-model="criterion.help_text"
                        rows="2"
                        class="flex min-h-20 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs"
                    />
                    <InputError :message="errors[`criteria.${index}.help_text`]" />
                </div>

                <div class="md:col-span-2">
                    <label class="flex items-start gap-3 rounded-lg border border-border/70 px-3 py-3">
                        <Checkbox v-model:checked="criterion.is_required" />
                        <div>
                            <div class="text-sm font-medium">Required criterion</div>
                            <p class="text-sm text-muted-foreground">Judges must score this criterion before submitting final marks.</p>
                        </div>
                    </label>
                </div>
            </div>
        </article>

        <div>
            <Button type="button" variant="outline" @click="emit('add')">
                <CirclePlus class="size-4" />
                Add criterion
            </Button>
        </div>
    </div>
</template>
