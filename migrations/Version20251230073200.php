<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251230073200 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE delivery (id INT AUTO_INCREMENT NOT NULL, order_customer_id INT DEFAULT NULL, delivery_status VARCHAR(255) NOT NULL, tracking_number VARCHAR(255) NOT NULL, shipment_date VARCHAR(255) NOT NULL, estimated_delivery_date DATETIME NOT NULL, shipping_cost NUMERIC(10, 2) NOT NULL, full_address VARCHAR(2550) DEFAULT NULL, INDEX IDX_3781EC108827BC75 (order_customer_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE delivery ADD CONSTRAINT FK_3781EC108827BC75 FOREIGN KEY (order_customer_id) REFERENCES `order` (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE delivery DROP FOREIGN KEY FK_3781EC108827BC75');
        $this->addSql('DROP TABLE delivery');
    }
}
