<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250506153533 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE shop DROP bank_holder, DROP bank_name, DROP bank_iban, DROP bank_country, DROP bank_swift');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE shop ADD bank_holder VARCHAR(255) DEFAULT NULL, ADD bank_name VARCHAR(255) DEFAULT NULL, ADD bank_iban VARCHAR(25) DEFAULT NULL, ADD bank_country VARCHAR(255) DEFAULT NULL, ADD bank_swift VARCHAR(255) DEFAULT NULL');
    }
}
