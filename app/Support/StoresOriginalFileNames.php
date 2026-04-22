<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;

trait StoresOriginalFileNames
{
    protected function storePublicFileWithOriginalName(UploadedFile $file, string $directory): string
    {
        return $file->storeAs($directory, $this->sanitizeUploadedFileName($file), config('filesystems.default'));
    }

    protected function sanitizeUploadedFileName(UploadedFile $file): string
    {
        // Keep the original filename, only strip path separators for safety.
        $name = trim(str_replace(['\\', '/'], '-', $file->getClientOriginalName()));

        if ($name === '' || $name === '.' || $name === '..') {
            return $file->hashName();
        }

        return $name;
    }
}
