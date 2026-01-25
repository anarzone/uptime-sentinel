<?php

declare(strict_types=1);

namespace App\Monitoring\Domain\Service;

use App\Monitoring\Application\Dto\CheckResultDto;
use App\Monitoring\Domain\Model\Monitor\Monitor;

/**
 * Interface for performing HTTP checks on monitors.
 *
 * Implementation lives in Infrastructure (using Symfony HttpClient).
 */
interface UrlCheckerInterface
{
    public function check(Monitor $monitor): CheckResultDto;

    /**
     * @param Monitor[] $monitors
     *
     * @return iterable<CheckResultDto>
     */
    public function checkBatch(array $monitors): iterable;
}
