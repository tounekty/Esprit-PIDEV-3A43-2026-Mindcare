<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260225120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create sujet_tagged_psychologue join table for tagged psychologists on forum topics.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE sujet_tagged_psychologue (id_sujet INT NOT NULL, id_psychologue INT NOT NULL, INDEX IDX_12B2F8DD6CB8D25A (id_sujet), INDEX IDX_12B2F8DDEFB876A4 (id_psychologue), UNIQUE INDEX uniq_sujet_psychologue_tag (id_sujet, id_psychologue), PRIMARY KEY(id_sujet, id_psychologue)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE sujet_tagged_psychologue ADD CONSTRAINT FK_12B2F8DD6CB8D25A FOREIGN KEY (id_sujet) REFERENCES sujet_forum (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE sujet_tagged_psychologue ADD CONSTRAINT FK_12B2F8DDEFB876A4 FOREIGN KEY (id_psychologue) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE sujet_tagged_psychologue');
    }
}
