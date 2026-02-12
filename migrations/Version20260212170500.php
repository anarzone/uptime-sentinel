<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Fix User table ID column type from 'guid' to 'string'
 */
final class Version20260212170500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fix User.id column type from guid to string for MySQL compatibility';
    }

    public function up(Schema $schema): void
    {
        // No schema changes needed - this is a type mapping fix only
        // The Doctrine entity already uses type: 'string' with length: 36
        // This migration is primarily for documentation and ensuring consistency
    }

    public function down(Schema $schema): void
    {
        // No down() needed - entity was already wrong
        // To rollback, one would need to change entity back to 'guid' type
    }
}
