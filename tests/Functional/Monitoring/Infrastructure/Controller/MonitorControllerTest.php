<?php

declare(strict_types=1);

namespace App\Tests\Functional\Monitoring\Infrastructure\Controller;

use App\Monitoring\Domain\Model\Monitor\MonitorId;
use App\Monitoring\Domain\Repository\MonitorRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\ResetDatabase;

final class MonitorControllerTest extends WebTestCase
{
    use ResetDatabase;

    private $client;
    private $user;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        $container = static::getContainer();
        $userRepository = $container->get(\App\Security\Infrastructure\Repository\UserRepository::class);

        $this->user = new \App\Security\Domain\Entity\User('test@example.com');
        $userRepository->save($this->user);

        $this->client->loginUser($this->user);
    }

    public function testCreateMonitorReturnsAcceptedResponse(): void
    {
        $payload = [
            'name' => 'Test Monitor',
            'url' => 'https://example.com',
            'method' => 'GET',
            'intervalSeconds' => 60,
            'timeoutSeconds' => 10,
            'expectedStatusCode' => 200,
        ];

        $this->client->request('POST', '/api/monitors', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($payload));

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(202);

        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('message', $response);
        $this->assertArrayHasKey('data', $response);
        $this->assertArrayHasKey('monitorId', $response['data']);
        $this->assertSame('Monitor creation request accepted', $response['message']);
    }

    public function testCreateMonitorWithAllFields(): void
    {
        $payload = [
            'name' => 'Full Monitor',
            'url' => 'https://api.example.com/v1/health',
            'method' => 'POST',
            'intervalSeconds' => 120,
            'timeoutSeconds' => 30,
            'expectedStatusCode' => 201,
            'headers' => ['Authorization' => 'Bearer token'],
            'body' => '{"test": "data"}',
        ];

        $this->client->request('POST', '/api/monitors', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($payload));

        $this->assertResponseStatusCodeSame(202);

        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('monitorId', $response['data']);
    }

    public function testCreateMonitorReturnsValidationErrorForMissingRequiredFields(): void
    {
        $payload = [
            'name' => 'Test Monitor',
            // Missing url, method, intervalSeconds, timeoutSeconds, expectedStatusCode
        ];

        $this->client->request('POST', '/api/monitors', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($payload));

        $this->assertResponseStatusCodeSame(422);

        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('error', $response);
    }

    public function testCreateMonitorReturnsValidationErrorForInvalidUrl(): void
    {
        $payload = [
            'name' => 'Test Monitor',
            'url' => 'not-a-valid-url',
            'method' => 'GET',
            'intervalSeconds' => 60,
            'timeoutSeconds' => 10,
            'expectedStatusCode' => 200,
        ];

        $this->client->request('POST', '/api/monitors', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($payload));

        $this->assertResponseStatusCodeSame(422);

        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('error', $response);
    }

    public function testCreateMonitorReturnsValidationErrorForInvalidInterval(): void
    {
        $payload = [
            'name' => 'Test Monitor',
            'url' => 'https://example.com',
            'method' => 'GET',
            'intervalSeconds' => -1,
            'timeoutSeconds' => 10,
            'expectedStatusCode' => 200,
        ];

        $this->client->request('POST', '/api/monitors', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($payload));

        $this->assertResponseStatusCodeSame(422);
    }

    public function testCreateMonitorReturnsValidationErrorForInvalidStatusCode(): void
    {
        $payload = [
            'name' => 'Test Monitor',
            'url' => 'https://example.com',
            'method' => 'GET',
            'intervalSeconds' => 60,
            'timeoutSeconds' => 10,
            'expectedStatusCode' => 99,
        ];

        $this->client->request('POST', '/api/monitors', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($payload));

        $this->assertResponseStatusCodeSame(422);
    }

    public function testCreateMonitorReturnsValidationErrorForInvalidMethod(): void
    {
        $payload = [
            'name' => 'Test Monitor',
            'url' => 'https://example.com',
            'method' => 'INVALID',
            'intervalSeconds' => 60,
            'timeoutSeconds' => 10,
            'expectedStatusCode' => 200,
        ];

        $this->client->request('POST', '/api/monitors', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($payload));

        $this->assertResponseStatusCodeSame(422);
    }

    public function testUpdateMonitorReturnsAcceptedResponse(): void
    {
        $container = static::getContainer();
        $repository = $container->get(MonitorRepositoryInterface::class);

        $monitorId = MonitorId::generate();

        // Create a monitor first
        $monitor = new \App\Monitoring\Domain\Model\Monitor\Monitor(
            id: $monitorId,
            name: 'Original Name',
            url: \App\Monitoring\Domain\Model\Monitor\Url::fromString('https://example.com'),
            method: \App\Monitoring\Domain\Model\Monitor\HttpMethod::GET,
            intervalSeconds: 60,
            timeoutSeconds: 10,
            status: \App\Monitoring\Domain\Model\Monitor\MonitorStatus::ACTIVE,
            expectedStatusCode: 200,
            headers: null,
            body: null,
            lastCheckedAt: null,
            nextCheckAt: new \DateTimeImmutable('+60 seconds'),
            createdAt: new \DateTimeImmutable(),
            updatedAt: new \DateTimeImmutable(),
            ownerId: \App\Monitoring\Domain\ValueObject\OwnerId::fromString($this->user->getId()->toRfc4122())
        );

        $repository->save($monitor);

        $payload = [
            'name' => 'Updated Name',
        ];

        $this->client->request('PUT', '/api/monitors/' . $monitorId->toString(), [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($payload));

        $this->assertResponseStatusCodeSame(202);

        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('message', $response);
        $this->assertArrayHasKey('data', $response);
        $this->assertArrayHasKey('monitorId', $response['data']);
        $this->assertSame('Monitor update request accepted', $response['message']);
    }

    public function testUpdateMonitorWithAllFields(): void
    {
        $container = static::getContainer();
        $repository = $container->get(MonitorRepositoryInterface::class);

        $monitorId = MonitorId::generate();

        $monitor = new \App\Monitoring\Domain\Model\Monitor\Monitor(
            id: $monitorId,
            name: 'Original Name',
            url: \App\Monitoring\Domain\Model\Monitor\Url::fromString('https://example.com'),
            method: \App\Monitoring\Domain\Model\Monitor\HttpMethod::GET,
            intervalSeconds: 60,
            timeoutSeconds: 10,
            status: \App\Monitoring\Domain\Model\Monitor\MonitorStatus::ACTIVE,
            expectedStatusCode: 200,
            headers: null,
            body: null,
            lastCheckedAt: null,
            nextCheckAt: new \DateTimeImmutable('+60 seconds'),
            createdAt: new \DateTimeImmutable(),
            updatedAt: new \DateTimeImmutable(),
            ownerId: \App\Monitoring\Domain\ValueObject\OwnerId::fromString($this->user->getId()->toRfc4122())
        );

        $repository->save($monitor);

        $payload = [
            'name' => 'Fully Updated',
            'url' => 'https://updated.com',
            'method' => 'POST',
            'intervalSeconds' => 120,
            'timeoutSeconds' => 20,
            'expectedStatusCode' => 201,
            'headers' => ['X-Custom' => 'value'],
            'body' => '{"updated": true}',
        ];

        $this->client->request('PUT', '/api/monitors/' . $monitorId->toString(), [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($payload));

        $this->assertResponseStatusCodeSame(202);
    }

    public function testUpdateMonitorReturnsValidationErrorForInvalidUrl(): void
    {
        $container = static::getContainer();
        $repository = $container->get(MonitorRepositoryInterface::class);

        $monitorId = MonitorId::generate();

        $monitor = new \App\Monitoring\Domain\Model\Monitor\Monitor(
            id: $monitorId,
            name: 'Original Name',
            url: \App\Monitoring\Domain\Model\Monitor\Url::fromString('https://example.com'),
            method: \App\Monitoring\Domain\Model\Monitor\HttpMethod::GET,
            intervalSeconds: 60,
            timeoutSeconds: 10,
            status: \App\Monitoring\Domain\Model\Monitor\MonitorStatus::ACTIVE,
            expectedStatusCode: 200,
            headers: null,
            body: null,
            lastCheckedAt: null,
            nextCheckAt: new \DateTimeImmutable('+60 seconds'),
            createdAt: new \DateTimeImmutable(),
            updatedAt: new \DateTimeImmutable(),
            ownerId: \App\Monitoring\Domain\ValueObject\OwnerId::fromString($this->user->getId()->toRfc4122())
        );

        $repository->save($monitor);

        $payload = [
            'url' => 'not-a-valid-url',
        ];

        $this->client->request('PUT', '/api/monitors/' . $monitorId->toString(), [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($payload));

        $this->assertResponseStatusCodeSame(422);
    }

    public function testCreateMonitorWithMinimalValidData(): void
    {
        $payload = [
            'name' => 'Minimal Monitor',
            'url' => 'https://example.com',
            'method' => 'HEAD',
            'intervalSeconds' => 30,
            'timeoutSeconds' => 5,
            'expectedStatusCode' => 200,
        ];

        $this->client->request('POST', '/api/monitors', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($payload));

        $this->assertResponseStatusCodeSame(202);
    }
}
