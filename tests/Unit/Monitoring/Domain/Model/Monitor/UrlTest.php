<?php

declare(strict_types=1);

namespace App\Tests\Unit\Monitoring\Domain\Model\Monitor;

use App\Monitoring\Domain\Model\Monitor\Url;
use PHPUnit\Framework\TestCase;

final class UrlTest extends TestCase
{
    public function test_can_be_created_from_valid_url(): void
    {
        $url = Url::fromString('https://example.com');

        $this->assertSame('https://example.com', $url->value);
        $this->assertSame('https://example.com', $url->toString());
    }

    public function test_accepts_http_urls(): void
    {
        $url = Url::fromString('http://example.com');

        $this->assertSame('http://example.com', $url->value);
    }

    public function test_accepts_https_urls(): void
    {
        $url = Url::fromString('https://example.com');

        $this->assertSame('https://example.com', $url->value);
    }

    public function test_accepts_urls_with_paths(): void
    {
        $urlString = 'https://example.com/api/v1/endpoint';
        $url = Url::fromString($urlString);

        $this->assertSame($urlString, $url->value);
    }

    public function test_accepts_urls_with_query_parameters(): void
    {
        $urlString = 'https://example.com?param1=value1&param2=value2';
        $url = Url::fromString($urlString);

        $this->assertSame($urlString, $url->value);
    }

    public function test_accepts_urls_with_fragments(): void
    {
        $urlString = 'https://example.com#section';
        $url = Url::fromString($urlString);

        $this->assertSame($urlString, $url->value);
    }

    public function test_accepts_complex_urls(): void
    {
        $urlString = 'https://api.example.com:8443/v1/monitoring?token=abc123&type=full#results';
        $url = Url::fromString($urlString);

        $this->assertSame($urlString, $url->value);
    }

    public function test_throws_exception_for_invalid_url(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid URL format');

        Url::fromString('not-a-valid-url');
    }

    public function test_throws_exception_for_empty_string(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid URL format');

        Url::fromString('');
    }

    public function test_throws_exception_for_url_without_scheme(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid URL format');

        Url::fromString('example.com');
    }

    public function test_is_valid_returns_true_for_valid_urls(): void
    {
        $this->assertTrue(Url::isValid('https://example.com'));
        $this->assertTrue(Url::isValid('http://example.com'));
        $this->assertTrue(Url::isValid('https://example.com/path?query=value'));
    }

    public function test_is_valid_returns_false_for_invalid_urls(): void
    {
        $this->assertFalse(Url::isValid('not-a-url'));
        $this->assertFalse(Url::isValid(''));
        $this->assertFalse(Url::isValid('example.com'));
    }

    public function test_from_string_and_to_string_are_symmetric(): void
    {
        $originalUrl = 'https://api.example.com/v1/endpoint';
        $url = Url::fromString($originalUrl);

        $this->assertSame($originalUrl, $url->toString());
    }
}
