<?php

declare(strict_types=1);

namespace App\Monitoring\Application\Dto;

use App\Monitoring\Domain\Model\Monitor\HttpMethod;
use Symfony\Component\Validator\Constraints as Assert;

class UpdateMonitorRequestDto
{
    public function __construct(
        #[Assert\Length(min: 3, max: 255)]
        public ?string $name = null,

        #[Assert\Url]
        public ?string $url = null,

        #[Assert\Choice(callback: [HttpMethod::class, 'getValues'])]
        public ?string $method = null,

        #[Assert\Positive]
        public ?int $intervalSeconds = null,

        #[Assert\Positive]
        public ?int $timeoutSeconds = null,

        #[Assert\Range(min: 100, max: 599)]
        public ?int $expectedStatusCode = null,

        #[Assert\Valid]
        public ?array $headers = null,

        public ?string $body = null
    ) {
    }
}
