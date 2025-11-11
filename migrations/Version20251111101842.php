<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251111101842 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE association CHANGE content content LONGTEXT DEFAULT NULL');
        $this->addSql('UPDATE association SET content = \'\' WHERE content = \'<h2>Description</h2><p>D&eacute;crivez votre association...</p><h2>Contact</h2><ul>	<li>Email :</li>	<li>T&eacute;l&eacute;phone :</li>	<li>Adresse :</li></ul><h2>Site Web / R&eacute;seaux sociaux</h2><p><a href="#">Lien vers votre site ou Facebook</a></p><h2>Logo</h2><p><img alt="Logo de lassociation" src="#" style="max-width:200px;" /></p><h2>Autres informations utiles</h2><p>Ajoutez ici toute information que vous jugez utile.</p>\'');
        $this->addSql('ALTER TABLE users ADD reset_token VARCHAR(64) DEFAULT NULL, ADD reset_token_expires_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE association CHANGE content content LONGTEXT NOT NULL');
        $this->addSql('ALTER TABLE users DROP reset_token, DROP reset_token_expires_at');
    }
}
