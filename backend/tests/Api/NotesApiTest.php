<?php

declare(strict_types=1);

namespace App\Notes\Tests\Api;

use GuzzleHttp\Client;
use League\OpenAPIValidation\PSR7\OperationAddress;
use League\OpenAPIValidation\PSR7\ResponseValidator;
use League\OpenAPIValidation\PSR7\ValidatorBuilder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

/**
 * User scenarios against the running stack; every response is checked against docs/openapi.yaml.
 *
 * @phpstan-type NoteJson array{id: int, title: string, content: string, createdAt: string, updatedAt: string}
 * @phpstan-type RequestOptions array{json?: mixed, query?: array<string, int>, body?: string, headers?: array<string, string>}
 */
final class NotesApiTest extends TestCase
{
    private const string MISSING_ID = '4294967295';
    private const array VALID_BODY = ['json' => ['title' => 'Итог', 'content' => '']];

    private static Client $http;
    private static ResponseValidator $contract;

    /** @var list<int> */
    private array $createdIds = [];

    public static function setUpBeforeClass(): void
    {
        self::$http = new Client([
            'base_uri' => getenv('NOTES_BASE_URL') ?: 'http://localhost:8080',
            'http_errors' => false,
        ]);
        self::$contract = (new ValidatorBuilder())
            ->fromYamlFile(dirname(__DIR__, 3) . '/docs/openapi.yaml')
            ->getResponseValidator();
    }

    protected function tearDown(): void
    {
        foreach ($this->createdIds as $id) {
            self::$http->delete("/api/notes/{$id}");
        }
    }

    public function testCreatedNoteCanBeViewed(): void
    {
        $response = $this->call('POST', '/notes', options: ['json' => ['title' => '  Покупки ', 'content' => 'молоко']]);

        self::assertSame(201, $response->getStatusCode());
        $note = $this->note($response);
        $this->createdIds[] = $note['id'];
        self::assertSame(['Покупки', 'молоко'], [$note['title'], $note['content']]);
        self::assertSame("/api/notes/{$note['id']}", $response->getHeaderLine('Location'));

        $view = $this->call('GET', '/notes/{id}', (string) $note['id']);

        self::assertSame(200, $view->getStatusCode());
        self::assertSame($note, $this->note($view));
    }

    public function testListShowsNewestNotesFirstPageByPage(): void
    {
        $older = $this->createNote('Первая');
        $newer = $this->createNote('Вторая');

        $first = $this->decode($this->call('GET', '/notes', options: ['query' => ['limit' => 1]]));
        $second = $this->decode($this->call('GET', '/notes', options: ['query' => ['limit' => 1, 'offset' => 1]]));

        self::assertEquals(['items' => [$this->summary($newer)], 'hasMore' => true], $first);
        self::assertEquals([$this->summary($older)], $second['items']);
    }

    public function testEditedNoteKeepsItsIdentity(): void
    {
        $note = $this->createNote('Черновик');

        $response = $this->call('PUT', '/notes/{id}', (string) $note['id'], ['json' => ['title' => 'Итог', 'content' => 'готово']]);

        self::assertSame(200, $response->getStatusCode());
        $edited = $this->note($response);
        self::assertSame(
            [$note['id'], $note['createdAt'], 'Итог', 'готово'],
            [$edited['id'], $edited['createdAt'], $edited['title'], $edited['content']],
        );
        self::assertSame($edited, $this->note($this->call('GET', '/notes/{id}', (string) $note['id'])));
    }

    public function testDeletedNoteIsGone(): void
    {
        $note = $this->createNote('Лишняя');

        self::assertSame(204, $this->call('DELETE', '/notes/{id}', (string) $note['id'])->getStatusCode());
        self::assertSame(404, $this->call('GET', '/notes/{id}', (string) $note['id'])->getStatusCode());
    }

