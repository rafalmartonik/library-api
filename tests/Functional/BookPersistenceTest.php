<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Book;
use App\Repository\BookRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class BookPersistenceTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private BookRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->repository = $container->get(BookRepository::class);

        // Clean slate so each test is independent.
        $this->entityManager->getConnection()->executeStatement('DELETE FROM books');
    }

    public function testBookIsPersistedAndFoundBySerialNumber(): void
    {
        $book = new Book('123456', 'Refactoring', 'Martin Fowler');
        $this->entityManager->persist($book);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $found = $this->repository->findOneBySerialNumber('123456');

        self::assertNotNull($found);
        self::assertNotSame($book, $found, 'the entity should be reloaded from the database');
        self::assertNotNull($found->getId());
        self::assertSame('123456', $found->getSerialNumber());
        self::assertSame('Refactoring', $found->getTitle());
        self::assertSame('Martin Fowler', $found->getAuthor());
        self::assertFalse($found->isBorrowed());
    }

    public function testBorrowStateIsPersisted(): void
    {
        $book = new Book('123456', 'Refactoring', 'Martin Fowler');
        $book->borrow('654321', new DateTimeImmutable('2026-07-05 10:00:00'));
        $this->entityManager->persist($book);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $found = $this->repository->findOneBySerialNumber('123456');

        self::assertNotNull($found);
        self::assertTrue($found->isBorrowed());
        self::assertSame('654321', $found->getBorrowedByCardNumber());
        self::assertNotNull($found->getBorrowedAt());
        self::assertSame('2026-07-05 10:00:00', $found->getBorrowedAt()->format('Y-m-d H:i:s'));
    }

    public function testFindOneBySerialNumberReturnsNullWhenMissing(): void
    {
        self::assertNull($this->repository->findOneBySerialNumber('000000'));
    }

    public function testSerialNumberIsUniqueAtDatabaseLevel(): void
    {
        $this->entityManager->persist(new Book('123456', 'Refactoring', 'Martin Fowler'));
        $this->entityManager->flush();

        $this->entityManager->persist(new Book('123456', 'Clean Code', 'Robert C. Martin'));

        $this->expectException(UniqueConstraintViolationException::class);
        $this->entityManager->flush();
    }
}
