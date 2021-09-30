<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   30 Sep 2021
 */

declare(strict_types=1);

namespace LSS\YADbal\Repository;

use Carbon\Carbon;
use LSS\YADbal\DatabaseException;
use LSS\YADbal\MemoryDatabaseConnection;
use PHPUnit\Framework\TestCase;

use function Latitude\QueryBuilder\field;
use function PHPUnit\Framework\assertEquals;

/**
 * to test all of AbstractRepository
 */
class ExampleRepositoryTest extends TestCase
{
    private ExampleRepository $table;

    private Carbon $now;

    private array $row = [
        1 => ['value' => 'ab', 'value_int' => 33, 'value_json' => ['one' => 11, 'two' => 22]],
        2 => ['value' => 'cd', 'value_int' => 44, 'value_json' => null],
    ];

    public function testSimpleGetters(): void
    {
        self::assertEquals(ExampleRepository::TABLE_NAME, $this->table->getTableName());
        self::assertEquals('id', $this->table->getIDFieldName());
    }

    public function testFindOrNullExists(): void
    {
        $this->assertValidRow(1, $this->table->findOrNull(1) ?? []);
    }

    public function testFindOrNullPartialColumns(): void
    {
        $row = $this->table->findOrNull($id = 1, ['id', 'value_json']) ?? [];
        self::assertEquals((string)$id, $row['id']);
        self::assertEquals($this->row[$id]['value_json'], $row['value_json']);
    }

    public function testFindOrExceptionExists(): void
    {
        $this->assertValidRow(1, $this->table->findOrException(1));
    }

    public function testFindOrNullNotExists(): void
    {
        self::assertEquals(null, $this->table->findOrNull(0));
        self::assertEquals(null, $this->table->findOrNull(999));
    }

    public function testFindOrExceptionNotExists(): void
    {
        $this->expectException(DatabaseException::class);
        $this->table->findOrException(999);
    }

    public function testDeleteRow(): void
    {
        self::assertNotNull($this->table->findOrNull(1));
        $this->table->deleteRow(1);
        self::assertNull($this->table->findOrNull(1));
    }

    public function testDeleteRowWithInvalidId(): void
    {
        $this->expectException(DatabaseException::class);
        $this->table->deleteRow(0);
    }

    public function testFetchAll(): void
    {
        $row = $this->table->fetchAll($this->table->selectAll()->orderBy('id'));
        $this->assertValidRow(1, $row[0], true);
        $this->assertValidRow(2, $row[1], true);
    }

    public function testFetchInt(): void
    {
        $select = $this->table->selectAll('value_int')->where(field('id')->eq(1));
        self::assertEquals($this->row[1]['value_int'], $this->table->fetchInt($select));
    }

    public function testFetchString(): void
    {
        self::assertEquals($this->row[1]['value'], $this->table->getValue(1));
    }

    public function testFetchPairs(): void
    {
        $select = $this->table->selectAll('value_int', 'value')->orderBy('value');
        self::assertEquals(
            [
                $this->row[1]['value_int'] => $this->row[1]['value'],
                $this->row[2]['value_int'] => $this->row[2]['value'],
            ],
            $this->table->fetchPairs($select)
        );
    }

    public function testFetchColumn(): void
    {
        self::assertEquals([$this->row[1]['value'], $this->row[2]['value']], $this->table->getValues([2, 1]));
    }

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow($this->now = Carbon::now());
        $database    = new MemoryDatabaseConnection();
        $this->table = new ExampleRepository($database);
        $database->write($this->table->getSchema()->toSQLite());
        $this->table->save($this->row[1]);
        $this->table->save($this->row[2]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    protected function assertValidRow(int $id, ?array $row, bool $decodeJson = false): void
    {
        self::assertNotNull($row);
        $json = $row['value_json'];
        if ($decodeJson && !is_null($json)) {
            $json = \Safe\json_decode($row['value_json'], true);
        }
        self::assertEquals((string)$id, $row['id']);
        self::assertEquals($this->row[$id]['value'], $row['value']);
        self::assertEquals($this->row[$id]['value_int'], $row['value_int']);
        self::assertEquals($this->row[$id]['value_json'], $json);
        self::assertEquals($row['date_created'], $this->now->toDateTimeString());
        self::assertEquals($row['date_updated'], $this->now->toDateTimeString());
    }
}
