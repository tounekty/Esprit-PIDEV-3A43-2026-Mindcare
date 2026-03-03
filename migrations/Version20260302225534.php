<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260302225534 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE like_message (id INT AUTO_INCREMENT NOT NULL, id_user INT NOT NULL, id_message INT NOT NULL, INDEX IDX_E5307A5C6B3CA4B (id_user), INDEX IDX_E5307A5C6820990F (id_message), UNIQUE INDEX uniq_like_user_message (id_user, id_message), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE message_forum_analysis (id INT AUTO_INCREMENT NOT NULL, status VARCHAR(20) NOT NULL, sentiment_label VARCHAR(30) DEFAULT NULL, sentiment_score DOUBLE PRECISION DEFAULT NULL, urgency_label VARCHAR(30) DEFAULT NULL, urgency_score DOUBLE PRECISION DEFAULT NULL, is_urgent TINYINT(1) NOT NULL, model_name VARCHAR(180) DEFAULT NULL, raw_response LONGTEXT DEFAULT NULL, error_message VARCHAR(500) DEFAULT NULL, analyzed_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, message_id INT NOT NULL, UNIQUE INDEX UNIQ_79571E9F537A1329 (message_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE sujet_tagged_psychologue (id_sujet INT NOT NULL, id_psychologue INT NOT NULL, INDEX IDX_786D4DCEC09618AD (id_sujet), INDEX IDX_786D4DCECED9C570 (id_psychologue), PRIMARY KEY(id_sujet, id_psychologue)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE like_message ADD CONSTRAINT FK_E5307A5C6B3CA4B FOREIGN KEY (id_user) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE like_message ADD CONSTRAINT FK_E5307A5C6820990F FOREIGN KEY (id_message) REFERENCES message_forum (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE message_forum_analysis ADD CONSTRAINT FK_79571E9F537A1329 FOREIGN KEY (message_id) REFERENCES message_forum (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE sujet_tagged_psychologue ADD CONSTRAINT FK_786D4DCEC09618AD FOREIGN KEY (id_sujet) REFERENCES sujet_forum (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE sujet_tagged_psychologue ADD CONSTRAINT FK_786D4DCECED9C570 FOREIGN KEY (id_psychologue) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE message_forum ADD is_anonymous TINYINT(1) NOT NULL, ADD parent_message_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE message_forum ADD CONSTRAINT FK_7A8D412614399779 FOREIGN KEY (parent_message_id) REFERENCES message_forum (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_7A8D412614399779 ON message_forum (parent_message_id)');
        $this->addSql('ALTER TABLE sujet_forum ADD is_anonymous TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE like_message DROP FOREIGN KEY FK_E5307A5C6B3CA4B');
        $this->addSql('ALTER TABLE like_message DROP FOREIGN KEY FK_E5307A5C6820990F');
        $this->addSql('ALTER TABLE message_forum_analysis DROP FOREIGN KEY FK_79571E9F537A1329');
        $this->addSql('ALTER TABLE sujet_tagged_psychologue DROP FOREIGN KEY FK_786D4DCEC09618AD');
        $this->addSql('ALTER TABLE sujet_tagged_psychologue DROP FOREIGN KEY FK_786D4DCECED9C570');
        $this->addSql('DROP TABLE like_message');
        $this->addSql('DROP TABLE message_forum_analysis');
        $this->addSql('DROP TABLE sujet_tagged_psychologue');
        $this->addSql('ALTER TABLE message_forum DROP FOREIGN KEY FK_7A8D412614399779');
        $this->addSql('DROP INDEX IDX_7A8D412614399779 ON message_forum');
        $this->addSql('ALTER TABLE message_forum DROP is_anonymous, DROP parent_message_id');
        $this->addSql('ALTER TABLE sujet_forum DROP is_anonymous');
    }
}
