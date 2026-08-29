<?php

namespace App\Application\Events;

final readonly class StoredEventArtwork
{
    public function __construct(
        public string $disk,
        public string $path,
        public string $url,
    ) {}
}
