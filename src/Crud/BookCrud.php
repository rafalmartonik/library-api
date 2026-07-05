<?php

declare(strict_types=1);

namespace App\Crud;

use App\Dto\BookView;
use App\Dto\CreateBookRequest;
use App\Dto\UpdateBookStatusRequest;
use App\Entity\Book;
use App\Service\BookService;
use App\Service\Exception\BookNotFoundException;
use App\Service\Exception\DuplicateSerialNumberException;
use DomainException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validator\ValidatorInterface;

use function assert;
use function count;
use function is_array;
use function json_decode;

use const JSON_THROW_ON_ERROR;

final class BookCrud
{
    public function __construct(
        private readonly BookService $books,
        private readonly ValidatorInterface $validator,
    ) {
    }

    public function list(): JsonResponse
    {
        return new JsonResponse(BookView::fromEntities($this->books->list()));
    }

    public function create(Request $request): JsonResponse
    {
        $dto = CreateBookRequest::fromArray($this->decode($request));
        $errors = $this->validate($dto);
        if ($errors !== null) {
            return $errors;
        }

        try {
            $book = $this->books->add($dto->serialNumber, $dto->title, $dto->author);
        } catch (DuplicateSerialNumberException $e) {
            return $this->error($e->getMessage(), Response::HTTP_CONFLICT);
        }

        return new JsonResponse(BookView::fromEntity($book), Response::HTTP_CREATED);
    }

    public function updateStatus(string $serialNumber, Request $request): JsonResponse
    {
        $dto = UpdateBookStatusRequest::fromArray($this->decode($request));
        $errors = $this->validate($dto);
        if ($errors !== null) {
            return $errors;
        }

        try {
            $book = $this->applyStatus($serialNumber, $dto);
        } catch (BookNotFoundException $e) {
            return $this->error($e->getMessage(), Response::HTTP_NOT_FOUND);
        } catch (DomainException $e) {
            return $this->error($e->getMessage(), Response::HTTP_CONFLICT);
        }

        return new JsonResponse(BookView::fromEntity($book));
    }

    public function delete(string $serialNumber): JsonResponse
    {
        try {
            $this->books->remove($serialNumber);
        } catch (BookNotFoundException $e) {
            return $this->error($e->getMessage(), Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    private function applyStatus(string $serialNumber, UpdateBookStatusRequest $dto): Book
    {
        if ($dto->borrowed !== true) {
            return $this->books->returnBook($serialNumber);
        }

        // A borrow request always carries a card number (enforced by UpdateBookStatusRequest).
        assert($dto->cardNumber !== null);

        return $this->books->borrow($serialNumber, $dto->cardNumber);
    }

    /**
     * @return array<array-key, mixed>
     */
    private function decode(Request $request): array
    {
        $data = json_decode($request->getContent(), true, flags: JSON_THROW_ON_ERROR);

        return is_array($data) ? $data : [];
    }

    private function validate(object $dto): ?JsonResponse
    {
        $violations = $this->validator->validate($dto);
        if (count($violations) === 0) {
            return null;
        }

        $errors = [];
        foreach ($violations as $violation) {
            $errors[$violation->getPropertyPath()] = (string) $violation->getMessage();
        }

        return new JsonResponse(['errors' => $errors], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    private function error(string $message, int $status): JsonResponse
    {
        return new JsonResponse(['error' => $message], $status);
    }
}
