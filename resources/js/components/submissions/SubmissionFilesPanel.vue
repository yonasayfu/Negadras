<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { Download, FileArchive, ShieldCheck, Trash2, Upload } from 'lucide-vue-next';
import { reactive } from 'vue';
import ConfirmActionDialog from '@/components/admin/ConfirmActionDialog.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { store as storeSubmissionFile, destroy as destroySubmissionFile } from '@/routes/submission-files';
import type { ManagedSubmissionFile, SubmissionFileDefinition } from '@/types';

type Props = {
    submissionId: number;
    definitions: SubmissionFileDefinition[];
    draftFiles: ManagedSubmissionFile[];
    currentVersionFiles: ManagedSubmissionFile[];
    canManage?: boolean;
};

const props = withDefaults(defineProps<Props>(), {
    canManage: false,
});

const selectedFiles = reactive<Record<string, File | null>>({});
const descriptions = reactive<Record<string, string>>({});
const uploadForm = useForm({
    file_type: '',
    description: '',
    file: null as File | null,
});

const filesForType = (fileType: string, scope: 'draft' | 'current'): ManagedSubmissionFile[] => {
    const source = scope === 'draft' ? props.draftFiles : props.currentVersionFiles;

    return source.filter((file) => file.fileType === fileType);
};

const onFileChange = (fileType: string, event: Event): void => {
    const target = event.target as HTMLInputElement;
    selectedFiles[fileType] = target.files?.[0] ?? null;
};

const upload = (definition: SubmissionFileDefinition): void => {
    uploadForm
        .transform(() => ({
            file_type: definition.type,
            description: descriptions[definition.type] || '',
            file: selectedFiles[definition.type],
        }))
        .post(storeSubmissionFile(props.submissionId).url, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                selectedFiles[definition.type] = null;
                descriptions[definition.type] = '';
                uploadForm.reset();
            },
        });
};

const deleteFile = (file: ManagedSubmissionFile): void => {
    router.delete(destroySubmissionFile(file.id).url, {
        preserveScroll: true,
    });
};

const formatSize = (size: number): string => {
    if (size < 1024) {
        return `${size} B`;
    }

    if (size < 1024 * 1024) {
        return `${(size / 1024).toFixed(1)} KB`;
    }

    return `${(size / (1024 * 1024)).toFixed(1)} MB`;
};
</script>

