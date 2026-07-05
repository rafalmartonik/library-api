<?php

declare(strict_types=1);

namespace App\Dto;

use App\Entity\Book;
use JsonSerializable;

use function array_map;

use const DATE_ATOM;

final class BookView implements JsonSerializable
{
    public function __construct(
        public string $serialNumber,
        public string $title,
        public string $author,
        public bool $borrowed,
        public ?string $borrowedByCardNumber,
        public ?string $borrowedAt,
    ) {
    }

    public static function fromEntity(Book $book): self
    {
        return new self(
            $book->getSerialNumber(),
            $book->getTitle(),
            $book->getAuthor(),
            $book->isBorrowed(),
            $book->getBorrowedByCardNumber(),
            $book->getBorrowedAt()?->format(DATE_ATOM),
        );
    }

    /**
     * @param Book[] $books
     *
     * @return self[]
     */
    public static function fromEntities(array $books): array
    {
        return array_map(self::fromEntity(...), $books);
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'serialNumber' => $this->serialNumber,
            'title' => $this->title,
            'author' => $this->author,
            'borrowed' => $this->borrowed,
            'borrowedByCardNumber' => $this->borrowedByCardNumber,
            'borrowedAt' => $this->borrowedAt,
        ];
    }
}
