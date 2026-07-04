<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\BookRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use DomainException;
use InvalidArgumentException;

#[ORM\Entity(repositoryClass: BookRepository::class)]
#[ORM\Table(name: 'books')]
#[ORM\UniqueConstraint(name: 'uniq_books_serial_number', columns: ['serial_number'])]
class Book
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 6, unique: true)]
    private string $serialNumber;

    #[ORM\Column(length: 255)]
    private string $title;

    #[ORM\Column(length: 255)]
    private string $author;

    #[ORM\Column(length: 6, nullable: true)]
    private ?string $borrowedByCardNumber = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $borrowedAt = null;

    public function __construct(string $serialNumber, string $title, string $author)
    {
        self::assertSixDigits($serialNumber, 'Serial number');

        $this->serialNumber = $serialNumber;
        $this->title = $title;
        $this->author = $author;
    }

    public function borrow(string $cardNumber, ?DateTimeImmutable $at = null): void
    {
        self::assertSixDigits($cardNumber, 'Library card number');

        if ($this->isBorrowed()) {
            throw new DomainException(sprintf('Book "%s" is already borrowed.', $this->serialNumber));
        }

        $this->borrowedByCardNumber = $cardNumber;
        $this->borrowedAt = $at ?? new DateTimeImmutable();
    }

    public function returnBook(): void
    {
        if (!$this->isBorrowed()) {
            throw new DomainException(sprintf('Book "%s" is not borrowed.', $this->serialNumber));
        }

        $this->borrowedByCardNumber = null;
        $this->borrowedAt = null;
    }

    public function isBorrowed(): bool
    {
        return $this->borrowedByCardNumber !== null;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSerialNumber(): string
    {
        return $this->serialNumber;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getAuthor(): string
    {
        return $this->author;
    }

    public function getBorrowedByCardNumber(): ?string
    {
        return $this->borrowedByCardNumber;
    }

    public function getBorrowedAt(): ?DateTimeImmutable
    {
        return $this->borrowedAt;
    }

    private static function assertSixDigits(string $value, string $field): void
    {
        if (preg_match('/^\d{6}$/', $value) !== 1) {
            throw new InvalidArgumentException(sprintf('%s must be a 6-digit number.', $field));
        }
    }
}
