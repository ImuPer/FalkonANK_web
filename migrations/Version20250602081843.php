<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250602081843 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ads ADD shop_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE ads ADD CONSTRAINT FK_7EC9F6204D16C4DD FOREIGN KEY (shop_id) REFERENCES shop (id)');
        $this->addSql('CREATE INDEX IDX_7EC9F6204D16C4DD ON ads (shop_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ads DROP FOREIGN KEY FK_7EC9F6204D16C4DD');
        $this->addSql('DROP INDEX IDX_7EC9F6204D16C4DD ON ads');
        $this->addSql('ALTER TABLE ads DROP shop_id');
    }
}
