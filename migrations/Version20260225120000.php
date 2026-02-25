<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260225120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add face ID fields to user';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD face_id_enabled TINYINT(1) NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE user ADD face_id_subject VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP face_id_enabled');
        $this->addSql('ALTER TABLE user DROP face_id_subject');
    }
}
