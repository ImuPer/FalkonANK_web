<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260521062011 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE album_purchase DROP INDEX UNIQ_92FB772E1137ABCF, ADD INDEX IDX_92FB772E1137ABCF (album_id)');
        $this->addSql('ALTER TABLE album_purchase ADD quantity INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE album_purchase DROP INDEX IDX_92FB772E1137ABCF, ADD UNIQUE INDEX UNIQ_92FB772E1137ABCF (album_id)');
        $this->addSql('ALTER TABLE album_purchase DROP quantity');
    }
}
