<?php

declare(strict_types=1);

namespace App\Service\Exception;

use RuntimeException;

use function sprintf;

final class DuplicateSerialNumberException extends RuntimeException
{
    public function __construct(string $serialNumber)
    {
        parent::__construct(sprintf('A book with serial number "%s" already exists.', $serialNumber));
    }
}
