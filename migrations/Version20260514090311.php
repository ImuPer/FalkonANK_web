<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260514090311 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE music (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, artist VARCHAR(255) NOT NULL, album VARCHAR(255) DEFAULT NULL, duration INT DEFAULT NULL, genre VARCHAR(100) DEFAULT NULL, release_date DATE DEFAULT NULL, cover_image VARCHAR(255) DEFAULT NULL, audio_file VARCHAR(255) DEFAULT NULL, views INT NOT NULL, is_published TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE carrier ADD CONSTRAINT FK_4739F11C8BAC62AF FOREIGN KEY (city_id) REFERENCES city (id)');
        $this->addSql('CREATE INDEX IDX_4739F11C8BAC62AF ON carrier (city_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE music');
        $this->addSql('ALTER TABLE carrier DROP FOREIGN KEY FK_4739F11C8BAC62AF');
        $this->addSql('DROP INDEX IDX_4739F11C8BAC62AF ON carrier');
    }
}
