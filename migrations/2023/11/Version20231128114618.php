<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231128114618 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE category_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE collection_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE collection_import_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE placeholder_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE prompt_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE prompt_template_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE shared_collection_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE "user_id_seq" INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE category (id INT NOT NULL, user_id INT NOT NULL, title VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_64C19C1A76ED395 ON category (user_id)');
        $this->addSql('COMMENT ON COLUMN category.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN category.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE collection (id INT NOT NULL, user_id INT NOT NULL, made_public_by_user_id INT DEFAULT NULL, title VARCHAR(100) NOT NULL, description TEXT DEFAULT NULL, is_public BOOLEAN DEFAULT false NOT NULL, is_favorite BOOLEAN DEFAULT false NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_FC4D6532A76ED395 ON collection (user_id)');
        $this->addSql('CREATE INDEX IDX_FC4D65323D9EFFA5 ON collection (made_public_by_user_id)');
        $this->addSql('COMMENT ON COLUMN collection.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN collection.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE collection_prompt_template (collection_id INT NOT NULL, prompt_template_id INT NOT NULL, PRIMARY KEY(collection_id, prompt_template_id))');
        $this->addSql('CREATE INDEX IDX_1764EEB4514956FD ON collection_prompt_template (collection_id)');
        $this->addSql('CREATE INDEX IDX_1764EEB4A7F0069D ON collection_prompt_template (prompt_template_id)');
        $this->addSql('CREATE TABLE collection_placeholder (collection_id INT NOT NULL, placeholder_id INT NOT NULL, PRIMARY KEY(collection_id, placeholder_id))');
        $this->addSql('CREATE INDEX IDX_694C0383514956FD ON collection_placeholder (collection_id)');
        $this->addSql('CREATE INDEX IDX_694C0383DA75C033 ON collection_placeholder (placeholder_id)');
        $this->addSql('CREATE TABLE collection_import (id INT NOT NULL, user_id INT NOT NULL, github_url VARCHAR(255) DEFAULT NULL, status VARCHAR(50) DEFAULT NULL, file_path VARCHAR(255) DEFAULT NULL, file_type VARCHAR(255) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_6F6B36EBA76ED395 ON collection_import (user_id)');
        $this->addSql('COMMENT ON COLUMN collection_import.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN collection_import.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE placeholder (id INT NOT NULL, user_id INT NOT NULL, key VARCHAR(100) NOT NULL, value TEXT NOT NULL, headline VARCHAR(100) DEFAULT \'\' NOT NULL, description TEXT DEFAULT \'\', is_favorite BOOLEAN DEFAULT false NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_F5E69F02A76ED395 ON placeholder (user_id)');
        $this->addSql('COMMENT ON COLUMN placeholder.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN placeholder.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE placeholder_category (placeholder_id INT NOT NULL, category_id INT NOT NULL, PRIMARY KEY(placeholder_id, category_id))');
        $this->addSql('CREATE INDEX IDX_D5F5C9FDDA75C033 ON placeholder_category (placeholder_id)');
        $this->addSql('CREATE INDEX IDX_D5F5C9FD12469DE2 ON placeholder_category (category_id)');
        $this->addSql('CREATE TABLE prompt (id INT NOT NULL, user_id INT NOT NULL, prompt_template_id INT DEFAULT NULL, title VARCHAR(255) DEFAULT NULL, content TEXT NOT NULL, openai_response TEXT DEFAULT NULL, openai_raw_response JSON DEFAULT NULL, content_without_placeholders TEXT DEFAULT NULL, status VARCHAR(100) DEFAULT \'created\' NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_62EF6C38A76ED395 ON prompt (user_id)');
        $this->addSql('CREATE INDEX IDX_62EF6C38A7F0069D ON prompt (prompt_template_id)');
        $this->addSql('COMMENT ON COLUMN prompt.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN prompt.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE prompt_template (id INT NOT NULL, user_id INT NOT NULL, category_id INT DEFAULT NULL, title VARCHAR(255) NOT NULL, content TEXT NOT NULL, is_favorite BOOLEAN DEFAULT false NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_1BD9F572A76ED395 ON prompt_template (user_id)');
        $this->addSql('CREATE INDEX IDX_1BD9F57212469DE2 ON prompt_template (category_id)');
        $this->addSql('COMMENT ON COLUMN prompt_template.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN prompt_template.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE shared_collection (id INT NOT NULL, collection_id INT NOT NULL, shared_by_user_id INT NOT NULL, shared_with_user_id INT NOT NULL, permissions INT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_885E85FD514956FD ON shared_collection (collection_id)');
        $this->addSql('CREATE INDEX IDX_885E85FDA88FC4FB ON shared_collection (shared_by_user_id)');
        $this->addSql('CREATE INDEX IDX_885E85FD42EBB09C ON shared_collection (shared_with_user_id)');
        $this->addSql('COMMENT ON COLUMN shared_collection.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN shared_collection.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE "user" (id INT NOT NULL, name VARCHAR(100) DEFAULT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, avatar VARCHAR(255) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON "user" (email)');
        $this->addSql('COMMENT ON COLUMN "user".created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN "user".updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE messenger_messages (id BIGSERIAL NOT NULL, body TEXT NOT NULL, headers TEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, available_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, delivered_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0 ON messenger_messages (queue_name)');
        $this->addSql('CREATE INDEX IDX_75EA56E0E3BD61CE ON messenger_messages (available_at)');
        $this->addSql('CREATE INDEX IDX_75EA56E016BA31DB ON messenger_messages (delivered_at)');
        $this->addSql('COMMENT ON COLUMN messenger_messages.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN messenger_messages.available_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN messenger_messages.delivered_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE OR REPLACE FUNCTION notify_messenger_messages() RETURNS TRIGGER AS $$
            BEGIN
                PERFORM pg_notify(\'messenger_messages\', NEW.queue_name::text);
                RETURN NEW;
            END;
        $$ LANGUAGE plpgsql;');
        $this->addSql('DROP TRIGGER IF EXISTS notify_trigger ON messenger_messages;');
        $this->addSql('CREATE TRIGGER notify_trigger AFTER INSERT OR UPDATE ON messenger_messages FOR EACH ROW EXECUTE PROCEDURE notify_messenger_messages();');
        $this->addSql('ALTER TABLE category ADD CONSTRAINT FK_64C19C1A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE collection ADD CONSTRAINT FK_FC4D6532A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE collection ADD CONSTRAINT FK_FC4D65323D9EFFA5 FOREIGN KEY (made_public_by_user_id) REFERENCES "user" (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE collection_prompt_template ADD CONSTRAINT FK_1764EEB4514956FD FOREIGN KEY (collection_id) REFERENCES collection (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE collection_prompt_template ADD CONSTRAINT FK_1764EEB4A7F0069D FOREIGN KEY (prompt_template_id) REFERENCES prompt_template (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE collection_placeholder ADD CONSTRAINT FK_694C0383514956FD FOREIGN KEY (collection_id) REFERENCES collection (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE collection_placeholder ADD CONSTRAINT FK_694C0383DA75C033 FOREIGN KEY (placeholder_id) REFERENCES placeholder (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE collection_import ADD CONSTRAINT FK_6F6B36EBA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE placeholder ADD CONSTRAINT FK_F5E69F02A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE placeholder_category ADD CONSTRAINT FK_D5F5C9FDDA75C033 FOREIGN KEY (placeholder_id) REFERENCES placeholder (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE placeholder_category ADD CONSTRAINT FK_D5F5C9FD12469DE2 FOREIGN KEY (category_id) REFERENCES category (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE prompt ADD CONSTRAINT FK_62EF6C38A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE prompt ADD CONSTRAINT FK_62EF6C38A7F0069D FOREIGN KEY (prompt_template_id) REFERENCES prompt_template (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE prompt_template ADD CONSTRAINT FK_1BD9F572A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE prompt_template ADD CONSTRAINT FK_1BD9F57212469DE2 FOREIGN KEY (category_id) REFERENCES category (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE shared_collection ADD CONSTRAINT FK_885E85FD514956FD FOREIGN KEY (collection_id) REFERENCES collection (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE shared_collection ADD CONSTRAINT FK_885E85FDA88FC4FB FOREIGN KEY (shared_by_user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE shared_collection ADD CONSTRAINT FK_885E85FD42EBB09C FOREIGN KEY (shared_with_user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP SEQUENCE category_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE collection_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE collection_import_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE placeholder_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE prompt_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE prompt_template_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE shared_collection_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE "user_id_seq" CASCADE');
        $this->addSql('ALTER TABLE category DROP CONSTRAINT FK_64C19C1A76ED395');
        $this->addSql('ALTER TABLE collection DROP CONSTRAINT FK_FC4D6532A76ED395');
        $this->addSql('ALTER TABLE collection DROP CONSTRAINT FK_FC4D65323D9EFFA5');
        $this->addSql('ALTER TABLE collection_prompt_template DROP CONSTRAINT FK_1764EEB4514956FD');
        $this->addSql('ALTER TABLE collection_prompt_template DROP CONSTRAINT FK_1764EEB4A7F0069D');
        $this->addSql('ALTER TABLE collection_placeholder DROP CONSTRAINT FK_694C0383514956FD');
        $this->addSql('ALTER TABLE collection_placeholder DROP CONSTRAINT FK_694C0383DA75C033');
        $this->addSql('ALTER TABLE collection_import DROP CONSTRAINT FK_6F6B36EBA76ED395');
        $this->addSql('ALTER TABLE placeholder DROP CONSTRAINT FK_F5E69F02A76ED395');
        $this->addSql('ALTER TABLE placeholder_category DROP CONSTRAINT FK_D5F5C9FDDA75C033');
        $this->addSql('ALTER TABLE placeholder_category DROP CONSTRAINT FK_D5F5C9FD12469DE2');
        $this->addSql('ALTER TABLE prompt DROP CONSTRAINT FK_62EF6C38A76ED395');
        $this->addSql('ALTER TABLE prompt DROP CONSTRAINT FK_62EF6C38A7F0069D');
        $this->addSql('ALTER TABLE prompt_template DROP CONSTRAINT FK_1BD9F572A76ED395');
        $this->addSql('ALTER TABLE prompt_template DROP CONSTRAINT FK_1BD9F57212469DE2');
        $this->addSql('ALTER TABLE shared_collection DROP CONSTRAINT FK_885E85FD514956FD');
        $this->addSql('ALTER TABLE shared_collection DROP CONSTRAINT FK_885E85FDA88FC4FB');
        $this->addSql('ALTER TABLE shared_collection DROP CONSTRAINT FK_885E85FD42EBB09C');
        $this->addSql('DROP TABLE category');
        $this->addSql('DROP TABLE collection');
        $this->addSql('DROP TABLE collection_prompt_template');
        $this->addSql('DROP TABLE collection_placeholder');
        $this->addSql('DROP TABLE collection_import');
        $this->addSql('DROP TABLE placeholder');
        $this->addSql('DROP TABLE placeholder_category');
        $this->addSql('DROP TABLE prompt');
        $this->addSql('DROP TABLE prompt_template');
        $this->addSql('DROP TABLE shared_collection');
        $this->addSql('DROP TABLE "user"');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
