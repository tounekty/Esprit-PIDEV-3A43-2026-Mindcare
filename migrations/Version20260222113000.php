<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260222113000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add anonymous flag to forum subjects and messages.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE sujet_forum ADD is_anonymous TINYINT(1) NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE message_forum ADD is_anonymous TINYINT(1) NOT NULL DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE message_forum DROP is_anonymous');
        $this->addSql('ALTER TABLE sujet_forum DROP is_anonymous');
    }
}
