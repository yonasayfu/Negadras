<?php

use App\Models\Submission;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Storage;

test('manager can download a submission file through the private authorized route', function () {
    Storage::fake('local');
    $this->seed(RolePermissionSeeder::class);

    $manager = User::factory()->create();
    $manager->assignRole('Manager');

    $submission = Submission::factory()->create();
    $file = $submission->files()->create([
        'submission_version_id' => null,
        'file_type' => 'application_pdf',
        'disk' => 'local',
        'original_name' => 'admin-visible.pdf',
        'file_path' => 'negadras/submissions/test/admin-visible.pdf',
        'mime_type' => 'application/pdf',
        'file_size' => 1200,
        'description' => 'Admin-visible file.',
        'uploaded_by' => $manager->id,
        'uploaded_at' => now(),
        'is_required' => true,
        'is_verified' => false,
    ]);

    Storage::disk('local')->put($file->file_path, 'pdf-content');

    $this->actingAs($manager)
        ->get(route('submission-files.download', $file))
        ->assertSuccessful();
});
