<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import PageContainer from '@/components/PageContainer.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as adminSubmissionsIndex } from '@/routes/admin-submissions';
import type { BreadcrumbItem, ManagedSubmission } from '@/types';

type Props = {
    submission: ManagedSubmission;
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Admin submissions',
        href: adminSubmissionsIndex(),
    },
    {
        title: props.submission.title,
        href: adminSubmissionsIndex(),
    },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="submission.title" />

        <PageContainer>
            <PageHeader
                :title="submission.title"
                description="This is the staff-facing read model for intake inspection before reviewer assignment and later screening workflows."
            >
                <template #actions>
                    <div class="flex items-center gap-2">
                        <StatusBadge :label="submission.statusLabel" :tone="submission.statusTone" />
                        <Button as-child variant="outline">
                            <Link :href="adminSubmissionsIndex()">
                                <ArrowLeft class="size-4" />
                                Back to admin submissions
                            </Link>
                        </Button>
                    </div>
                </template>
            </PageHeader>

            <div class="grid gap-6 lg:grid-cols-[1.2fr_0.8fr]">
                <section class="grid gap-6">
                    <div class="rounded-[1.5rem] border border-border/70 bg-card/85 p-5 shadow-sm">
                        <h2 class="text-base font-semibold">Summary</h2>
                        <p class="mt-3 whitespace-pre-wrap text-sm leading-6 text-muted-foreground">{{ submission.summary || 'No summary provided.' }}</p>
                    </div>

                    <div class="rounded-[1.5rem] border border-border/70 bg-card/85 p-5 shadow-sm">
                        <h2 class="text-base font-semibold">Problem statement</h2>
                        <p class="mt-3 whitespace-pre-wrap text-sm leading-6 text-muted-foreground">{{ submission.problemStatement || 'No problem statement provided.' }}</p>
                    </div>

                    <div class="rounded-[1.5rem] border border-border/70 bg-card/85 p-5 shadow-sm">
                        <h2 class="text-base font-semibold">Solution description</h2>
                        <p class="mt-3 whitespace-pre-wrap text-sm leading-6 text-muted-foreground">{{ submission.solutionDescription || 'No solution description provided.' }}</p>
                    </div>

                    <div class="rounded-[1.5rem] border border-border/70 bg-card/85 p-5 shadow-sm">
                        <h2 class="text-base font-semibold">Business model</h2>
                        <p class="mt-3 whitespace-pre-wrap text-sm leading-6 text-muted-foreground">{{ submission.businessModel || 'No business model provided.' }}</p>
                    </div>
                </section>

                <aside class="grid gap-6">
                    <div class="rounded-[1.5rem] border border-border/70 bg-card/85 p-5 shadow-sm">
                        <h2 class="text-base font-semibold">Operational context</h2>

                        <dl class="mt-4 grid gap-4 text-sm">
                            <div>
                                <dt class="text-muted-foreground">Presenter</dt>
                                <dd class="font-medium">{{ submission.applicantName || 'Unknown presenter' }}</dd>
                                <dd class="text-muted-foreground">{{ submission.applicantEmail || 'No email' }}</dd>
                            </div>
                            <div>
                                <dt class="text-muted-foreground">Organization</dt>
                                <dd class="font-medium">{{ submission.organizationName || 'Independent presenter' }}</dd>
                            </div>
                            <div>
                                <dt class="text-muted-foreground">Season / stage</dt>
                                <dd class="font-medium">{{ submission.seasonName || 'No season' }}</dd>
                                <dd class="text-muted-foreground">{{ submission.stageName || 'No stage' }}</dd>
                            </div>
                            <div>
                                <dt class="text-muted-foreground">Industry</dt>
                                <dd class="font-medium">{{ submission.industryName || 'No industry' }}</dd>
                            </div>
                            <div>
                                <dt class="text-muted-foreground">Public after approval</dt>
                                <dd class="font-medium">{{ submission.isPublicAfterApproval ? 'Yes' : 'No' }}</dd>
                            </div>
                            <div>
                                <dt class="text-muted-foreground">Submitted at</dt>
                                <dd class="font-medium">{{ submission.submittedAt ? new Date(submission.submittedAt).toLocaleString() : 'Draft only' }}</dd>
                            </div>
                            <div>
                                <dt class="text-muted-foreground">Last updated</dt>
                                <dd class="font-medium">{{ submission.updatedAt ? new Date(submission.updatedAt).toLocaleString() : 'N/A' }}</dd>
                            </div>
                        </dl>
                    </div>
                </aside>
            </div>
        </PageContainer>
    </AppLayout>
</template>
