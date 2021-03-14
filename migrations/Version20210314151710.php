<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210314151710 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE course RENAME COLUMN name_course TO name');
        $this->addSql('ALTER TABLE lesson RENAME COLUMN name_lesson TO name');
        $this->addSql('ALTER TABLE lesson RENAME COLUMN content_lesson TO content');
        $this->addSql('ALTER TABLE lesson RENAME COLUMN number_lesson TO number');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE course RENAME COLUMN name TO name_course');
        $this->addSql('ALTER TABLE lesson RENAME COLUMN name TO name_lesson');
        $this->addSql('ALTER TABLE lesson RENAME COLUMN content TO content_lesson');
        $this->addSql('ALTER TABLE lesson RENAME COLUMN number TO number_lesson');
    }
}
