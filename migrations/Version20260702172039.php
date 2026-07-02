<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260702172039 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE music_session DROP FOREIGN KEY FK_14B047AF1137ABCF');
        $this->addSql('ALTER TABLE music_session ADD is_locked TINYINT(1) NOT NULL, ADD takeover_code VARCHAR(10) DEFAULT NULL, ADD takeover_requested_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD device_fingerprint VARCHAR(64) DEFAULT NULL');
        $this->addSql('ALTER TABLE music_session ADD CONSTRAINT FK_14B047AF1137ABCF FOREIGN KEY (album_id) REFERENCES album (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE music_session DROP FOREIGN KEY FK_14B047AF1137ABCF');
        $this->addSql('ALTER TABLE music_session DROP is_locked, DROP takeover_code, DROP takeover_requested_at, DROP device_fingerprint');
        $this->addSql('ALTER TABLE music_session ADD CONSTRAINT FK_14B047AF1137ABCF FOREIGN KEY (album_id) REFERENCES album (id) ON UPDATE NO ACTION ON DELETE CASCADE');
    }
}
