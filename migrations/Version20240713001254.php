<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240713001254 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE borrowings (id INT AUTO_INCREMENT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE borrowings_user (borrowings_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_C2D03FCCADE86D94 (borrowings_id), INDEX IDX_C2D03FCCA76ED395 (user_id), PRIMARY KEY(borrowings_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE borrowings_book (borrowings_id INT NOT NULL, book_id INT NOT NULL, INDEX IDX_84A64AB4ADE86D94 (borrowings_id), INDEX IDX_84A64AB416A2B381 (book_id), PRIMARY KEY(borrowings_id, book_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE borrowings_user ADD CONSTRAINT FK_C2D03FCCADE86D94 FOREIGN KEY (borrowings_id) REFERENCES borrowings (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE borrowings_user ADD CONSTRAINT FK_C2D03FCCA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE borrowings_book ADD CONSTRAINT FK_84A64AB4ADE86D94 FOREIGN KEY (borrowings_id) REFERENCES borrowings (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE borrowings_book ADD CONSTRAINT FK_84A64AB416A2B381 FOREIGN KEY (book_id) REFERENCES book (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE borrowings_user DROP FOREIGN KEY FK_C2D03FCCADE86D94');
        $this->addSql('ALTER TABLE borrowings_user DROP FOREIGN KEY FK_C2D03FCCA76ED395');
        $this->addSql('ALTER TABLE borrowings_book DROP FOREIGN KEY FK_84A64AB4ADE86D94');
        $this->addSql('ALTER TABLE borrowings_book DROP FOREIGN KEY FK_84A64AB416A2B381');
        $this->addSql('DROP TABLE borrowings');
        $this->addSql('DROP TABLE borrowings_user');
        $this->addSql('DROP TABLE borrowings_book');
    }
}
