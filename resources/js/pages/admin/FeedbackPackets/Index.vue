<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import PageContainer from '@/components/PageContainer.vue';
import PageHeader from '@/components/PageHeader.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as feedbackPacketsIndex, send as sendFeedbackPacket, store as storeFeedbackPacket, update as updateFeedbackPacket } from '@/routes/feedback-packets';
import type { BreadcrumbItem, ManagedPresenterFeedbackPacket, SelectOption } from '@/types';

type SubmissionOption = {
    value: number;
    label: string;
};

type Props = {
    packets: ManagedPresenterFeedbackPacket[];
    submissionOptions: SubmissionOption[];
    visibilityOptions: SelectOption[];
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Feedback packets',
        href: feedbackPacketsIndex(),
    },
];

const createForm = useForm({
    submission_id: '',
    summary: '',
    strengths: '',
    improvement_areas: '',
    next_step_guidance: '',
    visibility_status: props.visibilityOptions[0]?.value ?? '',
    send_now: false,
});

const rowForms = Object.fromEntries(
    props.packets.map((packet) => [
        packet.id,
        useForm({
            summary: packet.summary,
            strengths: packet.strengths ?? '',
            improvement_areas: packet.improvementAreas ?? '',
            next_step_guidance: packet.nextStepGuidance ?? '',
            visibility_status: packet.visibilityStatus,
        }),
    ]),
);

const sendForms = Object.fromEntries(
    props.packets.map((packet) => [
        packet.id,
        useForm({
            send_notification: true,
        }),
    ]),
);

const submitCreate = (): void => {
    createForm.transform((data) => ({
        ...data,
        submission_id: Number(data.submission_id),
        summary: data.summary === '' ? null : data.summary,
        strengths: data.strengths === '' ? null : data.strengths,
        improvement_areas: data.improvement_areas === '' ? null : data.improvement_areas,
        next_step_guidance: data.next_step_guidance === '' ? null : data.next_step_guidance,
    })).post(storeFeedbackPacket().url, {
        preserveScroll: true,
    });
};

const submitUpdate = (packetId: number): void => {
    rowForms[packetId].put(updateFeedbackPacket(packetId).url, {
        preserveScroll: true,
    });
};

