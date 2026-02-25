<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260225133000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add parent_message_id to message_forum for nested replies.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE message_forum ADD parent_message_id INT DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_129887171D8B4AE5 ON message_forum (parent_message_id)');
        $this->addSql('ALTER TABLE message_forum ADD CONSTRAINT FK_129887171D8B4AE5 FOREIGN KEY (parent_message_id) REFERENCES message_forum (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE message_forum DROP FOREIGN KEY FK_129887171D8B4AE5');
        $this->addSql('DROP INDEX IDX_129887171D8B4AE5 ON message_forum');
        $this->addSql('ALTER TABLE message_forum DROP parent_message_id');
    }
}
