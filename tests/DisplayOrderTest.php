<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   22 Aug 2021
 */

declare(strict_types=1);

namespace LSS\YADbal;

use PHPUnit\Framework\TestCase;

class DisplayOrderTest extends TestCase
{
    public const TABLE_NAME = 'my_table';

    private const DATE1 = '2021-01-01';
    private const DATE2 = '2021-01-02';
    private const DATE3 = '2021-01-03';

    private MemoryDatabaseConnection $database;

    private array $data = [
        ['id' => '1', 'person_id' => '1', 'date_due' => self::DATE1, 'display_order' => '1', 'value' => 'a'], // p1 d1
        ['id' => '2', 'person_id' => '1', 'date_due' => self::DATE3, 'display_order' => '1', 'value' => 'b'], // p1 d3
        ['id' => '3', 'person_id' => '2', 'date_due' => self::DATE1, 'display_order' => '1', 'value' => 'c'], // p2 d1
        ['id' => '4', 'person_id' => '2', 'date_due' => self::DATE1, 'display_order' => '2', 'value' => 'd'], // p2 d1
        ['id' => '6', 'person_id' => '2', 'date_due' => self::DATE1, 'display_order' => '3', 'value' => 'e'], // p2 d1
        ['id' => '7', 'person_id' => '2', 'date_due' => self::DATE2, 'display_order' => '1', 'value' => 'f'], // p2 d2
        ['id' => '8', 'person_id' => '2', 'date_due' => self::DATE3, 'display_order' => '1', 'value' => 'g'], // p2 d3
        ['id' => '9', 'person_id' => '2', 'date_due' => self::DATE3, 'display_order' => '2', 'value' => 'h'], // p2 d3
        ['id' => '10', 'person_id' => '3', 'date_due' => self::DATE1, 'display_order' => '1', 'value' => 'i'], // p3 d1
    ];

    /**
     * @param int    $personId
     * @param string $dateDue
     * @param int    $insertAfter
     * @param int    $expectedDisplayOrder
     * @dataProvider getAddRow
     */
    public function testAddRow(int $personId, string $dateDue, int $insertAfter, int $expectedDisplayOrder): void
    {
        $this->getSubject()->save(['person_id' => $personId, 'date_due' => $dateDue, 'value' => $value = 'x']);
        $actual         = $this->getAllData();
        $expectedNewRow = [
            'id'            => '11',
            'person_id'     => (string)$personId,
            'date_due'      => $dateDue,
            'display_order' => (string)$expectedDisplayOrder,
            'value'         => $value,
        ];
        $expected       = array_merge(
            array_slice($this->data, 0, $insertAfter),
            [$expectedNewRow],
            array_slice($this->data, $insertAfter)
        );
        self::assertEquals($expected, $actual);
    }

    public function getAddRow(): array
    {
        return [
            // person 1
            'p1 d1' => [1, self::DATE1, 1, 2], // after row 1 with same date
            'p1 d2' => [1, self::DATE2, 1, 1], // between row 1 and 2
            'p1 d3' => [1, self::DATE3, 2, 2], //
            // person 2
            'p2 d1' => [2, self::DATE1, 5, 4], //
            'p2 d2' => [2, self::DATE2, 6, 2], //
            'p2 d3' => [2, self::DATE3, 8, 3], //
            // person 3
            'p3 d1' => [3, self::DATE1, 9, 2], //
            'p3 d2' => [3, self::DATE2, 9, 1], //
            'p3 d3' => [3, self::DATE3, 9, 1], //
        ];
    }

    /**
     * @param int   $rowId
     * @param array $expected
     * @dataProvider getMoveDown
     */
    public function testMoveDown(int $rowId, array $expected): void
    {
        $this->getSubject()->moveDown($rowId);
        $actual = $this->getAllData();
        self::assertEquals($expected, $actual, 'row ' . $rowId);
    }

