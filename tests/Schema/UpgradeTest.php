<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   22 11 2018
 */

declare(strict_types=1);

namespace LSS\YADbal\Schema;

use LSS\YADbal\Schema\Index\PrimaryIndex;
use PHPUnit\Framework\TestCase;

class UpgradeTest extends TestCase
{
    private const TABLE_NAME1        = 'one';
    private const TABLE_DESCRIPTION1 = 'first description';

    private const COL1  = 'column_1';
    private const COL2  = 'column_2';
    private const COL3  = 'column_3';
    private const COL4  = 'column_4';
    private const DESC1 = 'description 1';
    private const DESC2 = 'description 2';
    private const DESC3 = 'description 3';

    public function checkTableSQL(Table $wanted, Table $actual, array $expectedSQL): void
    {
        $this->checkDatabaseSQL((new Schema())->addTable($wanted), (new Schema())->addTable($actual), $expectedSQL);
    }

    public function checkDatabaseSQL(Schema $wanted, Schema $actual, array $expectedSQL): void
    {
        $actualSQL = (new SchemaUpgradeCalculator())->getUpgradeSQL($wanted, $actual);
        self::assertEquals($expectedSQL, $actualSQL);
    }

    public function getTable1(): Table
    {
        return (new TableBuilder(self::TABLE_NAME1, self::TABLE_DESCRIPTION1))
            ->addIntegerColumn(self::COL1, self::DESC1)
            ->addIntegerColumn(self::COL2, self::DESC2)
            ->addIntegerColumn(self::COL3, self::DESC3)
            ->build();
    }

    public function testAddColumnBeginning(): void
    {
        $table1 = $this->getTable1();
        $table2 = (new TableBuilder(self::TABLE_NAME1, self::TABLE_DESCRIPTION1))
            ->addIntegerColumn(self::COL2, self::DESC2)
            ->addIntegerColumn(self::COL3, self::DESC3)
            ->build();
        $sql    = 'alter table ' . self::TABLE_NAME1 . ' add column ' . $table1->getColumn(self::COL1)->toMySQL() .
            ' first';
        $this->checkTableSQL($table1, $table2, [$sql]);
    }

    public function testAddColumnMiddle(): void
    {
        $table1 = $this->getTable1();
        $table2 = (new TableBuilder(self::TABLE_NAME1, self::TABLE_DESCRIPTION1))
            ->addIntegerColumn(self::COL1, self::DESC1)
            ->addIntegerColumn(self::COL3, self::DESC3)
            ->build();
        $sql    = 'alter table ' . self::TABLE_NAME1 . ' add column ' . $table1->getColumn(self::COL2)->toMySQL() .
            ' after ' . self::COL1;
        $this->checkTableSQL($table1, $table2, [$sql]);
    }

    public function testAddColumnEnd(): void
    {
        $table1 = $this->getTable1();
        $table2 = (new TableBuilder(self::TABLE_NAME1, self::TABLE_DESCRIPTION1))
            ->addIntegerColumn(self::COL1, self::DESC1)
            ->addIntegerColumn(self::COL2, self::DESC2)
            ->build();
        $sql    = 'alter table ' . self::TABLE_NAME1 . ' add column ' . $table1->getColumn(self::COL3)->toMySQL() .
            ' after ' . self::COL2;
        $this->checkTableSQL($table1, $table2, [$sql]);
    }

    public function testModifyColumns(): void
    {
        $table1 = $this->getTable1();
        $table2 = (new TableBuilder(self::TABLE_NAME1, self::TABLE_DESCRIPTION1))
            ->addIntegerColumn(self::COL1, self::DESC1)
            ->addIntegerColumn(self::COL2, self::DESC2 . 'x')// should change
            ->addIntegerColumn(self::COL4, self::DESC3)// should change to COL3
            ->build();

        $sql = [
            'alter table ' . self::TABLE_NAME1 . ' change ' . self::COL2 . ' ' . $table1->getColumn(self::COL2)->toMySQL(),
            'alter table ' . self::TABLE_NAME1 . ' change ' . self::COL4 . ' ' . $table1->getColumn(self::COL3)->toMySQL(),
        ];
        $this->checkTableSQL($table1, $table2, $sql);
    }

