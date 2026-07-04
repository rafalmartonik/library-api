<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Book;
use DateTimeImmutable;
use DomainException;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class BookTest extends TestCase
{
    public function testNewBookExposesItsData(): void
    {
        $book = new Book('123456', 'Refactoring', 'Martin Fowler');

        self::assertSame('123456', $book->getSerialNumber());
        self::assertSame('Refactoring', $book->getTitle());
        self::assertSame('Martin Fowler', $book->getAuthor());
    }

    public function testNewBookIsNotBorrowed(): void
    {
        $book = new Book('123456', 'Refactoring', 'Martin Fowler');

        self::assertFalse($book->isBorrowed());
        self::assertNull($book->getBorrowedByCardNumber());
        self::assertNull($book->getBorrowedAt());
    }

    public function testIdIsNullBeforePersistence(): void
    {
        $book = new Book('123456', 'Refactoring', 'Martin Fowler');

        self::assertNull($book->getId());
    }

    #[DataProvider('invalidSixDigitValues')]
    public function testConstructorRejectsInvalidSerialNumber(string $serialNumber): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Book($serialNumber, 'Refactoring', 'Martin Fowler');
    }

    public function testBorrowMarksBookAsBorrowed(): void
    {
        $book = new Book('123456', 'Refactoring', 'Martin Fowler');
        $at = new DateTimeImmutable('2026-07-05 10:00:00');

        $book->borrow('654321', $at);

        self::assertTrue($book->isBorrowed());
        self::assertSame('654321', $book->getBorrowedByCardNumber());
        self::assertSame($at, $book->getBorrowedAt());
    }

    public function testBorrowDefaultsBorrowedAtToNow(): void
    {
        $book = new Book('123456', 'Refactoring', 'Martin Fowler');

        $before = new DateTimeImmutable();
        $book->borrow('654321');
        $after = new DateTimeImmutable();

        $borrowedAt = $book->getBorrowedAt();
        self::assertNotNull($borrowedAt);
        self::assertGreaterThanOrEqual($before, $borrowedAt);
        self::assertLessThanOrEqual($after, $borrowedAt);
    }

    #[DataProvider('invalidSixDigitValues')]
    public function testBorrowRejectsInvalidCardNumber(string $cardNumber): void
    {
        $book = new Book('123456', 'Refactoring', 'Martin Fowler');

        $this->expectException(InvalidArgumentException::class);

        $book->borrow($cardNumber);
    }

    public function testBorrowingAnAlreadyBorrowedBookThrows(): void
    {
        $book = new Book('123456', 'Refactoring', 'Martin Fowler');
        $book->borrow('654321');

        $this->expectException(DomainException::class);

        $book->borrow('111111');
    }

    public function testFailedBorrowDoesNotMutateState(): void
    {
        $book = new Book('123456', 'Refactoring', 'Martin Fowler');

        try {
            $book->borrow('not-6-digits');
        } catch (InvalidArgumentException) {
        }

        self::assertFalse($book->isBorrowed());
        self::assertNull($book->getBorrowedByCardNumber());
        self::assertNull($book->getBorrowedAt());
    }

    public function testReturnBookClearsBorrowState(): void
    {
        $book = new Book('123456', 'Refactoring', 'Martin Fowler');
        $book->borrow('654321');

        $book->returnBook();

        self::assertFalse($book->isBorrowed());
        self::assertNull($book->getBorrowedByCardNumber());
        self::assertNull($book->getBorrowedAt());
    }

    public function testReturningANotBorrowedBookThrows(): void
    {
        $book = new Book('123456', 'Refactoring', 'Martin Fowler');

        $this->expectException(DomainException::class);

        $book->returnBook();
    }

    public function testBookCanBeBorrowedAgainAfterReturn(): void
    {
        $book = new Book('123456', 'Refactoring', 'Martin Fowler');
        $book->borrow('654321');
        $book->returnBook();

        $book->borrow('111111');

        self::assertTrue($book->isBorrowed());
        self::assertSame('111111', $book->getBorrowedByCardNumber());
    }

    public static function invalidSixDigitValues(): iterable
    {
        yield 'too short' => ['12345'];
        yield 'too long' => ['1234567'];
        yield 'contains a letter' => ['12a456'];
        yield 'contains a space' => ['12 456'];
        yield 'empty' => [''];
    }
}
