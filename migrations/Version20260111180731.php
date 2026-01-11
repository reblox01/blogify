<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260111180731 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Enable RLS on messenger_messages
        $this->addSql('ALTER TABLE messenger_messages ENABLE ROW LEVEL SECURITY');
    }

    public function down(Schema $schema): void
    {
        // Disable RLS
        $this->addSql('ALTER TABLE messenger_messages DISABLE ROW LEVEL SECURITY');
    }
}