<template>
    <section class="rounded-[1.5rem] border border-border/70 bg-card/85 p-5 shadow-sm">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h2 class="text-base font-semibold">Submission files</h2>
                <p class="mt-1 text-sm text-muted-foreground">
                    Required categories are marked clearly. Draft uploads stay editable until the next final submit locks them into a version.
                </p>
            </div>
        </div>

        <div class="mt-6 grid gap-5">
            <article
                v-for="definition in definitions"
                :key="definition.type"
                class="rounded-[1.25rem] border border-border/70 bg-background/60 p-4"
            >
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="font-medium">{{ definition.label }}</h3>
                            <span
                                class="rounded-full px-2.5 py-1 text-[11px] font-medium uppercase tracking-wide"
                                :class="definition.required ? 'bg-amber-100 text-amber-900 dark:bg-amber-500/10 dark:text-amber-100' : 'bg-muted text-muted-foreground'"
                            >
                                {{ definition.required ? 'Required' : 'Optional' }}
                            </span>
                            <span
                                class="rounded-full px-2.5 py-1 text-[11px] font-medium uppercase tracking-wide"
                                :class="definition.multiple ? 'bg-sky-100 text-sky-900 dark:bg-sky-500/10 dark:text-sky-100' : 'bg-muted text-muted-foreground'"
                            >
                                {{ definition.multiple ? 'Multiple files' : 'Single file' }}
                            </span>
                        </div>
                        <p class="mt-2 text-sm text-muted-foreground">{{ definition.description }}</p>
                    </div>
                </div>

                <div class="mt-4 grid gap-4 lg:grid-cols-2">
                    <div class="grid gap-3">
                        <div class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Working draft files</div>

                        <div v-if="filesForType(definition.type, 'draft').length === 0" class="rounded-xl border border-dashed border-border/70 p-4 text-sm text-muted-foreground">
                            No draft files uploaded for this category yet.
                        </div>

                        <div v-else class="grid gap-3">
                            <div
                                v-for="file in filesForType(definition.type, 'draft')"
                                :key="file.id"
                                class="rounded-xl border border-border/70 bg-card/80 p-4"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <div class="space-y-1">
                                        <div class="font-medium">{{ file.originalName }}</div>
                                        <div class="text-sm text-muted-foreground">
                                            {{ formatSize(file.fileSize) }} · {{ file.mimeType || 'Unknown mime' }}
                                        </div>
                                        <div class="text-sm text-muted-foreground">
                                            {{ file.description || 'No description added.' }}
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <Button as-child size="sm" variant="outline">
                                            <a :href="file.downloadUrl">
                                                <Download class="size-4" />
                                                Download
                                            </a>
                                        </Button>

                                        <ConfirmActionDialog
                                            v-if="canManage"
                                            title="Delete draft file?"
                                            :description="`Delete ${file.originalName} from the working draft files?`"
                                            confirm-label="Delete file"
                                            @confirm="deleteFile(file)"
                                        >
                                            <template #trigger>
                                                <Button type="button" size="sm" variant="outline" class="text-destructive">
                                                    <Trash2 class="size-4" />
                                                </Button>
                                            </template>
                                        </ConfirmActionDialog>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-if="canManage" class="rounded-xl border border-border/70 bg-card/80 p-4">
                            <div class="grid gap-3">
                                <div class="grid gap-2">
                                    <Label :for="`file-${definition.type}`">Upload {{ definition.label }}</Label>
                                    <input
                                        :id="`file-${definition.type}`"
                                        type="file"
                                        :accept="definition.accept"
                                        class="block w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm file:mr-3 file:rounded-md file:border-0 file:bg-primary file:px-3 file:py-2 file:text-sm file:font-medium file:text-primary-foreground"
                                        @change="onFileChange(definition.type, $event)"
                                    >
                                </div>

                                <div class="grid gap-2">
                                    <Label :for="`description-${definition.type}`">Description</Label>
                                    <textarea
                                        :id="`description-${definition.type}`"
                                        v-model="descriptions[definition.type]"
                                        rows="3"
                                        class="flex min-h-24 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none ring-offset-background placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
                                        placeholder="Optional note for this file."
                                    />
                                </div>

                                <InputError :message="uploadForm.errors.file" />
                                <InputError :message="uploadForm.errors.file_type" />

                                <Button type="button" :disabled="uploadForm.processing || !selectedFiles[definition.type]" @click="upload(definition)">
                                    <Upload class="size-4" />
                                    {{ definition.multiple ? 'Upload file' : 'Upload or replace' }}
                                </Button>
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-3">
                        <div class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Current locked version files</div>

                        <div v-if="filesForType(definition.type, 'current').length === 0" class="rounded-xl border border-dashed border-border/70 p-4 text-sm text-muted-foreground">
                            No locked version files are currently attached for this category.
                        </div>

                        <div v-else class="grid gap-3">
                            <div
                                v-for="file in filesForType(definition.type, 'current')"
                                :key="file.id"
                                class="rounded-xl border border-border/70 bg-card/80 p-4"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <div class="space-y-1">
                                        <div class="font-medium">{{ file.originalName }}</div>
                                        <div class="text-sm text-muted-foreground">
                                            Version v{{ file.versionNumber ?? 'N/A' }} · {{ formatSize(file.fileSize) }}
                                        </div>
                                        <div class="text-sm text-muted-foreground">
                                            {{ file.description || 'No description added.' }}
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <span
                                            class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[11px] font-medium uppercase tracking-wide"
                                            :class="file.isVerified ? 'bg-emerald-100 text-emerald-900 dark:bg-emerald-500/10 dark:text-emerald-100' : 'bg-muted text-muted-foreground'"
                                        >
                                            <ShieldCheck class="size-3" />
                                            {{ file.isVerified ? 'Verified' : 'Pending verification' }}
                                        </span>

                                        <Button as-child size="sm" variant="outline">
                                            <a :href="file.downloadUrl">
                                                <Download class="size-4" />
                                                Download
                                            </a>
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </article>
        </div>

        <div class="mt-6 rounded-[1.25rem] border border-border/70 bg-background/60 p-4 text-sm text-muted-foreground">
            <div class="flex items-center gap-2 font-medium text-foreground">
                <FileArchive class="size-4" />
                Private storage rule
            </div>
            <p class="mt-2">
                Submission files are stored privately and only exposed through authorized download routes. Required categories are defined now, while deeper intake validation and staff verification rules can build on this foundation in later phases.
            </p>
        </div>
    </section>
</template>
