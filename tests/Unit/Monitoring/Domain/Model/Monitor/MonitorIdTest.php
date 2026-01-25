<?php

declare(strict_types=1);

namespace App\Tests\Unit\Monitoring\Domain\Model\Monitor;

use App\Monitoring\Domain\Model\Monitor\MonitorId;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\UuidV7;

final class MonitorIdTest extends TestCase
{
    public function test_can_be_created_from_valid_uuid_v7(): void
    {
        $validUuid = (new UuidV7())->toRfc4122();
        $monitorId = MonitorId::fromString($validUuid);

        $this->assertSame($validUuid, $monitorId->value);
        $this->assertSame($validUuid, $monitorId->toString());
    }

    public function test_can_be_generated(): void
    {
        $monitorId = MonitorId::generate();

        $this->assertInstanceOf(MonitorId::class, $monitorId);
        $this->assertTrue(UuidV7::isValid($monitorId->value));
    }

    public function test_throws_exception_for_invalid_uuid_format(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid UuidV7 format');

        MonitorId::fromString('not-a-valid-uuid');
    }

    public function test_throws_exception_for_uuid_v4(): void
    {
        $uuidV4 = '550e8400-e29b-41d4-a716-446655440000';

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid UuidV7 format');

        MonitorId::fromString($uuidV4);
    }

    public function test_two_generated_ids_are_different(): void
    {
        $id1 = MonitorId::generate();
        $id2 = MonitorId::generate();

        $this->assertNotSame($id1->value, $id2->value);
    }

    public function test_from_string_and_to_string_are_symmetric(): void
    {
        $originalUuid = (new UuidV7())->toRfc4122();
        $monitorId = MonitorId::fromString($originalUuid);

        $this->assertSame($originalUuid, $monitorId->toString());
    }
}
