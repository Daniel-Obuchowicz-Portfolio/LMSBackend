<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240715224634 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE borrowings DROP INDEX UNIQ_7547A7B416A2B381, ADD INDEX IDX_7547A7B416A2B381 (book_id)');
        $this->addSql('ALTER TABLE borrowings DROP INDEX UNIQ_7547A7B4A76ED395, ADD INDEX IDX_7547A7B4A76ED395 (user_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE borrowings DROP INDEX IDX_7547A7B416A2B381, ADD UNIQUE INDEX UNIQ_7547A7B416A2B381 (book_id)');
        $this->addSql('ALTER TABLE borrowings DROP INDEX IDX_7547A7B4A76ED395, ADD UNIQUE INDEX UNIQ_7547A7B4A76ED395 (user_id)');
    }
}
