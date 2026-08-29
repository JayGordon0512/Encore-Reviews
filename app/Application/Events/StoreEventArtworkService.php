<?php

namespace App\Application\Events;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

final class StoreEventArtworkService
{
    public function store(string $organisationId, UploadedFile $file): StoredEventArtwork
    {
        $disk = (string) config('encore.event_images.disk', 'public');
        $extension = match ($file->getMimeType()) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => throw new RuntimeException('The event artwork format is not supported.'),
        };
        $directory = 'event-artwork/'.$organisationId;
        $filename = Str::uuid().'.'.$extension;
        $path = Storage::disk($disk)->putFileAs($directory, $file, $filename, [
            'visibility' => 'public',
        ]);

        if (! is_string($path) || $path === '') {
            throw new RuntimeException('The event artwork could not be stored.');
        }

        return new StoredEventArtwork($disk, $path, Storage::disk($disk)->url($path));
    }

    public function delete(?string $disk, ?string $path): void
    {
        if (filled($disk) && filled($path)) {
            Storage::disk($disk)->delete($path);
        }
    }
}
