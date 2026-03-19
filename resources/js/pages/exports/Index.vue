<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Download, FileOutput } from 'lucide-vue-next';
import PageContainer from '@/components/PageContainer.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as exportsIndex } from '@/routes/exports';
import type { BreadcrumbItem } from '@/types';

defineProps<{
    resources: Array<{
        key: string;
        title: string;
        description: string;
        href: string;
        actionLabel: string;
        format: string;
    }>;
    recentJobs: Array<{
        id: number;
        type: string;
        statusLabel: string;
        statusTone: string;
        fileName: string | null;
        rowCount: number | null;
        requestedBy: string | null;
        completedAt: string | null;
    }>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Export center',
        href: exportsIndex(),
    },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Export center" />

        <PageContainer>
            <PageHeader
                title="Export center"
                description="Operational exports now cover the main Negadras reporting surfaces and keep a recorded job trail for governance."
            />

            <section class="grid gap-4 lg:grid-cols-2">
                <article
                    v-for="resource in resources"
                    :key="resource.key"
                    class="rounded-2xl border border-border/70 bg-card/85 p-5"
                >
                    <div class="flex items-center gap-3">
                        <div class="rounded-2xl bg-muted p-3">
                            <FileOutput class="size-5" />
                        </div>
                        <div>
                            <h2 class="text-lg font-semibold">{{ resource.title }}</h2>
                            <p class="text-xs uppercase tracking-[0.18em] text-muted-foreground">{{ resource.format }}</p>
                        </div>
                    </div>

                    <p class="mt-4 text-sm leading-6 text-muted-foreground">
                        {{ resource.description }}
                    </p>

                    <div class="mt-5">
                        <Button as-child>
                            <Link :href="resource.href">
                                <Download class="size-4" />
                                {{ resource.actionLabel }}
                            </Link>
                        </Button>
                    </div>
                </article>
            </section>

            <section class="rounded-2xl border border-border/70 bg-card/85 p-5">
                <h2 class="text-lg font-semibold">Recent export jobs</h2>
                <div class="mt-4 space-y-3">
                    <div
                        v-for="job in recentJobs"
                        :key="job.id"
                        class="flex flex-col gap-2 rounded-xl border border-border/60 px-4 py-3 md:flex-row md:items-center md:justify-between"
                    >
                        <div>
                            <div class="font-medium">{{ job.type }}</div>
                            <div class="text-sm text-muted-foreground">
                                {{ job.fileName || 'No file name' }} · {{ job.requestedBy || 'System' }}
                            </div>
                        </div>
                        <div class="text-sm text-muted-foreground">
                            {{ job.rowCount || 0 }} rows · {{ job.completedAt || 'Pending' }}
                        </div>
                    </div>
                </div>
            </section>
        </PageContainer>
    </AppLayout>
</template>
