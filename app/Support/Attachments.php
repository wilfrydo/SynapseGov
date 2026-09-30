<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Evidence files (report/complaint/comment attachments) live on the private "local" disk
 * (storage/app/attachments/...), so they are only reachable through the authorized file routes,
 * never through the public /storage symlink.
 *
 * Stored values keep the old shape ("attachments/reports/<hash>.jpg"), and files uploaded before
 * this change (storage/app/public/attachments/...) are still found by path().
 */
class Attachments
{
    public const DISK = 'local';

    public static function store(UploadedFile $file, string $folder): string
    {
        return $file->store('attachments/'.$folder, self::DISK);
    }

    /**
     * Absolute path of a stored attachment, or null if it is missing or looks like traversal.
     */
    public static function path(?string $stored): ?string
    {
        $normalized = ltrim(str_replace('\\', '/', (string) $stored), '/');

        if ($normalized === '' || str_contains($normalized, '..')) {
            return null;
        }

        $relative = str_starts_with($normalized, 'public/') ? substr($normalized, 7) : $normalized;

        foreach ([storage_path('app/'.$relative), storage_path('app/public/'.$relative)] as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    public static function delete(?string $stored): void
    {
        $normalized = str_replace('\\', '/', (string) $stored);
        $relative = str_starts_with($normalized, 'public/') ? substr($normalized, 7) : $normalized;

        if ($relative === '' || str_contains($relative, '..')) {
            return;
        }

        Storage::disk(self::DISK)->delete($relative);
        Storage::disk('public')->delete($relative);
    }
}
