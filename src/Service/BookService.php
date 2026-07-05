<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Book;
use App\Repository\BookRepository;
use App\Service\Exception\BookNotFoundException;
use App\Service\Exception\DuplicateSerialNumberException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;

class BookService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly BookRepository $books,
    ) {
    }

    /**
     * @return Book[]
     */
    public function list(): array
    {
        return $this->books->findBy([], ['title' => 'ASC']);
    }

    public function add(string $serialNumber, string $title, string $author): Book
    {
        if ($this->books->findOneBySerialNumber($serialNumber) !== null) {
            throw new DuplicateSerialNumberException($serialNumber);
        }

        $book = new Book($serialNumber, $title, $author);
        $this->entityManager->persist($book);

        try {
            $this->entityManager->flush();
        } catch (UniqueConstraintViolationException) {
            throw new DuplicateSerialNumberException($serialNumber);
        }

        return $book;
    }

    public function remove(string $serialNumber): void
    {
        $book = $this->getOrFail($serialNumber);
        $this->entityManager->remove($book);
        $this->entityManager->flush();
    }

    public function borrow(string $serialNumber, string $cardNumber): Book
    {
        $book = $this->getOrFail($serialNumber);
        $book->borrow($cardNumber);
        $this->entityManager->flush();

        return $book;
    }

    public function returnBook(string $serialNumber): Book
    {
        $book = $this->getOrFail($serialNumber);
        $book->returnBook();
        $this->entityManager->flush();

        return $book;
    }

    private function getOrFail(string $serialNumber): Book
    {
        $book = $this->books->findOneBySerialNumber($serialNumber);
        if ($book === null) {
            throw new BookNotFoundException($serialNumber);
        }

        return $book;
    }
}
