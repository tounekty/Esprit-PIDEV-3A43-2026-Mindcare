<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260210231125 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE appointment ADD patient_file_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE appointment ADD CONSTRAINT FK_FE38F844CEA82C87 FOREIGN KEY (patient_file_id) REFERENCES patient_file (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_FE38F844CEA82C87 ON appointment (patient_file_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE appointment DROP FOREIGN KEY FK_FE38F844CEA82C87');
        $this->addSql('DROP INDEX IDX_FE38F844CEA82C87 ON appointment');
        $this->addSql('ALTER TABLE appointment DROP patient_file_id');
    }
}
