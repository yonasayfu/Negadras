<?php

namespace App\Support;

use App\Models\Media;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;

class MediaUploader
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public static function store(
        UploadedFile $file,
        User $user,
        string $collection = 'library',
        string $disk = 'local',
        ?Model $attachable = null,
        array $metadata = [],
    ): Media {
        $normalizedCollection = str($collection)->trim()->lower()->slug()->toString() ?: 'library';
        $directory = 'media/'.$normalizedCollection;
        $storedPath = $file->store($directory, $disk);

        return Media::query()->create([
            'uploaded_by' => $user->id,
            'attachable_type' => $attachable?->getMorphClass(),
            'attachable_id' => $attachable?->getKey(),
            'collection' => $normalizedCollection,
            'disk' => $disk,
            'directory' => $directory,
            'path' => $storedPath,
            'original_name' => $file->getClientOriginalName(),
            'stored_name' => basename($storedPath),
            'extension' => $file->getClientOriginalExtension() ?: null,
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'metadata' => [
                'generated_name' => $file->hashName(),
                ...$metadata,
            ],
        ]);
    }
}
