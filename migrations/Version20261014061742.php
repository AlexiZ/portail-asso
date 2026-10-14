<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261014061742 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add event.updated_at, initialized with created_at';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE event ADD updated_at DATETIME DEFAULT NULL');
        $this->addSql('UPDATE event SET updated_at = created_at');
        $this->addSql('ALTER TABLE event MODIFY updated_at DATETIME NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE event DROP updated_at');
    }
}
