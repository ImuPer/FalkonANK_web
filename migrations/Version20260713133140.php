<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260713133140 extends AbstractMigration
{
    public function up(Schema $schema): void
{
    $this->addSql("
        ALTER TABLE subscription
        ADD status VARCHAR(30) NOT NULL,
        ADD created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
        ADD updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'
    ");
}

public function down(Schema $schema): void
{
    $this->addSql("
        ALTER TABLE subscription
        DROP status,
        DROP created_at,
        DROP updated_at
    ");
}
}
