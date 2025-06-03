<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250422131218 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE merchant ADD city_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE merchant ADD CONSTRAINT FK_74AB25E18BAC62AF FOREIGN KEY (city_id) REFERENCES city (id)');
        $this->addSql('CREATE INDEX IDX_74AB25E18BAC62AF ON merchant (city_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE merchant DROP FOREIGN KEY FK_74AB25E18BAC62AF');
        $this->addSql('DROP INDEX IDX_74AB25E18BAC62AF ON merchant');
        $this->addSql('ALTER TABLE merchant DROP city_id');
    }
}
