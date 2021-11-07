<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211028082848 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE Transfer CHANGE customer_from customer_from VARCHAR(255) NOT NULL, CHANGE customer_to customer_to VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE Transfer CHANGE customer_from customer_from INT NOT NULL, CHANGE customer_to customer_to INT NOT NULL');
    }

    public function isTransactional(): bool
    {
        // Fix for https://github.com/doctrine/migrations/issues/1104
        return false;
    }
}
