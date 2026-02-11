<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260211184500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add type and media columns to resource.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE resource ADD type VARCHAR(20) NOT NULL DEFAULT 'article', ADD video_url VARCHAR(500) DEFAULT NULL, ADD image_url VARCHAR(500) DEFAULT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE resource DROP image_url, DROP video_url, DROP type');
    }
}
