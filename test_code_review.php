<?php

declare(strict_types=1);

namespace App\Tests;

/**
 * This file contains intentional code issues to test the code review system.
 * This file should be deleted after testing.
 */

class TestCodeReview
{
    private string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    // ISSUE: Missing return type declaration
    public function getName()
    {
        return $this->name;
    }

    // ISSUE: SQL injection vulnerability - concatenated query
    public function getUserQuery(string $userId): string
    {
        return "SELECT * FROM users WHERE id = " . $userId;
    }

    // ISSUE: Hardcoded secret
    public function getApiKey(): string
    {
        return 'sk-9b316e6b3e5f442f930ff34f53babe4c.OmQ93f0lzw7jJyUG';
    }

    // ISSUE: Missing input validation
    public function processEmail(string $email): void
    {
        // No validation before processing
        $this->sendEmail($email);
    }

    private function sendEmail(string $email): void
    {
        // Email sending logic
    }
}
