<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260209165800 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE appointment (id INT AUTO_INCREMENT NOT NULL, date DATETIME NOT NULL, location VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, idetudiant INT DEFAULT NULL, idpsy INT DEFAULT NULL, INDEX IDX_FE38F844DBAB6AEE (idetudiant), INDEX IDX_FE38F8449F7E0988 (idpsy), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE appointment ADD CONSTRAINT FK_FE38F844DBAB6AEE FOREIGN KEY (idetudiant) REFERENCES user (id)');
        $this->addSql('ALTER TABLE appointment ADD CONSTRAINT FK_FE38F8449F7E0988 FOREIGN KEY (idpsy) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE appointment DROP FOREIGN KEY FK_FE38F844DBAB6AEE');
        $this->addSql('ALTER TABLE appointment DROP FOREIGN KEY FK_FE38F8449F7E0988');
        $this->addSql('DROP TABLE appointment');
    }
}
