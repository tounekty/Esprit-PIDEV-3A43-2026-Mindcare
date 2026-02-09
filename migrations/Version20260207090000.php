<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260207090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create sujet_forum and message_forum tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE sujet_forum (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, date_creation DATETIME NOT NULL, id_user INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE message_forum (id INT AUTO_INCREMENT NOT NULL, id_sujet INT NOT NULL, contenu LONGTEXT NOT NULL, date_message DATETIME NOT NULL, id_user INT NOT NULL, INDEX IDX_5F2DB1E730F03DF8 (id_sujet), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE message_forum ADD CONSTRAINT FK_5F2DB1E730F03DF8 FOREIGN KEY (id_sujet) REFERENCES sujet_forum (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE message_forum DROP FOREIGN KEY FK_5F2DB1E730F03DF8');
        $this->addSql('DROP TABLE message_forum');
        $this->addSql('DROP TABLE sujet_forum');
    }
}