    public function testModifyColumnType(): void
    {
        $table1 = $this->getTable1();
        $table2 = (new TableBuilder(self::TABLE_NAME1, self::TABLE_DESCRIPTION1))
            ->addIntegerColumn(self::COL1, self::DESC1)
            ->addTextColumn(self::COL2, self::DESC2)// should change
            ->addIntegerColumn(self::COL3, self::DESC3)// should change to COL3
            ->build();

        $sql = [
            'alter table ' . self::TABLE_NAME1 . ' change ' . self::COL2 . ' ' . $table1->getColumn(self::COL2)->toMySQL(),
        ];
        $this->checkTableSQL($table1, $table2, $sql);
    }

    public function testDeleteFirstColumn(): void
    {
        $table1 = $this->getTable1();
        $table2 = (new TableBuilder(self::TABLE_NAME1, self::TABLE_DESCRIPTION1))
            ->addIntegerColumn($zero = 'zero', 'xx')
            ->addIntegerColumn(self::COL1, self::DESC1)
            ->addIntegerColumn(self::COL2, self::DESC2)// should change
            ->addIntegerColumn(self::COL3, self::DESC3)// should change to COL3
            ->build();
        $sql    = [
            'alter table ' . self::TABLE_NAME1 . ' drop column ' . $zero,
        ];
        $this->checkTableSQL($table1, $table2, $sql);
    }

    public function testDeleteMiddleColumns(): void
    {
        $table1 = $this->getTable1();
        $table2 = (new TableBuilder(self::TABLE_NAME1, self::TABLE_DESCRIPTION1))
            ->addIntegerColumn(self::COL1, self::DESC1)
            ->addIntegerColumn(self::COL2, self::DESC2)// should change
            ->addIntegerColumn($zero1 = 'zero1', 'xx1')
            ->addIntegerColumn($zero2 = 'zero2', 'xx2')
            ->addIntegerColumn(self::COL3, self::DESC3)// should change to COL3
            ->build();
        $sql    = [
            'alter table ' . self::TABLE_NAME1 . ' drop column ' . $zero1,
            'alter table ' . self::TABLE_NAME1 . ' drop column ' . $zero2,
        ];
        $this->checkTableSQL($table1, $table2, $sql);
    }

    public function testDeleteEndColumns(): void
    {
        $table1 = $this->getTable1();
        $table2 = (new TableBuilder(self::TABLE_NAME1, self::TABLE_DESCRIPTION1))
            ->addIntegerColumn(self::COL1, self::DESC1)
            ->addIntegerColumn(self::COL2, self::DESC2)// should change
            ->addIntegerColumn(self::COL3, self::DESC3)// should change to COL3
            ->addIntegerColumn($zero1 = 'zero1', 'xx1')
            ->addIntegerColumn($zero2 = 'zero2', 'xx2')
            ->build();
        $sql    = [
            'alter table ' . self::TABLE_NAME1 . ' drop column ' . $zero1,
            'alter table ' . self::TABLE_NAME1 . ' drop column ' . $zero2,
        ];
        $this->checkTableSQL($table1, $table2, $sql);
    }

    public function testNoChange(): void
    {
        $table1 = $this->getTable1();
        $sql    = [];
        $this->checkTableSQL($table1, $table1, $sql);
    }

    public function testUpgradeIndexes(): void
    {
        $table1 = (new TableBuilder(self::TABLE_NAME1, self::TABLE_DESCRIPTION1))
            ->addIndex(new PrimaryIndex($index1 = self::COL1))
            ->addStandardIndex($index2 = 'indextwo', [self::COL2, self::COL3])
            ->build();
        $table2 = (new TableBuilder(self::TABLE_NAME1, self::TABLE_DESCRIPTION1))
            ->addStandardIndex($index3 = 'indexthree')
            ->addStandardIndex($index1)
            ->build();
        $sql    = [
            'alter table ' . self::TABLE_NAME1 . ' drop index ' . $index3,
            'alter table ' . self::TABLE_NAME1 . ' drop index ' . $index1,
            'alter table ' . self::TABLE_NAME1 . ' add ' . $table1->getIndexes()['']->toMySQL(),
            'alter table ' . self::TABLE_NAME1 . ' add ' . $table1->getIndexes()[$index2]->toMySQL(),
        ];
        $this->checkTableSQL($table1, $table2, $sql);
    }

