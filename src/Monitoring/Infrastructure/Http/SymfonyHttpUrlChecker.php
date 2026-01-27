<?php

declare(strict_types=1);

namespace App\Monitoring\Infrastructure\Http;

use App\Monitoring\Application\Dto\CheckResultDto;
use App\Monitoring\Domain\Model\Monitor\Monitor;
use App\Monitoring\Domain\Service\UrlCheckerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class SymfonyHttpUrlChecker implements UrlCheckerInterface
{
    public function __construct(
        private HttpClientInterface $httpClient,
    ) {
    }

    public function check(Monitor $monitor): CheckResultDto
    {
        $startTime = microtime(true);
        $checkedAt = new \DateTimeImmutable();

        try {
            $response = $this->httpClient->request(
                $monitor->method->value,
                $monitor->url->toString(),
                [
                    'timeout' => $monitor->timeoutSeconds,
                    'headers' => $monitor->headers ?? [],
                ]
            );

            $statusCode = $response->getStatusCode();
            $latencyMs = (int) ((microtime(true) - $startTime) * 1000);
            $isSuccess = $statusCode === $monitor->expectedStatusCode;
        } catch (\Throwable) {
            $statusCode = 0;
            $latencyMs = (int) ((microtime(true) - $startTime) * 1000);
            $isSuccess = false;
        }

        return new CheckResultDto(
            monitorId: $monitor->id->toString(),
            statusCode: $statusCode,
            latencyMs: $latencyMs,
            isSuccess: $isSuccess,
            checkedAt: $checkedAt,
        );
    }

    public function checkBatch(array $monitors): iterable
    {
        // 💡 Refactored to a simpler loop to avoid internal HttpClient 'stream'
        // destructor issues in CLI/Docker environments.
        // Todo: Try stream again
        foreach ($monitors as $monitor) {
            yield $this->check($monitor);
        }
    }
}
