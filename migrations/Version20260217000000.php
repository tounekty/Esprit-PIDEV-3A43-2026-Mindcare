<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260217000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create user_stats and entry_template tables';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        // Check if table doesn't already exist to avoid errors
        if (!$schema->hasTable('user_stats')) {
            $this->addSql('CREATE TABLE user_stats (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, user_id INTEGER NOT NULL, points INTEGER NOT NULL DEFAULT 0, badges CLOB NOT NULL DEFAULT \'[]\', total_entries INTEGER NOT NULL DEFAULT 0, consecutive_days INTEGER NOT NULL DEFAULT 0, last_entry_date DATETIME, UNIQUE INDEX UNIQ_D49F6DF6A76ED395 (user_id), CONSTRAINT FK_D49F6DF6A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE)');
        }
        
        if (!$schema->hasTable('entry_template')) {
            $this->addSql('CREATE TABLE entry_template (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, title VARCHAR(255) NOT NULL, prompt CLOB NOT NULL, category VARCHAR(50) NOT NULL, description CLOB, display_order INTEGER NOT NULL DEFAULT 0, is_active BOOLEAN NOT NULL DEFAULT 1)');
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE IF EXISTS user_stats');
        $this->addSql('DROP TABLE IF EXISTS entry_template');
    }
}
