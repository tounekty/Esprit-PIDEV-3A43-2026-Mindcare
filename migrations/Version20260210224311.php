<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260210224311 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE patient_file (id INT AUTO_INCREMENT NOT NULL, traitements_en_cours LONGTEXT DEFAULT NULL, allergies LONGTEXT DEFAULT NULL, contact_urgence_nom VARCHAR(255) DEFAULT NULL, contact_urgence_tel VARCHAR(20) DEFAULT NULL, antecedents_personnels LONGTEXT DEFAULT NULL, antecedents_familiaux LONGTEXT DEFAULT NULL, motif_consultation LONGTEXT DEFAULT NULL, objectifs_therapeutiques LONGTEXT DEFAULT NULL, notes_generales LONGTEXT DEFAULT NULL, niveau_risque VARCHAR(20) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, student_id INT NOT NULL, UNIQUE INDEX UNIQ_50E7BD8CB944F1A (student_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE patient_file ADD CONSTRAINT FK_50E7BD8CB944F1A FOREIGN KEY (student_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE patient_file DROP FOREIGN KEY FK_50E7BD8CB944F1A');
        $this->addSql('DROP TABLE patient_file');
    }
}
