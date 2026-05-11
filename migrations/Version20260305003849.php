<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260305003849 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commentaire ADD created_by_id INT DEFAULT NULL, ADD updated_by_id INT DEFAULT NULL, DROP created_by, DROP updated_by');
        $this->addSql('ALTER TABLE commentaire ADD CONSTRAINT FK_67F068BCB03A8386 FOREIGN KEY (created_by_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE commentaire ADD CONSTRAINT FK_67F068BC896DBBDE FOREIGN KEY (updated_by_id) REFERENCES users (id)');
        $this->addSql('CREATE INDEX IDX_67F068BCB03A8386 ON commentaire (created_by_id)');
        $this->addSql('CREATE INDEX IDX_67F068BC896DBBDE ON commentaire (updated_by_id)');
        $this->addSql('ALTER TABLE message_forum_analysis ADD created_by_id INT DEFAULT NULL, ADD updated_by_id INT DEFAULT NULL, DROP created_by, DROP updated_by');
        $this->addSql('ALTER TABLE message_forum_analysis ADD CONSTRAINT FK_79571E9FB03A8386 FOREIGN KEY (created_by_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE message_forum_analysis ADD CONSTRAINT FK_79571E9F896DBBDE FOREIGN KEY (updated_by_id) REFERENCES users (id)');
        $this->addSql('CREATE INDEX IDX_79571E9FB03A8386 ON message_forum_analysis (created_by_id)');
        $this->addSql('CREATE INDEX IDX_79571E9F896DBBDE ON message_forum_analysis (updated_by_id)');
        $this->addSql('ALTER TABLE patient_file ADD created_by_id INT DEFAULT NULL, ADD updated_by_id INT DEFAULT NULL, DROP created_by, DROP updated_by');
        $this->addSql('ALTER TABLE patient_file ADD CONSTRAINT FK_50E7BD8B03A8386 FOREIGN KEY (created_by_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE patient_file ADD CONSTRAINT FK_50E7BD8896DBBDE FOREIGN KEY (updated_by_id) REFERENCES users (id)');
        $this->addSql('CREATE INDEX IDX_50E7BD8B03A8386 ON patient_file (created_by_id)');
        $this->addSql('CREATE INDEX IDX_50E7BD8896DBBDE ON patient_file (updated_by_id)');
        $this->addSql('ALTER TABLE psychological_alert ADD created_by_id INT DEFAULT NULL, ADD updated_by_id INT DEFAULT NULL, DROP created_by, DROP updated_by');
        $this->addSql('ALTER TABLE psychological_alert ADD CONSTRAINT FK_4071ECE5B03A8386 FOREIGN KEY (created_by_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE psychological_alert ADD CONSTRAINT FK_4071ECE5896DBBDE FOREIGN KEY (updated_by_id) REFERENCES users (id)');
        $this->addSql('CREATE INDEX IDX_4071ECE5B03A8386 ON psychological_alert (created_by_id)');
        $this->addSql('CREATE INDEX IDX_4071ECE5896DBBDE ON psychological_alert (updated_by_id)');
        $this->addSql('ALTER TABLE resource ADD created_by_id INT DEFAULT NULL, ADD updated_by_id INT DEFAULT NULL, DROP created_by, DROP updated_by');
        $this->addSql('ALTER TABLE resource ADD CONSTRAINT FK_BC91F416B03A8386 FOREIGN KEY (created_by_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE resource ADD CONSTRAINT FK_BC91F416896DBBDE FOREIGN KEY (updated_by_id) REFERENCES users (id)');
        $this->addSql('CREATE INDEX IDX_BC91F416B03A8386 ON resource (created_by_id)');
        $this->addSql('CREATE INDEX IDX_BC91F416896DBBDE ON resource (updated_by_id)');
        $this->addSql('ALTER TABLE users ADD created_by_id INT DEFAULT NULL, ADD updated_by_id INT DEFAULT NULL, DROP created_by, DROP updated_by');
        $this->addSql('ALTER TABLE users ADD CONSTRAINT FK_1483A5E9B03A8386 FOREIGN KEY (created_by_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE users ADD CONSTRAINT FK_1483A5E9896DBBDE FOREIGN KEY (updated_by_id) REFERENCES users (id)');
        $this->addSql('CREATE INDEX IDX_1483A5E9B03A8386 ON users (created_by_id)');
        $this->addSql('CREATE INDEX IDX_1483A5E9896DBBDE ON users (updated_by_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commentaire DROP FOREIGN KEY FK_67F068BCB03A8386');
        $this->addSql('ALTER TABLE commentaire DROP FOREIGN KEY FK_67F068BC896DBBDE');
        $this->addSql('DROP INDEX IDX_67F068BCB03A8386 ON commentaire');
        $this->addSql('DROP INDEX IDX_67F068BC896DBBDE ON commentaire');
        $this->addSql('ALTER TABLE commentaire ADD created_by VARCHAR(255) DEFAULT NULL, ADD updated_by VARCHAR(255) DEFAULT NULL, DROP created_by_id, DROP updated_by_id');
        $this->addSql('ALTER TABLE message_forum_analysis DROP FOREIGN KEY FK_79571E9FB03A8386');
        $this->addSql('ALTER TABLE message_forum_analysis DROP FOREIGN KEY FK_79571E9F896DBBDE');
        $this->addSql('DROP INDEX IDX_79571E9FB03A8386 ON message_forum_analysis');
        $this->addSql('DROP INDEX IDX_79571E9F896DBBDE ON message_forum_analysis');
        $this->addSql('ALTER TABLE message_forum_analysis ADD created_by VARCHAR(255) DEFAULT NULL, ADD updated_by VARCHAR(255) DEFAULT NULL, DROP created_by_id, DROP updated_by_id');
        $this->addSql('ALTER TABLE patient_file DROP FOREIGN KEY FK_50E7BD8B03A8386');
        $this->addSql('ALTER TABLE patient_file DROP FOREIGN KEY FK_50E7BD8896DBBDE');
        $this->addSql('DROP INDEX IDX_50E7BD8B03A8386 ON patient_file');
        $this->addSql('DROP INDEX IDX_50E7BD8896DBBDE ON patient_file');
        $this->addSql('ALTER TABLE patient_file ADD created_by VARCHAR(255) DEFAULT NULL, ADD updated_by VARCHAR(255) DEFAULT NULL, DROP created_by_id, DROP updated_by_id');
        $this->addSql('ALTER TABLE psychological_alert DROP FOREIGN KEY FK_4071ECE5B03A8386');
        $this->addSql('ALTER TABLE psychological_alert DROP FOREIGN KEY FK_4071ECE5896DBBDE');
        $this->addSql('DROP INDEX IDX_4071ECE5B03A8386 ON psychological_alert');
        $this->addSql('DROP INDEX IDX_4071ECE5896DBBDE ON psychological_alert');
        $this->addSql('ALTER TABLE psychological_alert ADD created_by VARCHAR(255) DEFAULT NULL, ADD updated_by VARCHAR(255) DEFAULT NULL, DROP created_by_id, DROP updated_by_id');
        $this->addSql('ALTER TABLE resource DROP FOREIGN KEY FK_BC91F416B03A8386');
        $this->addSql('ALTER TABLE resource DROP FOREIGN KEY FK_BC91F416896DBBDE');
        $this->addSql('DROP INDEX IDX_BC91F416B03A8386 ON resource');
        $this->addSql('DROP INDEX IDX_BC91F416896DBBDE ON resource');
        $this->addSql('ALTER TABLE resource ADD created_by VARCHAR(255) DEFAULT NULL, ADD updated_by VARCHAR(255) DEFAULT NULL, DROP created_by_id, DROP updated_by_id');
        $this->addSql('ALTER TABLE users DROP FOREIGN KEY FK_1483A5E9B03A8386');
        $this->addSql('ALTER TABLE users DROP FOREIGN KEY FK_1483A5E9896DBBDE');
        $this->addSql('DROP INDEX IDX_1483A5E9B03A8386 ON users');
        $this->addSql('DROP INDEX IDX_1483A5E9896DBBDE ON users');
        $this->addSql('ALTER TABLE users ADD created_by VARCHAR(255) DEFAULT NULL, ADD updated_by VARCHAR(255) DEFAULT NULL, DROP created_by_id, DROP updated_by_id');
    }
}
