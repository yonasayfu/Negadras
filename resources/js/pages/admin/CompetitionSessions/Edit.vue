<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import PageContainer from '@/components/PageContainer.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as competitionSessionsIndex, update as updateCompetitionSession } from '@/routes/competition-sessions';
import type { BreadcrumbItem } from '@/types';

type Option = { value: number | string; label: string };

const props = defineProps<{
    seasonOptions: Option[];
    stageOptions: Option[];
    panelOptions: Option[];
    mediaOptions: Option[];
    sessionTypeOptions: Option[];
    sessionStatusOptions: Option[];
    session: {
        id: number;
        seasonId: number;
        stageId: number;
        panelId: number | null;
        name: string;
        sessionType: string;
        scheduledAt: string | null;
        location: string | null;
        status: string;
        etvVideoUrlOptional: string | null;
        mediaIds: number[];
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Competition sessions', href: competitionSessionsIndex() },
    { title: 'Edit', href: competitionSessionsIndex() },
];

const form = useForm({
    season_id: String(props.session.seasonId),
    stage_id: String(props.session.stageId),
    panel_id: props.session.panelId ? String(props.session.panelId) : '',
    name: props.session.name,
    session_type: props.session.sessionType,
    scheduled_at: props.session.scheduledAt ?? '',
    location: props.session.location ?? '',
    status: props.session.status,
    etv_video_url_optional: props.session.etvVideoUrlOptional ?? '',
    media_ids: props.session.mediaIds,
});

const submit = (): void => {
    form.put(updateCompetitionSession(props.session.id).url);
};
</script>

<template>
    <Head title="Edit competition session" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <PageContainer>
            <PageHeader
                title="Edit competition session"
                description="Adjust the live session metadata, linked panel, and supporting assets without losing the queue or event history."
            />

            <form class="grid gap-6 rounded-2xl border border-border/70 bg-card p-6 shadow-sm" @submit.prevent="submit">
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="name">Session name</Label>
                        <Input id="name" v-model="form.name" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="type">Session type</Label>
                        <select id="type" v-model="form.session_type" class="rounded-md border border-input bg-background px-3 py-2 text-sm">
                            <option v-for="option in sessionTypeOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                    </div>
                    <div class="grid gap-2">
                        <Label for="season">Season</Label>
                        <select id="season" v-model="form.season_id" class="rounded-md border border-input bg-background px-3 py-2 text-sm">
                            <option v-for="option in seasonOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                    </div>
                    <div class="grid gap-2">
                        <Label for="stage">Stage</Label>
                        <select id="stage" v-model="form.stage_id" class="rounded-md border border-input bg-background px-3 py-2 text-sm">
                            <option v-for="option in stageOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                    </div>
                    <div class="grid gap-2">
                        <Label for="panel">Panel</Label>
                        <select id="panel" v-model="form.panel_id" class="rounded-md border border-input bg-background px-3 py-2 text-sm">
                            <option value="">Optional panel</option>
                            <option v-for="option in panelOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                    </div>
                    <div class="grid gap-2">
                        <Label for="status">Status</Label>
                        <select id="status" v-model="form.status" class="rounded-md border border-input bg-background px-3 py-2 text-sm">
                            <option v-for="option in sessionStatusOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                    </div>
                    <div class="grid gap-2">
                        <Label for="scheduled_at">Scheduled at</Label>
                        <Input id="scheduled_at" v-model="form.scheduled_at" type="datetime-local" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="location">Location</Label>
                        <Input id="location" v-model="form.location" />
                    </div>
                </div>

                <div class="grid gap-2">
                    <Label for="video">Broadcast video URL</Label>
                    <Input id="video" v-model="form.etv_video_url_optional" />
                </div>

                <div class="grid gap-2">
                    <Label>Supporting media</Label>
                    <div class="grid gap-2 md:grid-cols-2">
                        <label v-for="option in mediaOptions" :key="option.value" class="flex items-center gap-2 rounded-md border border-border/60 px-3 py-2 text-sm">
                            <input
                                :checked="form.media_ids.includes(Number(option.value))"
                                type="checkbox"
                                @change="
                                    ($event) => {
                                        const value = Number(option.value);
                                        if (($event.target as HTMLInputElement).checked) {
                                            form.media_ids.push(value);
                                        } else {
                                            form.media_ids = form.media_ids.filter((id) => id !== value);
                                        }
                                    }
                                "
                            >
                            <span>{{ option.label }}</span>
                        </label>
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <Button as-child type="button" variant="outline">
                        <Link :href="competitionSessionsIndex()">Cancel</Link>
                    </Button>
                    <Button :disabled="form.processing" type="submit">Update session</Button>
                </div>
            </form>
        </PageContainer>
    </AppLayout>
</template>