    public function testCompareTableColumnsBigMessyChanges(): void
    {
        $table2 = (new TableBuilder(self::TABLE_NAME1, self::TABLE_DESCRIPTION1))
            ->addPrimaryKeyColumn($col1 = 'id', $desc1 = 'the key')
            ->addStringColumn($col2 = 'c2', 20, $desc2 = 'column 2')
            ->addIntegerColumn($col3 = 'c3', $desc3 = 'desc3')
            ->addIntegerColumn($col4 = 'c4', $desc4 = 'desc4')
            ->addFloatColumn($col5 = 'c5', 6, 1, $desc5 = 'desc5')
            ->build();

        $table1 = (new TableBuilder(self::TABLE_NAME1, self::TABLE_DESCRIPTION1))
            ->addPrimaryKeyColumn($col1, $desc1new = $desc1 . 'new')// change desc
            // c2 deleted
            ->addIntegerColumn($col3, $desc3)
            ->addIntegerColumn($col3a = 'foobar', 'baz')// inserted
            // c4 deleted
            ->addStringColumn($col5, 20, $desc5)// change type
            ->addStringColumn($col6 = 'c6', 20)// new column at end
            ->build();

        $sql = [
            'alter table ' . self::TABLE_NAME1 . ' drop column ' . $col2,
            'alter table ' . self::TABLE_NAME1 . ' drop column ' . $col4,
            'alter table ' . self::TABLE_NAME1 . ' change ' . $col1 . ' ' . $table1->getColumn($col1)->toMySQL(),
            'alter table ' . self::TABLE_NAME1 . ' add column ' . $table1->getColumn($col3a)->toMySQL() . ' after ' . $col3,
            'alter table ' . self::TABLE_NAME1 . ' change ' . $col5 . ' ' . $table1->getColumn($col5)->toMySQL(),
            'alter table ' . self::TABLE_NAME1 . ' add column ' . $table1->getColumn($col6)->toMySQL() . ' after ' . $col5,
        ];
        $this->checkTableSQL($table1, $table2, $sql);
    }

    public function testCreateTable(): void
    {
        $table1 = $this->getTable1();
        $sql    = [$table1->toMySQL()];
        $this->checkDatabaseSQL((new Schema())->addTable($table1), new Schema(), $sql);
    }

    public function testDeleteTable(): void
    {
        $table1 = $this->getTable1();
        $sql    = ['drop table ' . self::TABLE_NAME1];
        $this->checkDatabaseSQL(new Schema(), (new Schema())->addTable($table1), $sql);
    }

    /**
     * the final big bang tests of all above functionality
     */
    public function testRender(): void
    {
        $masterOne = new Table('one', 'my comment', [
            'id' => new Column\PrimaryKeyColumn($col1_1 = 'id', $desc1_1 = 'the key')]);
        $masterTwo = new Table('two', '', [
            'id' => new Column\PrimaryKeyColumn($col2_1 = 'id', $desc2_1 = 'the key'),
            'c2' => new Column\StringColumn($col2_2 = 'c2', $desc2_2 = 'column 2', '', 20)],
            [$col2_2 => new Index\SecondaryIndex($col2_2, $col2_2, true)]);
        $master    = new Schema();
        $master->addTable($masterOne);
        $master->addTable($masterTwo);

        // table one is missing
        $copyTwo = new Table('two', '', [
            new Column\PrimaryKeyColumn($col2_1, $desc2_1),
            new Column\StringColumn($col2_2 = 'c2', $desc2_2 = 'desc changed', '', 20)]); // description changed
        // index is missing
        // table three is extra and must be deleted
        $copyThree = new Table('three', '', [
            new Column\PrimaryKeyColumn($col3_1 = 'id', $desc3_1 = 'the key')]);
        $copy      = new Schema();
        $copy->addTable($copyTwo);
        $copy->addTable($copyThree);

        $sql = [
            $masterOne->toMySQL(),
            'drop table ' . $copyThree->getName(),
            'alter table ' . $masterTwo->getName() . ' change ' . $col2_2 . ' ' . $masterTwo->getColumn($col2_2)->toMySQL(),
            'alter table ' . $masterTwo->getName() . ' add ' . $masterTwo->getIndexes()[$col2_2]->toMySQL(),
        ];
        $this->checkDatabaseSQL($master, $copy, $sql);
    }
}
