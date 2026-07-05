<?php

declare(strict_types=1);

namespace App\Tests\Acceptance;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

use function array_column;
use function json_decode;
use function json_encode;

use const JSON_THROW_ON_ERROR;

final class BookApiTest extends WebTestCase
{
    private const ENDPOINT = '/api/books';

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = self::createClient();

        // Clean slate so each test is independent.
        self::getContainer()->get(EntityManagerInterface::class)
            ->getConnection()
            ->executeStatement('DELETE FROM books');
    }

    public function testListIsEmptyInitially(): void
    {
        $this->client->request('GET', self::ENDPOINT);

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        self::assertSame([], $this->decode());
    }

    public function testCreateReturnsCreatedBook(): void
    {
        $this->post(self::ENDPOINT, ['serialNumber' => '123456', 'title' => 'Solaris', 'author' => 'Lem']);

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        self::assertSame(
            [
                'serialNumber' => '123456',
                'title' => 'Solaris',
                'author' => 'Lem',
                'borrowed' => false,
                'borrowedByCardNumber' => null,
                'borrowedAt' => null,
            ],
            $this->decode(),
        );
    }

    public function testCreateDuplicateSerialReturnsConflict(): void
    {
        $this->post(self::ENDPOINT, ['serialNumber' => '123456', 'title' => 'Solaris', 'author' => 'Lem']);
        $this->post(self::ENDPOINT, ['serialNumber' => '123456', 'title' => 'Dune', 'author' => 'Herbert']);

        self::assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
        self::assertArrayHasKey('error', $this->decode());
    }

    public function testCreateInvalidSerialReturnsValidationErrors(): void
    {
        $this->post(self::ENDPOINT, ['serialNumber' => 'abc', 'title' => 'Solaris', 'author' => 'Lem']);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertArrayHasKey('serialNumber', $this->decode()['errors']);
    }

    public function testCreateBlankFieldsReturnsValidationErrors(): void
    {
        $this->post(self::ENDPOINT, ['serialNumber' => '123456', 'title' => '', 'author' => '']);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $errors = $this->decode()['errors'];
        self::assertArrayHasKey('title', $errors);
        self::assertArrayHasKey('author', $errors);
    }

    public function testListReturnsBooksSortedByTitle(): void
    {
        $this->post(self::ENDPOINT, ['serialNumber' => '222222', 'title' => 'Zzz', 'author' => 'Author B']);
        $this->post(self::ENDPOINT, ['serialNumber' => '111111', 'title' => 'Aaa', 'author' => 'Author A']);

        $this->client->request('GET', self::ENDPOINT);

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        $titles = array_column($this->decode(), 'title');
        self::assertSame(['Aaa', 'Zzz'], $titles);
    }

    public function testBorrowUpdatesState(): void
    {
        $this->post(self::ENDPOINT, ['serialNumber' => '123456', 'title' => 'Solaris', 'author' => 'Lem']);

        $this->patch(self::ENDPOINT . '/123456', ['borrowed' => true, 'cardNumber' => '654321']);

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        $view = $this->decode();
        self::assertTrue($view['borrowed']);
        self::assertSame('654321', $view['borrowedByCardNumber']);
        self::assertNotNull($view['borrowedAt']);
    }

    public function testBorrowAlreadyBorrowedReturnsConflict(): void
    {
        $this->post(self::ENDPOINT, ['serialNumber' => '123456', 'title' => 'Solaris', 'author' => 'Lem']);
        $this->patch(self::ENDPOINT . '/123456', ['borrowed' => true, 'cardNumber' => '654321']);

        $this->patch(self::ENDPOINT . '/123456', ['borrowed' => true, 'cardNumber' => '111111']);

        self::assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
    }

    public function testBorrowWithoutCardNumberReturnsValidationError(): void
    {
        $this->post(self::ENDPOINT, ['serialNumber' => '123456', 'title' => 'Solaris', 'author' => 'Lem']);

        $this->patch(self::ENDPOINT . '/123456', ['borrowed' => true]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertArrayHasKey('cardNumber', $this->decode()['errors']);
    }

    public function testBorrowWithBadCardFormatReturnsValidationError(): void
    {
        $this->post(self::ENDPOINT, ['serialNumber' => '123456', 'title' => 'Solaris', 'author' => 'Lem']);

        $this->patch(self::ENDPOINT . '/123456', ['borrowed' => true, 'cardNumber' => 'xx']);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertArrayHasKey('cardNumber', $this->decode()['errors']);
    }

    public function testReturnClearsBorrowState(): void
    {
        $this->post(self::ENDPOINT, ['serialNumber' => '123456', 'title' => 'Solaris', 'author' => 'Lem']);
        $this->patch(self::ENDPOINT . '/123456', ['borrowed' => true, 'cardNumber' => '654321']);

        $this->patch(self::ENDPOINT . '/123456', ['borrowed' => false]);

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        $view = $this->decode();
        self::assertFalse($view['borrowed']);
        self::assertNull($view['borrowedByCardNumber']);
        self::assertNull($view['borrowedAt']);
    }

    public function testReturnNotBorrowedReturnsConflict(): void
    {
        $this->post(self::ENDPOINT, ['serialNumber' => '123456', 'title' => 'Solaris', 'author' => 'Lem']);

        $this->patch(self::ENDPOINT . '/123456', ['borrowed' => false]);

        self::assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
    }

    public function testUpdateStatusUnknownBookReturnsNotFound(): void
    {
        $this->patch(self::ENDPOINT . '/999999', ['borrowed' => false]);

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testDeleteRemovesBook(): void
    {
        $this->post(self::ENDPOINT, ['serialNumber' => '123456', 'title' => 'Solaris', 'author' => 'Lem']);

        $this->client->request('DELETE', self::ENDPOINT . '/123456');

        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        $this->client->request('GET', self::ENDPOINT);
        self::assertSame([], $this->decode());
    }

    public function testDeleteUnknownBookReturnsNotFound(): void
    {
        $this->client->request('DELETE', self::ENDPOINT . '/999999');

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function post(string $uri, array $payload): void
    {
        $this->json('POST', $uri, $payload);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function patch(string $uri, array $payload): void
    {
        $this->json('PATCH', $uri, $payload);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function json(string $method, string $uri, array $payload): void
    {
        $this->client->request(
            $method,
            $uri,
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($payload, JSON_THROW_ON_ERROR),
        );
    }

    /**
     * @return array<array-key, mixed>
     */
    private function decode(): array
    {
        return json_decode((string) $this->client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
    }
}
