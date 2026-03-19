<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSubmissionFileRequest;
use App\Models\Submission;
use App\Models\SubmissionFile;
use App\Support\ActivityLogger;
use App\Support\SubmissionFileRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SubmissionFileController extends Controller
{
    public function store(StoreSubmissionFileRequest $request, Submission $submission): RedirectResponse
    {
        $this->authorize('create', [SubmissionFile::class, $submission]);

        $definition = SubmissionFileRegistry::definition($request->validated('file_type'));
        $file = $request->file('file');
        $disk = 'local';
        $directory = sprintf('negadras/submissions/%s/%s', $submission->id, $request->validated('file_type'));
        $storedPath = $file->store($directory, $disk);

        if (! $definition['multiple']) {
            $submission->files()
                ->whereNull('submission_version_id')
                ->where('file_type', $request->validated('file_type'))
                ->get()
                ->each(function (SubmissionFile $existing): void {
                    Storage::disk($existing->disk)->delete($existing->file_path);
                    $existing->delete();
                });
        }

        $submissionFile = $submission->files()->create([
            'submission_version_id' => null,
            'file_type' => $request->validated('file_type'),
            'disk' => $disk,
            'original_name' => $file->getClientOriginalName(),
            'file_path' => $storedPath,
            'mime_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
            'description' => $request->validated('description'),
            'uploaded_by' => $request->user()?->id,
            'uploaded_at' => now(),
            'is_required' => $definition['required'],
            'is_verified' => false,
        ]);

        ActivityLogger::record(
            actor: $request->user(),
            event: 'negadras.submission-files.uploaded',
            description: "Uploaded {$submissionFile->original_name} for {$submission->title}.",
            subject: $submission,
            properties: [
                'file_type' => $submissionFile->file_type,
                'submission_file_id' => $submissionFile->id,
            ],
            request: $request,
        );

        return to_route('submissions.edit', $submission)->with('success', 'Submission file uploaded successfully.');
    }

    public function download(SubmissionFile $submissionFile): StreamedResponse
    {
        $this->authorize('view', $submissionFile);

        abort_unless(Storage::disk($submissionFile->disk)->exists($submissionFile->file_path), 404);

        return Storage::disk($submissionFile->disk)->download($submissionFile->file_path, $submissionFile->original_name);
    }

    public function destroy(Request $request, SubmissionFile $submissionFile): RedirectResponse
    {
        $this->authorize('delete', $submissionFile);

        abort_if($submissionFile->submission_version_id !== null, 403, 'Locked version files cannot be deleted.');

        Storage::disk($submissionFile->disk)->delete($submissionFile->file_path);

        ActivityLogger::record(
            actor: $request->user(),
            event: 'negadras.submission-files.deleted',
            description: "Deleted {$submissionFile->original_name} from {$submissionFile->submission->title}.",
            subject: $submissionFile->submission,
            properties: [
                'file_type' => $submissionFile->file_type,
                'submission_file_id' => $submissionFile->id,
            ],
            request: $request,
        );

        $submission = $submissionFile->submission;
        $submissionFile->delete();

        return to_route('submissions.edit', $submission)->with('success', 'Submission file deleted successfully.');
    }
}
