<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260225103000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create like_message table for one-like-per-user-per-message in forum.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE like_message (id INT AUTO_INCREMENT NOT NULL, id_user INT NOT NULL, id_message INT NOT NULL, INDEX IDX_677533A56B3CA4B (id_user), INDEX IDX_677533A2537A1329 (id_message), UNIQUE INDEX uniq_like_user_message (id_user, id_message), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE like_message ADD CONSTRAINT FK_677533A56B3CA4B FOREIGN KEY (id_user) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE like_message ADD CONSTRAINT FK_677533A2537A1329 FOREIGN KEY (id_message) REFERENCES message_forum (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE like_message');
    }
}
