<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260222120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create psychological_alert table';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE psychological_alert (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, alert_type VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, details LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, notified_admin TINYINT(1) NOT NULL DEFAULT 0, notified_psychologist TINYINT(1) NOT NULL DEFAULT 0, resolved TINYINT(1) NOT NULL DEFAULT 0, resolved_at DATETIME DEFAULT NULL, admin_notes LONGTEXT DEFAULT NULL, INDEX IDX_PSYCHOLOGICAL_ALERT_USER (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE psychological_alert ADD CONSTRAINT FK_PSYCHOLOGICAL_ALERT_USER FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE psychological_alert DROP FOREIGN KEY FK_PSYCHOLOGICAL_ALERT_USER');
        $this->addSql('DROP TABLE psychological_alert');
    }
}
