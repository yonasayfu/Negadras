<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import PageContainer from '@/components/PageContainer.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as competitionSessionsIndex, store as storeCompetitionSession } from '@/routes/competition-sessions';
import type { BreadcrumbItem } from '@/types';

type Option = { value: number | string; label: string };

const props = defineProps<{
    seasonOptions: Option[];
    stageOptions: Option[];
    panelOptions: Option[];
    mediaOptions: Option[];
    sessionTypeOptions: Option[];
    sessionStatusOptions: Option[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Competition sessions', href: competitionSessionsIndex() },
    { title: 'Create', href: competitionSessionsIndex() },
];

const form = useForm({
    season_id: '',
    stage_id: '',
    panel_id: '',
    name: '',
    session_type: 'pitch',
    scheduled_at: '',
    location: '',
    status: 'scheduled',
    etv_video_url_optional: '',
    media_ids: [] as Array<number>,
});

const submit = (): void => {
    form.post(storeCompetitionSession().url);
};
</script>

<template>
    <Head title="Create competition session" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <PageContainer>
            <PageHeader
                title="Create competition session"
                description="Set up the season, panel, schedule, and supporting media before the live operations team takes over."
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
                            <option value="">Select season</option>
                            <option v-for="option in seasonOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                    </div>
                    <div class="grid gap-2">
                        <Label for="stage">Stage</Label>
                        <select id="stage" v-model="form.stage_id" class="rounded-md border border-input bg-background px-3 py-2 text-sm">
                            <option value="">Select stage</option>
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
                    <Button :disabled="form.processing" type="submit">Create session</Button>
                </div>
            </form>
        </PageContainer>
    </AppLayout>
</template>
