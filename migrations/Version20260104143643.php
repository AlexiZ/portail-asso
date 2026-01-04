<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260104143643 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE contact (id INT AUTO_INCREMENT NOT NULL, association_id INT NOT NULL, name VARCHAR(255) NOT NULL, function VARCHAR(255) DEFAULT NULL, INDEX IDX_4C62E638EFB9C8A5 (association_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE contact ADD CONSTRAINT FK_4C62E638EFB9C8A5 FOREIGN KEY (association_id) REFERENCES association (id)');
        $this->addSql('INSERT INTO contact (association_id, name, function) SELECT id, contact_name, contact_function FROM association');
        $this->addSql('ALTER TABLE association DROP contact_name, DROP contact_function');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE contact DROP FOREIGN KEY FK_4C62E638EFB9C8A5');
        $this->addSql('DROP TABLE contact');
        $this->addSql('ALTER TABLE association ADD contact_name VARCHAR(255) DEFAULT NULL, ADD contact_function VARCHAR(255) DEFAULT NULL');
    }
}
