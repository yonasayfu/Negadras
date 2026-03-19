<?php

namespace App\Support;

class SubmissionFileRegistry
{
    /**
     * @return array<string, array{label: string, description: string, required: bool, multiple: bool, accept: string, extensions: list<string>, maxKilobytes: int}>
     */
    public static function definitions(): array
    {
        return [
            'application_pdf' => [
                'label' => 'Application PDF',
                'description' => 'Primary written application or summary document for intake review.',
                'required' => true,
                'multiple' => false,
                'accept' => '.pdf,application/pdf',
                'extensions' => ['pdf'],
                'maxKilobytes' => 10240,
            ],
            'pitch_deck' => [
                'label' => 'Pitch deck',
                'description' => 'Main deck file used for reviewer and judge preparation.',
                'required' => true,
                'multiple' => false,
                'accept' => '.pdf,.ppt,.pptx,application/pdf,application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'extensions' => ['pdf', 'ppt', 'pptx'],
                'maxKilobytes' => 20480,
            ],
            'pitch_video' => [
                'label' => 'Pitch video',
                'description' => 'Optional uploaded pitch video for richer preview and later archive preparation.',
                'required' => false,
                'multiple' => false,
                'accept' => 'video/*',
                'extensions' => ['mp4', 'mov', 'avi', 'mkv', 'webm'],
                'maxKilobytes' => 102400,
            ],
            'gallery_image' => [
                'label' => 'Gallery image',
                'description' => 'Optional screenshots, product photos, or supporting visual evidence.',
                'required' => false,
                'multiple' => true,
                'accept' => 'image/*',
                'extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
                'maxKilobytes' => 10240,
            ],
            'business_document' => [
                'label' => 'Business document',
                'description' => 'Optional financial, legal, or business-supporting material.',
                'required' => false,
                'multiple' => true,
                'accept' => '.pdf,.doc,.docx,.xls,.xlsx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'extensions' => ['pdf', 'doc', 'docx', 'xls', 'xlsx'],
                'maxKilobytes' => 20480,
            ],
        ];
    }

    public static function exists(string $type): bool
    {
        return array_key_exists($type, self::definitions());
    }

    /**
     * @return array{label: string, description: string, required: bool, multiple: bool, accept: string, extensions: list<string>, maxKilobytes: int}
     */
    public static function definition(string $type): array
    {
        return self::definitions()[$type];
    }
}
