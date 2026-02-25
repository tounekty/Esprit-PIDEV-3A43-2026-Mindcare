<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260225150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create message_forum_analysis table for Hugging Face sentiment and urgency analysis.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE message_forum_analysis (id INT AUTO_INCREMENT NOT NULL, message_id INT NOT NULL, status VARCHAR(20) NOT NULL, sentiment_label VARCHAR(30) DEFAULT NULL, sentiment_score DOUBLE PRECISION DEFAULT NULL, urgency_label VARCHAR(30) DEFAULT NULL, urgency_score DOUBLE PRECISION DEFAULT NULL, is_urgent TINYINT(1) NOT NULL, model_name VARCHAR(180) DEFAULT NULL, raw_response LONGTEXT DEFAULT NULL, error_message VARCHAR(500) DEFAULT NULL, analyzed_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', UNIQUE INDEX UNIQ_996E13A5537A1329 (message_id), INDEX IDX_996E13A5537A1329 (message_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql('ALTER TABLE message_forum_analysis ADD CONSTRAINT FK_996E13A5537A1329 FOREIGN KEY (message_id) REFERENCES message_forum (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE message_forum_analysis DROP FOREIGN KEY FK_996E13A5537A1329');
        $this->addSql('DROP TABLE message_forum_analysis');
    }
}
