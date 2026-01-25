<?php

declare(strict_types=1);

namespace App\Monitoring\Domain\Model\Monitor;

enum HttpMethod: string
{
    case GET = 'GET';
    case POST = 'POST';
    case HEAD = 'HEAD';
    case PUT = 'PUT';
}
