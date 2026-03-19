<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import PageContainer from '@/components/PageContainer.vue';
import PageHeader from '@/components/PageHeader.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import ResourceToolbar from '@/components/admin/ResourceToolbar.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import { exportMethod as exportShortlist, index as shortlistIndex, update as updateShortlist } from '@/routes/shortlist';
import type { BreadcrumbItem, ManagedShortlistRecord, SelectOption } from '@/types';

type Props = {
    records: ManagedShortlistRecord[];
    filters: {
        search: string;
        approvalStatus: string;
        stageId: string;
    };
    approvalStatusOptions: SelectOption[];
    stageOptions: SelectOption[];
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Shortlist',
        href: shortlistIndex(),
    },
];

const allApprovalStatusesValue = '__all_approval_statuses__';
const allStagesValue = '__all_stages__';

const forms = Object.fromEntries(
    props.records.map((record) => [
        record.id,
        useForm({
            rank_order_optional: record.rankOrderOptional ? String(record.rankOrderOptional) : '',
            notes: record.notes ?? '',
            approval_status: record.approvalStatus,
        }),
    ]),
);

const updateFilters = (partial: Record<string, string>): void => {
    const search = partial.search ?? props.filters.search;
    const approvalStatus = partial.approvalStatus ?? props.filters.approvalStatus;
    const stageId = partial.stageId ?? props.filters.stageId;

    router.get(shortlistIndex().url, {
        search: search || undefined,
        approval_status: approvalStatus || undefined,
        stage_id: stageId || undefined,
    }, {
        preserveScroll: true,
        preserveState: true,
        replace: true,
    });
};

const submitRecord = (recordId: number): void => {
    forms[recordId].transform((data) => ({
        ...data,
        rank_order_optional: data.rank_order_optional === '' ? null : Number(data.rank_order_optional),
    })).put(updateShortlist(recordId).url, {
        preserveScroll: true,
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Shortlist" />

        <PageContainer>
            <PageHeader
                title="Shortlist"
                description="Approve shortlist records, refine ranking, and export the approved shortlist for downstream operations."
            >
                <template #actions>
                    <Button as-child variant="outline">
                        <a :href="exportShortlist().url">Export approved shortlist</a>
                    </Button>
                </template>
            </PageHeader>

            <ResourceToolbar
                :search="filters.search"
                search-placeholder="Search by submission, presenter, or organization"
                @update:search="(search) => updateFilters({ search })"
            >
                <template #actions>
                    <Select :model-value="filters.approvalStatus || allApprovalStatusesValue" @update:model-value="(value) => updateFilters({ approvalStatus: String(value) === allApprovalStatusesValue ? '' : String(value ?? '') })">
                        <SelectTrigger class="w-[190px]">
                            <SelectValue placeholder="All approval states" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem :value="allApprovalStatusesValue">All approval states</SelectItem>
                            <SelectItem v-for="option in approvalStatusOptions" :key="option.value" :value="String(option.value)">
                                {{ option.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>

                    <Select :model-value="filters.stageId || allStagesValue" @update:model-value="(value) => updateFilters({ stageId: String(value) === allStagesValue ? '' : String(value ?? '') })">
                        <SelectTrigger class="w-[190px]">
                            <SelectValue placeholder="All stages" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem :value="allStagesValue">All stages</SelectItem>
                            <SelectItem v-for="option in stageOptions" :key="option.value" :value="String(option.value)">
                                {{ option.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </template>
            </ResourceToolbar>

            <div class="grid gap-4">
                <article
                    v-for="record in records"
                    :key="record.id"
                    class="rounded-[1.5rem] border border-border/70 bg-card/85 p-5 shadow-sm"
                >
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="space-y-2">
                            <div class="flex flex-wrap items-center gap-2">
                                <div class="text-lg font-semibold">{{ record.submissionTitle || 'Untitled submission' }}</div>
                                <StatusBadge :label="record.approvalStatusLabel" :tone="record.approvalStatus === 'approved' ? 'published' : 'review'" />
                            </div>
                            <div class="grid gap-1 text-sm text-muted-foreground md:grid-cols-2">
                                <div>Season: {{ record.seasonName || 'No season' }}</div>
                                <div>Stage: {{ record.stageName || 'No stage' }}</div>
                                <div>Presenter: {{ record.applicantName || 'No presenter' }}</div>
                                <div>Organization: {{ record.organizationName || 'Independent presenter' }}</div>
                                <div>Created by: {{ record.createdBy || 'System' }}</div>
                                <div>Approved by: {{ record.approvedBy || 'Pending' }}</div>
                            </div>
                        </div>

                        <div class="text-sm text-muted-foreground">
                            Exported: {{ record.exportedAt ? new Date(record.exportedAt).toLocaleString() : 'Not exported' }}
                        </div>
                    </div>

                    <form class="mt-4 grid gap-4 rounded-xl border border-border/70 bg-background/60 p-4" @submit.prevent="submitRecord(record.id)">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="grid gap-2">
                                <Label :for="`rank-${record.id}`">Rank</Label>
                                <Input :id="`rank-${record.id}`" v-model="forms[record.id].rank_order_optional" type="number" min="1" />
                                <InputError :message="forms[record.id].errors.rank_order_optional" />
                            </div>

                            <div class="grid gap-2">
                                <Label :for="`approval-${record.id}`">Approval status</Label>
                                <Select v-model="forms[record.id].approval_status">
                                    <SelectTrigger :id="`approval-${record.id}`">
                                        <SelectValue placeholder="Select approval status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="option in approvalStatusOptions" :key="option.value" :value="String(option.value)">
                                            {{ option.label }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError :message="forms[record.id].errors.approval_status" />
                            </div>
                        </div>

                        <div class="grid gap-2">
                            <Label :for="`notes-${record.id}`">Notes</Label>
                            <textarea
                                :id="`notes-${record.id}`"
                                v-model="forms[record.id].notes"
                                rows="4"
                                class="flex min-h-28 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs"
                            />
                            <InputError :message="forms[record.id].errors.notes" />
                        </div>

                        <div class="flex justify-end">
                            <Button type="submit" :disabled="forms[record.id].processing">
                                Save shortlist record
                            </Button>
                        </div>
                    </form>
                </article>
            </div>
        </PageContainer>
    </AppLayout>
</template>
