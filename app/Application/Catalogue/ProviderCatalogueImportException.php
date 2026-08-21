<?php

namespace App\Application\Catalogue;

use RuntimeException;

final class ProviderCatalogueImportException extends RuntimeException
{
    public function __construct(public readonly string $errorCode, string $message)
    {
        parent::__construct($message);
    }
}
