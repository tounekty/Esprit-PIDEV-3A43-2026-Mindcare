<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260211173000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add event and reservation_event tables.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE event (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, date_event DATETIME NOT NULL, lieu VARCHAR(255) NOT NULL, capacite INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE reservation_event (id INT AUTO_INCREMENT NOT NULL, date_reservation DATETIME NOT NULL, statut VARCHAR(20) NOT NULL, id_event INT NOT NULL, id_user INT NOT NULL, INDEX IDX_2FDE7E0F4A1F23D1 (id_event), INDEX IDX_2FDE7E0F6B3CA4B (id_user), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE reservation_event ADD CONSTRAINT FK_2FDE7E0F4A1F23D1 FOREIGN KEY (id_event) REFERENCES event (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reservation_event ADD CONSTRAINT FK_2FDE7E0F6B3CA4B FOREIGN KEY (id_user) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE reservation_event DROP FOREIGN KEY FK_2FDE7E0F4A1F23D1');
        $this->addSql('ALTER TABLE reservation_event DROP FOREIGN KEY FK_2FDE7E0F6B3CA4B');
        $this->addSql('DROP TABLE reservation_event');
        $this->addSql('DROP TABLE event');
    }
}
