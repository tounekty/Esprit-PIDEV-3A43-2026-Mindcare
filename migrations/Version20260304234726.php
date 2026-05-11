<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260304234726 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE journal_emotionnel CHANGE contenu contenu LONGTEXT NOT NULL');
        $this->addSql('ALTER TABLE message_forum_analysis CHANGE error_message error_message LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE sujet_forum CHANGE status status VARCHAR(30) DEFAULT NULL, CHANGE category category VARCHAR(60) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE journal_emotionnel CHANGE contenu contenu LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE message_forum_analysis CHANGE error_message error_message VARCHAR(500) DEFAULT NULL');
        $this->addSql('ALTER TABLE sujet_forum CHANGE status status VARCHAR(50) DEFAULT NULL, CHANGE category category VARCHAR(100) DEFAULT NULL');
    }
}
