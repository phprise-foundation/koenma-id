<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260912141613 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE api_key (id UUID NOT NULL, key_hash VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, key_prefix VARCHAR(16) NOT NULL, key_suffix VARCHAR(16) NOT NULL, expires_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, project_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C912ED9D57BFB971 ON api_key (key_hash)');
        $this->addSql('CREATE INDEX IDX_C912ED9D166D1F9C ON api_key (project_id)');
        $this->addSql('CREATE TABLE contractor (id UUID NOT NULL, name VARCHAR(255) NOT NULL, document VARCHAR(32) NOT NULL, active BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, partner_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_437BD2EFD8698A76 ON contractor (document)');
        $this->addSql('CREATE INDEX IDX_437BD2EF9393F8FE ON contractor (partner_id)');
        $this->addSql('CREATE TABLE partner (id UUID NOT NULL, name VARCHAR(255) NOT NULL, email_address VARCHAR(255) NOT NULL, email_verified BOOLEAN NOT NULL, document VARCHAR(32) NOT NULL, active BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_312B3E16B08E074E ON partner (email_address)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_312B3E16D8698A76 ON partner (document)');
        $this->addSql('CREATE TABLE project (id UUID NOT NULL, name VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, active BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, partner_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_2FB3D0EE9393F8FE ON project (partner_id)');
        $this->addSql('CREATE TABLE refresh_token (id UUID NOT NULL, token_hash VARCHAR(255) NOT NULL, expires_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, revoked_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, user_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C74F2195B3BC57DA ON refresh_token (token_hash)');
        $this->addSql('CREATE INDEX IDX_C74F2195A76ED395 ON refresh_token (user_id)');
        $this->addSql('CREATE TABLE "user" (id UUID NOT NULL, email_address VARCHAR(255) NOT NULL, email_verified BOOLEAN NOT NULL, username VARCHAR(180) NOT NULL, password VARCHAR(255) NOT NULL, active BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, contractor_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_user_username ON "user" (username)');
        $this->addSql('CREATE INDEX IDX_8D93D649B0265DC7 ON "user" (contractor_id)');
        $this->addSql('ALTER TABLE api_key ADD CONSTRAINT FK_C912ED9D166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE contractor ADD CONSTRAINT FK_437BD2EF9393F8FE FOREIGN KEY (partner_id) REFERENCES partner (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE project ADD CONSTRAINT FK_2FB3D0EE9393F8FE FOREIGN KEY (partner_id) REFERENCES partner (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE refresh_token ADD CONSTRAINT FK_C74F2195A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE "user" ADD CONSTRAINT FK_8D93D649B0265DC7 FOREIGN KEY (contractor_id) REFERENCES contractor (id) NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE api_key DROP CONSTRAINT FK_C912ED9D166D1F9C');
        $this->addSql('ALTER TABLE contractor DROP CONSTRAINT FK_437BD2EF9393F8FE');
        $this->addSql('ALTER TABLE project DROP CONSTRAINT FK_2FB3D0EE9393F8FE');
        $this->addSql('ALTER TABLE refresh_token DROP CONSTRAINT FK_C74F2195A76ED395');
        $this->addSql('ALTER TABLE "user" DROP CONSTRAINT FK_8D93D649B0265DC7');
        $this->addSql('DROP TABLE api_key');
        $this->addSql('DROP TABLE contractor');
        $this->addSql('DROP TABLE partner');
        $this->addSql('DROP TABLE project');
        $this->addSql('DROP TABLE refresh_token');
        $this->addSql('DROP TABLE "user"');
    }
}
