<?php

declare(strict_types=1);

namespace App\Monitoring\Domain\Service;

use App\Monitoring\Application\Dto\CheckResultDto;

/**
 * Interface for buffering check results before bulk ingestion.
 *
 * Implementation lives in Infrastructure (using Redis).
 */
interface TelemetryBufferInterface
{
    public function push(CheckResultDto $result): void;
}
