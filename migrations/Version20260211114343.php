<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260211114343 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE commentaire (id INT AUTO_INCREMENT NOT NULL, author_name VARCHAR(100) NOT NULL, author_email VARCHAR(180) NOT NULL, content LONGTEXT NOT NULL, rating INT DEFAULT NULL, created_at DATETIME NOT NULL, edit_token VARCHAR(64) DEFAULT NULL, resource_id INT NOT NULL, INDEX IDX_67F068BC89329D25 (resource_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE journal_emotionnel (id INT AUTO_INCREMENT NOT NULL, contenu LONGTEXT DEFAULT NULL, dateecriture DATETIME NOT NULL, mood_id INT NOT NULL, INDEX IDX_443F70FB889D33E (mood_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE message_forum (id INT AUTO_INCREMENT NOT NULL, contenu LONGTEXT NOT NULL, date_message DATETIME NOT NULL, id_user INT NOT NULL, attachment_path VARCHAR(255) DEFAULT NULL, attachment_mime_type VARCHAR(100) DEFAULT NULL, attachment_size INT DEFAULT NULL, id_sujet INT NOT NULL, INDEX IDX_7A8D4126C09618AD (id_sujet), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE mood (id INT AUTO_INCREMENT NOT NULL, humeur VARCHAR(50) NOT NULL, intensite INT NOT NULL, datemood DATE NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE resource (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, file_path VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE sujet_forum (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, date_creation DATETIME NOT NULL, id_user INT NOT NULL, image_url VARCHAR(255) DEFAULT NULL, is_pinned TINYINT(1) NOT NULL, status VARCHAR(50) DEFAULT NULL, category VARCHAR(100) DEFAULT NULL, attachment_path VARCHAR(255) DEFAULT NULL, attachment_mime_type VARCHAR(100) DEFAULT NULL, attachment_size INT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, first_name VARCHAR(255) NOT NULL, last_name VARCHAR(255) NOT NULL, email VARCHAR(180) NOT NULL, role VARCHAR(50) NOT NULL, password VARCHAR(255) NOT NULL, banned_until DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE commentaire ADD CONSTRAINT FK_67F068BC89329D25 FOREIGN KEY (resource_id) REFERENCES resource (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE journal_emotionnel ADD CONSTRAINT FK_443F70FB889D33E FOREIGN KEY (mood_id) REFERENCES mood (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE message_forum ADD CONSTRAINT FK_7A8D4126C09618AD FOREIGN KEY (id_sujet) REFERENCES sujet_forum (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commentaire DROP FOREIGN KEY FK_67F068BC89329D25');
        $this->addSql('ALTER TABLE journal_emotionnel DROP FOREIGN KEY FK_443F70FB889D33E');
        $this->addSql('ALTER TABLE message_forum DROP FOREIGN KEY FK_7A8D4126C09618AD');
        $this->addSql('DROP TABLE commentaire');
        $this->addSql('DROP TABLE journal_emotionnel');
        $this->addSql('DROP TABLE message_forum');
        $this->addSql('DROP TABLE mood');
        $this->addSql('DROP TABLE resource');
        $this->addSql('DROP TABLE sujet_forum');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
