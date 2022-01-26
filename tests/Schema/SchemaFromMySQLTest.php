<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   03 Dec 2020
 */

declare(strict_types=1);

namespace LSS\YADbal\Schema;

use LSS\YADbal\DatabaseConnection;
use LSS\YADbal\FakeDatabaseConnection;
use LSS\YADbal\Schema\Column\ForeignKeyColumn;
use PHPUnit\Framework\TestCase;

class SchemaFromMySQLTest extends TestCase
{
    private const PATH           = __DIR__ . DIRECTORY_SEPARATOR . 'Fixture' . DIRECTORY_SEPARATOR;
    private const TABLE_NAMES    = self::PATH . 'TableNames.json';
    private const COLUMN_INFO    = self::PATH . 'ColumnInfo.json';
    private const INDEX_INFO     = self::PATH . 'IndexInfo.json';
    private const FOREIGN_KEY    = self::PATH . 'ForeignKey.json';
    private const REFERENCED_COL = self::PATH . 'ReferencedColumn.json';

    public function testEmpty(): void
    {
        $database = $this->createMock(DatabaseConnection::class);
        $subject  = new SchemaFromMySQL($database);
        $schema   = $subject->build();
        self::assertEquals(0, $schema->getTableCount());
    }

    public function testSchema(): void
    {
        $actual = $this->getSubject();
        self::assertEquals(['company', 'company_job', 'user', 'user_mentor'], $actual->getTableNames());
        $upgradeSQL = (new SchemaUpgradeCalculator())->getUpgradeSQL($this->getWanted(), $actual);
        self::assertEquals([], $upgradeSQL);
    }

    private function getWanted(): Schema
    {
        $schema = new Schema();
        $schema->addTable(
            (new TableBuilder('company', 'Company profile'))
                ->addPrimaryKeyColumn()
                ->addIntegerColumn('capsule_id', 'Company ID from Capsule CRM if synced')
                ->addStringColumn('name', 60, 'name of company')
                ->addTextColumn('description', 'longer description in markdown format')
                ->addIntegerColumn('tech_staff', 'number of tech employed')
                ->addDateColumn('last_visit', 'date a staff member last visited the company')
                ->addBooleanColumn('is_visible', 'true if the listing is shown to the public')
                ->addDateCreatedColumn()
                ->addDateUpdatedColumn()
                ->addStandardIndex('capsule_id')
                ->build()
        );
        $schema->addTable(
            (new TableBuilder('company_job', 'Job opportunity for company'))
                ->addPrimaryKeyColumn()
                ->addForeignKeyColumn('company')
                ->addStringColumn('title', 80, 'title of job')
                ->addTextColumn('content', 'description of job in plain text')
                ->addStringColumn('web_site', 100, 'where to apply')
                ->addSetColumn('skill_level', ['Intern', 'Junior', 'Intermediate', 'Senior'])
                ->addDateColumn('date_expires', 'show the job until this date')
                ->addDateCreatedColumn()
                ->addStandardIndex('date_expires')
                ->build()
        );
        $schema->addTable(
            (new TableBuilder('user', 'Someone who can log in'))
                ->addPrimaryKeyColumn()
                ->addStringColumn('email_address', 100, 'email for user')
                ->addStringColumn('password', 255, 'encrypted password')
                ->addStringColumn('full_name', 60, 'name of user')
                ->addStringColumn('description', 1000, 'plain text about you')
                ->addJsonColumn('viewed_by', 'array of company_id and unix time')
                ->addEnumerationColumn('skill_level', ['', 'Student', 'Intern', 'Junior', 'Intermediate', 'Senior'])
                ->addEnumerationColumn('role', ['Member', 'Employer', 'Admin', 'Disabled'])
                ->addForeignKeyColumnNullable('company', 'i work for / to hide me from')
                ->addDateTimeColumn('last_login', 'Date of last successful login')
                ->addDateTimeColumn('email_confirmed', 'Date of last email confirm')
                ->addDateCreatedColumn()
                ->addDateUpdatedColumn()
                ->addUniqueIndex('email_address')
                ->build()
        );
        $schema->addTable(
            (new TableBuilder('user_mentor', 'A tag for a user'))
                ->addPrimaryKeyColumn()
                ->addForeignKeyColumn('user', '', '', ForeignKeyColumn::ACTION_CASCADE)
                ->addForeignKeyColumn('user', '', 'mentor_id', ForeignKeyColumn::ACTION_RESTRICT)
                ->addEnumerationColumn('type', ['Technical', 'Career', 'Business', 'Identity', 'Group'])
                ->addDateColumn('date_started', 'when the pairing began')
                ->addDateColumn('date_finished', 'when the pairing ended')
                ->addStandardIndex('date_started', ['date_started', 'date_finished'])
                ->build()
        );
        return $schema;
    }

    private function getSubject(): Schema
    {
        $params   = ['schema' => $databaseName = 'testdb'];
        $database = new FakeDatabaseConnection();
        $database->expectSelect('select database()', [], [$databaseName]);
        $database->expectSelect(
            '|select.*from information_schema.tables|',
            $params,
            $this->getFixture(self::TABLE_NAMES)
        );
        $database->expectSelect(
            '|select.*from information_schema.columns|',
            $params,
            $this->getFixture(self::COLUMN_INFO)
        );
        $database->expectSelect(
            '|select.*from information_schema.statistics|',
            $params,
            $this->getFixture(self::INDEX_INFO)
        );
        $database->expectSelect(
            '|select.*from information_schema.key_column_usage|',
            $params,
            $this->getFixture(self::REFERENCED_COL)
        );
        $database->expectSelect(
            '|select.*from information_schema.referential_constraints|',
            $params,
            $this->getFixture(self::FOREIGN_KEY)
        );
        $subject = new SchemaFromMySQL($database);
        return $subject->build();
    }

    private function getFixture(string $file): array
    {
        return (array) \Safe\json_decode(file_get_contents($file) ?: '', true);
    }
}
