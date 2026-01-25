<?php

declare(strict_types=1);

namespace App\Monitoring\Application\Dto;

use App\Monitoring\Domain\Model\Monitor\HttpMethod;
use Symfony\Component\Validator\Constraints as Assert;

class CreateMonitorRequestDto
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 3, max: 255)]
        public string $name,

        #[Assert\NotBlank]
        #[Assert\Url]
        public string $url,

        #[Assert\NotNull]
        #[Assert\Choice(callback: [HttpMethod::class, 'getValues'])]
        public string $method,

        #[Assert\NotNull]
        #[Assert\Positive]
        public int $intervalSeconds,

        #[Assert\NotNull]
        #[Assert\Positive]
        public int $timeoutSeconds,

        #[Assert\NotNull]
        #[Assert\Range(min: 100, max: 599)]
        public int $expectedStatusCode,

        #[Assert\Valid]
        public ?array $headers = null,

        public ?string $body = null
    ) {
    }
}
