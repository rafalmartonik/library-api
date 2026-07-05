<?php

declare(strict_types=1);

namespace App\Tests\Acceptance;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

use function json_decode;

use const JSON_THROW_ON_ERROR;

final class ApiDocTest extends WebTestCase
{
    public function testSwaggerUiPageIsServed(): void
    {
        $client = self::createClient();
        $client->request('GET', '/api/doc');

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('swagger-ui', (string) $client->getResponse()->getContent());
    }

    public function testOpenApiSpecIsServed(): void
    {
        $client = self::createClient();
        $client->request('GET', '/api/openapi.json');

        self::assertResponseIsSuccessful();

        $spec = json_decode((string) $client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame('3.0.3', $spec['openapi']);
        self::assertArrayHasKey('/api/books', $spec['paths']);
    }
}
