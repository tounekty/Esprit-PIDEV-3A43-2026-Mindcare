<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260210152708 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__journal_emotionnel AS SELECT id, mood_id, contenu, dateecriture FROM journal_emotionnel');
        $this->addSql('DROP TABLE journal_emotionnel');
        $this->addSql('CREATE TABLE journal_emotionnel (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, mood_id INTEGER NOT NULL, contenu CLOB DEFAULT NULL, dateecriture DATETIME NOT NULL, CONSTRAINT FK_443F70FB889D33E FOREIGN KEY (mood_id) REFERENCES mood (id) ON UPDATE NO ACTION ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO journal_emotionnel (id, mood_id, contenu, dateecriture) SELECT id, mood_id, contenu, dateecriture FROM __temp__journal_emotionnel');
        $this->addSql('DROP TABLE __temp__journal_emotionnel');
        $this->addSql('CREATE INDEX IDX_443F70FB889D33E ON journal_emotionnel (mood_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__journal_emotionnel AS SELECT id, contenu, dateecriture, mood_id FROM journal_emotionnel');
        $this->addSql('DROP TABLE journal_emotionnel');
        $this->addSql('CREATE TABLE journal_emotionnel (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, contenu CLOB NOT NULL, dateecriture DATETIME NOT NULL, mood_id INTEGER NOT NULL, CONSTRAINT FK_443F70FB889D33E FOREIGN KEY (mood_id) REFERENCES mood (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO journal_emotionnel (id, contenu, dateecriture, mood_id) SELECT id, contenu, dateecriture, mood_id FROM __temp__journal_emotionnel');
        $this->addSql('DROP TABLE __temp__journal_emotionnel');
        $this->addSql('CREATE INDEX IDX_443F70FB889D33E ON journal_emotionnel (mood_id)');
    }
}
