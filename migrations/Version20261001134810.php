<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261001134810 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Scope username uniqueness to the contractor.';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX uniq_user_username');
        $this->addSql('CREATE UNIQUE INDEX uniq_user_contractor_username ON "user" (contractor_id, username)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX uniq_user_contractor_username');
        $this->addSql('CREATE UNIQUE INDEX uniq_user_username ON "user" (username)');
    }
}
