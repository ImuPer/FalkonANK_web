<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260714142138 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE subscription_invoice (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, subscription_id INT NOT NULL, invoice_number VARCHAR(50) NOT NULL, stripe_invoice_id VARCHAR(255) NOT NULL, stripe_payment_intent VARCHAR(255) DEFAULT NULL, status VARCHAR(20) NOT NULL, amount DOUBLE PRECISION NOT NULL, currency VARCHAR(10) NOT NULL, period_start DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', period_end DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', stripe_session_id VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_E370F7DF2DA68207 (invoice_number), INDEX IDX_E370F7DFA76ED395 (user_id), INDEX IDX_E370F7DF9A1887DC (subscription_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE subscription_invoice ADD CONSTRAINT FK_E370F7DFA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE subscription_invoice ADD CONSTRAINT FK_E370F7DF9A1887DC FOREIGN KEY (subscription_id) REFERENCES subscription (id)');
        $this->addSql('ALTER TABLE subscription DROP FOREIGN KEY FK_A3C664D3A76ED395');
        $this->addSql('ALTER TABLE subscription CHANGE user_id user_id INT NOT NULL');
        $this->addSql('ALTER TABLE subscription ADD CONSTRAINT FK_A3C664D3A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE subscription_invoice DROP FOREIGN KEY FK_E370F7DFA76ED395');
        $this->addSql('ALTER TABLE subscription_invoice DROP FOREIGN KEY FK_E370F7DF9A1887DC');
        $this->addSql('DROP TABLE subscription_invoice');
        $this->addSql('ALTER TABLE subscription CHANGE user_id user_id INT DEFAULT NULL');
    }
}