    /**
     * @return iterable<string, array{0: string, 1: string, 2: RequestOptions, 3: string, 4?: string}>
     */
    public static function invalidRequests(): iterable
    {
        yield 'list with too large a page' => ['GET', '/notes', ['query' => ['limit' => 101]], 'limit'];
        yield 'create with a blank title' => ['POST', '/notes', ['json' => ['title' => ' ', 'content' => '']], 'title'];
        yield 'view by a malformed id' => ['GET', '/notes/{id}', [], 'id', 'abc'];
        yield 'edit by a malformed id' => ['PUT', '/notes/{id}', self::VALID_BODY, 'id', '0'];
        yield 'edit with an unknown field' => [
            'PUT',
            '/notes/{id}',
            ['json' => self::VALID_BODY['json'] + ['createdAt' => '2000-01-01T00:00:00+00:00']],
            'createdAt',
        ];
        yield 'delete by a malformed id' => ['DELETE', '/notes/{id}', [], 'id', '1.5'];
    }

    /**
     * @param RequestOptions $options
     */
    #[DataProvider('invalidRequests')]
    public function testRejectsInvalidDataWithFieldErrors(string $method, string $path, array $options, string $field, ?string $id = null): void
    {
        $id ??= str_contains($path, '{id}') ? (string) $this->createNote('Черновик')['id'] : null;

        $response = $this->call($method, $path, $id, $options);

        self::assertSame(422, $response->getStatusCode());
        $errors = $this->decode($response)['errors'];
        self::assertIsArray($errors);
        self::assertArrayHasKey($field, $errors);
    }

    /**
     * @return iterable<string, array{string, string, RequestOptions}>
     */
    public static function missingNotes(): iterable
    {
        yield 'view' => ['GET', self::MISSING_ID, []];
        yield 'edit' => ['PUT', self::MISSING_ID, self::VALID_BODY];
        yield 'delete' => ['DELETE', self::MISSING_ID, []];
    }

    /**
     * @param RequestOptions $options
     */
    #[DataProvider('missingNotes')]
    public function testReportsMissingNote(string $method, string $id, array $options): void
    {
        self::assertSame(404, $this->call($method, '/notes/{id}', $id, $options)->getStatusCode());
    }

    /**
     * @return iterable<string, array{string, string, int}>
     */
    public static function unreadableBodies(): iterable
    {
        yield 'form instead of JSON' => ['application/x-www-form-urlencoded', 'title=x&content=y', 415];
        yield 'malformed JSON' => ['application/json', '{"title":', 400];
        yield 'JSON that is not an object' => ['application/json', '["title"]', 400];
    }

    #[DataProvider('unreadableBodies')]
    public function testRejectsUnreadableBody(string $contentType, string $body, int $status): void
    {
        $response = $this->call('POST', '/notes', options: ['headers' => ['Content-Type' => $contentType], 'body' => $body]);

        self::assertSame($status, $response->getStatusCode());
    }

    /**
     * Sends a request to the operation at $path (an OpenAPI path template) and checks the response against the contract.
     *
     * @param RequestOptions $options
     */
    private function call(string $method, string $path, ?string $id = null, array $options = []): ResponseInterface
    {
        $uri = '/api' . str_replace('{id}', (string) $id, $path);
        $response = self::$http->request($method, $uri, $options);
        self::$contract->validate(new OperationAddress($path, strtolower($method)), $response);

        return $response;
    }

    /**
     * @return NoteJson
     */
    private function createNote(string $title): array
    {
        $note = $this->note($this->call('POST', '/notes', options: ['json' => ['title' => $title, 'content' => 'текст']]));
        $this->createdIds[] = $note['id'];

        return $note;
    }

    /**
     * @return NoteJson
     */
    private function note(ResponseInterface $response): array
    {
        /** @var NoteJson */
        return $this->decode($response);
    }

    /**
     * @param NoteJson $note
     * @return array<string, int|string>
     */
    private function summary(array $note): array
    {
        unset($note['content']);

        return $note;
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(ResponseInterface $response): array
    {
        $data = json_decode((string) $response->getBody(), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($data);

        /** @var array<string, mixed> $data */
        return $data;
    }
}
