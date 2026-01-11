<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260111183247 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE posts ADD image_data TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE posts ADD image_mime_type VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE posts ALTER image_path DROP NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE posts DROP image_data');
        $this->addSql('ALTER TABLE posts DROP image_mime_type');
        $this->addSql('ALTER TABLE posts ALTER image_path SET NOT NULL');
    }
}
