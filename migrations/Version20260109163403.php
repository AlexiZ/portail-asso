<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260109163403 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE association DROP contact_name, DROP contact_function');
        $this->addSql('ALTER TABLE contact CHANGE function function VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE event ADD poster_filename VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE contact CHANGE function function VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE event DROP poster_filename');
        $this->addSql('ALTER TABLE association ADD contact_name VARCHAR(255) DEFAULT NULL, ADD contact_function VARCHAR(255) DEFAULT NULL');
    }
}
