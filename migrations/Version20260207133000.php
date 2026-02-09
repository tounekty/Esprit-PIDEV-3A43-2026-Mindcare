<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260207133000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add forum enhancement fields (image, pin/status/category, attachment metadata)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE sujet_forum ADD image_url VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE sujet_forum ADD is_pinned TINYINT(1) NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE sujet_forum ADD status VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE sujet_forum ADD category VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE sujet_forum ADD attachment_path VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE sujet_forum ADD attachment_mime_type VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE sujet_forum ADD attachment_size INT DEFAULT NULL');

        $this->addSql('ALTER TABLE message_forum ADD attachment_path VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE message_forum ADD attachment_mime_type VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE message_forum ADD attachment_size INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE sujet_forum DROP image_url');
        $this->addSql('ALTER TABLE sujet_forum DROP is_pinned');
        $this->addSql('ALTER TABLE sujet_forum DROP status');
        $this->addSql('ALTER TABLE sujet_forum DROP category');
        $this->addSql('ALTER TABLE sujet_forum DROP attachment_path');
        $this->addSql('ALTER TABLE sujet_forum DROP attachment_mime_type');
        $this->addSql('ALTER TABLE sujet_forum DROP attachment_size');

        $this->addSql('ALTER TABLE message_forum DROP attachment_path');
        $this->addSql('ALTER TABLE message_forum DROP attachment_mime_type');
        $this->addSql('ALTER TABLE message_forum DROP attachment_size');
    }
}
