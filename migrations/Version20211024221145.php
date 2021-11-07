<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20211024221145 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the Transfer table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE Transfer (id INT AUTO_INCREMENT NOT NULL, amount NUMERIC(10, 2) NOT NULL, date DATETIME NOT NULL, customer_from INT NOT NULL, customer_to INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE Transfer');
    }

    public function isTransactional(): bool
    {
        // Fix for https://github.com/doctrine/migrations/issues/1104
        return false;
    }
}
