<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   01 Oct 2021
 */

declare(strict_types=1);

namespace LSS\YADbal\Repository\Example;

use Carbon\Carbon;
use LSS\YADbal\DatabaseException;
use LSS\YADbal\MemoryDatabaseConnection;
use PHPUnit\Framework\TestCase;

use function Latitude\QueryBuilder\express;

class ChildRepositoryTest extends TestCase
{
    private ChildRepository $table;

    private array $row = [
        1 => ['example_id' => 10, 'value' => 'ab'],
        2 => ['example_id' => 10, 'value' => 'cd'],
        3 => ['example_id' => 11, 'value' => 'ef'],
    ];

    public function testFindOrNullExists(): void
    {
        $this->assertValidRow(1, $this->table->findChildOrNull(1, 10) ?? []);
    }

    public function testFindOrNullPartialColumns(): void
    {
        $row = $this->table->findChildOrNull($id = 1, 10, ['id', 'value']) ?? [];
        self::assertEquals((string)$id, $row['id']);
        self::assertEquals($this->row[$id]['value'], $row['value']);
    }

    public function testFindChildOrExceptionExists(): void
    {
        $this->assertValidRow(1, $this->table->findChildOrException(1, 10));
    }

    public function testFindOrNullNotExists(): void
    {
        self::assertEquals(null, $this->table->findChildOrNull(0, 0));
        self::assertEquals(null, $this->table->findChildOrNull(0, 10));
        self::assertEquals(null, $this->table->findChildOrNull(1, 11));
        self::assertEquals(null, $this->table->findChildOrNull(1, 999));
        self::assertEquals(null, $this->table->findChildOrNull(999, 0));
        self::assertEquals(null, $this->table->findChildOrNull(999, 1));
        self::assertEquals(null, $this->table->findChildOrNull(999, 10));
    }

    public function testFindOrExceptionNotExists(): void
    {
        $this->expectException(DatabaseException::class);
        $this->table->findChildOrException(999, 10);
    }

    public function testDeleteChildRowExists(): void
    {
        $select = $this->table->selectAll(express('count(*)'));
        self::assertEquals(1, $this->table->deleteChildRow(1, 10));
        self::assertEquals(count($this->row) - 1, $this->table->fetchInt($select));
    }

    public function testDeleteChildRowNotExists(): void
    {
        $select = $this->table->selectAll(express('count(*)'));
        self::assertEquals(0, $this->table->deleteChildRow(0, 0));
        self::assertEquals(0, $this->table->deleteChildRow(1, 0));
        self::assertEquals(0, $this->table->deleteChildRow(1, 11));
        self::assertEquals(0, $this->table->deleteChildRow(1, 999));
        self::assertEquals(0, $this->table->deleteChildRow(2, 11));
        self::assertEquals(0, $this->table->deleteChildRow(2, 999));
        self::assertEquals(0, $this->table->deleteChildRow(3, 10));
        self::assertEquals(0, $this->table->deleteChildRow(999, 999));
        self::assertEquals(count($this->row), $this->table->fetchInt($select), 'all rows exist still');
    }

    public function testSaveNewValid(): void
    {
        $id     = $this->table->save($expected = ['example_id' => $parentId = 44, 'value' => 'xx']);
        $actual = $this->table->findChildOrNull($id, $parentId) ?? [];
        self::assertEquals($id, $actual['id']);
        self::assertEquals($expected['example_id'], $actual['example_id']);
        self::assertEquals($expected['value'], $actual['value']);
    }

    public function testSaveNewInValid(): void
    {
        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessageMatches('|Missing example_id|');
        $id = $this->table->save(['value' => 'xx']); // example_id is missing
    }

    public function testSaveExistingValid(): void
    {
        $id = $this->table->save(
            ['id' => 1, 'example_id' => $parentId = $this->row[1]['example_id'], 'value' => $value = 'xx']
        );
        self::assertEquals(1, $id);
        $actual = $this->table->findChildOrNull($id, $parentId) ?? [];
        self::assertEquals($value, $actual['value']);
        self::assertEquals($parentId, $actual['example_id']);
    }

    public function testSaveExistingInValid(): void
    {
        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessageMatches('|Missing example_id|');
        $id = $this->table->save(['id' => 1, 'value' => 'xx']); // example_id is missing
    }

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::now());
        $database    = new MemoryDatabaseConnection();
        $this->table = new ChildRepository($database);
        $toSQLite    = $this->table->getSchema()->toSQLite();
        $database->write($toSQLite);
        $this->table->save($this->row[1]);
        $this->table->save($this->row[2]);
        $this->table->save($this->row[3]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    protected function assertValidRow(int $id, ?array $row): void
    {
        self::assertNotNull($row);
        self::assertEquals((string)$id, $row['id']);
        self::assertEquals($this->row[$id]['value'], $row['value']);
    }
}
