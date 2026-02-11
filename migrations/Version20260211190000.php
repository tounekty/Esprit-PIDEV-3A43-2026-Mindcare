<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260211190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add approved column to commentaire.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE commentaire ADD approved TINYINT(1) NOT NULL DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE commentaire DROP approved');
    }
}
