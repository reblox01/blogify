<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260111180423 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Enable RLS on users and posts
        $this->addSql('ALTER TABLE users ENABLE ROW LEVEL SECURITY');
        $this->addSql('ALTER TABLE posts ENABLE ROW LEVEL SECURITY');
    }

    public function down(Schema $schema): void
    {
        // Disable RLS
        $this->addSql('ALTER TABLE users DISABLE ROW LEVEL SECURITY');
        $this->addSql('ALTER TABLE posts DISABLE ROW LEVEL SECURITY');
    }
}
