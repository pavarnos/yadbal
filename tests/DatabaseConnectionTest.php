<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   02 Dec 2020
 */

declare(strict_types=1);

namespace LSS\YADbal;

use LSS\YADbal\DatabaseConnection\PDOConnection;
use PHPUnit\Framework\TestCase;

use function Latitude\QueryBuilder\field;

/**
 * Also tests down through to PDOConnection
 */
class DatabaseConnectionTest extends TestCase
{
    protected array $data = [
        1  => 'Anna',
        2  => 'Betty',
        3  => 'Clara',
        4  => 'Donna',
        5  => 'Fiona',
        6  => 'Gertrude',
        7  => 'Hanna',
        8  => 'Ione',
        9  => 'Julia',
        10 => 'Kara',
    ];

    public function testFetchAll(): void
    {
        $database = $this->createDatabase();
        $expected = [];
        foreach ($this->data as $id => $value) {
            $expected[] = ['id' => $id, 'name' => $value];
        }
        self::assertEquals($expected, $database->fetchAll('select * from pdotest'));
    }

    public function testFetchPairs(): void
    {
        $database = $this->createDatabase();
        self::assertEquals($this->data, $database->fetchPairs('select * from pdotest'));
    }

    public function testFetchRow(): void
    {
        $database = $this->createDatabase();
        self::assertEquals(['id' => 3, 'name' => 'Clara'], $database->fetchRow('select * from pdotest where id = 3'));
        self::assertEquals([], $database->fetchRow('select * from pdotest where id = 999'));
    }

    public function testFetchCol(): void
    {
        $database = $this->createDatabase();
        self::assertEquals(array_keys($this->data), $database->fetchCol('select id from pdotest'));
        self::assertEquals(array_values($this->data), $database->fetchCol('select name from pdotest'));
    }

    public function testFetchValue(): void
    {
        $database = $this->createDatabase();
        self::assertEquals('Clara', $database->fetchValue('select name from pdotest where id = 3'));
        self::assertEquals('', $database->fetchValue('select name from pdotest where id = 999'));
    }

    public function testFetchInt(): void
    {
        $database = $this->createDatabase();
        self::assertEquals(3, $database->fetchInt('select id from pdotest where id = 3'));
        self::assertEquals(0, $database->fetchInt('select id from pdotest where id = 999'));
    }

    public function testFetchString(): void
    {
        $database = $this->createDatabase();
        self::assertEquals('Clara', $database->fetchString('select name from pdotest where id = 3'));
        self::assertEquals('', $database->fetchString('select id from pdotest where id = 999'));
    }

    public function testParameterTypes(): void
    {
        $database = $this->createDatabase();
        self::assertEquals('', $database->fetchString('select name from pdotest where name = ?', [null]));
        self::assertEquals('', $database->fetchString('select id from pdotest where name = ?', [false]));
    }

    /**
     * @dataProvider getWrite
     * @param string $sql
     * @param array  $params
     */
    public function testWrite(string $sql, array $params): void
    {
        $database = $this->createDatabase();
        self::assertEquals(1, $database->write($sql, $params));
        self::assertEquals(11, $database->lastInsertId());
        self::assertEquals('eleven', $database->fetchValue('select name from pdotest where id = ?', [11]));
    }

    public function getWrite(): array
    {
        return [
            '?'     => ['insert into pdotest (name,id) values (?,?)', ['eleven', 11]],
            'named' => ['insert into pdotest (name,id) values (:name,:id)', ['name' => 'eleven', 'id' => 11]],
        ];
    }

    public function testTransactionValid(): void
    {
        $database = $this->createDatabase();
        $database->transaction(
            fn() => $database->write('insert into pdotest (name,id) values (?,?)', ['eleven', 11])
        );
        // should be committed
        self::assertEquals(11, $database->lastInsertId());
        self::assertEquals(
            ['id' => 11, 'name' => 'eleven'],
            $database->fetchRow('select * from pdotest where id = 11')
        );
    }

    public function testTransactionFails(): void
    {
        $database = $this->createDatabase();
        try {
            $database->transaction(
                function () use ($database): void {
                    $database->write('insert into pdotest (name,id) values (?,?)', ['twelve', 12]);
                    throw new DatabaseException('Expected');
                }
            );
            self::fail('Should not get to here');
        } catch (DatabaseException $ex) {
        }
        // row should not be added
        self::assertEquals([], $database->fetchRow('select * from pdotest where id = 12'));
    }

    public function testReadWriteConnectionsAreSeparate(): void
    {
        $readSQL       = 'select name from foo where bar = ? and baz = ?';
        $readParams    = [null, true];
        $readStatement = $this->createMock(\PDOStatement::class);
        $readStatement->method('fetchColumn')->willReturn($expectedString = 'abc123');
        $read = $this->createMock(PDOConnection::class);
        $read->expects(self::once())->method('perform')->with($readSQL, $readParams)->willReturn($readStatement);

        $writeSQL       = 'update foo where bar = ?';
        $writeParams    = [7];
        $writeStatement = $this->createMock(\PDOStatement::class);
        $writeStatement->method('rowCount')->willReturn($affectedRows = 5);
        $write = $this->createMock(PDOConnection::class);
        $write->expects(self::once())->method('perform')->with($writeSQL, $writeParams)->willReturn($writeStatement);

        $database = new DatabaseConnection($read, $write);
        self::assertEquals($affectedRows, $database->write($writeSQL, $writeParams));
        self::assertEquals($expectedString, $database->fetchString($readSQL, $readParams));
    }

    private function createDatabase(): DatabaseConnectionInterface
    {
        $database    = new MemoryDatabaseConnection();
        $createTable = "CREATE TABLE pdotest (
            id   INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(10) NOT NULL
        )";
        $database->write($createTable);
        foreach ($this->data as $key => $value) {
            $database->write('INSERT INTO pdotest (name) VALUES (?)', [$value]);
        }
        return $database;
    }
}
