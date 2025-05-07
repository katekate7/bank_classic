<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250422133749 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE expense DROP FOREIGN KEY FK_2D3A8DA623AE877F
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_2D3A8DA623AE877F ON expense
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE expense CHANGE category_id category_id INT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE expense ADD CONSTRAINT FK_2D3A8DA612469DE2 FOREIGN KEY (category_id) REFERENCES category (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_2D3A8DA612469DE2 ON expense (category_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE expense DROP FOREIGN KEY FK_2D3A8DA612469DE2
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_2D3A8DA612469DE2 ON expense
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE expense CHANGE category_id category_id INT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE expense ADD CONSTRAINT FK_2D3A8DA623AE877F FOREIGN KEY (category_id) REFERENCES category (id) ON UPDATE NO ACTION ON DELETE NO ACTION
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_2D3A8DA623AE877F ON expense (category_id)
        SQL);
    }
}
