<?php

namespace App\Traits;

use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

trait Fileable
{
    /**
     * Upload and save file in storage.
     *
     * @return string The stored file name with extension
     */
    public function uploadFile(
        UploadedFile $file,
        string $storagePath,
        ?string $fileName = null
    ): string {
        if ($fileName) {
            $fileExtension = $file->getClientOriginalExtension();
            $fileNameWithExtension = Str::slug($fileName) . '.' . $fileExtension;

            $filePathInStorage = Storage::putFileAs($storagePath, $file, $fileNameWithExtension);
        } else {
            // Generates Unique File Name
            $filePathInStorage = Storage::putFile($storagePath, $file);
        }

        return Str::remove($storagePath . '/', $filePathInStorage);
    }

}
