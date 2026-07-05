<?php

declare(strict_types=1);

namespace App\Service\Exception;

use RuntimeException;

use function sprintf;

final class BookNotFoundException extends RuntimeException
{
    public function __construct(string $serialNumber)
    {
        parent::__construct(sprintf('Book with serial number "%s" was not found.', $serialNumber));
    }
}
