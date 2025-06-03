<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250422125200 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE city_beneficiary (id INT AUTO_INCREMENT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE `order` ADD city_beneficiary_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE `order` ADD CONSTRAINT FK_F5299398FFDBA0F4 FOREIGN KEY (city_beneficiary_id) REFERENCES city (id)');
        $this->addSql('CREATE INDEX IDX_F5299398FFDBA0F4 ON `order` (city_beneficiary_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE city_beneficiary');
        $this->addSql('ALTER TABLE `order` DROP FOREIGN KEY FK_F5299398FFDBA0F4');
        $this->addSql('DROP INDEX IDX_F5299398FFDBA0F4 ON `order`');
        $this->addSql('ALTER TABLE `order` DROP city_beneficiary_id');
    }
}
