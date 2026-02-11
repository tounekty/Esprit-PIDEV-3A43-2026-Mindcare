<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260211181500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add reservation form fields to reservation_event.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE reservation_event ADD nom VARCHAR(100) NOT NULL, ADD prenom VARCHAR(100) NOT NULL, ADD telephone VARCHAR(30) NOT NULL, ADD commentaire LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE reservation_event DROP commentaire, DROP telephone, DROP prenom, DROP nom');
    }
}
