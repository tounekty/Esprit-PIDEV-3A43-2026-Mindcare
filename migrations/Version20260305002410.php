<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260305002410 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commentaire ADD updated_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE psychological_alert ADD updated_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE resource ADD updated_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE users ADD updated_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commentaire DROP updated_at');
        $this->addSql('ALTER TABLE psychological_alert DROP updated_at');
        $this->addSql('ALTER TABLE resource DROP updated_at');
        $this->addSql('ALTER TABLE users DROP updated_at');
    }
}
