<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260123163230 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE setting (id INT AUTO_INCREMENT NOT NULL, key_name VARCHAR(100) NOT NULL, value JSON NOT NULL COMMENT \'(DC2Type:json)\', help_text LONGTEXT DEFAULT NULL, is_editable TINYINT(1) DEFAULT 1 NOT NULL, UNIQUE INDEX uniq_setting_key_name (key_name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('INSERT INTO `setting` (`id`, `key_name`, `value`, `help_text`, `is_editable`) VALUES
            (1, \'breton\', \'\"1\"\', \'Activer la langue bretonne ?\', 0),
            (2, \'roomReservationPlatform\', \'\"https:\\/\\/reservation-salle.3douest.com\\/plabennec\"\', \'Lien vers le site de réservation des salles\', 0)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE setting');
    }
}
