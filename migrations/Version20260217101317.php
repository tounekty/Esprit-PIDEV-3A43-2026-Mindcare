<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260217101317 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE appointment (id INT AUTO_INCREMENT NOT NULL, date DATETIME NOT NULL, location VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, status VARCHAR(20) NOT NULL, idetudiant INT DEFAULT NULL, idpsy INT DEFAULT NULL, patient_file_id INT DEFAULT NULL, INDEX IDX_FE38F844DBAB6AEE (idetudiant), INDEX IDX_FE38F8449F7E0988 (idpsy), INDEX IDX_FE38F844CEA82C87 (patient_file_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE commentaire (id INT AUTO_INCREMENT NOT NULL, author_name VARCHAR(100) NOT NULL, author_email VARCHAR(180) NOT NULL, content LONGTEXT NOT NULL, rating INT DEFAULT NULL, created_at DATETIME NOT NULL, edit_token VARCHAR(64) DEFAULT NULL, approved TINYINT(1) NOT NULL, resource_id INT NOT NULL, id_user INT NOT NULL, INDEX IDX_67F068BC89329D25 (resource_id), INDEX IDX_67F068BC6B3CA4B (id_user), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE event (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, date_event DATETIME NOT NULL, lieu VARCHAR(255) NOT NULL, capacite INT NOT NULL, id_user INT NOT NULL, INDEX IDX_3BAE0AA76B3CA4B (id_user), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE journal_emotionnel (id INT AUTO_INCREMENT NOT NULL, contenu LONGTEXT DEFAULT NULL, dateecriture DATETIME NOT NULL, mood_id INT NOT NULL, id_user INT NOT NULL, INDEX IDX_443F70FB889D33E (mood_id), INDEX IDX_443F70F6B3CA4B (id_user), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE message_forum (id INT AUTO_INCREMENT NOT NULL, contenu LONGTEXT NOT NULL, date_message DATETIME NOT NULL, attachment_path VARCHAR(255) DEFAULT NULL, attachment_mime_type VARCHAR(100) DEFAULT NULL, attachment_size INT DEFAULT NULL, id_sujet INT NOT NULL, id_user INT NOT NULL, INDEX IDX_7A8D4126C09618AD (id_sujet), INDEX IDX_7A8D41266B3CA4B (id_user), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE mood (id INT AUTO_INCREMENT NOT NULL, humeur VARCHAR(50) NOT NULL, intensite INT NOT NULL, datemood DATE NOT NULL, id_user INT NOT NULL, INDEX IDX_339AEF66B3CA4B (id_user), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE patient_file (id INT AUTO_INCREMENT NOT NULL, traitements_en_cours LONGTEXT DEFAULT NULL, allergies LONGTEXT DEFAULT NULL, contact_urgence_nom VARCHAR(255) DEFAULT NULL, contact_urgence_tel VARCHAR(20) DEFAULT NULL, antecedents_personnels LONGTEXT DEFAULT NULL, antecedents_familiaux LONGTEXT DEFAULT NULL, motif_consultation LONGTEXT DEFAULT NULL, objectifs_therapeutiques LONGTEXT DEFAULT NULL, notes_generales LONGTEXT DEFAULT NULL, niveau_risque VARCHAR(20) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, student_id INT NOT NULL, UNIQUE INDEX UNIQ_50E7BD8CB944F1A (student_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE reservation_event (id INT AUTO_INCREMENT NOT NULL, date_reservation DATETIME NOT NULL, statut VARCHAR(20) NOT NULL, nom VARCHAR(100) NOT NULL, prenom VARCHAR(100) NOT NULL, telephone VARCHAR(30) NOT NULL, commentaire LONGTEXT DEFAULT NULL, id_event INT NOT NULL, id_user INT NOT NULL, INDEX IDX_78D1DA00D52B4B97 (id_event), INDEX IDX_78D1DA006B3CA4B (id_user), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE resource (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, file_path VARCHAR(255) DEFAULT NULL, type VARCHAR(20) DEFAULT \'article\' NOT NULL, video_url VARCHAR(500) DEFAULT NULL, image_url VARCHAR(500) DEFAULT NULL, created_at DATETIME NOT NULL, id_user INT NOT NULL, INDEX IDX_BC91F4166B3CA4B (id_user), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE sujet_forum (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, date_creation DATETIME NOT NULL, image_url VARCHAR(255) DEFAULT NULL, is_pinned TINYINT(1) NOT NULL, status VARCHAR(50) DEFAULT NULL, category VARCHAR(100) DEFAULT NULL, attachment_path VARCHAR(255) DEFAULT NULL, attachment_mime_type VARCHAR(100) DEFAULT NULL, attachment_size INT DEFAULT NULL, id_user INT NOT NULL, INDEX IDX_EB9769ED6B3CA4B (id_user), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, first_name VARCHAR(255) NOT NULL, last_name VARCHAR(255) NOT NULL, email VARCHAR(180) NOT NULL, role VARCHAR(50) NOT NULL, password VARCHAR(255) NOT NULL, banned_until DATETIME DEFAULT NULL, is_verified TINYINT(1) NOT NULL, verification_token VARCHAR(255) DEFAULT NULL, created_at DATETIME DEFAULT NULL, reset_code VARCHAR(6) DEFAULT NULL, reset_code_expires_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE appointment ADD CONSTRAINT FK_FE38F844DBAB6AEE FOREIGN KEY (idetudiant) REFERENCES user (id)');
        $this->addSql('ALTER TABLE appointment ADD CONSTRAINT FK_FE38F8449F7E0988 FOREIGN KEY (idpsy) REFERENCES user (id)');
        $this->addSql('ALTER TABLE appointment ADD CONSTRAINT FK_FE38F844CEA82C87 FOREIGN KEY (patient_file_id) REFERENCES patient_file (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE commentaire ADD CONSTRAINT FK_67F068BC89329D25 FOREIGN KEY (resource_id) REFERENCES resource (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE commentaire ADD CONSTRAINT FK_67F068BC6B3CA4B FOREIGN KEY (id_user) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE event ADD CONSTRAINT FK_3BAE0AA76B3CA4B FOREIGN KEY (id_user) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE journal_emotionnel ADD CONSTRAINT FK_443F70FB889D33E FOREIGN KEY (mood_id) REFERENCES mood (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE journal_emotionnel ADD CONSTRAINT FK_443F70F6B3CA4B FOREIGN KEY (id_user) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE message_forum ADD CONSTRAINT FK_7A8D4126C09618AD FOREIGN KEY (id_sujet) REFERENCES sujet_forum (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE message_forum ADD CONSTRAINT FK_7A8D41266B3CA4B FOREIGN KEY (id_user) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE mood ADD CONSTRAINT FK_339AEF66B3CA4B FOREIGN KEY (id_user) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE patient_file ADD CONSTRAINT FK_50E7BD8CB944F1A FOREIGN KEY (student_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE reservation_event ADD CONSTRAINT FK_78D1DA00D52B4B97 FOREIGN KEY (id_event) REFERENCES event (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reservation_event ADD CONSTRAINT FK_78D1DA006B3CA4B FOREIGN KEY (id_user) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE resource ADD CONSTRAINT FK_BC91F4166B3CA4B FOREIGN KEY (id_user) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE sujet_forum ADD CONSTRAINT FK_EB9769ED6B3CA4B FOREIGN KEY (id_user) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE appointment DROP FOREIGN KEY FK_FE38F844DBAB6AEE');
        $this->addSql('ALTER TABLE appointment DROP FOREIGN KEY FK_FE38F8449F7E0988');
        $this->addSql('ALTER TABLE appointment DROP FOREIGN KEY FK_FE38F844CEA82C87');
        $this->addSql('ALTER TABLE commentaire DROP FOREIGN KEY FK_67F068BC89329D25');
        $this->addSql('ALTER TABLE commentaire DROP FOREIGN KEY FK_67F068BC6B3CA4B');
        $this->addSql('ALTER TABLE event DROP FOREIGN KEY FK_3BAE0AA76B3CA4B');
        $this->addSql('ALTER TABLE journal_emotionnel DROP FOREIGN KEY FK_443F70FB889D33E');
        $this->addSql('ALTER TABLE journal_emotionnel DROP FOREIGN KEY FK_443F70F6B3CA4B');
        $this->addSql('ALTER TABLE message_forum DROP FOREIGN KEY FK_7A8D4126C09618AD');
        $this->addSql('ALTER TABLE message_forum DROP FOREIGN KEY FK_7A8D41266B3CA4B');
        $this->addSql('ALTER TABLE mood DROP FOREIGN KEY FK_339AEF66B3CA4B');
        $this->addSql('ALTER TABLE patient_file DROP FOREIGN KEY FK_50E7BD8CB944F1A');
        $this->addSql('ALTER TABLE reservation_event DROP FOREIGN KEY FK_78D1DA00D52B4B97');
        $this->addSql('ALTER TABLE reservation_event DROP FOREIGN KEY FK_78D1DA006B3CA4B');
        $this->addSql('ALTER TABLE resource DROP FOREIGN KEY FK_BC91F4166B3CA4B');
        $this->addSql('ALTER TABLE sujet_forum DROP FOREIGN KEY FK_EB9769ED6B3CA4B');
        $this->addSql('DROP TABLE appointment');
        $this->addSql('DROP TABLE commentaire');
        $this->addSql('DROP TABLE event');
        $this->addSql('DROP TABLE journal_emotionnel');
        $this->addSql('DROP TABLE message_forum');
        $this->addSql('DROP TABLE mood');
        $this->addSql('DROP TABLE patient_file');
        $this->addSql('DROP TABLE reservation_event');
        $this->addSql('DROP TABLE resource');
        $this->addSql('DROP TABLE sujet_forum');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
