<?php

declare(strict_types=1);

namespace App\Tests;

/**
 * This file contains intentional code issues to test the code review system.
 * This file should be deleted after testing.
 *
 * TESTING: Added another issue to trigger pre-push hook again
 * PR TEST: Testing GitHub Actions code review on PR
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

    // ISSUE: Hardcoded secret (fake key for testing)
    public function getApiKey(): string
    {
        return 'fake-api-key-12345-for-testing-only';
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

    // ISSUE: N+1 query problem in loop
    public function fetchUsers(array $userIds): array
    {
        $users = [];
        foreach ($userIds as $id) {
            // This creates N+1 queries - should fetch all at once
            $user = $this->db->query("SELECT * FROM users WHERE id = " . $id);
            $users[] = $user;
        }
        return $users;
    }
}
