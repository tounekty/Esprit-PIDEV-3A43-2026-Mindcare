<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260225083004 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE reservation_event ADD confirmation_token VARCHAR(64) DEFAULT NULL, DROP is_waiting_list, DROP waiting_position');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE reservation_event ADD is_waiting_list TINYINT(1) DEFAULT 0 NOT NULL, ADD waiting_position INT DEFAULT NULL, DROP confirmation_token');
    }
}