    public function getMoveDown(): array
    {
        $row3Moved                     = $this->swap($this->data, 2, 3);
        $row3Moved[2]['display_order'] = '1';
        $row3Moved[3]['display_order'] = '2';
        $row4Moved                     = $this->swap($this->data, 3, 4);
        $row4Moved[3]['display_order'] = '2';
        $row4Moved[4]['display_order'] = '3';
        $row8Moved                     = $this->swap($this->data, 6, 7);
        $row8Moved[6]['display_order'] = '1';
        $row8Moved[7]['display_order'] = '2';
        return [
            'id1'  => [1, $this->data],
            'id2'  => [2, $this->data],
            'id3'  => [3, $row3Moved],
            'id4'  => [4, $row4Moved],
            'id6'  => [6, $this->data],
            'id7'  => [7, $this->data],
            'id8'  => [8, $row8Moved],
            'id9'  => [9, $this->data],
            'id10' => [10, $this->data],
        ];
    }

    /**
     * @param int   $rowId
     * @param array $expected
     * @dataProvider getMoveUp
     */
    public function testMoveUp(int $rowId, array $expected): void
    {
        $this->getSubject()->moveUp($rowId);
        $actual = $this->getAllData();
        self::assertEquals($expected, $actual, 'row ' . $rowId);
    }

    public function getMoveUp(): array
    {
        $row4Moved                     = $this->swap($this->data, 2, 3);
        $row4Moved[2]['display_order'] = '1';
        $row4Moved[3]['display_order'] = '2';
        $row6Moved                     = $this->swap($this->data, 3, 4);
        $row6Moved[3]['display_order'] = '2';
        $row6Moved[4]['display_order'] = '3';
        $row9Moved                     = $this->swap($this->data, 6, 7);
        $row9Moved[6]['display_order'] = '1';
        $row9Moved[7]['display_order'] = '2';
        return [
            'id1'  => [1, $this->data],
            'id2'  => [2, $this->data],
            'id3'  => [3, $this->data],
            'id4'  => [4, $row4Moved],
            'id6'  => [6, $row6Moved],
            'id7'  => [7, $this->data],
            'id8'  => [8, $this->data],
            'id9'  => [9, $row9Moved],
            'id10' => [10, $this->data],
        ];
    }

    public function testRenumber(): void
    {
        $subject   = $this->getSubject();
        $duplicate = $this->data[0];
        // create a new row
        $id = $subject->save(
            $newRow = ['person_id' => $duplicate['person_id'], 'date_due' => $duplicate['date_due'], 'value' => 'x']
        );
        // deliberately mess up the display order on the row to make a duplicate
        $subject->save(['id' => $id, 'display_order' => $duplicate['display_order']]);
        // try a move operation on some row in the group, which should trigger a renumber
        $subject->moveDown($id);
        $expected = array_merge(
            array_slice($this->data, 0, 1),
            [array_merge($newRow, ['id' => '11', 'display_order' => '2'])], // expected row with fixed display order
            array_slice($this->data, 1)
        );
        self::assertEquals($expected, $this->getAllData());
    }

    protected function setUp(): void
    {
        $this->database = new MemoryDatabaseConnection();

        $createTable = 'CREATE TABLE ' . self::TABLE_NAME . ' (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                person_id INTEGER NOT NULL,
                date_due TEXT NOT NULL,
                display_order INTEGER NOT NULL,
                value TEXT NOT NULL
            );';
        $this->database->write($createTable);
        $insert = $this->database->insertBulk(self::TABLE_NAME, array_values($this->data))->compile();
        $this->database->write($insert->sql(), $insert->params());
    }

    private function getAllData(): array
    {
        return $this->database->fetchAll(
            'select * from ' . self::TABLE_NAME . ' order by person_id, date_due, display_order'
        );
    }

    private function getSubject(): DisplayOrderRepository
    {
        return new DisplayOrderRepository($this->database);
    }

    private function swap(array $data, int $one, int $two): array
    {
        $temp       = $data[$one];
        $data[$one] = $data[$two];
        $data[$two] = $temp;
        return $data;
    }
}
