<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260628133333 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE music_session (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, album_id INT NOT NULL, token VARCHAR(255) NOT NULL, device_name VARCHAR(255) DEFAULT NULL, user_agent VARCHAR(255) DEFAULT NULL, ip_address VARCHAR(45) DEFAULT NULL, is_active TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', last_activity DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_14B047AF5F37A13B (token), INDEX IDX_14B047AFA76ED395 (user_id), INDEX IDX_14B047AF1137ABCF (album_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE music_session ADD CONSTRAINT FK_14B047AFA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE music_session ADD CONSTRAINT FK_14B047AF1137ABCF FOREIGN KEY (album_id) REFERENCES album (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE music_session DROP FOREIGN KEY FK_14B047AFA76ED395');
        $this->addSql('ALTER TABLE music_session DROP FOREIGN KEY FK_14B047AF1137ABCF');
        $this->addSql('DROP TABLE music_session');
    }
}
