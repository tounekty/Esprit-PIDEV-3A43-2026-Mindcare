<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260305001356 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('SET FOREIGN_KEY_CHECKS = 0');
        $this->addSql('CREATE TABLE user_stat (id INT AUTO_INCREMENT NOT NULL, points INT DEFAULT 0 NOT NULL, badges JSON NOT NULL, total_entries INT DEFAULT 0 NOT NULL, consecutive_days INT DEFAULT 0 NOT NULL, last_entry_date DATETIME DEFAULT NULL, user_id INT NOT NULL, UNIQUE INDEX UNIQ_5A39B3E8A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE users (id INT AUTO_INCREMENT NOT NULL, first_name VARCHAR(255) NOT NULL, last_name VARCHAR(255) NOT NULL, email VARCHAR(180) NOT NULL, role VARCHAR(50) NOT NULL, password VARCHAR(255) NOT NULL, banned_until DATETIME DEFAULT NULL, is_verified TINYINT(1) NOT NULL, verification_token VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, reset_code VARCHAR(6) DEFAULT NULL, reset_code_expires_at DATETIME DEFAULT NULL, face_id_enabled TINYINT(1) NOT NULL, face_id_subject VARCHAR(255) DEFAULT NULL, created_by VARCHAR(255) DEFAULT NULL, updated_by VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_1483A5E9E7927C74 (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE user_stat ADD CONSTRAINT FK_5A39B3E8A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE user_stats DROP FOREIGN KEY IF EXISTS FK_B5859CF2A76ED395');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE user_stats');
        $this->addSql('ALTER TABLE appointment DROP FOREIGN KEY FK_FE38F8449F7E0988');
        $this->addSql('ALTER TABLE appointment DROP FOREIGN KEY FK_FE38F844DBAB6AEE');
        $this->addSql('DROP INDEX IDX_FE38F844DBAB6AEE ON appointment');
        $this->addSql('DROP INDEX IDX_FE38F8449F7E0988 ON appointment');
        $this->addSql('ALTER TABLE appointment ADD etudiant_id INT DEFAULT NULL, ADD psy_id INT DEFAULT NULL, DROP idetudiant, DROP idpsy');
        $this->addSql('ALTER TABLE appointment ADD CONSTRAINT FK_FE38F844DDEAB1A3 FOREIGN KEY (etudiant_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE appointment ADD CONSTRAINT FK_FE38F8448BA5C549 FOREIGN KEY (psy_id) REFERENCES users (id)');
        $this->addSql('CREATE INDEX IDX_FE38F844DDEAB1A3 ON appointment (etudiant_id)');
        $this->addSql('CREATE INDEX IDX_FE38F8448BA5C549 ON appointment (psy_id)');
        $this->addSql('ALTER TABLE commentaire DROP FOREIGN KEY FK_67F068BC6B3CA4B');
        $this->addSql('DROP INDEX IDX_67F068BC6B3CA4B ON commentaire');
        $this->addSql('ALTER TABLE commentaire ADD created_by VARCHAR(255) DEFAULT NULL, ADD updated_by VARCHAR(255) DEFAULT NULL, CHANGE id_user user_id INT NOT NULL');
        $this->addSql('ALTER TABLE commentaire ADD CONSTRAINT FK_67F068BCA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_67F068BCA76ED395 ON commentaire (user_id)');
        $this->addSql('ALTER TABLE event DROP FOREIGN KEY FK_3BAE0AA76B3CA4B');
        $this->addSql('DROP INDEX IDX_3BAE0AA76B3CA4B ON event');
        $this->addSql('ALTER TABLE event CHANGE id_user user_id INT NOT NULL');
        $this->addSql('ALTER TABLE event ADD CONSTRAINT FK_3BAE0AA7A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_3BAE0AA7A76ED395 ON event (user_id)');
        $this->addSql('ALTER TABLE journal_emotionnel DROP FOREIGN KEY FK_443F70F6B3CA4B');
        $this->addSql('DROP INDEX IDX_443F70F6B3CA4B ON journal_emotionnel');
        $this->addSql('ALTER TABLE journal_emotionnel CHANGE id_user user_id INT NOT NULL');
        $this->addSql('ALTER TABLE journal_emotionnel ADD CONSTRAINT FK_443F70FA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_443F70FA76ED395 ON journal_emotionnel (user_id)');
        $this->addSql('ALTER TABLE like_message DROP FOREIGN KEY FK_E5307A5C6B3CA4B');
        $this->addSql('ALTER TABLE like_message DROP FOREIGN KEY FK_E5307A5C6820990F');
        $this->addSql('DROP INDEX IDX_E5307A5C6B3CA4B ON like_message');
        $this->addSql('DROP INDEX IDX_E5307A5C6820990F ON like_message');
        $this->addSql('DROP INDEX uniq_like_user_message ON like_message');
        $this->addSql('ALTER TABLE like_message ADD user_id INT NOT NULL, ADD message_id INT NOT NULL, DROP id_user, DROP id_message');
        $this->addSql('ALTER TABLE like_message ADD CONSTRAINT FK_E5307A5CA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE like_message ADD CONSTRAINT FK_E5307A5C537A1329 FOREIGN KEY (message_id) REFERENCES message_forum (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_E5307A5CA76ED395 ON like_message (user_id)');
        $this->addSql('CREATE INDEX IDX_E5307A5C537A1329 ON like_message (message_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_like_user_message ON like_message (user_id, message_id)');
        $this->addSql('ALTER TABLE message_forum DROP FOREIGN KEY FK_7A8D4126C09618AD');
        $this->addSql('ALTER TABLE message_forum DROP FOREIGN KEY FK_7A8D41266B3CA4B');
        $this->addSql('DROP INDEX IDX_7A8D4126C09618AD ON message_forum');
        $this->addSql('DROP INDEX IDX_7A8D41266B3CA4B ON message_forum');
        $this->addSql('ALTER TABLE message_forum ADD sujet_id INT NOT NULL, ADD user_id INT NOT NULL, DROP id_sujet, DROP id_user');
        $this->addSql('ALTER TABLE message_forum ADD CONSTRAINT FK_7A8D41267C4D497E FOREIGN KEY (sujet_id) REFERENCES sujet_forum (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE message_forum ADD CONSTRAINT FK_7A8D4126A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_7A8D41267C4D497E ON message_forum (sujet_id)');
        $this->addSql('CREATE INDEX IDX_7A8D4126A76ED395 ON message_forum (user_id)');
        $this->addSql('ALTER TABLE message_forum_analysis ADD created_by VARCHAR(255) DEFAULT NULL, ADD updated_by VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE mood DROP FOREIGN KEY FK_339AEF66B3CA4B');
        $this->addSql('DROP INDEX IDX_339AEF66B3CA4B ON mood');
        $this->addSql('ALTER TABLE mood CHANGE id_user user_id INT NOT NULL');
        $this->addSql('ALTER TABLE mood ADD CONSTRAINT FK_339AEF6A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_339AEF6A76ED395 ON mood (user_id)');
        $this->addSql('ALTER TABLE patient_file DROP FOREIGN KEY FK_50E7BD8CB944F1A');
        $this->addSql('ALTER TABLE patient_file ADD created_by VARCHAR(255) DEFAULT NULL, ADD updated_by VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE patient_file ADD CONSTRAINT FK_50E7BD8CB944F1A FOREIGN KEY (student_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE psychological_alert DROP FOREIGN KEY FK_4071ECE5A76ED395');
        $this->addSql('ALTER TABLE psychological_alert ADD created_by VARCHAR(255) DEFAULT NULL, ADD updated_by VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE psychological_alert ADD CONSTRAINT FK_4071ECE5A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE reservation_event DROP FOREIGN KEY FK_78D1DA00D52B4B97');
        $this->addSql('ALTER TABLE reservation_event DROP FOREIGN KEY FK_78D1DA006B3CA4B');
        $this->addSql('DROP INDEX IDX_78D1DA006B3CA4B ON reservation_event');
        $this->addSql('DROP INDEX IDX_78D1DA00D52B4B97 ON reservation_event');
        $this->addSql('ALTER TABLE reservation_event ADD event_id INT NOT NULL, ADD user_id INT NOT NULL, DROP id_event, DROP id_user');
        $this->addSql('ALTER TABLE reservation_event ADD CONSTRAINT FK_78D1DA0071F7E88B FOREIGN KEY (event_id) REFERENCES event (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reservation_event ADD CONSTRAINT FK_78D1DA00A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_78D1DA0071F7E88B ON reservation_event (event_id)');
        $this->addSql('CREATE INDEX IDX_78D1DA00A76ED395 ON reservation_event (user_id)');
        $this->addSql('ALTER TABLE resource DROP FOREIGN KEY FK_BC91F4166B3CA4B');
        $this->addSql('DROP INDEX IDX_BC91F4166B3CA4B ON resource');
        $this->addSql('ALTER TABLE resource ADD created_by VARCHAR(255) DEFAULT NULL, ADD updated_by VARCHAR(255) DEFAULT NULL, CHANGE id_user user_id INT NOT NULL');
        $this->addSql('ALTER TABLE resource ADD CONSTRAINT FK_BC91F416A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_BC91F416A76ED395 ON resource (user_id)');
        $this->addSql('ALTER TABLE sujet_forum DROP FOREIGN KEY FK_EB9769ED6B3CA4B');
        $this->addSql('DROP INDEX IDX_EB9769ED6B3CA4B ON sujet_forum');
        $this->addSql('ALTER TABLE sujet_forum CHANGE id_user user_id INT NOT NULL');
        $this->addSql('ALTER TABLE sujet_forum ADD CONSTRAINT FK_EB9769EDA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_EB9769EDA76ED395 ON sujet_forum (user_id)');
        $this->addSql('ALTER TABLE sujet_tagged_psychologue DROP FOREIGN KEY FK_786D4DCEC09618AD');
        $this->addSql('ALTER TABLE sujet_tagged_psychologue DROP FOREIGN KEY FK_786D4DCECED9C570');
        $this->addSql('DROP INDEX IDX_786D4DCEC09618AD ON sujet_tagged_psychologue');
        $this->addSql('DROP INDEX IDX_786D4DCECED9C570 ON sujet_tagged_psychologue');
        $this->addSql('ALTER TABLE sujet_tagged_psychologue ADD sujet_id INT NOT NULL, ADD psychologue_id INT NOT NULL, DROP id_sujet, DROP id_psychologue, DROP PRIMARY KEY, ADD PRIMARY KEY (sujet_id, psychologue_id)');
        $this->addSql('ALTER TABLE sujet_tagged_psychologue ADD CONSTRAINT FK_786D4DCE7C4D497E FOREIGN KEY (sujet_id) REFERENCES sujet_forum (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE sujet_tagged_psychologue ADD CONSTRAINT FK_786D4DCE465459D3 FOREIGN KEY (psychologue_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_786D4DCE7C4D497E ON sujet_tagged_psychologue (sujet_id)');
        $this->addSql('CREATE INDEX IDX_786D4DCE465459D3 ON sujet_tagged_psychologue (psychologue_id)');
        $this->addSql('SET FOREIGN_KEY_CHECKS = 1');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, first_name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, last_name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, email VARCHAR(180) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, role VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, password VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, banned_until DATETIME DEFAULT NULL, is_verified TINYINT(1) NOT NULL, verification_token VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, created_at DATETIME DEFAULT NULL, reset_code VARCHAR(6) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, reset_code_expires_at DATETIME DEFAULT NULL, face_id_enabled TINYINT(1) NOT NULL, face_id_subject VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE user_stats (id INT AUTO_INCREMENT NOT NULL, points INT DEFAULT 0 NOT NULL, badges JSON NOT NULL, total_entries INT DEFAULT 0 NOT NULL, consecutive_days INT DEFAULT 0 NOT NULL, last_entry_date DATETIME DEFAULT NULL, user_id INT NOT NULL, UNIQUE INDEX UNIQ_B5859CF2A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE user_stats ADD CONSTRAINT FK_B5859CF2A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user_stat DROP FOREIGN KEY FK_5A39B3E8A76ED395');
        $this->addSql('DROP TABLE user_stat');
        $this->addSql('DROP TABLE users');
        $this->addSql('ALTER TABLE appointment DROP FOREIGN KEY FK_FE38F844DDEAB1A3');
        $this->addSql('ALTER TABLE appointment DROP FOREIGN KEY FK_FE38F8448BA5C549');
        $this->addSql('DROP INDEX IDX_FE38F844DDEAB1A3 ON appointment');
        $this->addSql('DROP INDEX IDX_FE38F8448BA5C549 ON appointment');
        $this->addSql('ALTER TABLE appointment ADD idetudiant INT DEFAULT NULL, ADD idpsy INT DEFAULT NULL, DROP etudiant_id, DROP psy_id');
        $this->addSql('ALTER TABLE appointment ADD CONSTRAINT FK_FE38F8449F7E0988 FOREIGN KEY (idpsy) REFERENCES user (id)');
        $this->addSql('ALTER TABLE appointment ADD CONSTRAINT FK_FE38F844DBAB6AEE FOREIGN KEY (idetudiant) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_FE38F844DBAB6AEE ON appointment (idetudiant)');
        $this->addSql('CREATE INDEX IDX_FE38F8449F7E0988 ON appointment (idpsy)');
        $this->addSql('ALTER TABLE commentaire DROP FOREIGN KEY FK_67F068BCA76ED395');
        $this->addSql('DROP INDEX IDX_67F068BCA76ED395 ON commentaire');
        $this->addSql('ALTER TABLE commentaire DROP created_by, DROP updated_by, CHANGE user_id id_user INT NOT NULL');
        $this->addSql('ALTER TABLE commentaire ADD CONSTRAINT FK_67F068BC6B3CA4B FOREIGN KEY (id_user) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_67F068BC6B3CA4B ON commentaire (id_user)');
        $this->addSql('ALTER TABLE event DROP FOREIGN KEY FK_3BAE0AA7A76ED395');
        $this->addSql('DROP INDEX IDX_3BAE0AA7A76ED395 ON event');
        $this->addSql('ALTER TABLE event CHANGE user_id id_user INT NOT NULL');
        $this->addSql('ALTER TABLE event ADD CONSTRAINT FK_3BAE0AA76B3CA4B FOREIGN KEY (id_user) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_3BAE0AA76B3CA4B ON event (id_user)');
        $this->addSql('ALTER TABLE journal_emotionnel DROP FOREIGN KEY FK_443F70FA76ED395');
        $this->addSql('DROP INDEX IDX_443F70FA76ED395 ON journal_emotionnel');
        $this->addSql('ALTER TABLE journal_emotionnel CHANGE user_id id_user INT NOT NULL');
        $this->addSql('ALTER TABLE journal_emotionnel ADD CONSTRAINT FK_443F70F6B3CA4B FOREIGN KEY (id_user) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_443F70F6B3CA4B ON journal_emotionnel (id_user)');
        $this->addSql('ALTER TABLE like_message DROP FOREIGN KEY FK_E5307A5CA76ED395');
        $this->addSql('ALTER TABLE like_message DROP FOREIGN KEY FK_E5307A5C537A1329');
        $this->addSql('DROP INDEX IDX_E5307A5CA76ED395 ON like_message');
        $this->addSql('DROP INDEX IDX_E5307A5C537A1329 ON like_message');
        $this->addSql('DROP INDEX uniq_like_user_message ON like_message');
        $this->addSql('ALTER TABLE like_message ADD id_user INT NOT NULL, ADD id_message INT NOT NULL, DROP user_id, DROP message_id');
        $this->addSql('ALTER TABLE like_message ADD CONSTRAINT FK_E5307A5C6B3CA4B FOREIGN KEY (id_user) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE like_message ADD CONSTRAINT FK_E5307A5C6820990F FOREIGN KEY (id_message) REFERENCES message_forum (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_E5307A5C6B3CA4B ON like_message (id_user)');
        $this->addSql('CREATE INDEX IDX_E5307A5C6820990F ON like_message (id_message)');
        $this->addSql('CREATE UNIQUE INDEX uniq_like_user_message ON like_message (id_user, id_message)');
        $this->addSql('ALTER TABLE message_forum DROP FOREIGN KEY FK_7A8D41267C4D497E');
        $this->addSql('ALTER TABLE message_forum DROP FOREIGN KEY FK_7A8D4126A76ED395');
        $this->addSql('DROP INDEX IDX_7A8D41267C4D497E ON message_forum');
        $this->addSql('DROP INDEX IDX_7A8D4126A76ED395 ON message_forum');
        $this->addSql('ALTER TABLE message_forum ADD id_sujet INT NOT NULL, ADD id_user INT NOT NULL, DROP sujet_id, DROP user_id');
        $this->addSql('ALTER TABLE message_forum ADD CONSTRAINT FK_7A8D4126C09618AD FOREIGN KEY (id_sujet) REFERENCES sujet_forum (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE message_forum ADD CONSTRAINT FK_7A8D41266B3CA4B FOREIGN KEY (id_user) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_7A8D4126C09618AD ON message_forum (id_sujet)');
        $this->addSql('CREATE INDEX IDX_7A8D41266B3CA4B ON message_forum (id_user)');
        $this->addSql('ALTER TABLE message_forum_analysis DROP created_by, DROP updated_by');
        $this->addSql('ALTER TABLE mood DROP FOREIGN KEY FK_339AEF6A76ED395');
        $this->addSql('DROP INDEX IDX_339AEF6A76ED395 ON mood');
        $this->addSql('ALTER TABLE mood CHANGE user_id id_user INT NOT NULL');
        $this->addSql('ALTER TABLE mood ADD CONSTRAINT FK_339AEF66B3CA4B FOREIGN KEY (id_user) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_339AEF66B3CA4B ON mood (id_user)');
        $this->addSql('ALTER TABLE patient_file DROP FOREIGN KEY FK_50E7BD8CB944F1A');
        $this->addSql('ALTER TABLE patient_file DROP created_by, DROP updated_by');
        $this->addSql('ALTER TABLE patient_file ADD CONSTRAINT FK_50E7BD8CB944F1A FOREIGN KEY (student_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE psychological_alert DROP FOREIGN KEY FK_4071ECE5A76ED395');
        $this->addSql('ALTER TABLE psychological_alert DROP created_by, DROP updated_by');
        $this->addSql('ALTER TABLE psychological_alert ADD CONSTRAINT FK_4071ECE5A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE reservation_event DROP FOREIGN KEY FK_78D1DA0071F7E88B');
        $this->addSql('ALTER TABLE reservation_event DROP FOREIGN KEY FK_78D1DA00A76ED395');
        $this->addSql('DROP INDEX IDX_78D1DA0071F7E88B ON reservation_event');
        $this->addSql('DROP INDEX IDX_78D1DA00A76ED395 ON reservation_event');
        $this->addSql('ALTER TABLE reservation_event ADD id_event INT NOT NULL, ADD id_user INT NOT NULL, DROP event_id, DROP user_id');
        $this->addSql('ALTER TABLE reservation_event ADD CONSTRAINT FK_78D1DA00D52B4B97 FOREIGN KEY (id_event) REFERENCES event (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reservation_event ADD CONSTRAINT FK_78D1DA006B3CA4B FOREIGN KEY (id_user) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_78D1DA006B3CA4B ON reservation_event (id_user)');
        $this->addSql('CREATE INDEX IDX_78D1DA00D52B4B97 ON reservation_event (id_event)');
        $this->addSql('ALTER TABLE resource DROP FOREIGN KEY FK_BC91F416A76ED395');
        $this->addSql('DROP INDEX IDX_BC91F416A76ED395 ON resource');
        $this->addSql('ALTER TABLE resource DROP created_by, DROP updated_by, CHANGE user_id id_user INT NOT NULL');
        $this->addSql('ALTER TABLE resource ADD CONSTRAINT FK_BC91F4166B3CA4B FOREIGN KEY (id_user) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_BC91F4166B3CA4B ON resource (id_user)');
        $this->addSql('ALTER TABLE sujet_forum DROP FOREIGN KEY FK_EB9769EDA76ED395');
        $this->addSql('DROP INDEX IDX_EB9769EDA76ED395 ON sujet_forum');
        $this->addSql('ALTER TABLE sujet_forum CHANGE user_id id_user INT NOT NULL');
        $this->addSql('ALTER TABLE sujet_forum ADD CONSTRAINT FK_EB9769ED6B3CA4B FOREIGN KEY (id_user) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_EB9769ED6B3CA4B ON sujet_forum (id_user)');
        $this->addSql('ALTER TABLE sujet_tagged_psychologue DROP FOREIGN KEY FK_786D4DCE7C4D497E');
        $this->addSql('ALTER TABLE sujet_tagged_psychologue DROP FOREIGN KEY FK_786D4DCE465459D3');
        $this->addSql('DROP INDEX IDX_786D4DCE7C4D497E ON sujet_tagged_psychologue');
        $this->addSql('DROP INDEX IDX_786D4DCE465459D3 ON sujet_tagged_psychologue');
        $this->addSql('ALTER TABLE sujet_tagged_psychologue ADD id_sujet INT NOT NULL, ADD id_psychologue INT NOT NULL, DROP sujet_id, DROP psychologue_id, DROP PRIMARY KEY, ADD PRIMARY KEY (id_sujet, id_psychologue)');
        $this->addSql('ALTER TABLE sujet_tagged_psychologue ADD CONSTRAINT FK_786D4DCEC09618AD FOREIGN KEY (id_sujet) REFERENCES sujet_forum (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE sujet_tagged_psychologue ADD CONSTRAINT FK_786D4DCECED9C570 FOREIGN KEY (id_psychologue) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_786D4DCEC09618AD ON sujet_tagged_psychologue (id_sujet)');
        $this->addSql('CREATE INDEX IDX_786D4DCECED9C570 ON sujet_tagged_psychologue (id_psychologue)');
    }
}