const revealToPresenter = (packetId: number): void => {
    sendForms[packetId].post(sendFeedbackPacket(packetId).url, {
        preserveScroll: true,
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Feedback Packets" />

        <PageContainer>
            <PageHeader
                title="Feedback packets"
                description="Turn review outcomes into presenter-facing guidance and release them only when the packet is ready."
            />

            <div class="grid gap-6 lg:grid-cols-[0.95fr_1.05fr]">
                <section class="rounded-[1.5rem] border border-border/70 bg-card/85 p-5 shadow-sm">
                    <h2 class="text-base font-semibold">Generate packet</h2>
                    <form class="mt-4 grid gap-4" @submit.prevent="submitCreate">
                        <div class="grid gap-2">
                            <Label for="submission_id">Submission</Label>
                            <Select v-model="createForm.submission_id">
                                <SelectTrigger id="submission_id">
                                    <SelectValue placeholder="Select submission" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="option in submissionOptions" :key="option.value" :value="String(option.value)">
                                        {{ option.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="createForm.errors.submission_id" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="packet_visibility">Initial visibility</Label>
                            <Select v-model="createForm.visibility_status">
                                <SelectTrigger id="packet_visibility">
                                    <SelectValue placeholder="Select visibility" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="option in visibilityOptions" :key="option.value" :value="String(option.value)">
                                        {{ option.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="createForm.errors.visibility_status" />
                        </div>

                        <label class="flex items-center gap-3 rounded-xl border border-border/70 bg-background/60 px-4 py-3 text-sm">
                            <input v-model="createForm.send_now" type="checkbox" class="size-4 rounded border-border" />
                            <span>Send to presenter immediately after generation</span>
                        </label>

                        <div class="flex justify-end">
                            <Button type="submit" :disabled="createForm.processing || createForm.submission_id === ''">
                                Create feedback packet
                            </Button>
                        </div>
                    </form>
                </section>

                <section class="grid gap-4">
                    <div v-if="packets.length === 0" class="rounded-[1.5rem] border border-dashed border-border bg-card/70 p-10 text-sm text-muted-foreground">
                        No presenter feedback packets exist yet.
                    </div>

                    <article
                        v-for="packet in packets"
                        :key="packet.id"
                        class="rounded-[1.5rem] border border-border/70 bg-card/85 p-5 shadow-sm"
                    >
                        <div class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
                            <div>
                                <div class="text-lg font-semibold">{{ packet.submissionTitle || 'Untitled submission' }}</div>
                                <div class="mt-1 grid gap-1 text-sm text-muted-foreground md:grid-cols-2">
                                    <div>Presenter: {{ packet.applicantName || 'Unknown presenter' }}</div>
                                    <div>Organization: {{ packet.organizationName || 'Independent presenter' }}</div>
                                    <div>Stage: {{ packet.stageName || 'No stage' }}</div>
                                    <div>Visible: {{ packet.visibilityStatusLabel }}</div>
                                </div>
                            </div>
                            <div class="text-sm text-muted-foreground">
                                Sent: {{ packet.sentAtOptional || 'Not yet sent' }}
                            </div>
                        </div>

                        <form class="mt-4 grid gap-4 rounded-xl border border-border/70 bg-background/60 p-4" @submit.prevent="submitUpdate(packet.id)">
                            <div class="grid gap-2">
                                <Label :for="`summary-${packet.id}`">Summary</Label>
                                <textarea :id="`summary-${packet.id}`" v-model="rowForms[packet.id].summary" rows="4" class="flex min-h-28 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs" />
                                <InputError :message="rowForms[packet.id].errors.summary" />
                            </div>

                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="grid gap-2">
                                    <Label :for="`strengths-${packet.id}`">Strengths</Label>
                                    <textarea :id="`strengths-${packet.id}`" v-model="rowForms[packet.id].strengths" rows="5" class="flex min-h-32 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs" />
                                    <InputError :message="rowForms[packet.id].errors.strengths" />
                                </div>

                                <div class="grid gap-2">
                                    <Label :for="`improvements-${packet.id}`">Improvement areas</Label>
                                    <textarea :id="`improvements-${packet.id}`" v-model="rowForms[packet.id].improvement_areas" rows="5" class="flex min-h-32 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs" />
                                    <InputError :message="rowForms[packet.id].errors.improvement_areas" />
                                </div>
                            </div>

                            <div class="grid gap-2">
                                <Label :for="`next-step-${packet.id}`">Next-step guidance</Label>
                                <textarea :id="`next-step-${packet.id}`" v-model="rowForms[packet.id].next_step_guidance" rows="4" class="flex min-h-28 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs" />
                                <InputError :message="rowForms[packet.id].errors.next_step_guidance" />
                            </div>

                            <div class="grid gap-2 md:max-w-sm">
                                <Label :for="`visibility-${packet.id}`">Visibility</Label>
                                <Select v-model="rowForms[packet.id].visibility_status">
                                    <SelectTrigger :id="`visibility-${packet.id}`">
                                        <SelectValue placeholder="Select visibility" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="option in visibilityOptions" :key="option.value" :value="String(option.value)">
                                            {{ option.label }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError :message="rowForms[packet.id].errors.visibility_status" />
                            </div>

                            <div class="flex flex-col gap-3 md:flex-row md:justify-end">
                                <Button type="submit" variant="outline" :disabled="rowForms[packet.id].processing">
                                    Save packet
                                </Button>
                                <Button type="button" :disabled="sendForms[packet.id].processing" @click="revealToPresenter(packet.id)">
                                    Send to presenter
                                </Button>
                            </div>
                        </form>
                    </article>
                </section>
            </div>
        </PageContainer>
    </AppLayout>
</template>
