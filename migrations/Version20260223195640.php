<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260223195640 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE mood ADD COLUMN ai_analysis LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE mood ADD COLUMN pdf_path VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__mood AS SELECT id, humeur, intensite, datemood, id_user FROM mood');
        $this->addSql('DROP TABLE mood');
        $this->addSql('CREATE TABLE mood (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, humeur VARCHAR(50) NOT NULL, intensite INTEGER NOT NULL, datemood DATE NOT NULL, id_user INTEGER NOT NULL, CONSTRAINT FK_339AEF66B3CA4B FOREIGN KEY (id_user) REFERENCES user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO mood (id, humeur, intensite, datemood, id_user) SELECT id, humeur, intensite, datemood, id_user FROM __temp__mood');
        $this->addSql('DROP TABLE __temp__mood');
        $this->addSql('CREATE INDEX IDX_339AEF66B3CA4B ON mood (id_user)');
    }
}
